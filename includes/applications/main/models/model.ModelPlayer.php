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
                    "name_tournament, name_archetype, decklist_player, arena_id,
                    discord_id, IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins,
                    IF(COUNT(1) IS NULL, 0, COUNT(1)) AS matches", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people AND arena_id LIKE '%" . $pArenaId . "%'")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
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
                ->groupBy("name_archetype");
            $data = $q->execute($this->handler);
            $sum = 0;
            foreach ($data as $d) {
                $sum += $d['count'];
            }
            foreach ($data as &$d) {
                $d['percent'] = round(100*$d['count']/$sum);
            }
            usort($data, function ($a, $b) {
                return $b['count'] - $a['count'];
            });
            return $data;
        }

        public function evaluatePlayerArchetypeById ($pPlayerId) {
            $player = $this->getTupleById($pPlayerId);
            if ($player) {

            }
        }

        public function getPlayerIdByTournamentIdArenaId ($pTournamentId, $pArenaId) {
            $id_player = Query::select("id_player", $this->table)
                ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " . $pTournamentId . " AND people.arena_id = '" . $pArenaId . "'")
                ->execute($this->handler);
            if (!$id_player) {
                preg_match('/(#[0-9]+)/', $pArenaId, $output_array);
                $id_player = Query::select("id_player", $this->table)
                    ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " . $pTournamentId . " AND people.arena_id LIKE '%" . $output_array[1] . "'")
                    ->execute($this->handler);
            }
            return $id_player ? $id_player[0]['id_player'] : null;
        }
    }
}