<?php
namespace app\api\controllers\front {

    use app\main\models\ModelCard;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;

    class card extends RestController
    {
        protected $modelCard;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelCard = new ModelCard();
            parent::__construct();
        }

        public function getCardById () {
            if (!Core::checkRequiredGetVars('id_card')) {
                $this->throwError(
                    422, "Parameter [id_card] not found"
                );
            }
            $id = $_GET['id_card'];
            if (!$card = $this->modelCard->getTupleById($id)) {
                $this->throwError(
                    422, "Format ID $id not found"
                );
            }
            $this->content = SimpleJSON::encode($card, JSON_UNESCAPED_SLASHES);
        }
    }
}