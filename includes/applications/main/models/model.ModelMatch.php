<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;
    use core\db\QueryCondition;
    use core\db\QuerySelect;

    class ModelMatch extends BaseModel {

        public function __construct()
        {
            parent::__construct("matches", "id_player");
        }

        public function getWinrate ($pCondition = null, $order_archetypes = array()) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $pCondition->andCondition(
                Query::condition()->andWhere("p.id_archetype", Query::NOT_EQUAL, "op.id_archetype", false)
            );
            $wins = $this->countWinsByArchetypeId($pCondition, $order_archetypes);
            $matches = $this->countMatchesByArchetypeId($pCondition, $order_archetypes);
            $counts = array();
            foreach ($matches as $archetype) {
                $counts[$archetype['id_archetype']] = $archetype['count'];
            }
            foreach ($wins as &$archetype) {
                // invert winrate because we were searching global winrate against those archetyes
                $archetype['winrate'] = 100 - round(100*$archetype['wins']/$counts[$archetype['id_archetype']], 1);
            }

            return $wins;
        }

        public function getWinrateByArchetypeId ($pArchetypeId, $pCondition = null, $order_archetypes = array()) {
            $data = array();
            if(!$pCondition)
                $pCondition = Query::condition();
            $pCondition->andCondition(Query::condition()->andWhere("p.id_archetype", Query::EQUAL, $pArchetypeId));
            $wins = $this->countWinsByArchetypeId($pCondition, $order_archetypes);
            $matches = $this->countMatchesByArchetypeId($pCondition, $order_archetypes);

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

        /**
         * @param QueryCondition $pCondition
         * @param array $order_archetypes
         * @return array|resource
         * @throws \Exception
         */
        public function countWinsByArchetypeId ($pCondition = null, $order_archetypes = array()) {
            if(!$pCondition)
                $pCondition = Query::condition();
            /** @var QuerySelect $q */
            $q = Query::select("op.id_archetype, SUM(matches.result_match) AS wins", $this->table)
                ->join("players p", Query::JOIN_INNER, $this->table . ".id_player = p.id_player")
                ->join("players op", Query::JOIN_INNER, $this->table . ".opponent_id_player = op.id_player")
//                ->join("archetypes ap", Query::JOIN_INNER, "ap.id_archetype = p.id_archetype")
//                ->join("archetypes aop", Query::JOIN_INNER, "aop.id_archetype = op.id_archetype")
                ->andCondition($pCondition)
                ->groupBy("op.id_archetype");
            if(strpos($pCondition->getWhere(), "tournaments")) {
                $q->join('tournaments', Query::JOIN_INNER, "tournaments.id_tournament = p.id_tournament");
            }
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

        public function countMatchesByArchetypeId ($pCondition = null, $order_archetypes = array()) {
            if(!$pCondition)
                $pCondition = Query::condition();
            /** @var QuerySelect $q */
            $q = Query::select("op.id_archetype, COUNT(1) AS count", $this->table)
                ->join("players p", Query::JOIN_INNER, $this->table . ".id_player = p.id_player")
                ->join("players op", Query::JOIN_INNER, $this->table . ".opponent_id_player = op.id_player")
                ->andCondition($pCondition)
                ->groupBy("op.id_archetype");
            if(strpos($pCondition->getWhere(), "tournaments")) {
                $q->join('tournaments', Query::JOIN_INNER, "tournaments.id_tournament = p.id_tournament");
            }
            if ($order_archetypes) {
                $q->andWhere("op.id_archetype", Query::IN, "(" . implode(",", $order_archetypes) . ")", false)
                    ->order("FIELD(op.id_archetype, " . implode(",", $order_archetypes) . ")");
            }
            $data = $q->execute($this->handler);
            return $data;
        }
    }
}