<?php
namespace app\api\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelPeople;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;

    class decklist extends RestController
    {
        protected $modelCard;
        protected $modelPlayer;
        protected $modelPeople;
        protected $modelArchetype;
        protected $modelTournament;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelCard = new ModelCard();
            $this->modelPlayer = new ModelPlayer();
            $this->modelPeople = new ModelPeople();
            $this->modelArchetype = new ModelArchetype();
            $this->modelTournament = new ModelTournament();
            parent::__construct();
        }

        public function getDecklistById () {
            if (!Core::checkRequiredGetVars('id_decklist')) {
                $this->throwError(
                    422, "Parameter [id_decklist] not found"
                );
            }
            $id = $_GET['id_decklist'];
            if (!$decklist = $this->modelPlayer->getDecklistById($id)) {
                $this->throwError(
                    422, "Decklist ID $id not found"
                );
            }
            $this->content = SimpleJSON::encode($decklist, JSON_UNESCAPED_SLASHES);
        }

        public function getDecklistsByIdArchetype () {
            if (!Core::checkRequiredGetVars('id_archetype')) {
                $this->throwError(
                    422, "Parameter [id_archetype] not found"
                );
            }
            $id = $_GET['id_archetype'];
            if (!$archetype = $this->modelArchetype->getTupleById($id)) {
                $this->throwError(
                    422, "Archetype ID $id not found"
                );
            }
            $decklists = $this->modelPlayer->getDecklistsByIdArchetype($id);
            $this->content = SimpleJSON::encode($decklists, JSON_UNESCAPED_SLASHES);
        }

        public function getDecklistsByIdTournament () {
            if (!Core::checkRequiredGetVars('id_tournament')) {
                $this->throwError(
                    422, "Parameter [id_tournament] not found"
                );
            }
            $id = $_GET['id_tournament'];
            if (!$tournament = $this->modelTournament->getTupleById($id)) {
                $this->throwError(
                    422, "Tournament ID $id not found"
                );
            }
            $decklists = $this->modelPlayer->getDecklistsByIdTournament($id);
            $this->content = SimpleJSON::encode($decklists, JSON_UNESCAPED_SLASHES);
        }

        public function getDecklistsByIdPlayer () {
            if (!Core::checkRequiredGetVars('id_player')) {
                $this->throwError(
                    422, "Parameter [id_player] not found"
                );
            }
            $id = $_GET['id_player'];
            if (!$player = $this->modelPeople->getTupleById($id)) {
                $this->throwError(
                    422, "Player ID $id not found"
                );
            }
            $decklists = $this->modelPlayer->getDecklistsByIdPlayer($id);
            $this->content = SimpleJSON::encode($decklists, JSON_UNESCAPED_SLASHES);
        }

        public function getDecklistsByIdCard () {
            if (!Core::checkRequiredGetVars('id_card')) {
                $this->throwError(
                    422, "Parameter [id_card] not found"
                );
            }
            $id = $_GET['id_card'];
            if (!$card = $this->modelPeople->getTupleById($id)) {
                $this->throwError(
                    422, "Card ID $id not found"
                );
            }
            $decklists = $this->modelPlayer->getDecklistsByIdCard($id);
            $this->content = SimpleJSON::encode($decklists, JSON_UNESCAPED_SLASHES);
        }
    }
}