<?php
namespace app\api\controllers\front
{
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPeople;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;
    use core\db\Query;

    class index extends RestController
    {
        protected $modelCard;
        protected $modelFormat;
        protected $modelPlayer;
        protected $modelPeople;
        protected $modelMatches;
        protected $modelTournament;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelCard = new ModelCard();
            $this->modelFormat = new ModelFormat();
            $this->modelPlayer = new ModelPlayer();
            $this->modelPeople = new ModelPeople();
            $this->modelMatches = new ModelMatch();
            $this->modelTournament = new ModelTournament();
            parent::__construct();
        }

        public function search () {
            $results = array();
            $item = 'all';
            if (!Core::checkRequiredGetVars('term')) {
                $this->throwError(
                    422, "Parameter [term] not found"
                );
            }
            $term = trim($_GET['term']);
            // if type item specified
            if (Core::checkRequiredGetVars('item')) {
                $item = $_GET['item'];
            }
            $limit_results = Core::checkRequiredGetVars('results') ? $_GET['results'] : ($item == 'all' ? 0 : 10);
            
            if ($item == 'all' || $item == 'archetype') {
                $archetypes = $this->modelPlayer->searchPlayerByDecklistName($term, false, $limit_results);
                $count_archetypes = $this->modelPlayer->searchPlayerByDecklistName($term, true);
                $results['archetype'] = array(
                    "label" => "Archetypes",
                    "elements" => $archetypes,
                    "count" => $count_archetypes
                );
            }
            if ($item == 'all' || $item == 'format') {
                $formats = $this->modelFormat->searchFormatByName($term, false, $limit_results);
                $count_formats= $this->modelFormat->searchFormatByName($term, true);
                $results['format'] = array(
                    "label" => "Formats",
                    "elements" => $formats,
                    "count" => $count_formats
                );
            }
            if ($item == 'all' || $item == 'player') {
                $players = $this->modelPeople->searchPeopleByName($term, false, $limit_results);
                $count_players = $this->modelPeople->searchPeopleByName($term, true);
                $results['player'] = array(
                    "label" => "Players",
                    "elements" => $players,
                    "count" => $count_players
                );
            }
            if ($item == 'all' || $item == 'card') {
                $cards = $this->modelCard->searchCardsByName($term, false, $limit_results);
                $count_cards = $this->modelCard->searchCardsByName($term, true);
                $results['card'] = array(
                    "label" => "Cards",
                    "elements" => $cards,
                    "count" => $count_cards
                );
            }
            if ($item == 'all' || $item == 'tournament') {
                $tournaments = $this->modelTournament->searchTournamentsByName($term, $limit_results);
                $count_tournaments = $this->modelTournament->count(Query::condition()->andWhere("name_tournament", Query::LIKE, "%" . $term . "%"));

                foreach ($tournaments as $key => $tournament) {
                    $tournaments[$key]['date_tournament'] = date('j M Y', strtotime($tournament['date_tournament']));
                }
                $results['tournament'] = array(
                    "label" => "Tournaments",
                    "elements" => $tournaments,
                    "count" => $count_tournaments
                );
            }
            if ($item != 'all') {
                $results = $results[$item];
            }

            $this->content = SimpleJSON::encode($results, JSON_UNESCAPED_SLASHES);
        }
    }
}