<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\DefaultController;
    use core\db\Query;
    use core\utils\StatsUtils;

    class dashboard extends DefaultController
    {
        protected $modelPlayer;
        protected $modelMatches;
        protected $modelArchetypes;
        protected $modelTournament;
        protected $modelFormat;

        public function __construct()
        {
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatches = new ModelMatch();
            $this->modelArchetypes = new ModelArchetype();
            $this->modelTournament = new ModelTournament();
            $this->modelFormat = new ModelFormat();
        }

        public function index () {
            $this->addContent("list_formats", $this->modelFormat->all());

            $format = $this->modelFormat->getTupleById($_GET['id_format']);
            if ($format) {
                $this->setTitle("Dashboard - " . $format['name_format']);
                $this->addContent("format", $format);

                $format_cond = Query::condition()->andWhere("id_format", Query::EQUAL, $format['id_format']);
                $data = array(
                    "count_tournaments" => $this->modelTournament->count($format_cond),
                    "count_players" => $this->modelPlayer->countPlayers($format_cond),
                    "count_matches" => $this->modelMatches->countMatches($format_cond) / 2,
                    "count_wins" => $this->modelMatches->countWins($format_cond) / 2
                );
                $data["percent"] = round(100 * $data['count_wins'] / $data['count_matches'], 2);
                $this->addContent("data", $data);

                $metagame = $this->modelPlayer->countArchetypes($format_cond);
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
                    $winrate = $this->modelMatches->getWinrateByArchetypeId($archetype['id_archetype'], $format_cond, $order_archetypes);
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
            $this->addContent("list_formats", $this->modelFormat->all());
            $format = $this->modelFormat->getTupleById($_GET['id_format']);
            if ($format) {
                $this->setTitle("Dashboard - " . $format['name_format']);
                $this->addContent("format", $format);
                $archetypes = $this->modelArchetypes->getArchetypesGroupsByFormat($format['id_format']);
                $this->addContent("archetypes", $archetypes);
            }

        }
    }
}