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
                            $card['count_players_main'] = $winrate['count_players'];
                            $card['avg_main'] = round($card['count_total_main']/$card['count_players_main'], 1);
                            $deviation = StatsUtils::getStandardDeviation($card['winrate_main'], $card['total_main']);
                            $card['deviation_up_main']   = $card['winrate_main'] + $deviation;
                            $card['deviation_down_main'] = $card['winrate_main'] - $deviation;
                            $card['display_actions_main'] = ($stats_rules['count_players'] > $card['count_players_main']) ? 1 : 0;
                            // if we have less matches for current card than total for current rules
                            if ($stats_rules['total'] > $card['total_main'] && $card['count_players_main'] < $stats_rules['count_players']) {
                                $card['winrate_without_main'] = round(100 * ($stats_rules['wins'] - $winrate['wins']) / ($stats_rules['total'] - $card['total_main']), 2);
                                $deviation = StatsUtils::getStandardDeviation($card['winrate_without_main'], $stats_rules['total'] - $card['total_main']);
                                $card['deviation_up_without_main']   = $card['winrate_without_main'] + $deviation;
                                $card['deviation_down_without_main'] = $card['winrate_without_main'] - $deviation;
                            }
                        }
                        if ($card['count_total_side'] > 0) {
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
                            $card['count_players_side'] = $winrate['count_players'];
                            $card['avg_side'] = round($card['count_total_side']/$card['count_players_side'], 1);
                            $deviation = StatsUtils::getStandardDeviation($winrate['winrate'], $winrate['total']);
                            $card['deviation_up_side']   = $card['winrate_side'] + $deviation;
                            $card['deviation_down_side'] = $card['winrate_side'] - $deviation;
                            $card['display_actions_side'] = ($stats_rules['count_players'] > $card['count_players_side']) ? 1 : 0;
                            // if we have less matches for current card than total for current rules
                            if ($stats_rules['total'] > $card['total_side'] && $card['count_players_side'] < $stats_rules['count_players']) {
                                $card['winrate_without_side'] = round(100 * ($stats_rules['wins'] - $winrate['wins']) / ($stats_rules['total'] - $card['total_side']), 2);
                                $deviation = StatsUtils::getStandardDeviation($card['winrate_without_side'], $stats_rules['total'] - $card['total_side']);
                                $card['deviation_up_without_side']   = $card['winrate_without_side'] + $deviation;
                                $card['deviation_down_without_side'] = $card['winrate_without_side'] - $deviation;
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
            $this->addContent("list_formats", $this->modelFormat->all());
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
            $decklists = $this->modelPlayer->getDecklists($format_cond, $_GET['id_archetype'] == ModelArchetype::ARCHETYPE_OTHER_ID);
            $this->addContent("decklists", $decklists);
        }

        // TODO WIP
        public function evaluate () {
            if(!Core::$request_async || !isset($_POST['url'])) {
                Go::to404();
            }
            // evaluate given decklist
            $bot = new MetagamerBot("Decklist");
            $name_archetype = $bot->parsePlayer(0, $_POST['url'], false, false);
            $this->addContent("archetype", $name_archetype);
            return $name_archetype;
        }
    }
}