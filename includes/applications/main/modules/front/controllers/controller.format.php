<?php

namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use app\main\models\ModelTypeFormat;
    use core\application\Autoload;
    use core\application\Core;
    use core\application\DefaultFrontController;
    use core\application\routing\RoutingHandler;
    use core\db\Query;

    class format extends DefaultFrontController
    {
        protected $modelTournament;
        protected $modelArchetype;
        protected $modelPlayer;
        protected $modelMatch;
        protected $modelFormat;
        protected $modelTypeFormat;
        protected $modelCard;

        public function __construct()
        {
            $this->modelTournament = new ModelTournament();
            $this->modelArchetype = new ModelArchetype();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatch = new ModelMatch();
            $this->modelFormat = new ModelFormat();
            $this->modelTypeFormat = new ModelTypeFormat();
            $this->modelCard = new ModelCard();
            parent::__construct();
            Autoload::addComponent("Metagamer");
        }

        public function index () {
            if (isset($_POST['create-format']) && isset($_POST['create-format']['name_format'])) {
                $exists_format = $this->modelFormat->one(
                    Query::condition()
                        ->andWhere("name_format", Query::EQUAL, $_POST['create-format']['name_format'])
                );
                if (!$exists_format) {
                    $this->modelFormat->insert($_POST['create-format']);
                }
            }
            $count_open = 3;
            $formats = array();
            $data = $this->modelFormat->all(Query::condition()->order("id_format", "DESC"));
            foreach ($data as $format) {
                $formats[$format['id_format']] = array(
                    "name_format"    => $format['name_format'],
                    "link_dashboard" => RoutingHandler::rewrite("dashboard", "") . "?id_format=" . $format['id_format'],
                    "link_other"     => RoutingHandler::rewrite("archetype", "lists") . "?id_archetype=" . ModelArchetype::ARCHETYPE_OTHER_ID . "&id_format=" . $format['id_format'],
                    "link_metagame"  => RoutingHandler::rewrite("tournament", "metagame") . "?id_format=" . $format['id_format'],
                    "tournaments"    => array(),
                    "opened"         => $count_open-- > 0 ? 1 : 0
                );
            }
            $tournaments = $this->modelTournament->allOrdered();
            foreach ($tournaments as $tournament) {
                if ($tournament['id_tournament']) {
                    $formats[$tournament['id_format']]['tournaments'][$tournament['id_tournament']] = array(
                        "name_tournament" => $tournament['name_tournament'],
                        "count_players" => $tournament['count_players'],
                        "count_matches" => $tournament['count_matches'],
                        "count_rounds"  => $tournament['count_rounds']
                    );
                }
            }
            $this->addContent("type_formats", $this->modelTypeFormat->all());
            $this->addContent("formats", $formats);
        }
    }
}