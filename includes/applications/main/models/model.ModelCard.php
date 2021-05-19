<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelCard extends BaseModel
    {
        CONST DECKLIST_MAX_COLUMNS = 8;
        CONST DECKLIST_MAX_LANDS = 7;

        protected $tablePlayerCards;
        protected $modelPlayer;

        public function __construct()
        {
            $this->tablePlayerCards = "player_card";
            $this->modelPlayer = new ModelPlayer();
            parent::__construct("cards", "id_card");
        }

        /**
         * Sort maindeck cards for visual display
         * @param $pDecklist
         * @param bool $pIsLimited
         * @return array
         */
        public function sortDecklistByCurve ($pDecklist, $pIsLimited = false) {
            $return = array();
            $lands = array();
            $decklist_by_curve = array();
            $decklist_by_curve_spells = array();

            foreach ($pDecklist as $card) {
                if ($card['mana_cost_card'] == "") {
                    $card['cmc_card'] = 99;
                    $lands[] = $card;
                } else {
                    if ($pIsLimited) {
                        // fill creatures/spells curve at the same time
                        if (strpos($card['type_card'], "Creature") === false) {
                            $decklist_by_curve_spells[$card['cmc_card']][] = $card;
                        } else {
                            $decklist_by_curve[$card['cmc_card']][] = $card;
                        }
                    } else {
                        $decklist_by_curve[$card['cmc_card']][] = $card;
                    }
                }
            }

            // align creatures & spells in curve
            if ($pIsLimited) {
                for ($curve = 0; $curve <= 10; $curve++) {
                    if (array_key_exists($curve, $decklist_by_curve)) {
                        if (!array_key_exists($curve, $decklist_by_curve_spells)) {
                            $decklist_by_curve_spells[$curve] = array();
                        }
                    } elseif (array_key_exists($curve, $decklist_by_curve_spells)) {
                        $decklist_by_curve[$curve] = array();
                    }
                }
                ksort($decklist_by_curve_spells);
            }
            ksort($decklist_by_curve);

            $max_columns = count($lands) > self::DECKLIST_MAX_LANDS ?
                self::DECKLIST_MAX_COLUMNS-1 : self::DECKLIST_MAX_COLUMNS;

            // if more than N columns before lands, group columns N+
            if (count($decklist_by_curve) >= $max_columns) {
                $keep = array_slice($decklist_by_curve, 0, $max_columns-2);
                $merge = array_slice($decklist_by_curve, $max_columns-2);
                $merged = call_user_func_array('array_merge', $merge);
                array_push($keep, $merged);
                $decklist_by_curve = $keep;
            }
            if ($pIsLimited) {
                // get max height for creatures block
                $return["creatures_main_height"] = count(max($decklist_by_curve))*60+210;
            }

            // split lands in 2 columns if needed
            if (count($lands) > self::DECKLIST_MAX_LANDS) {
                $size_lands = round(count($lands)/2);
                $lands_bis = array_slice($lands, $size_lands);
                $lands = array_slice($lands, 0, $size_lands);
                $decklist_by_curve[98] = $lands;
                $decklist_by_curve[99] = $lands_bis;
            } else {
                $decklist_by_curve[99] = $lands;
            }

            if ($pIsLimited) {
                if (count($decklist_by_curve_spells) >= $max_columns) {
                    $keep = array_slice($decklist_by_curve_spells, 0, $max_columns-2);
                    $merge = array_slice($decklist_by_curve_spells, $max_columns-2);
                    $merged = call_user_func_array('array_merge', $merge);
                    array_push($keep, $merged);
                    $decklist_by_curve_spells = $keep;
                }
                $return['curve_spells'] = $decklist_by_curve_spells;
            }

            $return['curve'] = $decklist_by_curve;
            return $return;
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

        /**
         * Function used for aggregate decklists tool
         * @param null $pCondition
         * @return array|resource
         */
        public function getPlayedCardsByCopies ($pCondition = null, $pMaindeck = true) {
            $count_cards = $pMaindeck ? "count_main" : "count_side";
            if(!$pCondition)
                $pCondition = Query::condition();
            $q = Query::select("cards.id_card, $count_cards AS 'copie_n', name_card,
                IF(type_card LIKE '%land%',1,0) AS is_land, COUNT(1) AS count_players_main", $this->tablePlayerCards)
                ->join("players p", Query::JOIN_INNER, "p.id_player = player_card.id_player")
                ->join($this->table, Query::JOIN_INNER, "cards.id_card = player_card.id_card")
                ->join("tournaments", Query::JOIN_INNER, "p.id_tournament = tournaments.id_tournament")
                ->andWhere("$count_cards", Query::NOT_EQUAL, 0)
                ->andCondition(clone $pCondition)
                ->groupBy("cards.id_card, copie_n")
                ->order("cards.id_card, copie_n", "DESC");
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
         * @param null $pCondition
         * @param null $pOrder
         * @param string $pAsc
         * @return array|resource
         */
        public function getDecklistCards ($pIdPlayer, $pCondition = null, $pOrder = null, $pAsc = "") {
            if (!$pCondition) {
                $pCondition = Query::condition();
            }
            $q = Query::select("cards.id_card, cards.name_card, cards.mana_cost_card, cards.type_card, cards.image_card,
                IF(cards.mana_cost_card LIKE '{X}%', 20, cards.cmc_card) AS cmc_card, count_main, count_side", $this->tablePlayerCards)
                ->join($this->table, Query::JOIN_INNER, "cards.id_card = player_card.id_card AND id_player = $pIdPlayer")
                ->andCondition($pCondition)
                ->groupBy("cards.id_card");
            if ($pOrder) {
                $q->order($pOrder, $pAsc);
            } else {
                $q->order(" CASE  WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                        WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10 WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                        cmc_card, count_main DESC, color_card", "");
            }
            return $q->execute($this->handler);
        }

        public function getDecklistColors ($pIdPlayer) {
            $q = Query::select("color_card, SUM(count_main)", $this->table)
                ->join("player_card", Query::JOIN_INNER, "cards.id_card = player_card.id_card AND id_player = $pIdPlayer AND color_card != ''")
                ->groupBy("color_card")
                ->order("SUM(count_main)", "DESC")
                ->limit(0, 2);
            return $q->execute($this->handler);
        }

        public static function formatManaCost ($pManaCost) {
            $pManaCost = preg_replace('/(\{([\dxcpsurbgw])\})/i', '<i class="ms ms-$2"></i>', strtolower($pManaCost));
            $pManaCost = preg_replace('/(\{([\dxcpsurbgw])\/([\dxcpsurbgw])\})/i', '<i class="ms ms-ci-2 ms-ci-$2$3"></i>', strtolower($pManaCost));
            return $pManaCost;
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
            $q = Query::select(($pCount ? "COUNT(1) AS count" : "cards.id_card, name_card, mana_cost_card, cmc_card,
                    type_card, color_card, set_card, image_card"), $this->table)
                ->andWhere("cards.name_card", Query::LIKE, "'%" . $pName . "%'", false)
                ->order("cards.name_card");
            if (!$pCount) {
                $q->limit(0, $pLimit);
            }
            $data = $q->execute($this->handler);
            return $pCount ? $data[0]['count'] : $data;
        }

        public function getBasicLandIds () {
            $basics = array();
            $data = $this->all(
                Query::condition()
                    ->andWhere("type_card", Query::LIKE, "%basic%")
                    ->andWhere("cmc_card", Query::EQUAL, 0),
                "id_card, name_card"
            );
            foreach ($data as $card) {
                $basics[$card['id_card']] = $card['name_card'];
            }
            return $basics;
        }

        public function getCardCount ($pCondition) {
            $subquery = Query::select("SUM(count_main) AS count_cards", $this->tablePlayerCards)
                ->join("players", Query::JOIN_INNER, "players.id_player = player_card.id_player")
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                ->andCondition($pCondition)
                ->groupBy("players.id_player")
                ->get(false);
            $data = Query::select("COUNT(1) AS count_players, count_cards", "($subquery) tmp ")
                ->groupBy("count_cards")
                ->order("count_players", "DESC")
                ->execute($this->handler);
            return $data;
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