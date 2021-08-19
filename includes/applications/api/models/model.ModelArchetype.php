<?php
namespace app\api\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelArchetype extends BaseModel
    {

        public function __construct()
        {
            parent::__construct("archetypes", "id_archetype");
        }

        public function getArchetypeById($pIdArchetype)
        {
            $data = Query::select(
                "archetypes.id_archetype, name_archetype, image_archetype,
                    COUNT(DISTINCT players.id_player) AS count_players", $this->table)
                ->join("players", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->andWhere("archetypes.id_archetype", Query::EQUAL, $pIdArchetype)
                ->groupBy("archetypes.id_archetype")
                ->execute($this->handler);
            if (empty($data)) {
                return false;
            }
            return $data;
        }

        public function getArchetypesByIdFormat($pIdFormat)
        {
            // get count players in format
            $count_players = Query::select("COUNT(DISTINCT players.id_player) AS nb", "players")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("tournaments.id_format", Query::EQUAL, $pIdFormat)
                ->execute($this->handler);
            $count_players = $count_players[0]['nb'];
            if (!$count_players) {
                return false;
            }
            $data = Query::select(
                "archetypes.id_archetype, name_archetype, colors_archetype", $this->table)
                ->join("players", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("tournaments.id_format", Query::EQUAL, $pIdFormat)
                ->groupBy("archetypes.id_archetype")
                ->execute($this->handler);
            return $data;
        }

        // TODO order Other ?
        public function getArchetypesDataByIdFormat ($pIdFormat) {
            return $this->getArchetypesDataByCond(Query::condition()->andWhere("tournaments.id_format", Query::EQUAL, $pIdFormat));
        }

        // TODO QUICKFIX for ALPHA version 20/08
        public function getArchetypesDataByCond ($pCondition) {
            if (!$pCondition) {
                return false;
            } else {
                $cond = clone $pCondition;
            }
            // get count players in format
            $count_players = Query::select("COUNT(DISTINCT players.id_player) AS nb", "players")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andCondition($cond)
                ->execute($this->handler);
            $count_players = $count_players[0]['nb'];
            if (!$count_players) {
                return false;
            }
            $data = Query::select(
                "archetypes.id_archetype, name_archetype, image_archetype,
                    COUNT(DISTINCT players.id_player) AS count_players,
                    ROUND(COUNT(DISTINCT players.id_player)/$count_players, 3) AS meta_share_archetype,
                    ROUND(SUM(result_match)/COUNT(1), 3) AS winrate_archetype, COUNT(1) AS total_matches_archetype", $this->table)
                ->join("players", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->join("matches", Query::JOIN_INNER, "matches.id_player = players.id_player")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andCondition($cond)
                ->groupBy("archetypes.id_archetype")
                ->order("count_players", "DESC")
                ->execute($this->handler);
            return $data;
        }
    }
}