Metagamer bot
=============

-- Metagame des SCG Satellites d'un format N

SELECT name_archetype, COUNT(1) FROM
(SELECT id_player, id_tournament, name_archetype, SUM(result_match) AS wins, COUNT(result_match)-SUM(result_match) AS loss FROM `players`
	INNER JOIN archetypes USING(id_archetype)
	INNER JOIN matches USING(id_player)
	INNER JOIN tournaments USING(id_tournament)
	WHERE name_tournament LIKE 'SCG Tour online%' AND id_format = 64
    GROUP BY id_player HAVING SUM(result_match) >= 4 )
     tmp GROUP BY name_archetype ORDER BY COUNT(1) DESC


-- Get winrates in {$archetype} mirror in {$format} MD
SELECT name_card,
       ROUND(100*SUM(result_match)/COUNT(1), 2) AS 'winrate en mirror',
       SUM(result_match) AS wins,
       COUNT(1) AS total,
       COUNT(DISTINCT matches.id_player) AS count_players
FROM matches
INNER JOIN players p ON matches.id_player = p.id_player
AND p.id_archetype = 9
INNER JOIN players op ON matches.opponent_id_player = op.id_player
AND op.id_archetype = 9
INNER JOIN tournaments ON p.id_tournament = tournaments.id_tournament
INNER JOIN player_card ON player_card.id_player = p.id_player
INNER JOIN cards USING(id_card)
WHERE (id_format = '10')
  AND count_main > '0'
GROUP BY cards.id_card
LIMIT 0,
      150


-- Get winrates for {$archetype1} against {$archetype2} in {$format} SB

SELECT name_card,
       ROUND(100*SUM(result_match)/COUNT(1), 2) AS 'winrate en mirror',
       SUM(result_match) AS wins,
       COUNT(1) AS total,
       COUNT(DISTINCT matches.id_player) AS count_players
FROM matches
INNER JOIN players p ON matches.id_player = p.id_player
AND p.id_archetype = 9
INNER JOIN players op ON matches.opponent_id_player = op.id_player
AND op.id_archetype = 9
INNER JOIN tournaments ON p.id_tournament = tournaments.id_tournament
INNER JOIN player_card ON player_card.id_player = p.id_player
INNER JOIN cards USING(id_card)
WHERE (id_format = '10')
       AND count_side > '0'
       GROUP BY cards.id_card
       LIMIT 0, 150

-- Get winrates for 0-4 {$card} for {$archetype} in {$format} MD (against {$archetype})
SELECT count_main,
       COUNT(*),
       SUM(result_match) AS wins
FROM `players`
LEFT OUTER JOIN player_card ON players.id_player = player_card.id_player
AND id_card = 558
INNER JOIN tournaments ON tournaments.id_tournament = players.id_tournament
AND id_format = 10
AND id_archetype = 39
INNER JOIN matches ON matches.id_player = players.id_player
INNER JOIN players op ON matches.opponent_id_player = op.id_player
AND op.id_archetype = 38
GROUP BY (count_main)

// winrate by tag against tag N
SELECT player_tag.tag_player, SUM(result_match), COUNT(1), SUM(result_match)/COUNT(1) AS winrate FROM `player_tag`
INNER JOIN players USING(id_player)
INNER JOIN matches USING(id_player)
INNER JOIN players op ON op.id_player = matches.opponent_id_player
INNER JOIN player_tag opt ON opt.id_player = op.id_player AND opt.tag_player = 'challenger'
GROUP BY tag_player

// overall winrates by tag_player
SELECT tag_player, SUM(result_match), COUNT(1) FROM `player_tag`
INNER JOIN players USING(id_player)
INNER JOIN matches USING(id_player)
GROUP BY tag_player


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
