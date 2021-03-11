<?php
namespace app\main\models {

    use core\application\BaseModel;

    class ModelTypeFormat extends BaseModel {

        public function __construct()
        {
            parent::__construct("type_format", "id_type_format");
        }
    }
}