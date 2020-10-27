<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;
    use core\db\QuerySelect;

    class ModelPlayer extends BaseModel {

        public function __construct()
        {
            parent::__construct("players", "id_player");
        }

        public function getDataByPlayerId ($pIdPlayer) {
            $data = Query::select(
                "players.id_player, tournaments.id_tournament, name_tournament, name_format, name_archetype, decklist_player,
                    arena_id, IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins,
                    IF(COUNT(1) IS NULL, 0, COUNT(1)) AS matches", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people AND players.id_player = $pIdPlayer")
                ->join("archetypes", Query::JOIN_OUTER_LEFT, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->groupBy("players.id_player")
                ->limit(0, 1)
                ->execute($this->handler);
            return $data[0];
        }

        public function searchPlayerByArenaId ($pArenaId) {
            $data = Query::select(
                    "players.id_player, tournaments.id_tournament, name_tournament, name_format, name_archetype,
                    arena_id, discord_id, IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins,
                    IF(COUNT(1) IS NULL, 0, COUNT(1)) AS matches", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people AND arena_id LIKE '%" . $pArenaId . "%'")
                ->join("archetypes", Query::JOIN_OUTER_LEFT, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->groupBy("players.id_player")
                ->order("formats.id_format DESC, arena_id, date_tournament")
                ->limit(0, 300)
                ->execute($this->handler);
            return $data;
        }

        public function countArchetypes ($pCondition = null) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $q = Query::select("archetypes.id_archetype, name_archetype, image_archetype, COUNT(1) AS count", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = " . $this->table . ".id_tournament")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = " . $this->table . ".id_archetype")
                ->andCondition($pCondition)
                // exclude archetypes from dashboard
                //->andWhere("archetypes.id_archetype", Query::NOT_IN, "(13, 73, 77, 83, 84, 92, 93, 94)", false)
                ->groupBy("name_archetype")
                ->order("FIELD (players.id_archetype, " . ModelArchetype::ARCHETYPE_OTHER_ID . "), COUNT(1)", "DESC");
            $data = $q->execute($this->handler);
            $sum = 0;
            foreach ($data as $d) {
                $sum += $d['count'];
            }
            foreach ($data as &$d) {
                $d['percent'] = round(100*$d['count']/$sum, 2);
            }
            return $data;
        }

        public function getDecklists ($pCondition, $pDecklistNames = false) {
            $fields = "SUM(result_match) AS wins, COUNT(1) AS total, p.id_player, people.arena_id, tournaments.id_format, tournaments.id_tournament, name_tournament";
            if ($pDecklistNames) {
                $fields .= ", p.name_deck";
            }
            return Query::select($fields, "players p")
                ->join("people", Query::JOIN_INNER, "people.id_people = p.id_people")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = p.id_tournament")
                ->join("matches", Query::JOIN_INNER, "matches.id_player = p.id_player")
                ->andCondition($pCondition)
                ->groupBy("p.id_player")
                ->execute($this->handler);
        }

        public function getPlayerIdByTournamentIdArenaId ($pTournamentId, $pArenaId) {
            $id_player = Query::select("id_player", $this->table)
                ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " .
                    $pTournamentId . " AND people.arena_id = '" . $pArenaId . "'")
                ->execute($this->handler);
            if (!$id_player && preg_match('/^([^#]+)#([0-9]+)/', $pArenaId, $output_array)) {
                $id_player = Query::select("id_player", $this->table)
                    ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " .
                        $pTournamentId . " AND (people.arena_id LIKE '%#" . $output_array[2] . "' OR people.arena_id LIKE '" . $output_array[1] . "#%' OR people.discord_id = '" . $pArenaId . "')")
                    ->execute($this->handler);
                // cancel if several players could match
                if (count($id_player) > 1) {
                    $id_player = array();
                }
            }
            return $id_player ? $id_player[0]['id_player'] : null;
        }

        public function getPlayerByFormatId ($pIdFormat, $pFields = "players.*") {
            $players = Query::select($pFields, "players")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("id_format", Query::EQUAL, $pIdFormat)
                ->execute($this->handler);
            return $players;
        }

        public function countPlayersWithoutDecklist () {
            return $this->count(
                Query::condition()
                    ->andWhere("id_archetype", Query::IS, "NULL", false)
            );
        }

        public function countPlayers ($pCond = null, $pRulesCondition = null) {
            if (!$pCond) {
                $pCond = Query::condition();
            }
            $q = Query::select("count(1) as nb", "players p")
                ->join("tournaments", Query::JOIN_INNER, "p.id_tournament = tournaments.id_tournament")
                ->setCondition(clone $pCond);
            if ($pRulesCondition) {
                $rules_cond = clone $pRulesCondition;
                $q->andCondition($rules_cond);
            }
            $data = $q->execute($this->handler);
            return $data[0]["nb"];
        }

        public function deletePlayerById ($pIdPlayer) {
            $id_player = $this->one(Query::condition()->andWhere("id_player", Query::EQUAL, $pIdPlayer), "id_player");
            if (!$id_player) {
                return false;
            }
            $id_player = $id_player["id_player"];
            Query::delete()
                ->from("matches")
                ->andCondition(
                    Query::condition()
                        ->orWhere("id_player", Query::EQUAL, $id_player)
                        ->orWhere("opponent_id_player", Query::EQUAL, $id_player)
                )->execute($this->handler);
            Query::delete()
                ->from("player_card")
                ->andCondition(
                    Query::condition()
                        ->andWhere("id_player", Query::EQUAL, $id_player)
                )
                ->execute($this->handler);
            return $this->deleteById($id_player);
        }

        public function cleanDuplicatePlayers ($pCondition) {
            $cleaned_duplicates = 0;
            // Clean duplicate decklists
            $duplicates = $this->getDuplicatePlayers($pCondition);
            $delete_players = array();
            foreach ($duplicates as $duplicate) {
                if (!array_key_exists($duplicate['decklist_player'], $delete_players) || $delete_players[$duplicate['decklist_player']]['count'] > $duplicate['count']) {
                    $delete_players[$duplicate['decklist_player']] = array(
                        "id_player" => $duplicate['id_player'],
                        "count"     => $duplicate['count']
                    );
                }
            }
            foreach ($delete_players as $player) {
                if ($this->deletePlayerById($player['id_player'])) {
                    $cleaned_duplicates++;
                }
            }
            return $cleaned_duplicates;
        }

        public function countDuplicatePlayers ($pCondition) {
            return count($this->getDuplicatePlayers($pCondition));
        }

        public function getDuplicatePlayers ($pCondition) {
            $subquery = Query::select("decklist_player", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andCondition($pCondition)
                ->groupBy("decklist_player")
                ->having("COUNT(1) > 1", false)
                ->get(false);
            $duplicates = Query::select("players.id_player, decklist_player, COUNT(1) AS count", $this->table)
                ->join("matches", Query::JOIN_INNER, "players.id_player = matches.id_player")
                ->andWhere("decklist_player", Query::IN, "(" . $subquery . ")", false)
                ->groupBy("id_player")
                ->execute($this->handler);
            return $duplicates;
        }
    }
}