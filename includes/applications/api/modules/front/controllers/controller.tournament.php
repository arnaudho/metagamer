<?php
namespace app\api\controllers\front {

    use app\api\models\ModelFormat;
    use app\api\models\ModelTournament;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;

    class tournament extends RestController
    {
        protected $modelFormat;
        protected $modelTournament;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelFormat = new ModelFormat();
            $this->modelTournament = new ModelTournament();
            parent::__construct();
        }

        public function getTournamentById () {
            if (!Core::checkRequiredGetVars('id_tournament')) {
                $this->throwError(
                    422, "Parameter [id_tournament] not found"
                );
            }
            $id = $_GET['id_tournament'];
            if (!$tournament = $this->modelTournament->getTournamentById($id)) {
                $this->throwError(
                    422, "Tournament ID $id not found"
                );
            }
            $this->content = SimpleJSON::encode($tournament, JSON_UNESCAPED_SLASHES);
        }

        public function getTournamentsByIdFormat () {
            if (!Core::checkRequiredGetVars('id_format')) {
                $this->throwError(
                    422, "Parameter [id_format] not found"
                );
            }
            $id = $_GET['id_format'];
            if (!$this->modelFormat->getTupleById($id)) {
                $this->throwError(
                    422, "Format ID $id not found"
                );
            }
            $tournaments = $this->modelTournament->getTournamentsByIdFormat($id);
            $this->content = SimpleJSON::encode($tournaments, JSON_UNESCAPED_SLASHES);
        }
    }
}