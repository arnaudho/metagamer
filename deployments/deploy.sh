#!/bin/bash -xe

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
TEMP_DIR=$(mktemp -d)
DEPLOYMENT_NAME=${1}
shift

cp -r ${SCRIPT_DIR}/*.yml ${TEMP_DIR}
cp -r ${SCRIPT_DIR}/../*.sql ${TEMP_DIR}
cd ${TEMP_DIR}

cat <<EOF >${TEMP_DIR}/host_patch.json
[
  {
    "op": "replace",
    "path": "/spec/rules/0/host",
    "value": "${DEPLOYMENT_NAME}.kub.soufflet.io"
  }, {
    "op": "replace",
    "path": "/spec/tls/0/hosts/0",
    "value": "${DEPLOYMENT_NAME}.kub.soufflet.io"
  }
]
EOF

sed "s/mtg.kub.soufflet.io/${DEPLOYMENT_NAME}.kub.soufflet.io/g" ${SCRIPT_DIR}/../includes/applications/setup.json > metagamer_setup.json
sed "s/MYSQL_PASSWORD/${MYSQL_ROOT_PASSWORD}/g" ${SCRIPT_DIR}/../includes/applications/kube.config.json > metagamer_config.json
kubectl create configmap metagamer-config \
  --from-file=metagamer_setup.json \
  --from-file=metagamer_config.json \
  --dry-run=client -o yaml > metagamer_config.yml

kubectl create configmap sql-init \
  --from-file=schema.sql \
  --from-file=users.sql \
  --dry-run=client -o yaml > configmap.yml

kubectl create secret generic mysql-secret \
  --from-literal=ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD} \
  --dry-run=client -o yaml > secret.yml

alias kustomize="${SCRIPT_DIR}/../kustomize"
kustomize edit set namespace ${GITHUB_RUN_ID}
kustomize edit set image gcr.io/PROJECT_ID/IMAGE:TAG=gcr.io/$PROJECT_ID/$IMAGE:$GITHUB_SHA
kustomize build .
# kustomize build . | kubectl apply -f -
# kubectl rollout status deployment/$DEPLOYMENT_NAME
# kubectl get services -o wide
