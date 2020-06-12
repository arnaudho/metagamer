<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelPlayer extends BaseModel {

        public function __construct()
        {
            parent::__construct("players", "id_player");
        }

        public function searchPlayerByArenaId ($pArenaId) {
            $data = Query::select(
                    "tournaments.id_tournament, name_tournament, id_format, name_archetype, decklist_player,
                    arena_id, discord_id, IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins,
                    IF(COUNT(1) IS NULL, 0, COUNT(1)) AS matches", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people AND arena_id LIKE '%" . $pArenaId . "%'")
                ->join("archetypes", Query::JOIN_OUTER_LEFT, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->groupBy("players.id_player")
                ->order("arena_id, date_tournament")
                ->limit(0, 100)
                ->execute($this->handler);
            return $data;
        }

        public function countArchetypes ($pCondition = null) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $q = Query::select("archetypes.id_archetype, name_archetype, COUNT(*) AS count", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = " . $this->table . ".id_tournament")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = " . $this->table . ".id_archetype")
                ->andCondition($pCondition)
                ->groupBy("name_archetype")
                ->order("COUNT(*)", "DESC");
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

        public function getDecklists ($pCondition) {
            // TODO check which fields we actually need
            return Query::select("SUM(result_match) AS wins, COUNT(1) AS total, p.*, people.*, tournaments.*", "players p")
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
    }
}