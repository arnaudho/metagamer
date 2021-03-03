<?php

namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\Autoload;
    use core\application\DefaultController;
    use core\db\Query;

    // PUBLIC CONTROLLER
    class coverage extends DefaultController
    {
        CONST DECKLIST_MAX_COLUMNS = 8;

        protected $modelTournament;
        protected $modelArchetype;
        protected $modelPlayer;
        protected $modelMatch;
        protected $modelFormat;
        protected $modelCard;

        public function __construct()
        {
            trace_r("COVERAGE MODULE");
            $this->modelTournament = new ModelTournament();
            $this->modelArchetype = new ModelArchetype();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatch = new ModelMatch();
            $this->modelFormat = new ModelFormat();
            $this->modelCard = new ModelCard();
            Autoload::addComponent("Metagamer");
        }

        public function tournament () {
            if (!isset($_GET['id_tournament']) || !$tournament = $this->modelTournament->getTupleById($_GET['id_tournament'])) {
                $this->addContent("error", "Tournament not found");
            } else {
                // TODO ModelPlayer method to get standings
                $players = $this->modelPlayer->getPlayersByTournamentId($tournament['id_tournament']);
                trace_r($players);
                $tournament['date_tournament'] = date("d F Y", strtotime($tournament['date_tournament']));
                trace_r($tournament);
                foreach ($players as &$player) {
                    if ($player['name_archetype'] == ModelArchetype::ARCHETYPE_OTHER) {
                        $player['name_archetype'] = $player['name_deck'];
                    }
                }
                $this->addContent("tournament", $tournament);
                $this->addContent("players", $players);
            }
        }

        public function decklist () {
            if (!isset($_GET['id_decklist']) || !$player = $this->modelPlayer->getTupleById($_GET['id_decklist'])) {
                $this->addContent("error", "Decklist not found");
            } else {
                $player = $this->modelPlayer->getDataByPlayerId($player["id_player"]);
                trace_r($player);

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

                // TODO check limited decklists

                $cards_side = $this->modelCard->getDecklistCards($player['id_player'],
                    $sideboard_condition,
                    $sideboard_order);

                $decklist_by_curve = array();
                $lands = array();
                // order MD by curve
                foreach ($cards_main as $card) {
                    if ($card['mana_cost_card'] == "") {
                        $card['cmc_card'] = 99;
                        $lands[] = $card;
                    } else {
                        $decklist_by_curve[$card['cmc_card']][] = $card;
                    }
                }
                // if more than 7 columns before lands, group columns 7+
                if (count($decklist_by_curve) >= self::DECKLIST_MAX_COLUMNS) {
                    $keep = array_slice($decklist_by_curve, 0, self::DECKLIST_MAX_COLUMNS-2);
                    $merge = array_slice($decklist_by_curve, self::DECKLIST_MAX_COLUMNS-2);
                    $merged = call_user_func_array('array_merge', $merge);
                    array_push($keep, $merged);
                    $decklist_by_curve = $keep;
                }
                $decklist_by_curve[99] = $lands;

                $this->setTemplate("player", "decklist");
                $this->addContent("player", $player);
                $this->addContent("maindeck_width", count($decklist_by_curve)*165+40);
                $this->addContent("cards_main", $decklist_by_curve);
                $this->addContent("cards_side", $cards_side);
            }
        }
    }
}