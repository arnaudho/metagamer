<?php
namespace app\main\controllers\front {

    use app\main\models\ModelCountry;
    use app\main\models\ModelFormat;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\Autoload;
    use core\application\Core;
    use core\application\DefaultFrontController;

    class home extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelTournament;
        protected $modelFormat;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelFormat = new ModelFormat();
            $this->modelTournament = new ModelTournament();
        }

        public function index () {
            Autoload::addStyle("flags/css/flag-icon.min.css");
            $results = $this->modelPlayer->getPlayersByCountry(ModelCountry::COUNTRY_ID_FRANCE, array(4090, 4091, 5287, 5288));
            $players = array();
            foreach ($results as $player) {
                if (!isset($players[$player['id_people']])) {
                    $players[$player['id_people']] = array(
                        "name_player"  => $player['arena_id'],
                        "tag_player"   => $player['tag_player'],
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
                $players[$player['id_people']]["t".$player['id_tournament']]['wins'] = $player['wins'];
                $players[$player['id_people']]["t".$player['id_tournament']]['loss'] = $player['total']-$player['wins'];
                $players[$player['id_people']]['global']['wins'] += $player['wins'];
                $players[$player['id_people']]['global']['loss'] += $player['total']-$player['wins'];
            }
            uasort($players, array($this, "sortPlayerByRecord"));
            $this->addContent("players", $players);
        }

        protected function sortPlayerByRecord ($pA, $pB) {
            return $pA['global']['wins'] == $pB['global']['wins'] ?
                ($pA['global']['loss'] > ['global']['loss'] ? -1 : 1) :
                ($pA['global']['wins'] < $pB['global']['wins'] ? 1 : -1);
        }
    }
}