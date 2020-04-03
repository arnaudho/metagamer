<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
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

        public function __construct()
        {
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatches = new ModelMatch();
            $this->modelArchetypes = new ModelArchetype();
            $this->modelTournament = new ModelTournament();
        }

        public function index () {
            $data = array(
                "count_tournaments" => $this->modelTournament->count(Query::condition()),
                "count_players"     => $this->modelPlayer->count(Query::condition()),
                "count_matches"     => $this->modelMatches->count(Query::condition())/2,
                "count_wins"        => $this->modelMatches->getValue("SUM(result_match)", Query::condition())/2
            );
            $data["percent"] = round(100*$data['count_wins']/$data['count_matches'], 2);
            $this->addContent("data", $data);

            $metagame = $this->modelPlayer->countArchetypes();
            $this->addContent("metagame", $metagame);

            $order_archetypes = array();
            foreach ($metagame as $deck) {
                $order_archetypes[] = $deck['id_archetype'];
            }

            $archetypes = $metagame;

            foreach ($archetypes as &$archetype) {
                $winrate = $this->modelMatches->getWinrateByArchetypeId($archetype['id_archetype'], null, $order_archetypes);
                foreach ($winrate as &$matchup) {
                    $deviation = StatsUtils::getStandardDeviation($matchup['percent'], $matchup['count']);
                    $matchup['deviation_up'] = round($matchup['percent'] + $deviation);
                    if ($matchup['deviation_up'] > 100) {
                        $matchup['deviation_up'] = 100;
                    }
                    $matchup['deviation_down'] = round($matchup['percent'] - $deviation);
                    if ($matchup['deviation_down'] < 0) {
                        $matchup['deviation_down'] = 0;
                    }
                }
                $archetype['winrates'] = $winrate;
            }
            $this->addContent("archetypes", $archetypes);
            return 0;
        }
    }
}