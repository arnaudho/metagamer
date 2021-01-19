<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelTournament extends BaseModel {

        CONST LEAGUE_TOURNAMENT_IDS = array(15128, 12133, 15143);
        CONST PT_TOURNAMENT_IDS = array(4090);

        public function __construct()
        {
            parent::__construct("tournaments", "id_tournament");
        }

        public function getTupleById($pId, $pFields = "*")
        {
            $res = Query::select($pFields, $this->table)
                ->join("formats", Query::JOIN_INNER, "formats.id_format = tournaments.id_format")
                ->andWhere($this->id, Query::EQUAL, $pId)
                ->limit(0, 1)
                ->execute($this->handler);
            if(!isset($res[0]))
                return null;
            return $res[0];
        }

        public function getProTournamentLabels () {
            $labels = $this->all(
                Query::condition()
                    ->orWhere("id_tournament", Query::IN, "(" . implode(", ", self::LEAGUE_TOURNAMENT_IDS) . ")", false)
                    ->orWhere("id_tournament", Query::IN, "(" . implode(", ", self::PT_TOURNAMENT_IDS) . ")", false)
                    ->order("date_tournament")
            );
            return $labels;
        }

        public function countPlayers ($pCondition = null) {
            if (!$pCondition) {
                $pCondition = Query::condition();
            }
            $players = Query::select("COUNT(1) AS count", "players")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andCondition($pCondition)
                ->execute();
            return $players[0]['count'];
        }

        public function getTournamentData ($pTournamentId) {
            $name = $this->getTupleById($pTournamentId, "name_tournament");
            $players = Query::count(
                "players",
                Query::condition()
                    ->andWhere("id_tournament", Query::EQUAL, $pTournamentId)
            );
            $matches = Query::select("COUNT(1) AS nb", "matches")
                ->join("players", Query::JOIN_INNER, "matches.id_player = players.id_player")
                ->andWhere("id_tournament", Query::EQUAL, $pTournamentId)
                ->execute();
            $data = array(
                "name_tournament" => $name['name_tournament'],
                "count_players"   => $players,
                "count_matches"   => $matches[0]['nb']
            );
            return $data;
        }

        public function allOrdered ($pCondition = null) {
            $cond = Query::condition();
            if ($pCondition) {
                $cond = clone $pCondition;
            }
            $cond->order("id_format", "DESC");
            return Query::select("tournaments.*, formats.*, COUNT(id_player) AS count_players", "formats")
                ->join($this->table, Query::JOIN_OUTER_LEFT, "tournaments.id_format = formats.id_format")
                ->join("players", Query::JOIN_OUTER_LEFT, "tournaments.id_tournament = players.id_tournament")
                ->andCondition($cond)
                ->order("formats.id_format DESC, tournaments.date_tournament ASC, tournaments.id_tournament")
                ->groupBy("tournaments.id_tournament")
                ->execute($this->handler);
        }

        public function searchTournamentsByName ($pName, $pCount = false, $pLimit = 10) {
            $q = Query::select(($pCount ? "COUNT(1) AS count" : "*"), $this->table)
                ->andWhere("tournaments.name_tournament", Query::LIKE, "'%" . $pName . "%'", false)
                ->order("tournaments.date_tournament", "DESC");
            if (!$pCount) {
                $q->limit(0, $pLimit);
            }
            $data = $q->execute($this->handler);
            return $pCount ? $data[0]['count'] : $data;
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
                ->from("player_card")
                ->andCondition(
                    Query::condition()
                        ->andWhere("id_player", Query::IN, $players, false)
                )
                ->execute($this->handler);
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