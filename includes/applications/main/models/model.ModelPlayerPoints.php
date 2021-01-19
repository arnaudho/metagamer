<?php
namespace app\main\models {

    use core\application\BaseModel;

    class ModelPlayerPoints extends BaseModel
    {

        public function __construct()
        {
            parent::__construct("player_points", "id_player");
        }
    }
}