<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use core\application\DefaultFrontController;
    use core\application\Go;
    use core\db\Query;

    class player extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelCard;
        protected $modelMatches;
        protected $modelArchetypes;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelCard = new ModelCard();
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

        public function display () {
            $player = $this->modelPlayer->getDataByPlayerId($_GET["id_player"]);
            if (!$player) {
                Go::to404();
            }
            $player['arena_id'] = ucwords($player['arena_id']);
            $cards = $this->modelCard->getDecklistCards($player['id_player']);
            // TODO reorder sideboard cards by CMC / #copies ?
            // filter lands by set ? to group bilands & basics

            // display sideboard cards
            $count_side = 0;
            foreach ($cards as $key => $card) {
                if ($card['count_side'] > 0) {
                    $count_side++;
                    $cards[$key]['side_margin'] = $count_side;
                }
            }
            foreach ($cards as $key => $card) {
                if ($card['count_side'] > 0) {
                    $cards[$key]['side_margin'] = ($cards[$key]['side_margin']-1)*(222-(450/$count_side));
                }
            }

            $this->setTemplate("player", "decklist");
            $this->addContent("player", $player);
            $this->addContent("cards", $cards);
        }
    }
}