<?php

namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelCountry;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\Autoload;
    use core\application\Core;
    use core\application\DefaultFrontController;
    use core\db\Query;

    class coverage extends DefaultFrontController
    {
        CONST TOURNAMENT_IDS = array(
            4090 => array(4090, 4091),
            5287 => array(5287, 5288),
            6392 => array(6392, 6393)
        );

        protected $modelTournament;
        protected $modelArchetype;
        protected $modelPlayer;
        protected $modelMatch;
        protected $modelFormat;
        protected $modelCard;

        public function __construct()
        {
            $this->modelTournament = new ModelTournament();
            $this->modelArchetype = new ModelArchetype();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatch = new ModelMatch();
            $this->modelFormat = new ModelFormat();
            $this->modelCard = new ModelCard();
            parent::__construct();
            Autoload::addComponent("Metagamer");
        }

        public function index () {
            $tournaments = $this->modelTournament->allOrdered(
                Query::condition()
                    ->andWhere("tournaments.id_tournament", Query::IN, "(15128, 15133, 15143, 15148, 15154, 15155, 15166, 15167, 15206, 15207, 4090, 4091, 5351, 5287, 5288, 6392, 6393)", false)
            );
            foreach ($tournaments as $key => $tournament) {
                if (!$tournament['image_tournament']) {
                    $tournament['image_tournament'] = "arena_logo.png";
                }
                $tournaments[$key]['image_tournament'] = Core::$path_to_components . "/metagamer/imgs/" . $tournament['image_tournament'];
                $tournaments[$key]['link_tournament'] = "coverage/tournament/" . $tournament['id_tournament'] . '/';
            }
            // get all tournaments from class CONST, grouped by key
            // add all to template

            $groups = array();
            foreach ($tournaments as $tournament) {
                $name_group = $tournament['name_tournament'];
                if (strpos($tournament['name_tournament'], "Championship") !== false && preg_match('/^[^\-]*/', $tournament['name_tournament'], $output_array)) {
                    $name_group = $output_array[0];
                }
                $groups[$name_group][$tournament['id_type_format']] = $tournament;
            }
            foreach ($groups as $key => $group) {
                if (count($group) > 1) {
                    ksort($groups[$key]);
                }
            }

            $this->addContent("groups", $groups);
            $this->addContent("h1", "Tournament Coverage");
            $this->setTitle("Coverage");
        }


        // TODO set scores std + histo instead of tournaments IDS

        public function standings () {
            if (Core::checkRequiredGetVars("id_tournament")) {
                if (!array_key_exists($_GET['id_tournament'], self::TOURNAMENT_IDS) || !$tournament = $this->modelTournament->getTupleById($_GET['id_tournament'])) {
                    $this->addContent("error", "Tournament not found");
                } else {
                    Autoload::addStyle("flags/css/flag-icon.min.css");
                    $ids = self::TOURNAMENT_IDS[$tournament['id_tournament']];
                    $tournaments = $this->modelTournament->allWithFormat(Query::condition()
                        ->andWhere("id_tournament", Query::IN, "(" . implode(",", $ids) . ")", false));
                    $tournaments_formats = array();
                    foreach ($tournaments as $item) {
                        $tournaments_formats[$item['id_tournament']] = $item['id_type_format'];
                    }
                    $results = $this->modelPlayer->getPlayersByCountry(ModelCountry::COUNTRY_ID_FRANCE, $ids);
                    $players = array();

                    if (preg_match('/^[^\-]*/', $tournaments[0]['name_tournament'], $output_array)) {
                        $this->addContent("name_tournament", $output_array[0]);
                    }

                    /**
                     * TODO DROPPED players -- set id_player instead of id_people
                     */
                    $dropped = array(6732, 6696, 9126, 5753, 5132);

                    foreach ($results as $player) {
                        if (!isset($players[$player['id_people']])) {
                            $players[$player['id_people']] = array(
                                "name_player"  => $player['arena_id'],
                                "image_player" => Core::$path_to_components . "/metagamer/imgs/players/" . $player['id_people'] . ".png"
                            );
                            // ad player icons
                            if (
                                $player['tag_player'] == ModelPlayer::TAG_MPL ||
                                $player['tag_player'] == ModelPlayer::TAG_RIVALS
                            ) {
                                $players[$player['id_people']]['tag_player'] = Core::$path_to_components . "/metagamer/imgs/" . $player['tag_player'] . ".png";
                            }
                        }
                        $players[$player['id_people']]["t".$tournaments_formats[$player['id_tournament']]]['wins'] = $player['wins'] ? $player['wins'] : 0;
                        $players[$player['id_people']]["t".$tournaments_formats[$player['id_tournament']]]['loss'] = $player['total']-$player['wins'];
                        if (!array_key_exists('global', $players[$player['id_people']])) {
                            $players[$player['id_people']]['global'] = array('wins' => 0, 'loss' => 0);
                        }
                        $players[$player['id_people']]['global']['wins'] += $player['wins'];
                        $players[$player['id_people']]['global']['loss'] += $player['total']-$player['wins'];
                        $players[$player['id_people']]['decks'][$tournaments_formats[$player['id_tournament']]] = $player['player_archetype'];

                        if (in_array($player['id_people'], $dropped)) {
                            $players[$player['id_people']]['disabled'] = 1;
                        }
                    }
                    uasort($players, array($this, "sortPlayerByRecord"));
                    $this->addContent("players", $players);
                    $this->setTitle("Score des français");
                }
            } else {
                $ids = array_keys(self::TOURNAMENT_IDS);
                $tournaments = $this->modelTournament->allOrdered(
                    Query::condition()
                        ->andWhere("tournaments.id_tournament", Query::IN, "(" . implode(",", $ids) . ")", false)
                );
                foreach ($tournaments as &$tournament) {
                    if (preg_match('/^[^\-]*/', $tournament['name_tournament'], $output_array)) {
                        $tournament['name_tournament'] = $output_array[0];
                    }
                    if (!$tournament['image_tournament']) {
                        $tournament['image_tournament'] = "arena_logo.png";
                    }
                    $tournament['image_tournament'] = Core::$path_to_components . "/metagamer/imgs/" . $tournament['image_tournament'];
                    $tournament['link_tournament'] = "coverage/standings/" . $tournament['id_tournament'] . '/';
                }
                $this->addContent("groups", array($tournaments));
                $this->addContent("h1", "Score des français");

                $this->setTitle("Coverage");
                $this->setTemplate("coverage", "index");
            }
        }

        protected function sortPlayerByRecord ($pA, $pB) {
            return $pA['global']['wins'] == $pB['global']['wins'] ?
                ($pA['global']['loss'] < ['global']['loss'] ? -1 : 1) :
                ($pA['global']['wins'] < $pB['global']['wins'] ? 1 : -1);
        }

        public function tournament () {
            if (!isset($_GET['id_tournament']) || !$tournament = $this->modelTournament->getTupleById($_GET['id_tournament'])) {
                $this->addContent("error", "Tournament not found");
            } else {
                $display_league = 0;
                $players = $this->modelPlayer->getPlayersByTournamentId($tournament['id_tournament']);
                $tournament['date_tournament'] = date("d F Y", strtotime($tournament['date_tournament']));
                foreach ($players as &$player) {
                    if ($player['name_archetype'] == ModelArchetype::ARCHETYPE_OTHER) {
                        $player['name_archetype'] = $player['name_deck'];
                    }
                    // ad player icons
                    if (
                        $player['tag_player'] == ModelPlayer::TAG_MPL ||
                        $player['tag_player'] == ModelPlayer::TAG_RIVALS
                    ) {
                        $display_league = 1;
                        $player['tag_player'] = Core::$path_to_components . "/metagamer/imgs/" . $player['tag_player'] . ".png";
                    } else {
                        $player['tag_player'] = '';
                    }
                }
                $this->addContent("display_league", $display_league);
                $this->addContent("tournament", $tournament);
                $this->addContent("players", $players);
                $this->setTitle($tournament['name_tournament'] . " - Coverage");
            }
        }
    }
}