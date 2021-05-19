<?php
namespace app\api\controllers\front {

    use app\main\models\ModelPeople;
    use app\main\models\ModelPlayer;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;

    class player extends RestController
    {
        protected $modelPlayer;
        protected $modelPeople;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelPeople = new ModelPeople();
            $this->modelPlayer = new ModelPlayer();
            parent::__construct();
        }

        public function getPlayerById () {
            if (!Core::checkRequiredGetVars('id_player')) {
                $this->throwError(
                    422, "Parameter [id_player] not found"
                );
            }
            $id = $_GET['id_player'];
            if (!$player = $this->modelPeople->getPlayerById($id)) {
                $this->throwError(
                    422, "Player ID $id not found"
                );
            }
            $this->content = SimpleJSON::encode($player, JSON_UNESCAPED_SLASHES);
        }
    }
}