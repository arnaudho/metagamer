<?php
namespace app\api\controllers\front {

    use app\api\models\ModelArchetype;
    use app\api\models\ModelFormat;
    use app\api\models\ModelTournament;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;
    use core\db\Query;

    class archetype extends RestController
    {
        protected $modelFormat;
        protected $modelArchetype;
        protected $modelTournament;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelFormat = new ModelFormat();
            $this->modelArchetype = new ModelArchetype();
            $this->modelTournament = new ModelTournament();
            parent::__construct();
        }

        public function getArchetypeById () {
            if (!Core::checkRequiredGetVars('id_archetype')) {
                $this->throwError(
                    422, "Parameter [id_archetype] not found"
                );
            }
            $id = $_GET['id_archetype'];
            if (!$archetype = $this->modelArchetype->getArchetypeById($id)) {
                $this->throwError(
                    422, "Archetype ID $id not found"
                );
            }
            $this->content = SimpleJSON::encode($archetype, JSON_UNESCAPED_SLASHES);
        }

        public function getArchetypesByIdFormat () {
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
            // TODO QUICKFIX for ALPHA version 20/08
            // merge formats + only last 10 tournaments
            $ids_format = $this->modelFormat->getFormatsByIdFormat($id);
            $ids_tournament = $this->modelTournament->getLastTournaments(Query::condition()
                ->andWhere("tournaments.id_format", Query::IN, "(" . implode(",", $ids_format) . ")", false), 32, 10);

            $archetypes = $this->modelArchetype->getArchetypesDataByCond(
                Query::condition()
                    ->andWhere("tournaments.id_format", Query::IN, "(" . implode(",", $ids_format) . ")", false)
                    ->andWhere("tournaments.id_tournament", Query::IN, "(" . implode(",", $ids_tournament) . ")", false),
                true
            );
            $this->content = SimpleJSON::encode($archetypes, JSON_UNESCAPED_SLASHES);
        }
    }
}