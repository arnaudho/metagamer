<?php
namespace app\main\models {

    use core\application\BaseModel;

    class ModelFormat extends BaseModel
    {

        public function __construct()
        {
            parent::__construct("formats", "id_format");
        }
    }
}