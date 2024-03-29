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
         * @param null $pRulesCondition
         * @param null $pCardCondition
         * @param bool $pExcludeMirrors
         * @return mixed
         * @throws \Exception
         */
        public function getWinrateByArchetypeId (
            $pIdArchetype,
            $pFormatCondition = null,
            $pRulesCondition = null,
            $pCardCondition = null,
            $pExcludeMirrors = false
        ) {
            if(!$pFormatCondition)
                $pFormatCondition = Query::condition();
            $q = Query::select("ROUND(100*SUM(result_match)/COUNT(result_match), 2) AS winrate, SUM(result_match) AS wins, COUNT(result_match) AS total, COUNT(DISTINCT matches.id_player) AS count_players", $this->table)
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
            if ($pRulesCondition) {
                $rules_cond = clone $pRulesCondition;
                $q->andCondition($rules_cond);
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

        /**
         * @param $pArchetypeId
         * @param null $pCondition
         * @param array $order_archetypes
         * @param array $other_archetypes
         * @return array
         */
        public function getFullWinrateByArchetypeId ($pArchetypeId, $pCondition = null, $order_archetypes = array(), $other_archetypes = array()) {
            $data = array();
            if(!$pCondition)
                $pCondition = Query::condition();
            $pArchetypeCondition = clone $pCondition;
            if ($other_archetypes && $pArchetypeId == ModelArchetype::ARCHETYPE_OTHER_ID) {
                $pArchetypeCondition->andCondition(Query::condition()->andWhere(
                    "p.id_archetype",
                    Query::IN,
                    "(" . ModelArchetype::ARCHETYPE_OTHER_ID . ", " . implode(", ", $other_archetypes) . ")",
                    false
                ));
            } else {
                $pArchetypeCondition->andCondition(Query::condition()->andWhere("p.id_archetype", Query::EQUAL, $pArchetypeId));
            }
            // wins is returned for all opponents, matches only for those who are actually played
            $wins = $this->countWinsByArchetype($pArchetypeCondition, $order_archetypes, $other_archetypes);
            $matches = $this->countMatchesByArchetype($pArchetypeCondition, $order_archetypes, $other_archetypes);

            $total_wins = 0;
            $total_wins_mirror = 0;
            $total_matches = 0;
            $total_matches_mirror = 0;
            foreach ($wins as $win) {
                $data[$win['id_archetype']] = $win;
                // exclude mirror matches
                if ($win['id_archetype'] != $pArchetypeId) {
                    $total_wins += $win['wins'];
                    $total_wins_mirror += $win['wins'];
                } else {
                    $total_wins_mirror += $win['wins']/2;
                }
            }
            foreach ($matches as $match) {
                $data[$match['id_archetype']]['percent'] = round(100*$data[$match['id_archetype']]['wins']/$match['count'], 1);
                $data[$match['id_archetype']]['count'] = $match['count'];
                $data[$match['id_archetype']]['loss'] = $match['count']-$data[$match['id_archetype']]['wins'];
                // exclude mirror matches
                if ($match['id_archetype'] != $pArchetypeId) {
                    $total_matches += $match['count'];
                    $total_matches_mirror += $match['count'];
                } else {
                    $total_matches_mirror += $match['count']/2;
                }
            }
            // add total
            array_unshift($data, array(
                "name_archetype" => "total",
                "wins"           => $total_wins,
                "loss"           => $total_matches-$total_wins,
                "count"          => $total_matches,
                "percent"        => $total_matches == 0 ? '-' : round((100*$total_wins/$total_matches), 1),
                "id_archetype"   => 0
            ));
            // add total including mirror matches
            /*
            $data[] = array(
                "name_archetype" => "total_mirror",
                "wins"           => $total_wins_mirror,
                "count"          => $total_matches_mirror,
                "percent"        => $total_matches_mirror == 0 ? '-' : round((100*$total_wins_mirror/$total_matches_mirror), 1),
                "id_archetype"   => 0
            );*/
            return $data;
        }

        /**
         * @param QueryCondition $pCondition
         * @param array $order_archetypes
         * @param array $other_archetypes
         * @return array|resource
         * @throws \Exception
         */
        public function countWinsByArchetype ($pCondition = null, $order_archetypes = array(), $other_archetypes = array()) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $select = "op.id_archetype AS id_archetype, SUM(matches.result_match) AS wins";
            if ($other_archetypes) {
                // group all specified archetypes in 'other' category
                $select = "CASE WHEN op.id_archetype IN (" . implode(", ", $other_archetypes) . ") THEN " . ModelArchetype::ARCHETYPE_OTHER_ID . " ELSE op.id_archetype END AS id_archetype, SUM(matches.result_match) AS wins";
            }
            /** @var QuerySelect $q */
            $q = Query::select($select, $this->table)
                ->join("players p", Query::JOIN_INNER, $this->table . ".id_player = p.id_player")
                ->join("players op", Query::JOIN_INNER, $this->table . ".opponent_id_player = op.id_player")
//                ->join("archetypes ap", Query::JOIN_INNER, "ap.id_archetype = p.id_archetype")
//                ->join("archetypes aop", Query::JOIN_INNER, "aop.id_archetype = op.id_archetype")
                ->andCondition($pCondition)
                ->groupBy("id_archetype");
            if(strpos($pCondition->getWhere(), "tournaments") || strpos($pCondition->getWhere(), "format")) {
                $q->join('tournaments', Query::JOIN_INNER, "tournaments.id_tournament = p.id_tournament");
            }
            if ($order_archetypes) {
                $q->andWhere("op.id_archetype", Query::IN, "(" . implode(",", $order_archetypes) . ")", false);
            } else {
                trace_r("WARNING - no archetypes selected for metagame");
                return array();
            }
            $q2 = Query::select("archetypes.id_archetype, IF(wins IS NULL, 0, wins) AS wins", "(" . $q->get(false) . ") tmp")
                ->join("archetypes", Query::JOIN_OUTER_RIGHT, "tmp.id_archetype = archetypes.id_archetype");
            if ($order_archetypes) {
                if ($other_archetypes) {
                    // get only on archetypes which are not in 'Other' category
                    $q2
                        ->orWhere("archetypes.id_archetype", Query::EQUAL, ModelArchetype::ARCHETYPE_OTHER_ID)
                        ->orCondition(
                            Query::condition()
                            ->andWhere("archetypes.id_archetype", Query::IN, "(" . implode(",", $order_archetypes) . ")", false)
                            ->andWhere("archetypes.id_archetype", Query::NOT_IN, "(" . implode(",", $other_archetypes) . ")", false)
                    );
                } else {
                    $q2->andWhere("archetypes.id_archetype", Query::IN, "(" . implode(",", $order_archetypes) . ")", false);
                }
                $q2->order("FIELD(archetypes.id_archetype, " . implode(",", $order_archetypes) . ")");
            }
            $data = $q2->execute($this->handler);
            return $data;
        }

        /**
         * @param QueryCondition $pCondition
         * @param array $order_archetypes
         * @param array $other_archetypes
         * @return array|resource
         */
        public function countMatchesByArchetype ($pCondition = null, $order_archetypes = array(), $other_archetypes = array()) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $select = "op.id_archetype AS id_archetype, COUNT(1) AS count";
            if ($other_archetypes) {
                // group all specified archetypes in 'other' category
                $select = "CASE WHEN op.id_archetype IN (" . implode(", ", $other_archetypes) . ") THEN " . ModelArchetype::ARCHETYPE_OTHER_ID . " ELSE op.id_archetype END AS id_archetype, COUNT(1) AS count";
            }
            /** @var QuerySelect $q */
            $q = Query::select($select, $this->table)
                ->join("players p", Query::JOIN_INNER, $this->table . ".id_player = p.id_player")
                ->join("players op", Query::JOIN_INNER, $this->table . ".opponent_id_player = op.id_player")
                ->andCondition($pCondition)
                ->groupBy("id_archetype");
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