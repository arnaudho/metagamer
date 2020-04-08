<?php
namespace app\main\controllers\front {

    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use app\main\src\MetagamerBot;
    use core\application\DefaultController;
    use core\db\Query;

    class tournament extends DefaultController
    {
        protected $modelTournament;
        protected $modelPlayer;
        protected $modelMatch;

        public function __construct()
        {
            $this->modelTournament = new ModelTournament();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatch = new ModelMatch();
        }

        public function import () {
            $this->setTitle("Import tournament");
            if (isset($_GET['url'])) {
                $bot = new MetagamerBot("Roe (Online GP parser)");
                $result = $bot->parseDecklists($_GET['url']);
                if ($result) {
                    $id_tournament = $bot->tournament;
                    $data = $this->modelTournament->getTournamentData($id_tournament);
                    $this->addContent("data", $data);
                }
            }
        }

        public function search () {
            $this->setTitle("Search tournament");
            $list_tournaments = $this->modelTournament->all(Query::condition()->order("date_tournament DESC, name_tournament"), "id_tournament, name_tournament");
            $this->addContent("list_tournaments", $list_tournaments);
            if (isset($_GET['id'])) {
                $tournament = $this->modelTournament->getTupleById($_GET['id']);
                if ($tournament) {
                    $tournament_condition = Query::condition()->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament']);
                    $metagame = $this->modelPlayer->countArchetypes($tournament_condition);
                    $count_players = $this->modelTournament->countPlayers($tournament_condition);

                    $order_archetypes = array();
                    foreach ($metagame as $deck) {
                        $order_archetypes[] = $deck['id_archetype'];
                    }
                    $winrates = $this->modelMatch->getWinrate($tournament_condition, $order_archetypes);
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