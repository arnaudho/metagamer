<?php
namespace app\api\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelFormat extends BaseModel
    {

        public function __construct()
        {
            parent::__construct("formats", "id_format");
        }

        // TODO QUICKFIX for ALPHA version 20/08
        public function getFormatsByIdFormat ($pIdFormat) {
            $ids_format = array();
            if ($format = $this->getTupleById($pIdFormat)) {
                $ids_format = array($pIdFormat);
                if (preg_match('/(.*) \- week \d/i', $format['name_format'], $name_format)) {
                    $name_format = $name_format[1];
                    $data = $this->all(
                        Query::condition()
                            ->andWhere("name_format", Query::LIKE, "'" . $name_format . "%'", false),
                        "id_format"
                    );
                    foreach ($data as $item) {
                        $ids_format[] = $item['id_format'];
                    }
                }
            }
            return $ids_format;
        }

        public function getLastFormatIdByArchetypeId ($pIdArchetype) {
            $data = Query::select("id_format", "players")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("id_archetype", Query::EQUAL, $pIdArchetype)
                ->order("date_tournament", "DESC")
                ->limit(0, 1)
                ->execute($this->handler);
            if (empty($data)) {
                return false;
            }
            return $data[0]['id_format'];
        }

        public function getFormatById($pIdFormat)
        {
            $data = Query::select(
                "formats.id_format, name_format, COUNT(DISTINCT tournaments.id_tournament) AS count_tournaments,
                id_type_format, MIN(date_tournament) AS min_date, MAX(date_tournament) AS max_date", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->andWhere("formats.id_format", Query::EQUAL, $pIdFormat)
                ->groupBy("formats.id_format")
                ->execute($this->handler);
            if (empty($data)) {
                return false;
            }
            return $data[0];
        }

        public function allWithTournamentsData($pCond = null, $pFields = "*")
        {
            $cond = Query::condition();
            if ($pCond) {
                $cond = clone $pCond;
            }
            $data = Query::select(
                $pFields, $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->andCondition($cond)
                ->groupBy("formats.id_format")
                ->order("min_date", "DESC")
                ->execute($this->handler);
            return $data;
        }

        public function getFormatsByIdTypeFormat($pIdTypeFormat)
        {
            return $this->allWithTournamentsData(
                Query::condition()
                    ->andWhere("formats.id_type_format", Query::EQUAL, $pIdTypeFormat),
                "formats.id_format, name_format, COUNT(DISTINCT tournaments.id_tournament) AS count_tournaments,
                id_type_format, MIN(date_tournament) AS min_date, MAX(date_tournament) AS max_date"
            );
        }
    }
}