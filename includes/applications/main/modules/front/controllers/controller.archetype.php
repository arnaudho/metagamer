<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\src\MetagamerBot;
    use core\application\Core;
    use core\application\DefaultFrontController;
    use core\application\Go;

    class archetype extends DefaultFrontController
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

        // TODO WIP
        public function evaluate () {
            if(!Core::$request_async || !isset($_POST['url'])) {
                Go::to404();
            }
            // evaluate given decklist
            $bot = new MetagamerBot("Decklist");
            $name_archetype = $bot->parsePlayer(0, $_POST['url'], false, false);
            $this->addContent("archetype", $name_archetype);
            return $name_archetype;
        }
    }
}