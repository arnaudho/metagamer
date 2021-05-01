<?php
namespace app\api\controllers\front {

    use app\main\models\ModelPlayer;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;

    class leaderboard extends RestController
    {
        protected $modelPlayer;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelPlayer = new ModelPlayer();
            parent::__construct();
        }

        // TODO return column labels in JSON output -- especially tournament names
        public function getLeaderboard () {
            if (!Core::checkRequiredGetVars('tag') ||
                ($_GET['tag'] != ModelPlayer::TAG_MPL && $_GET['tag'] != ModelPlayer::TAG_RIVALS)
            ) {
                $this->throwError(
                    422, "Error fetching leaderboard"
                );
            }
            $board = $this->modelPlayer->getLeaderboard($_GET['tag']);
            $this->content = SimpleJSON::encode($board, JSON_UNESCAPED_SLASHES);
        }
    }
}