<?php
namespace app\main\controllers\front {

    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelPeople;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\Autoload;
    use core\application\DefaultFrontController;
    use core\application\Go;
    use core\db\Query;

    class search extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelPeople;
        protected $modelTournament;
        protected $modelFormat;
        protected $modelCard;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelPeople = new ModelPeople();
            $this->modelTournament = new ModelTournament();
            $this->modelFormat = new ModelFormat();
            $this->modelCard = new ModelCard();
        }

        public function index () {
            $this->setTitle("Search");
            $limit_results = 10;

            if (isset($_GET['q'])) {
                $term = trim($_GET['q']);
                $results = array();

                // search decklists / archetypes
                $archetypes = $this->modelPlayer->searchPlayerByDecklistName($term);
                $count_archetypes = $this->modelPlayer->searchPlayerByDecklistName($term, true);
                $results[] = array(
                    "label" => "Archetypes",
                    "elements" => $archetypes,
                    "count" => $count_archetypes
                );

                // search tournaments
                $tournaments = $this->modelTournament->searchTournamentsByName($term);
                $count_tournaments = $this->modelTournament->searchTournamentsByName($term, true);
                foreach ($tournaments as $key => $tournament) {
                    $tournaments[$key]['date_tournament'] = date('j M Y', strtotime($tournament['date_tournament']));
                }
                $results[] = array(
                    "label" => "Tournaments",
                    "elements" => $tournaments,
                    "count" => $count_tournaments
                );

                // search formats
                $formats = $this->modelFormat->searchFormatByName($term);
                $count_formats= $this->modelFormat->searchFormatByName($term, true);
                $results[] = array(
                    "label" => "Formats",
                    "elements" => $formats,
                    "count" => $count_formats
                );

                // search players
                $players = $this->modelPeople->searchPeopleByName($term);
                $count_players = $this->modelPeople->searchPeopleByName($term, true);
                $results[] = array(
                    "label" => "Players",
                    "elements" => $players,
                    "count" => $count_players
                );

                // search cards
                $cards = $this->modelCard->searchCardsByName($term);
                $count_cards = $this->modelCard->searchCardsByName($term, true);
                $results[] = array(
                    "label" => "Cards",
                    "elements" => $cards,
                    "count" => $count_cards
                );
                $max_results = max($count_players, $count_formats, $count_archetypes, $count_cards, $count_tournaments);
                if ($max_results > $limit_results) {
                    $max_results = $limit_results;
                }
                $this->addContent("max_results", range(0, $max_results-1));
                $this->addContent("count_results", $count_formats+$count_players+$count_tournaments+$count_archetypes+$count_cards);
                $this->addContent("results", $results);
                $this->addContent("term", $term);
            }
        }

        public function card () {
            Autoload::addStyle("mana/css/mana.min.css");
            $card = $this->modelCard->getTupleById($_GET["id_card"]);
            if (!$card) {
                Go::to404();
            }
            $card['mana_cost_card'] = preg_replace('/(\{([\durbgw])\})/i', '<i class="ms ms-$2"></i>', strtolower($card['mana_cost_card']));
            $this->addContent("card", $card);
            $players_standard = $this->modelPlayer->searchPlayerByCardId(
                $card['id_card'],
                Query::condition()->andWhere("formats.id_type_format", Query::EQUAL, ModelFormat::TYPE_FORMAT_STANDARD_ID));
            $players_historic = $this->modelPlayer->searchPlayerByCardId(
                $card['id_card'],
                Query::condition()->andWhere("formats.id_type_format", Query::EQUAL, ModelFormat::TYPE_FORMAT_HISTORIC_ID));

            $this->addContent("players", array(
                array(
                    "slug" => "standard",
                    "label" => "Last Standard decklists",
                    "players" => $players_standard
                ),
                array(
                    "slug" => "historic",
                    "label" => "Last Historic decklists",
                    "players" => $players_historic
                )
            ));
            $this->setTitle($card['name_card']);
        }
    }
}