<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use core\application\DefaultFrontController;

    class player extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelMatches;
        protected $modelArchetypes;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatches = new ModelMatch();
            $this->modelArchetypes = new ModelArchetype();
        }

        public function index () {
            $this->setTitle("Search player");
            if (isset($_GET['search'])) {
                $players = $this->modelPlayer->searchPlayerByArenaId($_GET['search']);
                $this->addContent("players", $players);
            }
        }
    }
}