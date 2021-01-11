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

        public function getPlayedCards ($pCondition = null, $pRulesCondition = null, $pOrder = "name_card", $pType = "ASC") {
            if(!$pCondition)
                $pCondition = Query::condition();
            if(!$pRulesCondition)
                $pRulesCondition = Query::condition();
            $q = Query::select("cards.id_card, name_card,
                COUNT(IF(count_main = 0, NULL, count_main)) AS count_players_main, COUNT(IF(count_side = 0, NULL, count_side)) AS count_players_side,
                SUM(count_main) AS count_total_main, SUM(count_side) AS count_total_side", $this->tablePlayerCards)
                ->join("players p", Query::JOIN_INNER, "p.id_player = player_card.id_player")
                ->join($this->table, Query::JOIN_INNER, "cards.id_card = player_card.id_card")
                ->join("tournaments", Query::JOIN_INNER, "p.id_tournament = tournaments.id_tournament")
                ->andCondition(clone $pCondition)
                ->groupBy("cards.id_card")
                ->order($pOrder, $pType);
            if ($pRulesCondition) {
                $rules_cond = clone $pRulesCondition;
                $q->andCondition($rules_cond);
            }
            return $q->execute($this->handler);
        }

        public function countSideboardCardsByIdPlayer ($pIdPlayer) {
            $q = Query::select("SUM(count_side) AS count", $this->tablePlayerCards)
                ->andWhere("id_player", Query::EQUAL, $pIdPlayer)
                ->groupBy("id_player")
                ->execute($this->handler);
            return array_key_exists(0, $q) ? $q[0]['count'] : 0;
        }

        public function getTotalCopiesByCardId ($pIdCard, $pIdArchetype, $pIdFormat, $pMainDeck = true) {
            $fields = $pMainDeck ? "SUM(count_main) AS total" : "SUM(count_side) AS total";
            $total = Query::select($fields, "players")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament AND id_format = $pIdFormat AND players.id_archetype = $pIdArchetype")
                ->join("player_card", Query::JOIN_INNER, "players.id_player = player_card.id_player AND id_card = $pIdCard")
                ->execute($this->handler);
            return $total[0]['total'];
        }

        public function getDecklistCardsByIdPlayer ($pIdPlayer) {
            $c = Query::select("cards.name_card, player_card.count_main", $this->tablePlayerCards)
                ->join($this->table, Query::JOIN_INNER, "cards.id_card = player_card.id_card AND id_player = $pIdPlayer")
                ->andWhere("count_main", Query::NOT_EQUAL, 0)
                ->order("cards.name_card")
                ->execute($this->handler);
            $cards = array();
            foreach ($c as $card) {
                for ($i = 1; $i <= $card['count_main']; $i++) {
                    $cards[] = $card['name_card'];
                }
            }
            return $cards;
        }

        /**
         * get ordered decklist for visual display
         * @param $pIdPlayer
         * @return array|resource
         */
        // order is only made for maindeck cards, if needed use a different query for sideboard cards
        public function getDecklistCards ($pIdPlayer) {
            $q = Query::select("cards.id_card, cards.name_card, cards.mana_cost_card, cards.type_card, cards.image_card, count_main, count_side", $this->tablePlayerCards)
                ->join($this->table, Query::JOIN_INNER, "cards.id_card = player_card.id_card AND id_player = $pIdPlayer")
                ->groupBy("cards.id_card")
                ->order(" CASE  WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                        WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10 WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                        cmc_card, count_main DESC, color_card", "");
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

        public function searchCardsByName ($pName, $pCount = false, $pLimit = 10) {
            $q = Query::select(($pCount ? "COUNT(1) AS count" : "*"), $this->table)
                ->andWhere("cards.name_card", Query::LIKE, "'%" . $pName . "%'", false)
                ->order("cards.name_card");
            if (!$pCount) {
                $q->limit(0, $pLimit);
            }
            $data = $q->execute($this->handler);
            return $pCount ? $data[0]['count'] : $data;
        }

        public function insertCards ($pCards) {
            if (empty($pCards)) {
                return false;
            }
            foreach ($pCards as $key => $card) {
                $pCards[$key] = Query::escapeValue($card);
            }
            return Query::execute("INSERT IGNORE INTO " . $this->table . "(name_card) VALUES (" . implode("), (", $pCards) . ")", $this->handler);
        }

        public function insertPlayerCards ($pCards = array()) {
            return Query::replaceMultiple($pCards)
                ->into($this->tablePlayerCards)
                ->execute($this->handler);
        }
    }
}