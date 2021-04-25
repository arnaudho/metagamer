<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use core\application\Autoload;
    use core\application\DefaultFrontController;
    use core\application\Go;
    use core\application\routing\RoutingHandler;
    use core\db\Query;

    class player extends DefaultFrontController
    {
        CONST DECKLIST_MAX_COLUMNS = 8;

        protected $modelPlayer;
        protected $modelCard;
        protected $modelMatches;
        protected $modelArchetype;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelCard = new ModelCard();
            $this->modelMatches = new ModelMatch();
            $this->modelArchetype = new ModelArchetype();
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
            // format player name
            $player['arena_id'] = " by " . ucwords(strtolower($player['arena_id']), " -\t\r\n\f\v");

            $cards_main = $this->modelCard->getDecklistCards($player['id_player'],
                Query::condition()->andWhere("count_main", Query::UPPER, 0),
                " CASE WHEN mana_cost_card = '' THEN 99 ELSE cmc_card END,
                        CASE WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                        WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10
                        WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                        type_card");
            $sideboard_condition = Query::condition()->andWhere("count_side", Query::UPPER, 0);
            $sideboard_order = "cmc_card ASC,
                        CASE WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                        WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10
                        WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                        type_card";
            $cards_side = $this->modelCard->getDecklistCards($player['id_player'],
                $sideboard_condition,
                $sideboard_order);

            foreach ($cards_main as $key => $card) {
                $cards_main[$key]['mana_cost_card'] = ModelCard::formatManaCost($card['mana_cost_card']);
            }
            foreach ($cards_side as $key => $card) {
                $cards_side[$key]['mana_cost_card'] = ModelCard::formatManaCost($card['mana_cost_card']);
            }

            $this->setTemplate("player", "decklist");
            $this->addContent("link_visual", RoutingHandler::rewrite("player", "visual") . "?id_player=" . $player['id_player']);
            $this->addContent("cards_main", $cards_main);
            $this->addContent("cards_side", $cards_side);
            $this->addContent("player", $player);
            $this->setTitle($player['name_archetype'] . " - " . $player['arena_id']);
            Autoload::addStyle("mana/css/mana.min.css");
        }

        // TODO filter lands by set ? to group bilands & basics
        public function visual () {
            $player = $this->modelPlayer->getDataByPlayerId($_GET["id_player"]);
            if (!$player) {
                Go::to404();
            }
            $is_limited = $player['id_type_format'] == ModelFormat::TYPE_FORMAT_LIMITED_ID;

            // format player name
            $player['arena_id'] = " by " . ucwords(strtolower($player['arena_id']), " -\t\r\n\f\v");

            $cards_main = $this->modelCard->getDecklistCards($player['id_player'],
                Query::condition()->andWhere("count_main", Query::UPPER, 0),
                " CASE WHEN mana_cost_card = '' THEN 99 ELSE cmc_card END,
                        CASE WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                        WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10
                        WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                        type_card");
            $sideboard_condition = Query::condition()->andWhere("count_side", Query::UPPER, 0);
            $sideboard_order = "cmc_card ASC,
                        CASE WHEN type_card LIKE '%Creature%' THEN 1 WHEN type_card IN ('Instant', 'Sorcery') THEN 2
                        WHEN type_card = 'Legendary Planeswalker' THEN 3 WHEN type_card = 'Basic Land' THEN 10
                        WHEN type_card LIKE '%Land%' THEN 9 ELSE 8 END ASC,
                        type_card";
            // limit SB cards for limited decklists
            if ($is_limited) {
                $colors = $this->modelCard->getDecklistColors($player['id_player']);
                $player_colors = array();
                foreach ($colors as $color) {
                    $player_colors[] = $color['color_card'];
                }
                $sideboard_condition
                    ->andWhere("type_card", Query::NOT_LIKE, "%Land%")
                    ->andCondition(
                    Query::condition()
                        ->orWhere("color_card", Query::EQUAL, '')
                        ->orWhere("color_card", Query::IN, "('" . implode("', '", $player_colors) . "')", false)
                );
                $sideboard_order = " CASE WHEN color_card IN ('" . implode("', '", $player_colors) . "') THEN 1
                        WHEN color_card = '' THEN 3 ELSE 2 END ASC, color_card, " . $sideboard_order;
            }

            $cards_side = $this->modelCard->getDecklistCards($player['id_player'],
                $sideboard_condition,
                $sideboard_order);
            if ($is_limited) {
                $cards_side = array_slice($cards_side, 0, 15);
                $player['count_cards_side'] = count($cards_side) . "/" . $player['count_cards_side'];
            }

            $decklist_data = $this->modelCard->sortDecklistByCurve($cards_main, $is_limited);

            if ($is_limited) {
                $this->addContent("creatures_main_height", $decklist_data["creatures_main_height"]);
                $this->addContent("cards_spells_main", $decklist_data["curve_spells"]);
            }

            $this->addContent("logo", 1);
            $this->addContent("overlay_twitter", 0);
            $this->setTemplate("player", "decklist_visual");
            $this->addContent("player", $player);
//            $this->addContent("maindeck_width", count($decklist_data["curve"])*165+40);
            $this->addContent("cards_main", $decklist_data["curve"]);
            $this->addContent("cards_side", $cards_side);
            $this->setTitle($player['name_archetype'] . " - " . $player['arena_id']);
        }
    }
}