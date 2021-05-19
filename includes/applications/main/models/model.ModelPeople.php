<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelPeople extends BaseModel {

        public function __construct()
        {
            parent::__construct("people", "id_people");
        }

        public function getProByArenaId ($pArenaId, $pFields = "*") {
            $people = Query::select($pFields, $this->table)
                ->join("player_tag", Query::JOIN_INNER, "people.id_people = player_tag.id_people")
                ->andWhere("tag_player", Query::IN, "('mpl', 'rivals')", false)
                ->andWhere("arena_id", Query::EQUAL, $pArenaId)
                ->execute($this->handler);
            return isset($people[0]) ? $people[0] : null;
        }

        // TODO order by most matches played ?
        public function searchPeopleByName ($pName, $pCount = false, $pLimit = 10) {
            $q = Query::select(($pCount ? "COUNT(1) AS count" : "id_people AS id_player, arena_id AS name_player"), $this->table)
                ->andWhere("people.arena_id", Query::LIKE, "'%" . $pName . "%'", false)
                ->order("arena_id");
            if (!$pCount) {
                $q->limit(0, $pLimit);
            }
            $data = $q->execute($this->handler);
            return $pCount ? $data[0]['count'] : $data;
        }

        public function getPlayerById ($pIdPeople) {
            $q = Query::select("people.id_people AS id_player, arena_id AS name_player, tag_player, country_player", "people")
                ->join("player_tag", Query::JOIN_OUTER_LEFT, "people.id_people = player_tag.id_people")
                ->andWhere("people.id_people", Query::EQUAL, $pIdPeople);
            return $q->execute($this->handler);
        }
    }
}