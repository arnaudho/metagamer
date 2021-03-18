<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use core\application\DefaultFrontController;
    use core\application\Go;
    use core\db\Query;

    class player extends DefaultFrontController
    {
        CONST DECKLIST_MAX_COLUMNS = 8;

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

        // TODO filter lands by set ? to group bilands & basics
        public function display () {
            $player = $this->modelPlayer->getDataByPlayerId($_GET["id_player"]);
            if (!$player) {
                Go::to404();
            }
            $is_limited = $player['id_type_format'] == ModelFormat::TYPE_FORMAT_LIMITED_ID;
            $player['arena_id'] = ucwords($player['arena_id']);
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
            }

            $lands = array();
            $decklist_by_curve = array();
            $decklist_by_curve_spells = array();

            // order MD by curve
            foreach ($cards_main as $card) {
                if ($card['mana_cost_card'] == "") {
                    $card['cmc_card'] = 99;
                    $lands[] = $card;
                } else {
                    if ($is_limited) {
                        // fill creatures/spells curve at the same time
                        if (strpos($card['type_card'], "Creature") === false) {
                            $decklist_by_curve_spells[$card['cmc_card']][] = $card;
                        } else {
                            $decklist_by_curve[$card['cmc_card']][] = $card;
                        }
                    } else {
                        $decklist_by_curve[$card['cmc_card']][] = $card;
                    }
                }
            }

            // align creatures & spells in curve
            if ($is_limited) {
                for ($curve = 0; $curve <= 10; $curve++) {
                    if (array_key_exists($curve, $decklist_by_curve)) {
                        if (!array_key_exists($curve, $decklist_by_curve_spells)) {
                            $decklist_by_curve_spells[$curve] = array();
                        }
                    } elseif (array_key_exists($curve, $decklist_by_curve_spells)) {
                        $decklist_by_curve[$curve] = array();
                    }
                }
            }
            ksort($decklist_by_curve);
            ksort($decklist_by_curve_spells);

            $max_columns = self::DECKLIST_MAX_COLUMNS;
            // if more than 7 columns before lands, group columns 7+
            if (count($decklist_by_curve) >= $max_columns) {
                $keep = array_slice($decklist_by_curve, 0, $max_columns-2);
                $merge = array_slice($decklist_by_curve, $max_columns-2);
                $merged = call_user_func_array('array_merge', $merge);
                array_push($keep, $merged);
                $decklist_by_curve = $keep;
            }
            $decklist_by_curve[99] = $lands;

            if ($is_limited) {
                if (count($decklist_by_curve_spells) >= $max_columns) {
                    $keep = array_slice($decklist_by_curve_spells, 0, $max_columns-2);
                    $merge = array_slice($decklist_by_curve_spells, $max_columns-2);
                    $merged = call_user_func_array('array_merge', $merge);
                    array_push($keep, $merged);
                    $decklist_by_curve_spells = $keep;
                }
                $this->addContent("cards_spells_main", $decklist_by_curve_spells);

                // get max height for creatures block
                $this->addContent("creatures_main_height", count(max($decklist_by_curve))*60+210);
            }

            $this->addContent("logo", 1);
            $this->addContent("overlay_twitter", 1);
            $this->setTemplate("player", "decklist");
            $this->addContent("player", $player);
            $this->addContent("cards_main", $decklist_by_curve);
            $this->addContent("cards_side", $cards_side);
        }
    }
}