<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\src\MetagamerBot;
    use core\application\Core;
    use core\application\DefaultFrontController;
    use core\application\Go;
    use core\application\routing\RoutingHandler;
    use core\db\Query;
    use core\utils\StatsUtils;

    class archetype extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelMatch;
        protected $modelArchetype;
        protected $modelCard;
        protected $modelFormat;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatch = new ModelMatch();
            $this->modelArchetype = new ModelArchetype();
            $this->modelCard = new ModelCard();
            $this->modelFormat = new ModelFormat();
        }

        /*
         * player count can be different between the header and the #lists playing a given card
         * if some players have no recorded match
         */
        public function index () {
            $analysis_cond = Query::condition();
            $format_cond = Query::condition();
            $archetype = array();
            $format = array();
            if (
                $_GET['id_archetype'] &&
                ($archetype = $this->modelArchetype->getTupleById($_GET['id_archetype'])) &&
                $_GET['id_format'] &&
                ($format = $this->modelFormat->getTupleById($_GET['id_format']))
            ) {
                $analysis_cond = Query::condition()
                    ->andWhere("id_archetype", Query::EQUAL, $archetype['id_archetype'])
                    ->andWhere("id_format", Query::EQUAL, $format['id_format']);
                $format_cond = Query::condition()
                    ->andWhere("id_format", Query::EQUAL, $format['id_format']);
                $analysis_hash = md5("format-" . $format['id_format'] . "-archetype-" . $archetype['id_archetype']);
                if ($_SESSION['analysis'] != $analysis_hash) {
                    $this->cleanCardRules();
                    $_SESSION['analysis'] = $analysis_hash;
                }
            }

            if ($archetype && $format) {
                $this->addContent("archetype", $archetype);
                $this->addContent("format", $format);

                // add new filters
                if (
                    isset($_POST['id_card']) &&
                    preg_match('/^(included|excluded)-(main|side)-(\d+)$/', $_POST['id_card'], $card_rule) &&
                    $card_name = $this->modelCard->getValueById("name_card", $card_rule[3])
                ) {
                    $_SESSION[$card_rule[1]][$card_rule[2]][$card_rule[3]] = $card_name;
                }
                // remove existing filter
                if (
                    isset($_POST['remove_rule']) &&
                    preg_match('/^(included|excluded)-(main|side)-(\d+)$/', $_POST['remove_rule'], $card_rule)
                ) {
                    unset($_SESSION[$card_rule[1]][$card_rule[2]][$card_rule[3]]);
                }
                $link_decklists = RoutingHandler::rewrite(
                    "archetype",
                    "lists") . "?" . http_build_query(array(
                        "id_archetype" => $archetype['id_archetype'],
                        "id_format"    => $format['id_format'],
                        "im"           => implode(":", array_flip($_SESSION['included']['main'])),
                        "is"           => implode(":", array_flip($_SESSION['included']['side'])),
                        "em"           => implode(":", array_flip($_SESSION['excluded']['main'])),
                        "es"           => implode(":", array_flip($_SESSION['excluded']['side']))
                    ));
                $this->addContent("link_decklists", $link_decklists);
                $this->addContent("included", $_SESSION['included']);
                $this->addContent("excluded", $_SESSION['excluded']);

                $rules_cond = $this->modelCard->getCardRuleCondition(
                    $archetype['id_archetype'],
                    $format_cond,
                    $_SESSION['included'],
                    $_SESSION['excluded']);

                $stats = $this->modelMatch->getWinrateByArchetypeId($archetype['id_archetype'], $format_cond);

                if ($stats['count_players'] > 0) {
                    // TODO 20 seconds query
                    $cards = $this->modelCard->getPlayedCards($analysis_cond, $rules_cond);

                    // TODO display standard deviation in grey shades
                    $deviation = StatsUtils::getStandardDeviation($stats['winrate'], $stats['total']);
                    $stats['deviation_up']   = $stats['winrate'] + $deviation;
                    $stats['deviation_down'] = $stats['winrate'] - $deviation;
                    $this->addContent("global", $stats);

                    $stats_rules = $this->modelMatch->getWinrateByArchetypeId($archetype['id_archetype'], $format_cond, $rules_cond);

                    if ($stats_rules['count_players'] > 0) {
                        $deviation = StatsUtils::getStandardDeviation($stats_rules['winrate'], $stats_rules['total']);
                        $stats_rules['deviation_up']   = $stats_rules['winrate'] + $deviation;
                        $stats_rules['deviation_down'] = $stats_rules['winrate'] - $deviation;
                        $stats_without_rules = array(
                            'total' => $stats['total'] - $stats_rules['total']
                        );
                        $stats_without_rules['winrate'] = round(100*($stats['wins'] - $stats_rules['wins'])/$stats_without_rules['total'], 2);
                        $deviation = StatsUtils::getStandardDeviation($stats_without_rules['winrate'], $stats_without_rules['total']);
                        $stats_without_rules['deviation_up']   = $stats_without_rules['winrate'] + $deviation;
                        $stats_without_rules['deviation_down'] = $stats_without_rules['winrate'] - $deviation;
                        $this->addContent("global_without_rules", $stats_without_rules);
                    }
                    $this->addContent("global_rules", $stats_rules);

                    foreach ($cards as &$card) {
                        // count_total_main|count_total_side > 0 : to calculate only with main|side presence
                        if ($card['count_total_main'] > 0) {
                            $card['avg_main'] = round($card['count_total_main']/$card['count_players_main'], 1);
                            if ($card['count_players_main'] >= 10 && ($stats_rules['count_players']-$card['count_players_main']) >= 10) {
                                $winrate = $this->modelMatch->getWinrateByArchetypeId(
                                    $archetype['id_archetype'],
                                    $format_cond,
                                    $rules_cond,
                                    Query::condition()
                                        ->andWhere("id_card", Query::EQUAL, $card['id_card'])
                                        ->andWhere("count_main", Query::UPPER, 0));

                                $card['winrate_main'] = $winrate['winrate'];
                                // total matches count
                                $card['total_main'] = $winrate['total'];
                                $deviation = StatsUtils::getStandardDeviation($card['winrate_main'], $card['total_main']);
                                $card['deviation_up_main'] = $card['winrate_main'] + $deviation;
                                $card['deviation_down_main'] = $card['winrate_main'] - $deviation;
                                // if we have less matches for current card than total for current rules
                                if ($stats_rules['total'] > $card['total_main'] && $card['count_players_main'] < $stats_rules['count_players']) {
                                    $card['winrate_without_main'] = round(100 * ($stats_rules['wins'] - $winrate['wins']) / ($stats_rules['total'] - $card['total_main']), 2);
                                    $deviation = StatsUtils::getStandardDeviation($card['winrate_without_main'], $stats_rules['total'] - $card['total_main']);
                                    $card['deviation_up_without_main'] = $card['winrate_without_main'] + $deviation;
                                    $card['deviation_down_without_main'] = $card['winrate_without_main'] - $deviation;
                                }
                            }
                            $card['display_actions_main'] = ($stats_rules['count_players'] > $card['count_players_main']) ? 1 : 0;
                        }
                        if ($card['count_total_side'] > 0) {
                            $card['avg_side'] = round($card['count_total_side']/$card['count_players_side'], 1);
                            if ($card['count_players_side'] >= 10 && ($stats_rules['count_players']-$card['count_players_side']) >= 10) {
                                $winrate = $this->modelMatch->getWinrateByArchetypeId(
                                    $archetype['id_archetype'],
                                    $format_cond,
                                    $rules_cond,
                                    Query::condition()
                                        ->andWhere("id_card", Query::EQUAL, $card['id_card'])
                                        ->andWhere("count_side", Query::UPPER, 0));

                                $card['winrate_side'] = $winrate['winrate'];
                                // total matches count
                                $card['total_side'] = $winrate['total'];
                                $deviation = StatsUtils::getStandardDeviation($winrate['winrate'], $winrate['total']);
                                $card['deviation_up_side'] = $card['winrate_side'] + $deviation;
                                $card['deviation_down_side'] = $card['winrate_side'] - $deviation;
                                $card['display_actions_side'] = ($stats_rules['count_players'] > $card['count_players_side']) ? 1 : 0;
                                // if we have less matches for current card than total for current rules
                                if ($stats_rules['total'] > $card['total_side'] && $card['count_players_side'] < $stats_rules['count_players']) {
                                    $card['winrate_without_side'] = round(100 * ($stats_rules['wins'] - $winrate['wins']) / ($stats_rules['total'] - $card['total_side']), 2);
                                    $deviation = StatsUtils::getStandardDeviation($card['winrate_without_side'], $stats_rules['total'] - $card['total_side']);
                                    $card['deviation_up_without_side'] = $card['winrate_without_side'] + $deviation;
                                    $card['deviation_down_without_side'] = $card['winrate_without_side'] - $deviation;
                                }
                            }
                        }
                    }
                    $this->addContent("cards", $cards);
                    $this->addContent("confidence", "0.90");
                } else {
                    $this->addMessage("No decklist found", self::MESSAGE_ERROR);
                }

            } else {
                $this->cleanCardRules();
            }
            $list_archetypes = $format ? $this->modelArchetype->allByFormat($format['id_format']) : $this->modelArchetype->all();
            $this->addContent("list_archetypes", $list_archetypes);
            $this->addContent("list_formats", $this->modelFormat->allOrdered());
        }

        protected function cleanCardRules () {
            $_SESSION['included'] = array("main" => array(), "side" => array());
            $_SESSION['excluded'] = array("main" => array(), "side" => array());
            $_SESSION['analysis'] = "";
        }

        public function lists () {
            $included = array(
                "main" => array(),
                "side" => array()
            );
            $excluded = array(
                "main" => array(),
                "side" => array()
            );
            if ($_GET['im']) {
                $_GET['im'] = explode(":", $_GET['im']);
                foreach ($_GET['im'] as $id_card) {
                    $included['main'][$id_card] = $id_card;
                }
            }
            if ($_GET['is']) {
                $_GET['is'] = explode(":", $_GET['is']);
                foreach ($_GET['is'] as $id_card) {
                    $included['side'][$id_card] = $id_card;
                }
            }
            if ($_GET['em']) {
                $_GET['em'] = explode(":", $_GET['em']);
                foreach ($_GET['em'] as $id_card) {
                    $excluded['main'][$id_card] = $id_card;
                }
            }
            if ($_GET['es']) {
                $_GET['es'] = explode(":", $_GET['es']);
                foreach ($_GET['es'] as $id_card) {
                    $excluded['side'][$id_card] = $id_card;
                }
            }
            $format_cond = Query::condition()->andWhere("id_format", Query::EQUAL, $_GET['id_format']);
            $rules_cond = $this->modelCard->getCardRuleCondition(
                $_GET['id_archetype'],
                $format_cond,
                $included,
                $excluded);
            $format_cond
                ->andWhere("id_archetype", Query::EQUAL, $_GET['id_archetype'])
                ->andCondition($rules_cond);
            $decklists = $this->modelPlayer->getDecklists($format_cond, true);
            $archetype = $this->modelArchetype->getTupleById($_GET['id_archetype'], "name_archetype, image_archetype");
            $format = $this->modelFormat->getTupleById($_GET['id_format'], "name_format");
            $this->addContent("format", $format);
            $this->addContent("archetype", $archetype);
            $this->addContent("decklists", $decklists);
        }

        protected function sortCardsByPlayerCount ($pA, $pB) {
            return $pA['count_players_main'] == $pB['count_players_main'] ?
                ($pA['name_card'] > $pB['name_card'] ? 1 : -1) :
                ($pA['count_players_main'] < $pB['count_players_main'] ? 1 : -1);
        }

        /*
         * KARSTEN algo #1
         *
         * Sort all played cards by copies and count players
         * Then add (up to 60 cards) each copies, most popular first
        */
        // work on manabase splits (ex if 10 lists play a 5-5 split on snow-covered basics
        // and some cards are played in 6 copies, the land count could be wrong in some corner cases)
        public function getAggregateList ($pIdFormat, $pIdArchetype) {
            $aggregate = array();
            $count_aggregate = 0;
            $aggregate_cond = Query::condition()
                ->andWhere("id_archetype", Query::EQUAL, $pIdArchetype)
                ->andWhere("id_format", Query::EQUAL, $pIdFormat);

            // get card cound for archetype
            $archetype_card_count = $this->modelCard->getCardCount($aggregate_cond);
            if ($archetype_card_count[0]['count_players'] < 5*$archetype_card_count[1]['count_players']) {
                trace_r("WARNING : multiple cards count in archetype");
                trace_r($archetype_card_count);
            }
            $archetype_card_count = $archetype_card_count[0]['count_cards'];

            $cards = $this->modelCard->getPlayedCardsByCopies($aggregate_cond);
            $current_card = "--";
            $current_player_count = 0;
            // add placeholder to check last card for filling copies
            $cards[] = array('name_card' => "---");
            $fill_copies = array();
            foreach ($cards as $key => $card) {
                if (!isset($card['copie_n'])) {
                    continue;
                }
                if ($current_card != $card['name_card']) {
                    $current_card = $card['name_card'];
                    $current_player_count = $card['count_players_main'];
                } else {
                    $current_player_count += $card['count_players_main'];
                    $cards[$key]['count_players_main'] = $current_player_count;
                }

                // if next card is not same name AND card was only played in N copies, manually add copies 1 to N-1 into the combined list
                if ($card['copie_n'] != 1 &&
                    isset($cards[$key+1]) &&
                    $cards[$key+1]['name_card'] != $card['name_card']
                ) {
                    for ($i = 1; $i < $card['copie_n']; $i++) {
                        $tmp_card = $card;
                        $tmp_card['copie_n'] = $i;
                        $tmp_card['count_players_main'] = $current_player_count;
                        $fill_copies[] = $tmp_card;
                    }
                }
            }
            $cards = array_merge($cards, $fill_copies);

            // sort copies by player count
            uasort($cards, array($this, "sortCardsByPlayerCount"));

            foreach ($cards as $card) {
                $aggregate[] = $card['name_card'];
                if (++$count_aggregate >= $archetype_card_count) {
                    break;
                }
            }
            sort($aggregate);
            return $aggregate;
        }

        public function aggregatelist ()
        {
            if (
                $_GET['id_format'] &&
                ($format = $this->modelFormat->getTupleById($_GET['id_format'])) &&
                $_GET['id_archetype'] &&
                ($archetype = $this->modelArchetype->getTupleById($_GET['id_archetype']))
            ) {
                $aggregate_cond = Query::condition()
                    ->andWhere("id_archetype", Query::EQUAL, $archetype['id_archetype'])
                    ->andWhere("id_format", Query::EQUAL, $format['id_format']);

                // get archetype aggregate list
                $aggregate = $this->getAggregateList($format['id_format'], $archetype['id_archetype']);
                $aggregate_counts = array_count_values($aggregate);
                $list_cards = array_unique($aggregate);

                $cards_week = array();
                $cards_data = $this->modelCard->all(
                    Query::condition()
                        ->andWhere("name_card", Query::IN, '("' . implode('","', $list_cards) . '")', false)
                        ->order(" CASE  WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                            WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10 WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                            cmc_card, color_card", ""),
                    "cards.id_card, cards.name_card, cards.mana_cost_card, cards.cmc_card, cards.type_card, cards.image_card"
                );
                foreach ($cards_data as $card) {
                    if (isset($aggregate_counts[$card['name_card']])) {
                        $card['count_main'] = $aggregate_counts[$card['name_card']];
                    } else {
                        trace_r("WARNING : " . $card['name_card'] . " - card count not found");
                    }
                    $cards_week[$card['id_card']] = $card;
                }

                if (
                    $_GET['id_format_compare'] &&
                    $_GET['id_format_compare'] != $_GET['id_format'] &&
                    ($format_compare = $this->modelFormat->getTupleById($_GET['id_format_compare']))
                ) {

                    // get average card count by id_format + id_archetype WHERE name_card IN (list_cards)
                    $cards = $this->modelCard->getPlayedCards(
                        Query::condition()
                            ->andWhere("name_card", Query::IN, '("' . implode('","', $list_cards) . '")', false),
                        $aggregate_cond
                    );
                    foreach ($cards as $card) {
                        $card['average_count_main'] = $card['count_total_main']/$card['count_players_main'];
                        $cards_week[$card['id_card']] = array_merge($cards_week[$card['id_card']], $card);
                    }

                    $compare_cond =
                        Query::condition()
                            ->andWhere("id_archetype", Query::EQUAL, $archetype['id_archetype'])
                            ->andWhere("id_format", Query::EQUAL, $format_compare['id_format']);

                    $cards_compare = $this->modelCard->getPlayedCards(
                        Query::condition()
                            ->andWhere("name_card", Query::IN, '("' . implode('","', $list_cards) . '")', false),
                        $compare_cond
                    );
                    // removed cards can't be highlighted here because we fetch old format data with cards from new format only

                    foreach ($cards_compare as $card) {
                        if (isset($cards_week[$card['id_card']]) && $card['count_players_main'] != 0) {
                            $cards_week[$card['id_card']]['average_old_count'] = $card['count_total_main']/$card['count_players_main'];
                            $cards_week[$card['id_card']]['diff_card'] = round($cards_week[$card['id_card']]['average_count_main'] - $cards_week[$card['id_card']]['average_old_count'], 1);
                        }
                    }
                    foreach ($cards_week as $key => $card) {
                        if (!isset($card['diff_card'])) {
                            $cards_week[$key]['diff_card'] = "NEW";
                            continue;
                        }
                        if ($card['diff_card'] > 0) {
                            $cards_week[$key]['diff_card'] = "+" . $card['diff_card'];
                        }
                    }
                }
                // Add decklist to content
                $decklist_by_curve = $this->modelCard->sortDecklistByCurve($cards_week);
                $this->addContent("cards_main", $decklist_by_curve);
                trace_r($decklist_by_curve);

                $this->addContent("player", array(
                    "name_archetype" => $archetype['name_archetype'],
                    "arena_id"       => "AGGREGATE DECKLIST",
                    "name_tournament" => $format['name_format'],
                    "count_cards_main" => count($aggregate)
                ));
            } else {
                $this->addMessage("Please specify a format and an archetype");
            }
            $this->setTemplate("player", "decklist");
        }
    }
}