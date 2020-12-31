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
    "value": "${DEPLOYMENT_NAME}-back.kub.soufflet.io"
  }, {
    "op": "replace",
    "path": "/spec/rules/1/host",
    "value": "${DEPLOYMENT_NAME}.kub.soufflet.io"
  }, {
    "op": "replace",
    "path": "/spec/tls/0/hosts/0",
    "value": "${DEPLOYMENT_NAME}-back.kub.soufflet.io"
  }, {
    "op": "replace",
    "path": "/spec/tls/0/hosts/1",
    "value": "${DEPLOYMENT_NAME}.kub.soufflet.io"
  }
]
EOF

sed "s/mtg-back.kub.soufflet.io/${DEPLOYMENT_NAME}-back.kub.soufflet.io/g" ${SCRIPT_DIR}/../includes/applications/setup.json > metagamer_setup.json
sed -i "s/mtg.kub.soufflet.io/${DEPLOYMENT_NAME}.kub.soufflet.io/g" metagamer_setup.json
sed "s/MYSQL_PASSWORD/${MYSQL_ROOT_PASSWORD}/g" ${SCRIPT_DIR}/../includes/applications/kube.config.json > metagamer_config.json

set +e
kubectl get namespace | grep -w mtg-dev
INIT_ENV=${?}
if [ ${INIT_ENV} == 1 ]; then
  kubectl create namespace ${DEPLOYMENT_NAME}
  kubectl create configmap metagamer-config \
    --from-file=metagamer_setup.json \
    --from-file=metagamer_config.json \
    --namespace ${DEPLOYMENT_NAME}
  kubectl create configmap sql-init \
    --from-file=schema.sql \
    --from-file=users.sql \
    --namespace ${DEPLOYMENT_NAME}
  kubectl create secret generic mysql-secret \
    --from-literal=ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD} \
    --namespace ${DEPLOYMENT_NAME}
fi
set -e

alias kustomize="${SCRIPT_DIR}/../kustomize"
kustomize edit set namespace ${DEPLOYMENT_NAME}
kustomize edit set image gcr.io/PROJECT_ID/IMAGE:TAG=gcr.io/$PROJECT_ID/$IMAGE:$GITHUB_SHA
kustomize build . | kubectl apply -f -

if [ ${INIT_ENV} == 1 ]; then
  # Load data into mysql
  curl https://gentux.s3.eu-west-2.amazonaws.com/mtg-data/data.sql > data.sql
  POD_NAME=$(kubectl get pod -n ${DEPLOYMENT_NAME} | awk '/mysql-deployment/ { print $1; }')

  set +e
  for i in $(seq 5); do
    kubectl exec -n ${DEPLOYMENT_NAME} -i ${POD_NAME} -- mysql -u root --password="${MYSQL_ROOT_PASSWORD}" -D metagamer -e '\q'

    if [ ${?} == 0 ]; then
      break
    fi
    sleep 15
  done

  sleep 30
  set -e
  kubectl exec -n ${DEPLOYMENT_NAME} ${POD_NAME} -i -- mysql -u root --password="${MYSQL_ROOT_PASSWORD}" -D metagamer < data.sql
fi

echo "Visit https://${DEPLOYMENT_NAME}.kub.soufflet.io"
