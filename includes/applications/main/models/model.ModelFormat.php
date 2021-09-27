<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\db\Query;

    class ModelFormat extends BaseModel
    {
        CONST TYPE_FORMAT_STANDARD_ID = 1;
        CONST TYPE_FORMAT_HISTORIC_ID = 2;
        CONST TYPE_FORMAT_LIMITED_ID = 3;
        CONST TYPE_FORMAT_STANDARD_2022_ID = 4;
        CONST MAPPING_TYPE_FORMAT = array(
            self::TYPE_FORMAT_STANDARD_ID => "standard",
            self::TYPE_FORMAT_HISTORIC_ID => "historic",
            self::TYPE_FORMAT_LIMITED_ID => "limited",
            self::TYPE_FORMAT_STANDARD_2022_ID => "standard_2022",
        );

        public $typeFormat;

        public function __construct($pIdTypeFormat = null)
        {
            parent::__construct("formats", "id_format");
            $this->typeFormat = $pIdTypeFormat;
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

        public function getLastFormatByIdTypeFormat ($pIdTypeFormat) {
            $last_format = Query::select("*", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->andWhere("id_type_format", Query::EQUAL, $pIdTypeFormat)
                ->order("date_tournament", "DESC")
                ->limit(0, 1)
                ->execute($this->handler);
            return $last_format[0];
        }

        public function getArchetypeRules () {
            return ModelArchetype::getArchetypesRules($this->typeFormat);
        }

        public function getFormatById($pIdFormat)
        {
            $data = Query::select(
                "formats.id_format, name_format, id_type_format, COUNT(DISTINCT tournaments.id_tournament) AS count_tournaments,
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

        public function allWithDates ($pCondition = null, $pLimit = null) {
            $cond = Query::condition();
            if ($pCondition) {
                $cond = clone $pCondition;
            }
            $q = Query::select("formats.*, name_type_format, DATE_FORMAT(MIN(date_tournament), '%d/%m') AS date_min,
                    DATE_FORMAT(MAX(date_tournament), '%d/%m') AS date_max", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->join("type_format", Query::JOIN_INNER, "type_format.id_type_format = formats.id_type_format")
                ->andCondition($cond)
                ->groupBy("id_format")
                ->order("MAX(date_tournament)", "DESC");
            if ($pLimit) {
                $q->limit(0, intval($pLimit));
            }
            $data = $q->execute($this->handler);
            return $data;
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