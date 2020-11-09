<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\Core;
    use core\application\DefaultFrontController;
    use core\application\Go;
    use core\application\Header;
    use core\db\Query;
    use core\utils\StatsUtils;
    use lib\core\tools\Http;

    class dashboard extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelMatches;
        protected $modelArchetypes;
        protected $modelTournament;
        protected $modelFormat;
        protected $modelCard;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatches = new ModelMatch();
            $this->modelArchetypes = new ModelArchetype();
            $this->modelTournament = new ModelTournament();
            $this->modelFormat = new ModelFormat();
            $this->modelCard = new ModelCard();
        }

        public function index () {
            $this->addContent("list_formats", $this->modelFormat->allOrdered());

            // handle tier1 archetypes selection
            if (isset($_POST['archetypes-select'])) {
                $_POST['archetypes-select'][] = ModelArchetype::ARCHETYPE_OTHER_ID;
                $_SESSION['archetypes'] = array();
                foreach ($_POST['archetypes-select'] as $id_archetype) {
                    $_SESSION['archetypes'][$id_archetype] = $id_archetype;
                }
            }

            $format = array();
            $tournament = array();
            $dashboard_cond = null;
            if ($_GET['id_format'] && $format = $this->modelFormat->getTupleById($_GET['id_format'])) {
                $dashboard_cond = Query::condition()->andWhere("id_format", Query::EQUAL, $format['id_format']);

                if (isset($_POST['duplicates'])) {
                    $cleaned_duplicates = $this->modelPlayer->cleanDuplicatePlayers($dashboard_cond);
                    trace_r("Clean duplicate decklists : $cleaned_duplicates");
                }
            }
            if ($format &&
                $_GET['id_tournament'] &&
                ($tournament = $this->modelTournament->one(
                    Query::condition()
                        ->andWhere("id_tournament", Query::EQUAL, $_GET['id_tournament'])
                        ->andWhere("id_format", Query::EQUAL, $format['id_format'])
                ))) {
                $dashboard_cond = Query::condition()->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament']);
            }
            if ($format && $dashboard_cond) {
                // check if duplicate players
                $count_duplicates = $this->modelPlayer->countDuplicatePlayers(
                    Query::condition()
                        ->andWhere("tournaments.id_format", Query::EQUAL, $format['id_format'])
                );

                if ($count_duplicates != 0) {
                    $this->addMessage("$count_duplicates duplicates decklists found", self::MESSAGE_ERROR);
                    $this->addContent("clean_duplicates", 1);
                }

                $this->addContent("list_tournaments", $this->modelTournament->all(
                    Query::condition()
                        ->andWhere("id_format", Query::EQUAL, $format['id_format'])
                ));
                $title = "Dashboard - " . $format['name_format'];
                if ($tournament) {
                    $title .= " - " . $tournament['name_tournament'];
                    $this->addContent("tournament", $tournament);
                }
                $this->setTitle($title);
                $this->addContent("format", $format);
                $data = array(
                    "count_tournaments" => $this->modelTournament->count($dashboard_cond),
                    "count_players" => $this->modelPlayer->countPlayers($dashboard_cond),
                    "count_matches" => $this->modelMatches->countMatches($dashboard_cond) / 2,
                    "count_wins" => $this->modelMatches->countWins($dashboard_cond) / 2
                );
                $data["percent"] = round(100 * $data['count_wins'] / $data['count_matches'], 2);
                $this->addContent("data", $data);

                $metagame = $this->modelPlayer->countArchetypes($dashboard_cond);
                $this->addContent("metagame", $metagame);
                if (empty($metagame)) {
                    $this->addMessage("No metagame data for selected format", self::MESSAGE_ERROR);
                }

                $archetypes = array();
                $other_archetypes = array();
                $order_archetypes = array();
                foreach ($metagame as $deck) {
                    $order_archetypes[] = $deck['id_archetype'];
                }

                $count_other = 0;
                if (isset($_SESSION['archetypes']) && !empty($_SESSION['archetypes'])) {
                    // filter archetypes
                    foreach ($metagame as $archetype) {
                        if (array_key_exists($archetype['id_archetype'], $_SESSION['archetypes'])) {
                            $archetypes[] = $archetype;
                        } else {
                            $count_other += $archetype['count'];
                            $other_archetypes[$archetype['id_archetype']] = $archetype['id_archetype'];
                        }
                    }
                }
                // add correct count to 'Other' archetype
                if ($count_other > 0) {
                    foreach ($archetypes as $key => $archetype) {
                        if ($archetype['id_archetype'] == ModelArchetype::ARCHETYPE_OTHER_ID) {
                            $archetypes[$key]['count'] += $count_other;
                            $archetypes[$key]['percent'] = round(100 * $archetypes[$key]['count'] / $data['count_players'], 1);
                        }
                    }
                }
                $this->addContent("other_archetypes", $other_archetypes);
                if (empty($archetypes)) {
                    $archetypes = $metagame;
                }

                foreach ($archetypes as $key => $archetype) {
                    $winrate = $this->modelMatches->getFullWinrateByArchetypeId($archetype['id_archetype'], $dashboard_cond, $order_archetypes, $other_archetypes);
                    foreach ($winrate as $m => $matchup) {
                        // divide mirror count
                        if ($matchup['id_archetype'] == $archetype['id_archetype']) {
                            $winrate[$m]['count'] = ceil($matchup['count'] / 2);
                        }
                        $deviation = StatsUtils::getStandardDeviation($matchup['percent'], $matchup['count'], StatsUtils::Z95);
                        $winrate[$m]['deviation'] = $deviation;
                        $winrate[$m]['deviation_up'] = round($matchup['percent'] + $deviation);
                        if ($winrate[$m]['deviation_up'] > 100) {
                            $winrate[$m]['deviation_up'] = 100;
                        }
                        $winrate[$m]['deviation_down'] = round($matchup['percent'] - $deviation);
                        if ($winrate[$m]['deviation_down'] < 0) {
                            $winrate[$m]['deviation_down'] = 0;
                        }
                    }
                    $archetypes[$key]['winrates'] = $winrate;
                }
                $this->addContent("archetypes", $archetypes);
                $this->addContent("confidence", "0.95");
            } else {
                $this->setTitle("Dashboard");
            }
        }

        public function leaderboard () {
            $mpl = $this->modelPlayer->getLeaderboard(ModelPlayer::TAG_MPL);
            $rivals = $this->modelPlayer->getLeaderboard(ModelPlayer::TAG_RIVALS);
            foreach ($mpl as &$player) {
                $player['name_player'] = mb_strtoupper($player['name_player']);
            }
            foreach ($rivals as &$player) {
                $player['name_player'] = mb_strtoupper($player['name_player']);
            }
            $this->addContent("mpl", $mpl);
            $this->addContent("rivals", $rivals);
        }

        public function data () {
            if (!Core::$request_async) {
                Go::to404();
            }

            $http_version = Http::V_1_1;
            $http_status = Http::CODE_200;

            if (!isset($_POST['action']) || empty($_POST['action']) ) {
                $http_status = Http::CODE_404;
                $this->addContent('error', "Missing parameter : action");
            } else {

                $action = $_POST['action'];

                switch($action) {
                    case 'get_archetypes_by_format':
                        if (isset($_POST['id_format']) && !empty($_POST['id_format'])) {
                            $archetypes = $this->modelArchetypes->allByFormat($_POST['id_format']);
                            $this->addContent("archetypes", $archetypes);
                        } else {
                            $http_status = Http::CODE_404;
                            $this->addContent('error', "Missing parameter : id_format");
                        }
                        break;
                    default:
                        $http_status = Http::CODE_404;
                        $this->addContent('error', "Invalid action specified");
                        break;
                }
            }


            Header::http("$http_version $http_status");
            Header::status("$http_status");
        }

        public function archetypes () {
            // update archetypes images
            $archetypes = $this->modelArchetypes->getArchetypesRules();
            $archetype_cards = array();
            foreach ($archetypes as $archetype_name => $archetype) {
                if (isset($archetype['image']) && $card = $this->modelCard->one(Query::condition()->andWhere("name_card", Query::LIKE, $archetype['image'] . "%"), "image_card")) {
                    // get archetype by name
                    $arch = $this->modelArchetypes->one(Query::condition()->andWhere("name_archetype", Query::EQUAL, $archetype_name));
                    if ($arch) {
                        $this->modelArchetypes->updateById(
                            $arch['id_archetype'],
                            array(
                                "image_archetype" => $card['image_card']
                            )
                        );
                    } else {
                        $this->modelArchetypes->insert(
                            array(
                                "name_archetype" => $archetype_name,
                                "image_archetype" => $card['image_card']
                            )
                        );
                    }
                    $archetypes[$archetype_name]['image_card'] = $card['image_card'];
                } else {
                    trace_r("ERROR : image not found for archetype $archetype_name");
                }

                $archetype_cards = array_merge($archetype_cards, $archetype['contains']);
                if (isset($archetype['exclude'])) {
                    $archetype_cards = array_merge($archetype_cards, $archetype['exclude']);
                }
            }

            // check card names
            $archetype_cards = array_unique($archetype_cards);
            $subquery = '(SELECT "' . implode('" AS name_card UNION ALL SELECT "', $archetype_cards) . '") c1';
            $not_found = Query::execute("SELECT name_card FROM (SELECT c1.name_card, coalesce(cards.id_card, 'NOT FOUND') AS found FROM " . $subquery . " LEFT JOIN cards ON cards.name_card = c1.name_card) tmp WHERE found = 'NOT FOUND'");
            if ($not_found) {
                foreach ($not_found as $key => $card) {
                    if ($this->modelCard->count(Query::condition()->andWhere('name_card', Query::LIKE, $card['name_card'] . '%'))) {
                        unset($not_found[$key]);
                    }
                }
            }
            if ($not_found) {
                $message = "Cards not found : <ul>";
                foreach ($not_found as $card) {
                    $message .= "<li>" . $card['name_card'] . "</li>";
                }
                $message .= "</ul>";
                $this->addMessage($message);
            }

            $this->addContent("archetypes", $archetypes);

            /*
             * // lists do not display archetye name anymore
            $this->addContent("list_formats", $this->modelFormat->all());
            $format = $this->modelFormat->getTupleById($_GET['id_format']);
            if ($format) {
                $this->setTitle("Dashboard - " . $format['name_format']);
                $this->addContent("format", $format);
                $archetypes = $this->modelArchetypes->getArchetypesGroupsByFormat($format['id_format']);
                $this->addContent("archetypes", $archetypes);
            }
*/
        }
    }
}