<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use core\application\DefaultController;

    class player extends DefaultController
    {
        protected $modelPlayer;
        protected $modelMatches;
        protected $modelArchetypes;

        public function __construct()
        {
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatches = new ModelMatch();
            $this->modelArchetypes = new ModelArchetype();
        }

        public function index () {
            if (isset($_GET['search'])) {
                $players = $this->modelPlayer->searchPlayerByArenaId($_GET['search']);
                $this->addContent("players", $players);
            }
        }
    }
}