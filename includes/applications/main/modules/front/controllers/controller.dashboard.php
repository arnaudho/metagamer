<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\DefaultFrontController;
    use core\db\Query;
    use core\utils\StatsUtils;

    class dashboard extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelMatches;
        protected $modelArchetypes;
        protected $modelTournament;
        protected $modelFormat;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatches = new ModelMatch();
            $this->modelArchetypes = new ModelArchetype();
            $this->modelTournament = new ModelTournament();
            $this->modelFormat = new ModelFormat();
        }

        public function index () {
            $this->addContent("list_formats", $this->modelFormat->all());

            $format = array();
            $tournament = array();
            $dashboard_cond = null;
            if ($_GET['id_format'] && $format = $this->modelFormat->getTupleById($_GET['id_format'])) {
                $dashboard_cond = Query::condition()->andWhere("id_format", Query::EQUAL, $format['id_format']);
            }
            if ($format &&
                $_GET['id_tournament'] &&
                ($tournament = $this->modelTournament->one(
                    Query::condition()
                        ->andWhere("id_tournament", Query::EQUAL, $_GET['id_tournament'])
                        ->andWhere("id_format", Query::EQUAL, $format['id_format'])
                ))) {
                $dashboard_cond = Query::condition()->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament']);
            }
            if ($format && $dashboard_cond) {
                $this->addContent("list_tournaments", $this->modelTournament->all(
                    Query::condition()
                        ->andWhere("id_format", Query::EQUAL, $format['id_format'])
                ));
                $title = "Dashboard - " . $format['name_format'];
                if ($tournament) {
                    $title .= " - " . $tournament['name_tournament'];
                    $this->addContent("tournament", $tournament);
                }
                $this->setTitle($title);
                $this->addContent("format", $format);
                $data = array(
                    "count_tournaments" => $this->modelTournament->count($dashboard_cond),
                    "count_players" => $this->modelPlayer->countPlayers($dashboard_cond),
                    "count_matches" => $this->modelMatches->countMatches($dashboard_cond) / 2,
                    "count_wins" => $this->modelMatches->countWins($dashboard_cond) / 2
                );
                $data["percent"] = round(100 * $data['count_wins'] / $data['count_matches'], 2);
                $this->addContent("data", $data);

                $metagame = $this->modelPlayer->countArchetypes($dashboard_cond);
                $this->addContent("metagame", $metagame);
                if (empty($metagame)) {
                    $this->addMessage("No metagame data for selected format", self::MESSAGE_ERROR);
                }

                $order_archetypes = array();
                foreach ($metagame as $deck) {
                    $order_archetypes[] = $deck['id_archetype'];
                }

                $archetypes = $metagame;

                foreach ($archetypes as $key => $archetype) {
                    $winrate = $this->modelMatches->getFullWinrateByArchetypeId($archetype['id_archetype'], $dashboard_cond, $order_archetypes);
                    foreach ($winrate as $m => $matchup) {
                        // divide mirror count
                        if ($matchup['id_archetype'] == $archetype['id_archetype']) {
                            $winrate[$m]['count'] = ceil($matchup['count'] / 2);
                        }
                        $deviation = StatsUtils::getStandardDeviation($matchup['percent'], $matchup['count']);
                        $winrate[$m]['deviation_up'] = round($matchup['percent'] + $deviation);
                        if ($winrate[$m]['deviation_up'] > 100) {
                            $winrate[$m]['deviation_up'] = 100;
                        }
                        $winrate[$m]['deviation_down'] = round($matchup['percent'] - $deviation);
                        if ($winrate[$m]['deviation_down'] < 0) {
                            $winrate[$m]['deviation_down'] = 0;
                        }
                    }
                    $archetypes[$key]['winrates'] = $winrate;
                }
                $this->addContent("archetypes", $archetypes);
            } else {
                $this->setTitle("Dashboard");
            }
        }

        public function archetypes () {
            $archetypes = $this->modelArchetypes->getArchetypesRules();
            $this->addContent("archetypes", $archetypes);

            /*
             * // lists do not display archetye name anymore
            $this->addContent("list_formats", $this->modelFormat->all());
            $format = $this->modelFormat->getTupleById($_GET['id_format']);
            if ($format) {
                $this->setTitle("Dashboard - " . $format['name_format']);
                $this->addContent("format", $format);
                $archetypes = $this->modelArchetypes->getArchetypesGroupsByFormat($format['id_format']);
                $this->addContent("archetypes", $archetypes);
            }
*/
        }
    }
}