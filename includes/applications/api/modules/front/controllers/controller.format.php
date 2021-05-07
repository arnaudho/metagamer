<?php
namespace app\api\controllers\front {

    use app\api\models\ModelFormat;
    use app\main\models\ModelTypeFormat;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;

    class format extends RestController
    {
        protected $modelFormat;
        protected $modelTypeFormat;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelFormat = new ModelFormat();
            $this->modelTypeFormat = new ModelTypeFormat();
            parent::__construct();
        }

        public function getFormatById () {
            if (!Core::checkRequiredGetVars('id_format')) {
                $this->throwError(
                    422, "Parameter [id_format] not found"
                );
            }
            $id = $_GET['id_format'];
            if (!$format = $this->modelFormat->getFormatById($id)) {
                $this->throwError(
                    422, "Format ID $id not found"
                );
            }
            $this->content = SimpleJSON::encode($format, JSON_UNESCAPED_SLASHES);
        }

        public function getFormatsByIdTypeFormat () {
            if (!Core::checkRequiredGetVars('id_type_format')) {
                $this->throwError(
                    422, "Parameter [id_type_format] not found"
                );
            }
            $id = $_GET['id_type_format'];
            if (!$this->modelTypeFormat->getTupleById($id)) {
                $this->throwError(
                    422, "Format type ID $id not found"
                );
            }
            $formats = $this->modelFormat->getFormatsByIdTypeFormat($id);
            $this->content = SimpleJSON::encode($formats, JSON_UNESCAPED_SLASHES);
        }
    }
}