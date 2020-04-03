<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelTournament extends BaseModel {

        public function __construct()
        {
            parent::__construct("tournaments", "id_tournament");
        }

        public function getTournamentData ($pTournamentId) {
            $name = $this->getTupleById($pTournamentId, "name_tournament");
            $players = Query::select("id_player", "players")
                ->andWhere("id_tournament", Query::EQUAL, $pTournamentId)
                ->execute();
            $data = array(
                "name_tournament" => $name['name_tournament'],
                "count_players"   => count($players)
            );
            $id_players = array_column($players, 'id_player');
            $id_players = "('" . implode("', '", $id_players) . "')";
            $matches = Query::count(
                "matches",
                Query::condition()->andWhere(
                    "id_player", Query::IN, $id_players, false
                )
            );
            $data['count_matches'] = $matches;
            return $data;
        }

        /**
         * Delete tournament data, players & matches
         * @param $pIdTournament
         * @return array|bool|resource
         */
        public function resetTournamentDataById ($pIdTournament) {
            $id_tournament = $this->one(Query::condition()->andWhere("id_tournament", Query::EQUAL, $pIdTournament), "id_tournament");
            if (!$id_tournament) {
                return false;
            }
            $id_tournament = $id_tournament["id_tournament"];
            $res = Query::select("id_player", "players")->andWhere("id_tournament", Query::EQUAL, $id_tournament)->execute($this->handler);
            $id_players = array();
            foreach ($res as $player) {
                $id_players[] = $player['id_player'];
            }
            $players = "('" . implode("', '", $id_players) . "')";
            Query::delete()
                ->from("matches")
                ->andCondition(
                    Query::condition()
                        ->orWhere("id_player", Query::IN, $players, false)
                        ->orWhere("opponent_id_player", Query::IN, $players, false)
                )->execute($this->handler);
            Query::delete()
                ->from("players")
                ->andCondition(
                    Query::condition()
                        ->andWhere("id_player", Query::IN, $players, false)
                )
                ->execute($this->handler);
            return Query::delete()->from($this->table)->andWhere("id_tournament", Query::EQUAL, $pIdTournament)->execute($this->handler);
        }
    }
}