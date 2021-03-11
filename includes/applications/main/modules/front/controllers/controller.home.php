<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelFormat;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use app\main\models\ModelTypeFormat;
    use core\application\DefaultFrontController;
    use core\application\routing\RoutingHandler;
    use core\db\Query;

    class home extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelTournament;
        protected $modelTypeFormat;
        protected $modelFormat;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelFormat = new ModelFormat();
            $this->modelTypeFormat = new ModelTypeFormat();
            $this->modelTournament = new ModelTournament();
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
                    "tournaments"    => array(),
                    "opened"         => $count_open-- > 0 ? 1 : 0
                );
            }
            $tournaments = $this->modelTournament->allOrdered();
            foreach ($tournaments as $tournament) {
                if ($tournament['id_tournament']) {
                    $formats[$tournament['id_format']]['tournaments'][$tournament['id_tournament']] = array(
                        "name_tournament" => $tournament['name_tournament'],
                        "count_players" => $tournament['count_players']
                    );
                }
            }
            $this->addContent("type_formats", $this->modelTypeFormat->all());
            $this->addContent("formats", $formats);
        }
    }
}