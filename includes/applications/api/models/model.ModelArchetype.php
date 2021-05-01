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

        // TODO order Other ?
        public function getArchetypesByIdFormat($pIdFormat)
        {
            $data = Query::select(
                "archetypes.id_archetype, name_archetype, image_archetype,
                    COUNT(DISTINCT players.id_player) AS count_players", $this->table)
                ->join("players", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("tournaments.id_format", Query::EQUAL, $pIdFormat)
                ->groupBy("archetypes.id_archetype")
                ->order("count_players", "DESC")
                ->execute($this->handler);
            return $data;
        }
    }
}