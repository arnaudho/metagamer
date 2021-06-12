<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelFormat extends BaseModel
    {
        CONST TYPE_FORMAT_STANDARD_ID = 1;
        CONST TYPE_FORMAT_HISTORIC_ID = 2;
        CONST MAPPING_TYPE_FORMAT = array(
            self::TYPE_FORMAT_STANDARD_ID => "standard",
            self::TYPE_FORMAT_HISTORIC_ID => "historic"
        );

        public $typeFormat;

        public function __construct($pIdTypeFormat = null)
        {
            parent::__construct("formats", "id_format");
            $this->typeFormat = $pIdTypeFormat;
        }

        public function getArchetypeRules () {
            return ModelArchetype::getArchetypesRules($this->typeFormat);
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
            return $data[0];
        }

        public function allOrdered ($pCondition = null, $pFields = "*") {
            $cond = Query::condition();
            if ($pCondition) {
                $cond = clone $pCondition;
            }
            $cond->order("id_format", "DESC");
            return $this->all($cond, $pFields);
        }

        // TODO order by most recent tournament ?
        public function searchFormatByName ($pName, $pCount = false, $pLimit = 10) {
            $q = Query::select(($pCount ? "COUNT(1) AS count" : "*"), $this->table)
                ->andWhere("formats.name_format", Query::LIKE, "'%" . $pName . "%'", false)
                ->order("formats.id_format", "DESC");
            if (!$pCount) {
                $q->limit(0, $pLimit);
            }
            $data = $q->execute($this->handler);
            return $pCount ? $data[0]['count'] : $data;
        }
    }
}