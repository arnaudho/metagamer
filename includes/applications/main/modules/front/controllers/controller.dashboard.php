<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\Autoload;
    use core\application\Core;
    use core\application\DefaultFrontController;
    use core\application\Go;
    use core\application\Header;
    use core\application\routing\RoutingHandler;
    use core\db\Query;
    use core\utils\StatsUtils;
    use lib\core\tools\Http;

    class dashboard extends DefaultFrontController
    {
        CONST DEFAULT_ARCHETYPES_COUNT = 15;

        protected $modelPlayer;
        protected $modelMatches;
        protected $modelArchetype;
        protected $modelTournament;
        protected $modelFormat;
        protected $modelCard;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatches = new ModelMatch();
            $this->modelArchetype = new ModelArchetype();
            $this->modelTournament = new ModelTournament();
            $this->modelFormat = new ModelFormat();
            $this->modelCard = new ModelCard();
        }

        public function index () {
            $format = array();
            $tournament = array();
            $dashboard_cond = null;
            $this->addContent("list_formats", $this->modelFormat->allOrdered());

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
                $count_duplicates = $this->modelPlayer->countDuplicatePlayers($dashboard_cond);

                $count_wainting = $this->modelPlayer->countPlayersWithoutDecklist($dashboard_cond);
                if ($count_wainting > 0) {
                    $this->addMessage("$count_wainting players without decklist - <a href='tournament/import/#mtgmelee_decklists_old'>Go to import</a>", self::MESSAGE_ERROR);
                }

                if ($count_duplicates != 0) {
                    $this->addMessage("$count_duplicates duplicates decklists found", self::MESSAGE_ERROR);
                    $this->addContent("clean_duplicates", 1);
                }

                $list_tournaments = $this->modelTournament->all(
                    Query::condition()
                        ->andWhere("id_format", Query::EQUAL, $format['id_format']),
                    "id_tournament, name_tournament, date_tournament"
                );

                $this->addContent("list_tournaments", $list_tournaments);
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
                if (empty($metagame)) {
                    $this->addMessage("No metagame data for selected format", self::MESSAGE_ERROR);
                }

                // limit matrix size by default
                if (!$_SESSION['archetypes']) {
                    $count = 0;
                    foreach ($metagame as $archetype) {
                        $_SESSION['archetypes'][$archetype['id_archetype']] = $archetype['id_archetype'];
                        if (++$count >= self::DEFAULT_ARCHETYPES_COUNT) {
                            break;
                        }
                    }
                    $_SESSION['archetypes'][ModelArchetype::ARCHETYPE_OTHER_ID] = ModelArchetype::ARCHETYPE_OTHER_ID;
                }
                if (isset($_SESSION['archetypes']) && !empty($_SESSION['archetypes'])) {
                    // filter archetypes
                    foreach ($metagame as $key => $archetype) {
                        if (array_key_exists($archetype['id_archetype'], $_SESSION['archetypes'])) {
                            $metagame[$key]['checked'] = 1;
                        }
                    }
                }

                $param = $tournament ? "?id_tournament=" . $tournament['id_tournament'] : "?id_format=" . $format['id_format'];
                $link_metagame = RoutingHandler::rewrite("tournament", "metagame") . $param;
                $link_matrix = RoutingHandler::rewrite("dashboard", "matrix") . $param;
                $this->addContent("link_condition", $param);
                $this->addContent("link_metagame", $link_metagame);
                $this->addContent("link_matrix", $link_matrix);

                $this->addContent("metagame", $metagame);
            } else {
                $this->setTitle("Dashboard");
            }
        }

        public function matrix () {
            $dashboard_cond = null;
            $metagame = array();
            if (
                isset($_GET['id_tournament']) &&
                $tournament = $this->modelTournament->getTupleById(
                    $_GET['id_tournament'],
                    "tournaments.*, DATE_FORMAT(date_tournament, '%d %b %Y') AS date_tournament"
                )
            ) {
                $dashboard_cond = Query::condition()
                    ->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament']);
                $metagame = $this->modelPlayer->countArchetypes($dashboard_cond);

                $title = $tournament['name_tournament'];
                $date = $tournament['date_tournament'];
            } elseif (
                isset($_GET['id_format']) &&
                $format = $this->modelFormat->getTupleById(
                    $_GET['id_format']
                )
            ) {
                $dashboard_cond = Query::condition()
                    ->andWhere("tournaments.id_format", Query::EQUAL, $format['id_format']);
                $metagame = $this->modelPlayer->countArchetypes($dashboard_cond);
                $title = $format['name_format'];

                $dates = $this->modelTournament->allWithFormat($dashboard_cond, "MIN(date_tournament) AS min_date, MAX(date_tournament) AS max_date");
                $dates = $dates[0];
                $dates_time = array_map('strtotime', $dates);
                if (date('j', $dates_time['min_date']) == date('j', $dates_time['max_date'])) {
                    $date = date('j M Y', $dates_time['max_date']);
                } else {
                    if (date('M', $dates_time['min_date']) == date('M', $dates_time['max_date'])) {
                        $date = date('j', $dates_time['min_date']) . "-" . date('j M Y', $dates_time['max_date']);
                    } else {
                        $date = date('j M', $dates_time['min_date']) . " - " . date('j M Y', $dates_time['max_date']);
                    }
                }
            }
            if (is_null($dashboard_cond)) {
                Go::to404();
            }

            // check if duplicate players or null archetypes
            $count_duplicates = $this->modelPlayer->countDuplicatePlayers($dashboard_cond);
            $count_wainting = $this->modelPlayer->countPlayersWithoutDecklist($dashboard_cond);
            if ($count_wainting > 0) {
                $this->addMessage("$count_wainting players without decklist - <a href='tournament/import/#mtgmelee_decklists_old'>Go to import</a>", self::MESSAGE_ERROR);
            }
            if ($count_duplicates != 0) {
                $id_format = isset($format) ? $format['id_format'] : $tournament['id_format'];
                $this->addMessage("$count_duplicates duplicates decklists found - <a href='dashboard/?id_format=$id_format'>Go to dashboard</a>", self::MESSAGE_ERROR);
            }
            if (isset($format)) {
                $list_tournaments = $this->modelTournament->all(
                    Query::condition()
                        ->andWhere("id_format", Query::EQUAL, $format['id_format']),
                    "id_tournament, name_tournament, date_tournament"
                );
                $this->addContent("list_tournaments", $list_tournaments);
            }

            // handle tier1 archetypes selection
            if (isset($_POST['archetypes-select'])) {
                $_POST['archetypes-select'][] = ModelArchetype::ARCHETYPE_OTHER_ID;
                $_SESSION['archetypes'] = array();
                foreach ($_POST['archetypes-select'] as $id_archetype) {
                    $_SESSION['archetypes'][$id_archetype] = $id_archetype;
                }
            }

            $meta_with_keys = array();
            foreach ($metagame as $deck) {
                $meta_with_keys[$deck['id_archetype']] = $deck;
            }
            $metagame = $meta_with_keys;

            $archetypes = array();
            $other_archetypes = array();
            $count_other = 0;
            $count_players = $this->modelPlayer->countPlayers($dashboard_cond);

            // limit matrix size by default
            if (!isset($_SESSION['archetypes'])) {
                $count = 0;
                foreach ($metagame as $archetype) {
                    $_SESSION['archetypes'][$archetype['id_archetype']] = $archetype['id_archetype'];
                    if (++$count >= self::DEFAULT_ARCHETYPES_COUNT) {
                        break;
                    }
                }
                if (!array_key_exists(ModelArchetype::ARCHETYPE_OTHER_ID, $_SESSION['archetypes'])) {
                    $_SESSION['archetypes'][ModelArchetype::ARCHETYPE_OTHER_ID] = ModelArchetype::ARCHETYPE_OTHER_ID;
                }
            }

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
                $other_id = null;
                foreach ($archetypes as $key => $archetype) {
                    if ($archetype['id_archetype'] == ModelArchetype::ARCHETYPE_OTHER_ID) {
                        $other_id = $key;
                        break;
                    }
                }
                if (is_null($other_id)) {
                    // TODO fetch 'Other' for current id_type_format
                    $other = $this->modelArchetype->getTupleById(ModelArchetype::ARCHETYPE_OTHER_ID);
                    $other_id = -1;
                    $archetypes[$other_id] = $other;
                }

                $archetypes[$other_id]['count'] += $count_other;
                $archetypes[$other_id]['percent'] = round(100 * $archetypes[$other_id]['count'] / $count_players, 1);
            }

            if (empty($archetypes)) {
                // no archetypes selected : get full metagame
                $archetypes = $metagame;
            }

            // set all decks in metagame in order condition
            // (because it is also used to filter matches in winrate -- we can change that if we pass another array to getFullWinrateByArchetypeId)
            $order_archetypes = array_keys($metagame, true);

            foreach ($archetypes as $key => $archetype) {
                $winrate = $this->modelMatches->getFullWinrateByArchetypeId($archetype['id_archetype'], $dashboard_cond, $order_archetypes, $other_archetypes);
                foreach ($winrate as $m => $matchup) {
                    // divide mirror count
                    if ($matchup['id_archetype'] == $archetype['id_archetype']) {
                        $winrate[$m]['count'] = ceil($matchup['count'] / 2);
                    }
                    if (isset($matchup['percent']) && isset($matchup['count']) && $matchup['count'] != 0) {
                        // League Weekend : count x2
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
                    } else {
                        $winrate[$m]['deviation_down'] = 0;
                        $winrate[$m]['deviation_up'] = 100;
                    }
                }
                $archetypes[$key]['winrates'] = $winrate;

                // fix name display
                $words = str_word_count($archetype['name_archetype'], 1);
                if (count($words) == 2) {
                    $archetypes[$key]['name_archetype'] = $words[0] . ' <br />' . $words[1];
                }
            }
            /*
                            // TODO POC tier1 domination
                            // cf. ModelMatch getFullWinrateByArchetypeId

                            $ids_metashare = array();
                            foreach ($archetypes as $key => $archetype) {
                                $ids_metashare[$archetype['id_archetype']] = $archetype['count']/$data['count_players'];
                            }
                            trace_r($ids_metashare);
                            foreach ($archetypes as $key => $archetype) {
                                // COEFF 1 : winrate total deck x %meta deck
            //                    $coeff1 = $archetype['count']*$archetype['winrates'][0]['wins']/$archetype['winrates'][0]['count'];

                                // COEFF 2 : winrate vs decksA-N x %meta deckA-N
                                // represents the expected winrate of the deck in the current metagame
                                // SHOULD COUNT mirror matches
                                $coeff2 = 0;
                                $coeff3 = 0;
                                foreach ($archetype['winrates'] as $winrate) {
                                    // exclude mirror : if winrate/id_archetype != archetype/id_archetype
                                    if ($winrate['id_archetype'] != 0) {
                                        $coeff2 += 100*$ids_metashare[$winrate['id_archetype']]*$winrate['wins']/$winrate['count'];
                                        $coeff3 += 10000*$ids_metashare[$winrate['id_archetype']]*$winrate['wins']/$winrate['count']/($archetype['count']/$ids_metashare[$archetype['id_archetype']]);
                                    }
                                }
                                trace_r($archetype['id_archetype'] . " -- " . $archetype['name_archetype']);
                                trace_r($coeff2);
                                trace_r($coeff3);

                                // TODO added matrix column 3
                                array_unshift($archetypes[$key]['winrates'], array(
                                    "name_archetype" => "total_theorical",
                                    "wins"           => 50,
                                    "count"          => 100,
                                    "percent"        => round($coeff2, 1),
                                    "id_archetype"   => 0
                                ));
                            }*/

            $param = $tournament ? "?id_format=" . $tournament['id_format'] . "&id_tournament=" . $tournament['id_tournament'] : "?id_format=" . $format['id_format'];
            $link_dashboard = RoutingHandler::rewrite("dashboard", "") . $param;
            $this->addContent("link_dashboard", $link_dashboard);
            $this->addContent("other_archetypes", $other_archetypes);
            $this->addContent("archetypes", $archetypes);
            $this->addContent("title", $title);
            $this->addContent("date", $date);
            $this->addContent("confidence", "0.95");
            $this->addContent("count_matches", $this->modelMatches->countMatches($dashboard_cond) / 2);
            $this->addContent("count_players", $count_players);
            $this->setTitle("$title - Winrate matrix");
        }

        public function leaderboard () {
            Autoload::addStyle("flags/css/flag-icon.min.css");
            $labels = $this->modelTournament->getProTournamentLabels();
            $this->addContent("tournament_labels", $labels);
            if (isset($_GET['detailed']) && $_GET['detailed'] == 1) {
                $mpl = $this->modelPlayer->getLeaderboard(ModelPlayer::TAG_MPL);
                $rivals = $this->modelPlayer->getLeaderboard(ModelPlayer::TAG_RIVALS);
                $this->addContent("detailed", true);
            } else {
                $mpl = $this->modelPlayer->getLeaderboard(ModelPlayer::TAG_MPL, false);
                $rivals = $this->modelPlayer->getLeaderboard(ModelPlayer::TAG_RIVALS, false);
            }
            if (isset($_GET['save']) && $_GET['save'] == 1) {
                foreach ($mpl as $player) {
                    Query::execute("UPDATE player_tag SET rank_player = " . $player['rank_player'] . " WHERE id_people = " . $player['id_people']);
                }
                foreach ($rivals as $player) {
                    Query::execute("UPDATE player_tag SET rank_player = " . $player['rank_player'] . " WHERE id_people = " . $player['id_people']);
                }
            }
            foreach ($mpl as &$player) {
                $player['name_player'] = ucwords($player['name_player'], " -\t\r\n\f\v");
                $player['rank_diff_player'] = $player['old_rank_player'] == 0 ? 0 : $player['old_rank_player'] - $player['rank_player'];
            }
            foreach ($rivals as &$player) {
                $player['name_player'] = ucwords($player['name_player'], " -\t\r\n\f\v");
                $player['rank_diff_player'] = $player['old_rank_player'] == 0 ? 0 : $player['old_rank_player'] - $player['rank_player'];
            }
            $this->addContent("img_path", Core::$path_to_components . '/metagamer/imgs/');
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
                            $archetypes = $this->modelArchetype->allByFormat($_POST['id_format']);
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
            $formats = array();
            foreach (ModelFormat::MAPPING_TYPE_FORMAT as $idTypeFormat => $typeFormat) {
                $archetypes = ModelArchetype::getArchetypesRules($idTypeFormat);
                $archetype_cards = array();
                // update archetypes images
                foreach ($archetypes as $archetype_name => $archetype) {
                    if (isset($archetype['image']) && $card = $this->modelCard->one(Query::condition()->andWhere("name_card", Query::LIKE, $archetype['image'] . "%"), "image_card")) {
                        // get archetype by name
                        $arch = $this->modelArchetype->one(Query::condition()->andWhere("name_archetype", Query::EQUAL, $archetype_name));
                        if ($arch) {
                            $this->modelArchetype->updateById(
                                $arch['id_archetype'],
                                array(
                                    "image_archetype" => $card['image_card']
                                )
                            );
                        } else {
                            $this->modelArchetype->insert(
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
                    $this->addMessage($message, self::MESSAGE_ERROR);
                }
                $formats[$idTypeFormat] = array(
                    "name_format" => ucfirst($typeFormat),
                    "archetypes"  => $archetypes
                );
            }

            $this->addContent("formats", $formats);
        }
    }
}