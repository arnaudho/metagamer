Metagamer bot
=============

Build and Run
=============

Start the database first:

```sh
docker run \
  --name mysql \
  --publish 3308:3306 \
  --env MYSQL_ALLOW_EMPTY_PASSWORD=true \
  --env MYSQL_DATABASE=metagamer \
  --volume ${PWD}/schema.sql:/docker-entrypoint-initdb.d/00-schema.sql \
  --volume ${PWD}/users.sql:/docker-entrypoint-initdb.d/10-users.sql \
  --detach \
  mysql:5.7
```

Then, build and start metagamer container

```sh
docker build -t metagamer .
docker run \
  --detach \
  --publish 80:80 \
  metagamer
```

You can access it [here](http://localhost:80)
