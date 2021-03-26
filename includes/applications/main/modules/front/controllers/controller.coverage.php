<?php

namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
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
                    ->andWhere("tournaments.id_tournament", Query::IN, "(4090, 4091, 5287, 5288)", false)
            );
            foreach ($tournaments as &$tournament) {
                $tournament['image_tournament'] = Core::$path_to_components . "/metagamer/imgs/kaldheim_championship.png";
            }
            $this->addContent("tournaments", $tournaments);
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
                    }
                }
                $this->addContent("display_league", $display_league);
                $this->addContent("tournament", $tournament);
                $this->addContent("players", $players);
            }
        }
    }
}