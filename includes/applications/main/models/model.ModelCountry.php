<?php
namespace app\main\models {

    use core\application\BaseModel;

    class ModelCountry extends BaseModel {

        CONST COUNTRY_ID_FRANCE = "fr";

        public function __construct()
        {
            parent::__construct("countries", "alpha_2_country");
        }
    }
}