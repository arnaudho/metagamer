<?php
namespace app\api\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelTournament extends BaseModel
    {

        public function __construct()
        {
            parent::__construct("tournaments", "id_tournament");
        }

        // TODO handle tournament icons
        public function getTournamentById ($pIdTournament) {
            $data = Query::select(
                "tournaments.id_tournament, name_tournament, formats.id_format, name_format,
                    date_tournament, COUNT(DISTINCT id_player) AS count_players", $this->table)
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->join("players", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                ->andWhere("tournaments.id_tournament", Query::EQUAL, $pIdTournament)
                ->groupBy("tournaments.id_tournament")
                ->limit(0, 1)
                ->execute($this->handler);
            if (empty($data)) {
                return false;
            }
            return $data;
        }

        // TODO handle tournament icons
        public function getTournamentsByIdFormat ($pIdFormat) {
            $data = Query::select(
                "tournaments.id_tournament, name_tournament, date_tournament,
                    COUNT(DISTINCT players.id_player) AS count_players", $this->table)
                ->join("players", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("tournaments.id_format", Query::EQUAL, $pIdFormat)
                ->groupBy("tournaments.id_tournament")
                ->order("tournaments.date_tournament", "DESC")
                ->execute($this->handler);
            return $data;
        }

        public function getLastTournaments ($pLimit = 50) {
            $data = Query::select("tournaments.id_tournament, name_tournament, date_tournament,
                    formats.id_format, id_type_format, name_format,
                    COUNT(DISTINCT players.id_player) AS count_players", $this->table)
                ->join("players", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("formats", Query::JOIN_INNER, "formats.id_format = tournaments.id_format")
                ->groupBy("tournaments.id_tournament")
                ->order("tournaments.date_tournament", "DESC")
                ->limit(0, intval($pLimit))
                ->execute($this->handler);
            return $data;
        }
    }
}