<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use app\main\src\BattlefyBot;
    use app\main\src\MagicGGBot;
    use app\main\src\MetagamerBot;
    use app\main\src\MtgMeleeBot;
    use core\application\DefaultFrontController;
    use core\data\SimpleJSON;
    use core\db\Query;

    class tournament extends DefaultFrontController
    {
        protected $modelTournament;
        protected $modelArchetype;
        protected $modelPlayer;
        protected $modelMatch;
        protected $modelFormat;

        public function __construct()
        {
            parent::__construct();
            $this->modelTournament = new ModelTournament();
            $this->modelArchetype = new ModelArchetype();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatch = new ModelMatch();
            $this->modelFormat = new ModelFormat();
        }

        public function import () {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                if ($_POST['import-cfb'] && $_POST['import-cfb']['url'] && $_POST['import-cfb']['id_format']) {
                    $post_data = $_POST['import-cfb'];
                    if (preg_match('/cfbevents/', $post_data['url'], $output_array)) {
                        $bot = new MetagamerBot("Roe (CFB events tournament parser)");
                    } else {
                        $this->addMessage("Unknown tournament source : " . $post_data['url'], self::MESSAGE_ERROR);
                    }
                    if (isset($bot)) {
                        $result = $bot->parseDecklists($post_data['url'], $post_data['id_format']);
                        if ($result) {
                            $id_tournament = $bot->tournament;
                            $data = $this->modelTournament->getTournamentData($id_tournament);
                            $data['id_tournament'] = $id_tournament;
                            $this->addContent("data", $data);
                        }
                    }
                } elseif ($_POST['import-magicgg']['url'] && $_POST['import-magicgg']['id_format']) {
                    $post_data = $_POST['import-magicgg'];
                    if (preg_match('/magic.gg/', $post_data['url'], $output_array)) {
                        $bot = new MagicGGBot("Scott (Magic.gg events tournament parser)");
                    } else {
                        $this->addMessage("Unknown tournament source : " . $post_data['url'], self::MESSAGE_ERROR);
                    }
                    if (isset($bot)) {
                        $result = $bot->parseTournament($post_data['url'], $post_data['id_format']);
                        if ($result) {
                            $id_tournament = $bot->tournament;
                            $data = $this->modelTournament->getTournamentData($id_tournament);
                            $data['id_tournament'] = $id_tournament;
                            $this->addContent("data", $data);
                        }
                    }
                } elseif ($_POST['import-mtgmelee'] && $_POST['import-mtgmelee']['data']) {
                    $bot = new MtgMeleeBot("Brad (MTG Melee tournament parser)");
                    $data = SimpleJSON::decode($_POST['import-mtgmelee']['data']);
                    if ($data) {
                        $result = $bot->parseRound($data, $_POST['import-mtgmelee']['id_format'], $_POST['import-mtgmelee']['tournament_name'], $_POST['import-mtgmelee']['tournament_date']);
                        if ($result) {
                            $id_tournament = $bot->tournament;
                            $data = $this->modelTournament->getTournamentData($id_tournament);
                            $data['id_tournament'] = $id_tournament;
                            $this->addContent("data", $data);
                        }
                    } else {
                        $this->addMessage("Empty or badly formatted data", self::MESSAGE_ERROR);
                    }
                } elseif ($_POST['import-mtgmelee-decklists'] && $_POST['import-mtgmelee-decklists']['data-raw']) {
                    $data = SimpleJSON::decode($_POST['import-mtgmelee-decklists']['data-raw']);
                    $decklists = array();
                    $decklists_data = array();
                    $count_players = 0;
                    foreach ($data as $decklist) {
                        if (isset($decklist['ID']) && isset($decklist['Deck'])) {
                            $decklists[] = "https://mtgmelee.com/Decklist/View/" . $decklist['ID'];
                            $decklists_data["https://mtgmelee.com/Decklist/View/" . $decklist['ID']] = $decklist['Deck'];
                        }
                    }
                    $players = $this->modelPlayer->all(Query::condition()
                        ->andWhere("decklist_player", Query::IN, "('" . implode("', '", $decklists) . "')", false),
                        "players.id_player, decklist_player"
                    );
                    $bot = new MtgMeleeBot("Parse decklists");
                    foreach ($players as $player) {
                        if (array_key_exists($player['decklist_player'], $decklists_data)) {
                            $bot->parseDecklist($player['id_player'], $decklists_data[$player['decklist_player']]);
                        }
                        $count_players++;
                    }
                    $this->addMessage("$count_players decklists imported", self::MESSAGE_INFO);
                } elseif ($_POST['import-mtgmelee-decklists'] && $_POST['import-mtgmelee-decklists']['count']) {
                    $count = $_POST['import-mtgmelee-decklists']['count'] > 100 ? 100 : intval($_POST['import-mtgmelee-decklists']['count']);
                    $players = $this->modelPlayer->all(Query::condition()
                        ->andWhere("id_archetype", Query::IS, "NULL", false)
                        // parse specific decklist here
                        ->limit(0, $count),
                        "id_player"
                    );
                    if (count($players) > 0) {
                        $bot = new MtgMeleeBot("Parse decklists with crawler");
                        foreach ($players as $player) {
                            $bot->parseDecklist($player['id_player']);
                            // wait 2 seconds to prevent HTTP 429
                            sleep(2);
                        }
                    }
                } elseif ($_POST['import-battlefy'] && $_POST['import-battlefy']['data']) {
                    $bot = new BattlefyBot("Carlos (Battlefy tournament parser)");
                    $data = SimpleJSON::decode($_POST['import-battlefy']['data']);
                    if ($data) {
                        // Quickfix add LATAM Challenge ID
                        $data[0]['TournamentId'] = 4089;

                        $result = $bot->parseRound($data, $_POST['import-battlefy']['id_format'], $_POST['import-battlefy']['tournament_name'], $_POST['import-battlefy']['tournament_date']);
                        if ($result) {
                            $id_tournament = $bot->tournament;
                            $data = $this->modelTournament->getTournamentData($id_tournament);
                            $data['id_tournament'] = $id_tournament;
                            $this->addContent("data", $data);
                        }
                    } else {
                        $this->addMessage("Empty or badly formatted data", self::MESSAGE_ERROR);
                    }
                } elseif ($_POST['import-battlefy-decklists'] && $_POST['import-battlefy-decklists']['data-raw']) {
                    $data = SimpleJSON::decode($_POST['import-battlefy-decklists']['data-raw']);
                    $decklists_data = array();
                    $count_players = 0;
                    foreach ($data as $decklist) {
                        if (isset($decklist['name_player']) && array_key_exists('raw_decklist', $decklist)) {
                            $decklists_data[$decklist['name_player']] = $decklist;
                        }
                    }
                    $players = $this->modelPlayer->all(Query::condition()
                        ->andWhere("decklist_player", Query::IN, "('" . implode("', '", array_keys($decklists_data)) . "')", false),
                        "players.id_player, decklist_player"
                    );
                    $bot = new BattlefyBot("Parse decklists");

                    foreach ($players as $player) {
                        if (array_key_exists($player['decklist_player'], $decklists_data)) {
                            $bot->parseDecklist(
                                $player['id_player'],
                                $decklists_data[$player['decklist_player']]['raw_decklist'],
                                $decklists_data[$player['decklist_player']]['uri_decklist'],
                                $decklists_data[$player['decklist_player']]['name_archetype'],
                                $decklists_data[$player['decklist_player']]['username_player']
                            );
                        }
                        $count_players++;
                    }
                    $this->addMessage("$count_players decklists imported", self::MESSAGE_INFO);
                } else {
                    $this->addMessage("Missing data for import", self::MESSAGE_ERROR);
                }
            }
            if (isset($bot)) {
                foreach ($bot->messages as $msg) {
                    $this->addMessage($msg['message'], $msg['type']);
                }
            }
            $this->setTitle("Import tournament");
            $this->addContent("list_formats", $this->modelFormat->allOrdered());
            $this->addContent("count_waiting", $this->modelPlayer->countPlayersWithoutDecklist());
        }

        // TODO get archetype OTHER by default
        // TODO accept id_format as well for dashboard metagame
        public function metagame () {
            if (
                !isset($_GET['id_tournament']) ||
                !$tournament = $this->modelTournament->getTupleById(
                    $_GET['id_tournament'],
                    "tournaments.*, DATE_FORMAT(date_tournament, '%d %b %Y') AS date_tournament"
                )
            ) {
                $this->addContent("error", "Tournament not found");
            } else {
                $metagame_cond = Query::condition()
                    ->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament']);
                $metagame = $this->modelPlayer->countArchetypes($metagame_cond);

                $condensed_metagame = $this->round_metagame($metagame);
                $this->addContent("metagame", $condensed_metagame);
                $this->addContent("title", $tournament['name_tournament']);
                $this->addContent("date", $tournament['date_tournament']);
            }
        }

        private function round_metagame ($pMetagame, $pMaxArchetypes = 7) {
            $other_id = null;
            $count_archetypes = 1;
            $metagame = array();
            $sum_other = 0;
            $percent_other = 0;
            foreach ($pMetagame as $key => $archetype) {
                if ($archetype['id_archetype'] == ModelArchetype::ARCHETYPE_OTHER_ID) {
                    $other_id = $key;
                } else {
                    if ($count_archetypes < $pMaxArchetypes) {
                        $metagame[] = $archetype;
                        $count_archetypes++;
                        $percent_other += $archetype['percent'];
                    } else {
                        $sum_other += $archetype['count'];
                    }
                }
            }
            $pMetagame[$other_id]['count'] += $sum_other;
            $pMetagame[$other_id]['percent'] = 100 - $percent_other;
            if ($other_id) {
                $metagame[] = $pMetagame[$other_id];
            }
            return $metagame;
        }

        // TODO : filter by format first, then async load tournament list
        public function search () {
            $this->setTitle("Search tournament");
            $list_tournaments = $this->modelTournament->all(Query::condition()->order("date_tournament DESC, name_tournament"), "id_tournament, name_tournament");
            $this->addContent("list_tournaments", $list_tournaments);
            if (isset($_GET['id'])) {
                $tournament = $this->modelTournament->getTupleById($_GET['id']);
                if ($tournament) {
                    $tournament_condition = Query::condition()->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament']);
                    if (isset($_POST['refresh'])) {
                        $count_refresh = 0;
                        // Refresh tournament archetypes
                        $players = $this->modelPlayer->all(Query::condition()->andWhere("id_tournament", Query::EQUAL, $tournament['id_tournament']));
                        foreach ($players as $player) {
                            $archetype = $player['id_archetype'];
                            $new_archetype = $this->modelArchetype->evaluatePlayerArchetype($player['id_player'], $tournament['id_type_format']);
                            if ($new_archetype && $archetype != $new_archetype['id_archetype']) {
                                trace_r("Update : $archetype => " . $new_archetype['name_archetype']);
                                $count_refresh++;
                            }
                        }
                        trace_r("Refresh tournament archetypes : $count_refresh");
                    }
                    if (isset($_POST['duplicates'])) {
                        $cleaned_duplicates = $this->modelPlayer->cleanDuplicatePlayers($tournament_condition);
                        trace_r("Clean duplicate decklists : $cleaned_duplicates");
                    }

                    // check if duplicate player
                    $count_duplicates = $this->modelPlayer->countDuplicatePlayers($tournament_condition);

                    if ($count_duplicates != 0) {
                        $this->addMessage("$count_duplicates duplicates decklists found", self::MESSAGE_ERROR);
                        $this->addContent("clean_duplicates", 1);
                    }

                    $metagame = $this->modelPlayer->countArchetypes($tournament_condition);
                    $count_players = $this->modelTournament->countPlayers($tournament_condition);

                    $order_archetypes = array();
                    foreach ($metagame as $deck) {
                        $order_archetypes[] = $deck['id_archetype'];
                    }
                    $winrates = $this->modelMatch->getFullWinrate($tournament_condition, $order_archetypes);
                    foreach ($metagame as $key => &$deck) {
                        // add winrate to deck
                        $deck['winrate'] = $winrates[$key]['winrate'];
                    }
                    $tournament['count_players'] = $count_players;
                    $this->addContent("tournament", $tournament);
                    $this->addContent("metagame", $metagame);
                }
            }
        }
    }
}