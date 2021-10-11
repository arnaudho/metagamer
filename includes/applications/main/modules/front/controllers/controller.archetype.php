<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use app\main\src\MetagamerBot;
    use core\application\Autoload;
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
        protected $modelTournament;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatch = new ModelMatch();
            $this->modelArchetype = new ModelArchetype();
            $this->modelCard = new ModelCard();
            $this->modelFormat = new ModelFormat();
            $this->modelTournament = new ModelTournament();
        }

        /*
         * player count can be different between the header and the #lists playing a given card
         * if some players have no recorded match
         */

        // TODO allow user to specify opposing archetype
        // => add a select field, then use id_opponent_archetype in format_cond (and check for other queries which should use this condition as well)
        // ADD $q->join("players op", Query::JOIN_INNER, "matches.opponent_id_player = op.id_player AND op.id_archetype = 67");
        // auto-join on players op in ModelMatch::getWinrateByArchetypeId ?

        // 16/05/21 : we should consider the number of matches for each card instead of the number of decklsits for the threshold
        // however this information isn't retrieved in the getPlayedCards method, because joining on the macthes table
        // would duplicate the cards count ; matches count is only fetched for each card passing the cut.

        public function index () {
            $analysis_cond = Query::condition();
            $format_cond = Query::condition();
            $archetype = array();
            $format = array();
            // TODO use em/es/im/is GET parameters instead of SESSION
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
                    $cards = $this->modelCard->getPlayedCards($analysis_cond, $rules_cond, "cards.name_card", true);

                    foreach ($cards as $key => $card) {
                        $cards[$key]['mana_cost_card'] = ModelCard::formatManaCost($card['mana_cost_card']);
                    }

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
                        $stats_without_rules['winrate'] = round(100*($stats['wins'] - $stats_rules['wins'])/$stats_without_rules['total'], 1);
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
                            $threshold_card = abs($card['count_players_main']/$stats_rules['count_players']-0.5);
                            // do not display < 5% or > 95%
                            if ($threshold_card <= 0.45) {
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
                                    $card['winrate_without_main'] = round(100 * ($stats_rules['wins'] - $winrate['wins']) / ($stats_rules['total'] - $card['total_main']), 1);
                                    $deviation = StatsUtils::getStandardDeviation($card['winrate_without_main'], $stats_rules['total'] - $card['total_main']);
                                    $card['deviation_up_without_main'] = $card['winrate_without_main'] + $deviation;
                                    $card['deviation_down_without_main'] = $card['winrate_without_main'] - $deviation;
                                }
                            }
                            $card['display_actions_main'] = ($stats_rules['count_players'] > $card['count_players_main']) ? 1 : 0;
                        }
                        if ($card['count_total_side'] > 0) {
                            $card['avg_side'] = round($card['count_total_side']/$card['count_players_side'], 1);
                            $threshold_card = abs($card['count_players_side']/$stats_rules['count_players']-0.5);
                            // do not display < 5% or > 95%
                            if ($threshold_card <= 0.45) {
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
                                    $card['winrate_without_side'] = round(100 * ($stats_rules['wins'] - $winrate['wins']) / ($stats_rules['total'] - $card['total_side']), 1);
                                    $deviation = StatsUtils::getStandardDeviation($card['winrate_without_side'], $stats_rules['total'] - $card['total_side']);
                                    $card['deviation_up_without_side'] = $card['winrate_without_side'] + $deviation;
                                    $card['deviation_down_without_side'] = $card['winrate_without_side'] - $deviation;
                                }
                            }
                        }
                    }
                    $this->addContent("cards", $cards);
                    $this->addContent("confidence", "0.90");
                    Autoload::addStyle("mana/css/mana.min.css");
                    $this->setTitle($archetype['name_archetype'] . " - Archetype analysis");
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

        // export tool for aggregate decklists
        public function aggregatecards () {
            if (
                $_GET['id_archetype'] &&
                ($archetype = $this->modelArchetype->getTupleById($_GET['id_archetype'])) &&
                $_GET['id_format'] &&
                ($format = $this->modelFormat->getTupleById($_GET['id_format']))
            ) {
                $decks = Query::execute("SELECT id_player, name_card, count_main, count_side FROM player_card
                    INNER JOIN cards USING(id_card)
                    INNER JOIN players USING(id_player)
                    INNER JOIN tournaments USING(id_tournament)
                    WHERE id_format = " . $format['id_format'] .
                    " AND id_archetype = " . $archetype['id_archetype'] . "
                    ORDER BY CASE WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                            WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10 WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                            cmc_card, color_card
                    ");

                $all_cards_main = array();
                $all_cards_side = array();
                $cards_main = array();
                $cards_side = array();
                foreach ($decks as $deck) {
                    if ($deck['count_main'] > 0) {
                        $all_cards_main[$deck['name_card']] = 0;
                    }
                    if ($deck['count_side'] > 0) {
                        $all_cards_side[$deck['name_card']] = 0;
                    }
                }
                foreach ($decks as $deck) {
                    if ($deck['count_main'] > 0) {
                        if (!array_key_exists($deck['id_player'], $cards_main)) {
                            $cards_main[$deck['id_player']] = $all_cards_main;
                        }
                        $cards_main[$deck['id_player']][$deck['name_card']] = $deck['count_main'];
                    }
                    if ($deck['count_side'] > 0) {
                        if (!array_key_exists($deck['id_player'], $cards_side)) {
                            $cards_side[$deck['id_player']] = $all_cards_side;
                        }
                        $cards_side[$deck['id_player']][$deck['name_card']] = $deck['count_side'];
                    }
                }
                $this->setTemplate("archetype", "decklists");
                $this->addContent("all_cards_main", array_keys($all_cards_main));
                $this->addContent("cards_main", $cards_main);
                $this->addContent("all_cards_side", array_keys($all_cards_side));
                $this->addContent("cards_side", $cards_side);

                $this->addContent("archetype", $archetype);
                $this->addContent("format", $format);
            } else {
                $this->addMessage("Incorrect format or archetype ID");
            }
        }

        // TODO add cards mana cost
        public function list_cards () {
            $analysis_cond = Query::condition();
            $archetype = null;
            $format = null;
            $archetype_cond = null;
            if (
                $_GET['id_archetype'] &&
                ($archetype = $this->modelArchetype->getTupleById($_GET['id_archetype'])) &&
                $_GET['id_format'] &&
                ($format = $this->modelFormat->getTupleById($_GET['id_format']))
            ) {
                $analysis_cond = Query::condition()
                    ->andWhere("id_archetype", Query::EQUAL, $archetype['id_archetype'])
                    ->andWhere("id_format", Query::EQUAL, $format['id_format']);
                $archetype_cond = Query::condition()
                    ->andWhere("id_archetype", Query::EQUAL, $archetype['id_archetype']);
            }

            if ($archetype && $format) {
                $count_players = $this->modelPlayer->countPlayersByIdFormat($format['id_format'], $archetype_cond);
                $this->addContent("archetype", $archetype);
                $this->addContent("format", $format);
                $cards = $this->modelCard->getPlayedCards($analysis_cond);
                foreach ($cards as $key => $card) {
                    if ($card['count_total_main'] > 0) {
                        $cards[$key]['avg_main'] = round($card['count_total_main'] / $card['count_players_main'], 1);
                    }
                    if ($card['count_total_side'] > 0) {
                        $cards[$key]['avg_side'] = round($card['count_total_side'] / $card['count_players_side'], 1);
                    }
                }
                $this->addContent("count_players", $count_players);
                $this->addContent("cards", $cards);
                $this->addContent("link_analysis",
                    RoutingHandler::rewrite("archetype", "") . "?id_archetype=" . $archetype['id_archetype'] . "&id_format=" . $format['id_format']);
                $this->setTitle($archetype['name_archetype'] . " - Cards list");
            }
            $this->setTemplate("archetype", "list_cards");
        }

        protected function cleanCardRules () {
            $_SESSION['included'] = array("main" => array(), "side" => array());
            $_SESSION['excluded'] = array("main" => array(), "side" => array());
            $_SESSION['analysis'] = "";
        }

        public function lists () {
            if ($_GET['id_tournament'] && $tournament = $this->modelTournament->getTupleById($_GET['id_tournament'])) {
                $format_cond = Query::condition()->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament']);
            } elseif ($_GET['id_format'] && $format = $this->modelFormat->getTupleById($_GET['id_format'])) {
                $format_cond = Query::condition()->andWhere("id_format", Query::EQUAL, $format['id_format']);
            } else {
                Go::to404();
            }
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
            $this->addContent("name_format", isset($tournament) ? $tournament['name_format'] : $format['name_format']);
            if (isset($tournament)) {
                $this->addContent("name_tournament", $tournament['name_tournament']);
            }
            // quickfix data sort for record
            $aplha = range('A', 'Z');
            $aplhaz = range('Z', 'A');
            foreach ($decklists as $key => $decklist) {
                $decklists[$key]["sort_record"] = $aplha[$decklist['wins']] . $aplhaz[$decklist['total']];
            }
            $this->addContent("archetype", $archetype);
            $this->addContent("decklists", $decklists);
        }

        protected function sortCardsByCmc ($pA, $pB) {
            return $pA['cmc_card'] == $pB['cmc_card'] ?
                ($pA['name_card'] > $pB['name_card'] ? 1 : -1) :
                ($pA['cmc_card'] > $pB['cmc_card'] ? 1 : -1);
        }

        protected function sortCardsByPlayerCount ($pA, $pB) {
            if (!array_key_exists('count_players_main', $pA)) {
                return 1;
            }
            if (!array_key_exists('count_players_main', $pB)) {
                return -1;
            }
            return $pA['count_players_main'] == $pB['count_players_main'] ?
                ($pA['name_card'] > $pB['name_card'] ? 1 : -1) :
                ($pA['count_players_main'] < $pB['count_players_main'] ? 1 : -1);
        }

        protected function sortDecklistsBySingularity ($pA, $pB) {
            if (!array_key_exists('singularity', $pA)) {
                return 1;
            }
            if (!array_key_exists('singularity', $pB)) {
                return -1;
            }
            return $pA['singularity'] < $pB['singularity'] ? 1 : -1;
        }

        public function aggregatelist ()
        {
            $aggregate_cond = null;
            if (
                $_GET['id_archetype'] &&
                ($archetype = $this->modelArchetype->getTupleById($_GET['id_archetype']))
            ) {
                if ($_GET['id_tournament'] && $tournament = $this->modelTournament->getTupleById($_GET['id_tournament'])) {
                    $aggregate_cond = Query::condition()
                        ->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament'])
                        ->andWhere("id_archetype", Query::EQUAL, $archetype['id_archetype']);
                } elseif ($_GET['id_format'] && $format = $this->modelFormat->getTupleById($_GET['id_format'])) {
                    $aggregate_cond = Query::condition()
                        ->andWhere("id_format", Query::EQUAL, $format['id_format'])
                        ->andWhere("id_archetype", Query::EQUAL, $archetype['id_archetype']);
                }
            }
            if ($aggregate_cond) {
                // get archetype aggregate list
                $aggregate_main = $this->modelArchetype->getAggregateList($aggregate_cond);
                $aggregate_counts = array_count_values($aggregate_main);
                $list_cards = array_unique($aggregate_main);

                // TODO set cond instead of id format
                $aggregate_side = $this->modelArchetype->getAggregateList($aggregate_cond, false);
                $aggregate_counts_side = array_count_values($aggregate_side);
                $list_cards_side = array_unique($aggregate_side);

                $cards_week = array();
                $cards_week_side = array();
                $cards_data = $this->modelCard->all(
                    Query::condition()
                        ->andWhere("name_card", Query::IN, '("' . implode('","', array_merge($list_cards, $list_cards_side)) . '")', false)
                        ->order(" CASE  WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                            WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10 WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                            cmc_card, color_card", ""),
                    "cards.id_card, cards.name_card, cards.mana_cost_card, cards.cmc_card, cards.type_card, cards.image_card"
                );
                // TODO remove average count
                // TODO set counts for copies 1-4 instead
                // maybe comapre those counts to last format ?
                foreach ($cards_data as $card) {
                    if (isset($aggregate_counts[$card['name_card']])) {
                        $card['count_main'] = $aggregate_counts[$card['name_card']];
                        $cards_week[$card['id_card']] = $card;
                    }
                    if (isset($aggregate_counts_side[$card['name_card']])) {
                        $card['count_side'] = $aggregate_counts_side[$card['name_card']];
                        $cards_week_side[$card['id_card']] = $card;
                    }
                    // TODO check that cards are correctly found ?
                }


                // TODO remove format comparing -- move to other tool ?
                if (
                    $_GET['id_format_compare'] &&
                    $_GET['id_format_compare'] != $_GET['id_format'] &&
                    ($format_compare = $this->modelFormat->getTupleById($_GET['id_format_compare']))
                ) {
                    $this->addMessage("Aggregate comparing is deprecated");
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
                // Sort decklist -- no aggregates for limited
                $decklist_data = $this->modelCard->sortDecklistByCurve($cards_week);
                $this->addContent("cards_main", $decklist_data['curve']);

                // sort sideboard cards by curve (also resets keys to proper display)
                usort($cards_week_side, array($this, "sortCardsByCmc"));

                $this->addContent("cards_side", $cards_week_side);
                $this->addContent("aggregate", 1);
                $this->addContent("logo", 1);
                $this->addContent("maindeck_width", count($decklist_data['curve'])*165+40);

                $this->addContent("player", array(
                    "name_archetype" => $archetype['name_archetype'],
                    "arena_id"       => "AGGREGATE DECKLIST",
                    "name_tournament" => $tournament ? $tournament['name_tournament'] : $format['name_format'],
                    "count_cards_main" => count($aggregate_main),
                    "count_cards_side" => count($aggregate_side)
                ));
            } else {
                $this->addMessage("Please specify a format / tournament and an archetype ID");
            }
            $this->setTemplate("player", "decklist_visual");
        }

        public function archetypereview () {
            $aggregate_cond = null;
            $name = null;
            if (
                $_GET['id_archetype'] &&
                ($archetype = $this->modelArchetype->getTupleById($_GET['id_archetype']))
            ) {
                if ($_GET['id_tournament'] && $tournament = $this->modelTournament->getTupleById($_GET['id_tournament'])) {
                    $aggregate_cond = Query::condition()
                        ->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament'])
                        ->andWhere("id_archetype", Query::EQUAL, $archetype['id_archetype']);
                    $name = $tournament['name_tournament'];
                } elseif ($_GET['id_format'] && $format = $this->modelFormat->getTupleById($_GET['id_format'])) {
                    $aggregate_cond = Query::condition()
                        ->andWhere("id_format", Query::EQUAL, $format['id_format'])
                        ->andWhere("id_archetype", Query::EQUAL, $archetype['id_archetype']);
                    $name = $format['name_format'];
                }
            }

            if ($aggregate_cond) {
                $sum_distances = 0;
                $max_distance = 0;
                // get archetype aggregate list (maindeck only)
                $aggregate_main = $this->modelArchetype->getAggregateList($aggregate_cond);
                $aggregate_counts = array_count_values($aggregate_main);
                $archetype['count_cards'] = count($aggregate_main);

                // compare with all decklists of archetype
                $players = $this->modelPlayer->getPlayerByCond($aggregate_cond, "players.id_player, arena_id AS name_player, name_deck,
                    name_tournament, CONCAT(SUM(result_match), '-', COUNT(1)-SUM(result_match)) AS result");

                foreach ($players as $key => $player) {
                    $decklist_player = $this->modelCard->getDecklistCardsByIdPlayer($player['id_player']);
                    $diff = $this->modelArchetype->decklistDiff($aggregate_main, $decklist_player);
                    $distance = max(count($diff['added']), count($diff['removed']));
                    $sum_distances += $distance;
                    if ($distance > $max_distance) {
                        $max_distance = $distance;
                    }
                    $players[$key]['distance'] = $distance;
                    $players[$key]['singularity'] = round(100*$distance/$archetype['count_cards'], 0);
                    // rework diff here
                    $players[$key]['diff'] = array(
                        "removed" => array(),
                        "added" => array()
                    );
                    foreach (array_count_values($diff['added']) as $item => $count) {
                        $players[$key]['diff']['added'][] = "$count $item";
                    }
                    foreach (array_count_values($diff['removed']) as $item => $count) {
                        $players[$key]['diff']['removed'][] = "$count $item";
                    }
                }
                // get average archetype radius
                $average_distance = round($sum_distances / count($players), 2);
                if ($average_distance == 0) {
                    $players = array();
                }

                foreach ($players as $key => $player) {
                    if ($player['distance'] < 12 && $player['distance'] < $average_distance*2) {
                        unset($players[$key]);
                    }
                }

                usort($players, array($this, "sortDecklistsBySingularity"));
                $this->addContent("aggregate_decklist", $aggregate_counts);
                $this->addContent("archetype", $archetype);
                $this->addContent("name_format", $name);
                $this->addContent("players", $players);
                $this->addContent("average_distance", $average_distance);
                $this->setTitle($archetype['name_archetype'] . " - Archetype review");
            }
            $this->setTemplate("archetype", "review");
        }
    }
}