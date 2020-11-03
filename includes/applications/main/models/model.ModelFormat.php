<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelFormat extends BaseModel
    {

        public function __construct()
        {
            parent::__construct("formats", "id_format");
        }

        public function allOrdered ($pCondition = null, $pFields = "*") {
            $cond = Query::condition();
            if ($pCondition) {
                $cond = clone $pCondition;
            }
            $cond->order("id_format", "DESC");
            return $this->all($cond, $pFields);
        }
    }
}