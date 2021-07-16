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
            // TODO add data quality flags
            // SELECT * FROM `player_card` WHERE id_player NOT IN (SELECT id_player FROM players)
            // SELECT * FROM `players` LEFT OUTER JOIN player_card USING(id_player) WHERE id_card IS NULL
            // SELECT COUNT(1) FROM matches WHERE id_player = 0 OR opponent_id_player = 0
            // SELECT * FROM player_card WHERE id_card = 0 => check dcklists with id_player in those
            // Check duplicate decklists
            // SELECT people without players -- button to delete ?
            // SELECT players without people

        }
    }
}