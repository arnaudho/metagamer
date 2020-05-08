<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelCard extends BaseModel
    {
        protected $tablePlayerCards;

        public function __construct()
        {
            $this->tablePlayerCards = "player_card";
            parent::__construct("cards", "id_card");
        }

        public function insertCards ($pCards) {
            if (empty($pCards)) {
                return false;
            }
            return Query::execute("INSERT IGNORE INTO " . $this->table . "(name_card) VALUES ('" . implode("'), ('", $pCards) . "')");
        }

        public function insertPlayerCards ($pCards = array()) {
            return Query::insertMultiple($pCards)
                ->into($this->tablePlayerCards)
                ->execute($this->handler);
        }
    }
}