<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelCard extends BaseModel
    {
        protected $tablePlayerCards;
        protected $modelPlayer;

        public function __construct()
        {
            $this->tablePlayerCards = "player_card";
            $this->modelPlayer = new ModelPlayer();
            parent::__construct("cards", "id_card");
        }

        public function getPlayedCards ($pCondition = null, $pRulesCondition = null) {
            if(!$pCondition)
                $pCondition = Query::condition();
            if(!$pRulesCondition)
                $pRulesCondition = Query::condition();
            $count_players = $this->modelPlayer->countPlayers(clone $pCondition, clone $pRulesCondition);
            // TODO division here ?
            $q = Query::select("cards.id_card, name_card, ROUND(SUM(count_main)/$count_players, 2) AS avg_main, ROUND(SUM(count_side)/$count_players, 2) AS avg_side", $this->tablePlayerCards)
                ->join("players p", Query::JOIN_INNER, "p.id_player = player_card.id_player")
                ->join($this->table, Query::JOIN_INNER, "cards.id_card = player_card.id_card")
                ->join("tournaments", Query::JOIN_INNER, "p.id_tournament = tournaments.id_tournament")
                ->andCondition(clone $pCondition)
                ->groupBy("cards.id_card")
                ->order("name_card");
            if ($pRulesCondition) {
                $rules_cond = clone $pRulesCondition;
                $q->andCondition($rules_cond);
            }
            return $q->execute($this->handler);
        }

        /**
         * @param $pIdArchetype
         * @param null $pFormatCondition
         * @param array $pIncludedIdCards
         * @param array $pExcludedIdCards
         * @return mixed
         */
        public function getCardRuleCondition ($pIdArchetype, $pFormatCondition = null, $pIncludedIdCards = array(), $pExcludedIdCards = array()) {
            $q = Query::condition();
            if (
                $pIncludedIdCards &&
                (!empty($pIncludedIdCards['main']) || !empty($pIncludedIdCards['side']))
            ) {
                $count_cards = 0;
                $cards_condition = Query::condition();
                foreach ($pIncludedIdCards['main'] as $id_card => $name_card) {
                    $cards_condition->orCondition(
                        Query::condition()
                            ->andWhere("id_card", Query::EQUAL, $id_card)
                            ->andWhere("count_main", Query::UPPER, 0, false)
                    );
                    $count_cards++;
                }
                foreach ($pIncludedIdCards['side'] as $id_card => $name_card) {
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
            if (
                $pExcludedIdCards &&
                (!empty($pExcludedIdCards['main']) || !empty($pExcludedIdCards['side']))
            ) {
                $cards_condition = Query::condition();
                foreach ($pExcludedIdCards['main'] as $id_card => $name_card) {
                    $cards_condition->orCondition(
                        Query::condition()
                            ->andWhere("id_card", Query::EQUAL, $id_card)
                            ->andWhere("count_main", Query::UPPER, 0, false)
                    );
                }
                foreach ($pExcludedIdCards['side'] as $id_card => $name_card) {
                    $cards_condition->orCondition(
                        Query::condition()
                            ->andWhere("id_card", Query::EQUAL, $id_card)
                            ->andWhere("count_side", Query::UPPER, 0, false)
                    );
                }
                $included_query = Query::select("players.id_player", "player_card")
                    ->join("players", Query::JOIN_INNER, "players.id_player = player_card.id_player AND players.id_archetype = $pIdArchetype")
                    ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                    ->andCondition($pFormatCondition)
                    ->andCondition($cards_condition)
                    ->groupBy("players.id_player")
                    ->andHaving("COUNT(1) >= 1", false)
                    ->get(false);
                $q->andWhere("p.id_player", Query::NOT_IN, "(" . $included_query . ")", false);
            }
            return $q;
        }

        public function insertCards ($pCards) {
            if (empty($pCards)) {
                return false;
            }
            return Query::execute("INSERT IGNORE INTO " . $this->table . "(name_card) VALUES ('" . implode("'), ('", $pCards) . "')");
        }

        public function insertPlayerCards ($pCards = array()) {
            return Query::insertMultiple($pCards)
                ->into($this->tablePlayerCards)
                ->execute($this->handler);
        }
    }
}