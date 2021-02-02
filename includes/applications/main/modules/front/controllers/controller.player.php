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
            $cards_main = $this->modelCard->getDecklistCards($player['id_player'],
                Query::condition()->andWhere("count_main", Query::UPPER, 0),
                " CASE WHEN cmc_card = '' THEN 99 ELSE cmc_card END,
                        CASE WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                        WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10 WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                        type_card");
            $cards_side = $this->modelCard->getDecklistCards($player['id_player'],
                        Query::condition()->andWhere("count_side", Query::UPPER, 0),
                        "cmc_card ASC,
                        CASE WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                        WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10 WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                        type_card");

            // limited decklists : display only sideboard cards with same color as MD + artifacts ?
            // TODO filter lands by set ? to group bilands & basics

            $decklist_by_curve = array();
            // order MD by curve
            foreach ($cards_main as $card) {
                if ($card['cmc_card'] == "") {
                    $card['cmc_card'] = 0;
                }
                $decklist_by_curve[$card['cmc_card']][] = $card;
            }

            $this->setTemplate("player", "decklist");
            $this->addContent("player", $player);
            $this->addContent("cards_main", $decklist_by_curve);
            $this->addContent("cards_side", $cards_side);
        }
    }
}