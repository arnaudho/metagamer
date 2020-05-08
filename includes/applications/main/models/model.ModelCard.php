<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelCard extends BaseModel
    {
        protected $tablePlayerCards;
        protected $modelPlayer;

        public function __construct()
        {
            $this->tablePlayerCards = "player_card";
            $this->modelPlayer = new ModelPlayer();
            parent::__construct("cards", "id_card");
        }

        public function getPlayedCards ($pCondition = null) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $count_players = $this->modelPlayer->countPlayers($pCondition);
            $data = Query::select("cards.id_card, name_card, ROUND(SUM(count_main)/$count_players, 2) AS avg_main, ROUND(SUM(count_side)/$count_players, 2) AS avg_side", $this->tablePlayerCards)
                ->join("players", Query::JOIN_INNER, "players.id_player = player_card.id_player")
                ->join($this->table, Query::JOIN_INNER, "cards.id_card = player_card.id_card")
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                ->andCondition($pCondition)
                ->groupBy("cards.id_card")
                ->order("name_card")
                ->execute($this->handler);
            return $data;
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