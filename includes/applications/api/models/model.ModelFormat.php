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

        public function getFormatById($pIdFormat)
        {
            $data = Query::select(
                "formats.id_format, name_format, COUNT(DISTINCT tournaments.id_tournament) AS count_tournaments,
                MIN(date_tournament) AS min_date, MAX(date_tournament) AS max_date", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->andWhere("formats.id_format", Query::EQUAL, $pIdFormat)
                ->groupBy("formats.id_format")
                ->execute($this->handler);
            if (empty($data)) {
                return false;
            }
            return $data;
        }

        public function all($pCond = null, $pFields = "*")
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
            return $this->all(
                Query::condition()
                    ->andWhere("formats.id_type_format", Query::EQUAL, $pIdTypeFormat),
                "formats.id_format, name_format, COUNT(DISTINCT tournaments.id_tournament) AS count_tournaments,
                MIN(date_tournament) AS min_date, MAX(date_tournament) AS max_date"
            );
        }
    }
}