<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelMatch extends BaseModel {

        public function __construct()
        {
            parent::__construct("matches", "id_player");
        }

        public function getWinrateByArchetypeId ($pArchetypeId, $pCondition = null, $order_archetypes = array()) {
            $data = array();
            $wins = $this->countWinsByArchetypeId($pArchetypeId, $pCondition, $order_archetypes);
            $matches = $this->countMatchesByArchetypeId($pArchetypeId, $pCondition, $order_archetypes);

            $total_wins = 0;
            $total_matches = 0;
            foreach ($wins as $win) {
                $data[$win['id_archetype']] = $win;
                // exclude mirror matches
                if ($win['id_archetype'] != $pArchetypeId) {
                    $total_wins += $win['wins'];
                }
            }
            foreach ($matches as $match) {
                $data[$match['id_archetype']]['percent'] = round(100*$data[$match['id_archetype']]['wins']/$match['count'], 1);
                $data[$match['id_archetype']]['count'] = $match['count'];
                // exclude mirror matches
                if ($match['id_archetype'] != $pArchetypeId) {
                    $total_matches += $match['count'];
                }
            }
            // add total
            $data[] = array(
                "name_archetype" => "total",
                "wins"           => $total_wins,
                "count"          => $total_matches,
                "percent"        => round((100*$total_wins/$total_matches), 2),
                "id_archetype"   => 0
            );
            return $data;
        }

        public function countWinsByArchetypeId ($pArchetypeId, $pCondition = null, $order_archetypes = array()) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $q = Query::select("op.id_archetype, SUM(matches.result_match) AS wins", $this->table)
                ->join("players p", Query::JOIN_INNER, $this->table . ".id_player = p.id_player")
                ->join("players op", Query::JOIN_INNER, $this->table . ".opponent_id_player = op.id_player")
//                ->join("archetypes ap", Query::JOIN_INNER, "ap.id_archetype = p.id_archetype")
//                ->join("archetypes aop", Query::JOIN_INNER, "aop.id_archetype = op.id_archetype")
                ->andCondition($pCondition)
                ->andWhere("p.id_archetype", Query::EQUAL, $pArchetypeId)
                ->groupBy("op.id_archetype");
            if ($order_archetypes) {
                $q->andWhere("op.id_archetype", Query::IN, "(" . implode(",", $order_archetypes) . ")", false);
            }
            $q2 = Query::select("archetypes.*, IF(wins IS NULL, 0, wins) AS wins", "(" . $q->get(false) . ") tmp")
                ->join("archetypes", Query::JOIN_OUTER_RIGHT, "tmp.id_archetype = archetypes.id_archetype");
            if ($order_archetypes) {
                $q2->andWhere("archetypes.id_archetype", Query::IN, "(" . implode(",", $order_archetypes) . ")", false)
                    ->order("FIELD(archetypes.id_archetype, " . implode(",", $order_archetypes) . ")");
            }
            $data = $q2->execute($this->handler);
            return $data;
        }

        public function countMatchesByArchetypeId ($pArchetypeId, $pCondition = null, $order_archetypes = array()) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $q = Query::select("op.id_archetype, COUNT(1) AS count", $this->table)
                ->join("players p", Query::JOIN_INNER, $this->table . ".id_player = p.id_player")
                ->join("players op", Query::JOIN_INNER, $this->table . ".opponent_id_player = op.id_player")
                ->andCondition($pCondition)
                ->andWhere("p.id_archetype", Query::EQUAL, $pArchetypeId)
                ->groupBy("op.id_archetype");
            if ($order_archetypes) {
                $q->andWhere("op.id_archetype", Query::IN, "(" . implode(",", $order_archetypes) . ")", false)
                    ->order("FIELD(op.id_archetype, " . implode(",", $order_archetypes) . ")");
            }
            $data = $q->execute($this->handler);
            return $data;
        }
    }
}