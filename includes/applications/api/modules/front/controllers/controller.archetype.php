<?php
namespace app\api\controllers\front {

    use app\api\models\ModelArchetype;
    use app\main\models\ModelFormat;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;

    class archetype extends RestController
    {
        protected $modelFormat;
        protected $modelArchetype;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelFormat = new ModelFormat();
            $this->modelArchetype = new ModelArchetype();
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
            $archetypes = $this->modelArchetype->getArchetypesByIdFormat($id);
            $this->content = SimpleJSON::encode($archetypes, JSON_UNESCAPED_SLASHES);
        }
    }
}