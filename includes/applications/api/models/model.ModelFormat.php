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
                "formats.id_format, name_format, COUNT(DISTINCT tournaments.id_tournament) AS count_tournaments", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->andWhere("formats.id_format", Query::EQUAL, $pIdFormat)
                ->groupBy("formats.id_format")
                ->execute($this->handler);
            if (empty($data)) {
                return false;
            }
            return $data;
        }

        // TODO order by last tournament date ?
        public function getFormatsByIdTypeFormat($pIdTypeFormat)
        {
            $data = Query::select(
                "formats.id_format, name_format, COUNT(DISTINCT tournaments.id_tournament) AS count_tournaments", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->andWhere("formats.id_type_format", Query::EQUAL, $pIdTypeFormat)
                ->groupBy("formats.id_format")
                ->order("formats.id_format", "DESC")
                ->execute($this->handler);
            return $data;
        }
    }
}