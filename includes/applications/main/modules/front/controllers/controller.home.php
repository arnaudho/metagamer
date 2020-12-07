<?php
namespace app\main\controllers\front {

    use app\main\models\ModelFormat;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\DefaultFrontController;
    use core\application\routing\RoutingHandler;

    class home extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelTournament;
        protected $modelFormat;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelFormat = new ModelFormat();
            $this->modelTournament = new ModelTournament();
        }

        public function index () {
            $formats = array();
            $tournaments = $this->modelTournament->allOrdered();
            $count_open = 3;
            foreach ($tournaments as $tournament) {
                if (!array_key_exists($tournament['id_format'], $formats)) {
                    $formats[$tournament['id_format']] = array(
                        "name_format" => $tournament['name_format'],
                        "link_format" => RoutingHandler::rewrite("dashboard", "") . "?id_format=" . $tournament['id_format'],
                        "tournaments" => array(),
                        "opened"          => $count_open-- > 0 ? 1 : 0
                    );
                }
                if ($tournament['id_tournament']) {
                    $formats[$tournament['id_format']]['tournaments'][$tournament['id_tournament']] = array(
                        "name_tournament" => $tournament['name_tournament'],
                        "count_players" => $tournament['count_players']
                    );
                }
            }
            $this->addContent("formats", $formats);

        }
    }
}