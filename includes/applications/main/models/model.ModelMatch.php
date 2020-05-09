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

        /**
         * Get global winrate for a given archetype
         * @param $pIdArchetype
         * @param null $pFormatCondition
         * @param null $pCardCondition
         * @param array $pIncludedIdCards
         * @param array $pExcludedIdCards
         * @param bool $pExcludeMirrors
         * @return mixed
         * @throws \Exception
         */
        public function getWinrateByArchetypeId ($pIdArchetype, $pFormatCondition = null, $pCardCondition = null, $pIncludedIdCards = array(), $pExcludedIdCards = array(), $pExcludeMirrors = false) {
            if(!$pFormatCondition)
                $pFormatCondition = Query::condition();
            $q = Query::select("ROUND(100*SUM(result_match)/COUNT(1), 2) AS winrate, COUNT(1) AS total", $this->table)
                ->join("players p", Query::JOIN_INNER, "matches.id_player = p.id_player AND p.id_archetype = $pIdArchetype")
                ->join("tournaments", Query::JOIN_INNER, "p.id_tournament = tournaments.id_tournament")
                ->andCondition($pFormatCondition);
            if($pCardCondition) {
                $q->join("player_card", Query::JOIN_INNER, "player_card.id_player = p.id_player")
                    ->andCondition($pCardCondition);
            }
            if ($pExcludeMirrors) {
                $q->join("players op", Query::JOIN_INNER, "matches.opponent_id_player = op.id_player AND op.id_archetype != $pIdArchetype");
            }
            if ($pIncludedIdCards) {
                $count_cards = 0;
                $cards_condition = Query::condition();
                foreach ($pIncludedIdCards['main'] as $id_card) {
                    $cards_condition->orCondition(
                        Query::condition()
                            ->andWhere("id_card", Query::EQUAL, $id_card)
                            ->andWhere("count_main", Query::UPPER, 0, false)
                    );
                    $count_cards++;
                }
                foreach ($pIncludedIdCards['side'] as $id_card) {
                    $cards_condition->orCondition(
                        Query::condition()
                            ->andWhere("id_card", Query::EQUAL, $id_card)
                            ->andWhere("count_side", Query::UPPER, 0, false)
                    );
                    $count_cards++;
                }
                $included_query = Query::select("players.id_player", "player_card")
                    ->join("players", Query::JOIN_INNER, "players.id_player = player_card.id_player AND players.id_archetype = $pIdArchetype")
                    ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                    ->andCondition($pFormatCondition)
                    ->andCondition($cards_condition)
                    ->groupBy("players.id_player")
                    ->andHaving("COUNT(1) >= $count_cards", false)
                    ->get(false);
                $q->andWhere("p.id_player", Query::IN, "(" . $included_query . ")", false);
            }
            if ($pExcludedIdCards) {
                $count_cards = 0;
                $cards_condition = Query::condition();
                foreach ($pExcludedIdCards['main'] as $id_card) {
                    $cards_condition->orCondition(
                        Query::condition()
                            ->andWhere("id_card", Query::EQUAL, $id_card)
                            ->andWhere("count_main", Query::UPPER, 0, false)
                    );
                    $count_cards++;
                }
                foreach ($pExcludedIdCards['side'] as $id_card) {
                    $cards_condition->orCondition(
                        Query::condition()
                            ->andWhere("id_card", Query::EQUAL, $id_card)
                            ->andWhere("count_side", Query::UPPER, 0, false)
                    );
                    $count_cards++;
                }
                $included_query = Query::select("players.id_player", "player_card")
                    ->join("players", Query::JOIN_INNER, "players.id_player = player_card.id_player AND players.id_archetype = $pIdArchetype")
                    ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                    ->andCondition($pFormatCondition)
                    ->andCondition($cards_condition)
                    ->groupBy("players.id_player")
                    ->andHaving("COUNT(1) >= $count_cards", false)
                    ->get(false);
                $q->andWhere("p.id_player", Query::NOT_IN, "(" . $included_query . ")", false);
            }
            $winrate = $q->execute($this->handler);
            return $winrate[0];
        }

        public function getFullWinrate ($pCondition = null, $order_archetypes = array()) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $pArchetypeCondition = clone $pCondition;
            $pArchetypeCondition->andCondition(
                Query::condition()->andWhere("p.id_archetype", Query::NOT_EQUAL, "op.id_archetype", false)
            );
            $wins = $this->countWinsByArchetype($pArchetypeCondition, $order_archetypes);
            $matches = $this->countMatchesByArchetype($pArchetypeCondition, $order_archetypes);
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

        public function getFullWinrateByArchetypeId ($pArchetypeId, $pCondition = null, $order_archetypes = array()) {
            $data = array();
            if(!$pCondition)
                $pCondition = Query::condition();
            $pArchetypeCondition = clone $pCondition;
            $pArchetypeCondition->andCondition(Query::condition()->andWhere("p.id_archetype", Query::EQUAL, $pArchetypeId));
            $wins = $this->countWinsByArchetype($pArchetypeCondition, $order_archetypes);
            $matches = $this->countMatchesByArchetype($pArchetypeCondition, $order_archetypes);

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
        public function countWinsByArchetype ($pCondition = null, $order_archetypes = array()) {
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
            if(strpos($pCondition->getWhere(), "tournaments") || strpos($pCondition->getWhere(), "format")) {
                $q->join('tournaments', Query::JOIN_INNER, "tournaments.id_tournament = p.id_tournament");
            }
            if ($order_archetypes) {
                $q->andWhere("op.id_archetype", Query::IN, "(" . implode(",", $order_archetypes) . ")", false);
            } else {
                trace_r("WARNING - no archetypes selected for metagame");
                return array();
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

        public function countMatchesByArchetype ($pCondition = null, $order_archetypes = array()) {
            if(!$pCondition)
                $pCondition = Query::condition();
            /** @var QuerySelect $q */
            $q = Query::select("op.id_archetype, COUNT(1) AS count", $this->table)
                ->join("players p", Query::JOIN_INNER, $this->table . ".id_player = p.id_player")
                ->join("players op", Query::JOIN_INNER, $this->table . ".opponent_id_player = op.id_player")
                ->andCondition($pCondition)
                ->groupBy("op.id_archetype");
            if(strpos($pCondition->getWhere(), "tournaments") || strpos($pCondition->getWhere(), "format")) {
                $q->join('tournaments', Query::JOIN_INNER, "tournaments.id_tournament = p.id_tournament");
            }
            if ($order_archetypes) {
                $q->andWhere("op.id_archetype", Query::IN, "(" . implode(",", $order_archetypes) . ")", false)
                    ->order("FIELD(op.id_archetype, " . implode(",", $order_archetypes) . ")");
            } else {
                trace_r("WARNING - no archetypes selected for metagame");
                return array();
            }
            $data = $q->execute($this->handler);
            return $data;
        }

        public function countMatches ($pCond = null) {
            if (!$pCond) {
                $pCond = Query::condition();
            }
            $q = Query::select("count(1) as nb", $this->table)
                ->join("players", Query::JOIN_INNER, "players.id_player = matches.id_player")
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                ->setCondition($pCond)
                ->execute($this->handler);
            return $q[0]["nb"];
        }

        public function countWins ($pCond = null) {
            if (!$pCond) {
                $pCond = Query::condition();
            }
            $q = Query::select("SUM(result_match) AS total", $this->table)
                ->join("players", Query::JOIN_INNER, "players.id_player = matches.id_player")
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                ->setCondition($pCond)
                ->execute($this->handler);
            return $q[0]["total"];
        }
    }
}