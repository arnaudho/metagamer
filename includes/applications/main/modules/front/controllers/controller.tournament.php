<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
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
                } elseif ($_POST['import-mtgmelee-decklists'] && $_POST['import-mtgmelee-decklists']['count']) {
                    $count = $_POST['import-mtgmelee-decklists']['count'] > 100 ? 100 : intval($_POST['import-mtgmelee-decklists']['count']);
                    $players = $this->modelPlayer->all(Query::condition()
                        ->andWhere("id_archetype", Query::IS, "NULL", false)
                        ->limit(0, $count),
                        "id_player"
                    );
                    if (count($players) > 0) {
                        $bot = new MtgMeleeBot("Test");
                        foreach ($players as $player) {
                            $bot->parseDecklist($player['id_player']);
                            // wait 2 seconds to prevent HTTP 429
                            sleep(2);
                        }
                    }
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
            $this->addContent("list_formats", $this->modelFormat->all());
            $this->addContent("count_waiting", $this->modelPlayer->countPlayersWithoutDecklist());
        }

        // TODO : filter by format first, then async load tournament list
        public function search () {
            $this->setTitle("Search tournament");
            $list_tournaments = $this->modelTournament->all(Query::condition()->order("date_tournament DESC, name_tournament"), "id_tournament, name_tournament");
            $this->addContent("list_tournaments", $list_tournaments);
            if (isset($_GET['id'])) {
                $tournament = $this->modelTournament->getTupleById($_GET['id']);
                if ($tournament) {
                    if (isset($_POST['refresh'])) {
                        $count_refresh = 0;
                        // Refresh tournament archetypes
                        $players = $this->modelPlayer->all(Query::condition()->andWhere("id_tournament", Query::EQUAL, $tournament['id_tournament']));
                        foreach ($players as $player) {
                            $archetype = $player['id_archetype'];
                            $new_archetype = $this->modelArchetype->evaluatePlayerArchetype($player['id_player']);
                            if ($new_archetype && $archetype != $new_archetype['id_archetype']) {
                                trace_r("Update : $archetype => " . $new_archetype['name_archetype']);
                                $count_refresh++;
                            }
                        }
                        trace_r("Refresh tournament archetypes : $count_refresh");
                    }

                    $tournament_condition = Query::condition()->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament']);
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