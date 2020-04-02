<?php
namespace app\main\models {

    use core\application\BaseModel;

    class ModelPeople extends BaseModel {

        public function __construct()
        {
            parent::__construct("people", "id_people");
        }
    }
}