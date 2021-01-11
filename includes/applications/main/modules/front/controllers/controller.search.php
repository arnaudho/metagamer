<?php
namespace app\main\controllers\front {

    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelPeople;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\DefaultFrontController;

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
                $results = array();

                // search decklists / archetypes
                $archetypes = $this->modelPlayer->searchPlayerByDecklistName($_GET['q']);
                $count_archetypes = $this->modelPlayer->searchPlayerByDecklistName($_GET['q'], true);
                $results[] = array(
                    "label" => "Archetypes",
                    "elements" => $archetypes,
                    "count" => $count_archetypes
                );

                // search tournaments
                $tournaments = $this->modelTournament->searchTournamentsByName($_GET['q']);
                $count_tournaments = $this->modelTournament->searchTournamentsByName($_GET['q'], true);
                foreach ($tournaments as $key => $tournament) {
                    $tournaments[$key]['date_tournament'] = date('j M Y', strtotime($tournament['date_tournament']));
                }
                $results[] = array(
                    "label" => "Tournaments",
                    "elements" => $tournaments,
                    "count" => $count_tournaments
                );

                // search formats
                $formats = $this->modelFormat->searchFormatByName($_GET['q']);
                $count_formats= $this->modelFormat->searchFormatByName($_GET['q'], true);
                $results[] = array(
                    "label" => "Formats",
                    "elements" => $formats,
                    "count" => $count_formats
                );

                // search players
                $players = $this->modelPeople->searchPeopleByName($_GET['q']);
                $count_players = $this->modelPeople->searchPeopleByName($_GET['q'], true);
                $results[] = array(
                    "label" => "Players",
                    "elements" => $players,
                    "count" => $count_players
                );

                // search cards
                $cards = $this->modelCard->searchCardsByName($_GET['q']);
                $count_cards = $this->modelCard->searchCardsByName($_GET['q'], true);
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
            }
        }
    }
}