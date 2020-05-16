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
            $this->addContent("list_archetypes", $this->modelArchetype->all());
            $this->addContent("list_formats", $this->modelFormat->all());
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
            }

            // TODO clean SESSION data when changing format / archetype

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

                    $deviation = StatsUtils::getStandardDeviation($stats['winrate'], $stats['total']);
                    $stats['deviation_up']   = $stats['winrate'] + $deviation;
                    $stats['deviation_down'] = $stats['winrate'] - $deviation;
                    $this->addContent("global", $stats);

                    $stats_rules = $this->modelMatch->getWinrateByArchetypeId(
                        $archetype['id_archetype'],
                        $format_cond,
                        $rules_cond);
                    if ($stats_rules['count_players'] > 0) {
                        $deviation = StatsUtils::getStandardDeviation($stats_rules['winrate'], $stats_rules['total']);
                        $stats_rules['deviation_up']   = $stats_rules['winrate'] + $deviation;
                        $stats_rules['deviation_down'] = $stats_rules['winrate'] - $deviation;
                    }
                    $this->addContent("global_rules", $stats_rules);

                    foreach ($cards as &$card) {
                        // TODO add winrate without this card

                        // avg_main|avg_side > 0 : to calculate only with main|side presence
                        if ($card['avg_main'] > 0) {

                            $winrate = $this->modelMatch->getWinrateByArchetypeId(
                                $archetype['id_archetype'],
                                $format_cond,
                                $rules_cond,
                                Query::condition()
                                    ->andWhere("id_card", Query::EQUAL, $card['id_card'])
                                    ->andWhere("count_main", Query::UPPER, 0));

                            $card['winrate_main'] = $winrate['winrate'];
                            $card['total_main'] = $winrate['total'];
                            $card['count_players_main'] = $winrate['count_players'];
                            // TODO add same for SB
                            // check test -- need compare count_players -- only total ?
                            if ($stats['count_players'] > $stats_rules['count_players'] && $stats_rules['total'] > $winrate['total']) {
                                $card['winrate_without_main'] = round(100 * ($stats_rules['wins'] - $winrate['wins']) / ($stats_rules['total'] - $winrate['total']), 2);
                            }
                            $deviation = StatsUtils::getStandardDeviation($winrate['winrate'], $winrate['total']);
                            $card['deviation_up_main']   = $card['winrate_main'] + $deviation;
                            $card['deviation_down_main'] = $card['winrate_main'] - $deviation;
                            $card['display_actions_main'] = ($stats_rules['count_players'] > $card['count_players_main']) ? 1 : 0;
                        }
                        if ($card['avg_side'] > 0) {
                            $winrate = $this->modelMatch->getWinrateByArchetypeId(
                                $archetype['id_archetype'],
                                $format_cond,
                                $rules_cond,
                                Query::condition()
                                    ->andWhere("id_card", Query::EQUAL, $card['id_card'])
                                    ->andWhere("count_side", Query::UPPER, 0));

                            $card['winrate_side'] = $winrate['winrate'];
                            $card['total_side'] = $winrate['total'];
                            $card['count_players_side'] = $winrate['count_players'];
                            $deviation = StatsUtils::getStandardDeviation($winrate['winrate'], $winrate['total']);
                            $card['deviation_up_side']   = $card['winrate_side'] + $deviation;
                            $card['deviation_down_side'] = $card['winrate_side'] - $deviation;
                            $card['display_actions_side'] = ($stats_rules['count_players'] > $card['count_players_side']) ? 1 : 0;
                        }
                    }
                    $this->addContent("cards", $cards);
                } else {
                    $this->addMessage("No decklist found", self::MESSAGE_ERROR);
                }

            } else {
                $this->cleanCardRules();
            }
        }

        protected function cleanCardRules () {
            $_SESSION['included'] = array("main" => array(), "side" => array());
            $_SESSION['excluded'] = array("main" => array(), "side" => array());
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