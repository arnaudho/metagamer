<?php
namespace app\main\controllers\front {

    use app\main\models\ModelTournament;
    use app\main\src\MetagamerBot;
    use core\application\DefaultController;

    class tournament extends DefaultController
    {
        protected $modelTournament;

        public function __construct()
        {
            $this->modelTournament = new ModelTournament();
        }

        public function import () {
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
    }
}