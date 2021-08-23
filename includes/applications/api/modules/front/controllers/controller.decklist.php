<?php
namespace app\api\controllers\front {

    use app\api\models\ModelFormat;
    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelPeople;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use app\main\models\ModelTypeFormat;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;
    use core\db\Query;

    class decklist extends RestController
    {
        protected $modelCard;
        protected $modelPlayer;
        protected $modelPeople;
        protected $modelFormat;
        protected $modelArchetype;
        protected $modelTournament;
        protected $modelTypeFormat;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelCard = new ModelCard();
            $this->modelPlayer = new ModelPlayer();
            $this->modelPeople = new ModelPeople();
            $this->modelFormat = new ModelFormat();
            $this->modelArchetype = new ModelArchetype();
            $this->modelTournament = new ModelTournament();
            $this->modelTypeFormat = new ModelTypeFormat();
            parent::__construct();
        }

        public function getDecklistById () {
            if (!Core::checkRequiredGetVars('id_decklist')) {
                $this->throwError(
                    422, "Parameter [id_decklist] not found"
                );
            }
            $id = $_GET['id_decklist'];
            if (!$decklist = $this->modelPlayer->getDecklistsByCondition(Query::condition()->andWhere("players.id_player", Query::EQUAL, $id))) {
                $this->throwError(
                    422, "Decklist ID $id not found"
                );
            }
            $this->content = SimpleJSON::encode($decklist[0], JSON_UNESCAPED_SLASHES);
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
            // TODO QUICKFIX for ALPHA version 20/08
            $id_type_format = null;
            if (
                isset($_GET['format_type']) &&
                $type_format = $this->modelTypeFormat->getTupleById($_GET['format_type'])
            ) {
                $id_type_format = $_GET['format_type'];
            }
            // limit decklists to last format group
            $last_format_id = $this->modelFormat->getLastFormatIdByArchetypeId($id, $id_type_format);
            $ids_format = $this->modelFormat->getFormatsByIdFormat($last_format_id);
            $decklists = $this->modelPlayer->getDecklistsByCondition(
                Query::condition()
                    ->andWhere("players.id_archetype", Query::EQUAL, $id)
                    ->andWhere("tournaments.id_format", Query::IN, "(" . implode(",", $ids_format) . ")", false),
                false);
            // do not filter on decklists by archetype
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
            $decklists = $this->modelPlayer->getDecklistsByCondition(Query::condition()->andWhere("players.id_tournament", Query::EQUAL, $tournament['id_tournament']));
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
            $decklists = $this->modelPlayer->getDecklistsByCondition(Query::condition()->andWhere("people.id_people", Query::EQUAL, $id));
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