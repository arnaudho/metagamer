<?php
namespace app\api\controllers\front {

    use app\api\models\ModelFormat;
    use app\api\models\ModelTournament;
    use app\main\models\ModelArchetype;
    use app\main\models\ModelPlayer;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;
    use core\db\Query;

    class tournament extends RestController
    {
        protected $modelFormat;
        protected $modelPlayer;
        protected $modelTournament;
        protected $modelArchetype;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelFormat = new ModelFormat();
            $this->modelPlayer = new ModelPlayer();
            $this->modelTournament = new ModelTournament();
            $this->modelArchetype = new ModelArchetype();
            parent::__construct();
        }

        public function getTournamentById () {
            if (!Core::checkRequiredGetVars('id_tournament')) {
                $this->throwError(
                    422, "Parameter [id_tournament] not found"
                );
            }
            $id = $_GET['id_tournament'];
            if (!$tournament = $this->modelTournament->getTournamentById($id)) {
                $this->throwError(
                    422, "Tournament ID $id not found"
                );
            }
            $tournament = $tournament[0];

            $metagame_cond = Query::condition()
                ->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament']);
            $metagame = $this->modelPlayer->countArchetypes($metagame_cond);

            $metagame = $this->round_metagame($metagame, 10);
            $tournament['metagame'] = $metagame;
            $this->content = SimpleJSON::encode($tournament, JSON_UNESCAPED_SLASHES);
        }

        public function getTournamentsByIdFormat () {
            if (!Core::checkRequiredGetVars('id_format')) {
                $this->throwError(
                    422, "Parameter [id_format] not found"
                );
            }
            $id = $_GET['id_format'];
            if (!$this->modelFormat->getTupleById($id)) {
                $this->throwError(
                    422, "Format ID $id not found"
                );
            }
            $tournaments = $this->modelTournament->getTournamentsByIdFormat($id);
            $this->content = SimpleJSON::encode($tournaments, JSON_UNESCAPED_SLASHES);
        }

        public function getLastTournaments () {
            $tournaments = $this->modelTournament->getLastTournaments(50);

            $this->content = SimpleJSON::encode($tournaments, JSON_UNESCAPED_SLASHES);
        }

        private function round_metagame ($pMetagame, $pMaxArchetypes = 10) {
            $other_id = null;
            $count_archetypes = 1;
            $metagame = array();
            $sum_other = 0;
            $percent_other = 0;
            foreach ($pMetagame as $key => $archetype) {
                if ($archetype['id_archetype'] == ModelArchetype::ARCHETYPE_OTHER_ID) {
                    $other_id = $key;
                } else {
                    if ($count_archetypes < $pMaxArchetypes) {
                        $metagame[] = $archetype;
                        $count_archetypes++;
                        $percent_other += $archetype['percent'];
                    } else {
                        $sum_other += $archetype['count'];
                    }
                }
            }
            if ($sum_other > 0) {
                if (is_null($other_id)) {
                    // TODO fetch other for current id_type_format
                    $other = $this->modelArchetype->getTupleById(ModelArchetype::ARCHETYPE_OTHER_ID);
                    $other_id = -1;
                    $pMetagame[$other_id] = $other;
                }
                $pMetagame[$other_id]['count'] += $sum_other;
                $pMetagame[$other_id]['percent'] = 100 - $percent_other;
                if ($other_id) {
                    $metagame[] = $pMetagame[$other_id];
                }
            }
            return $metagame;
        }
    }
}