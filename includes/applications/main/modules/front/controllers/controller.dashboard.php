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
                "count_matches"     => $this->modelMatches->count(Query::condition()),
                "count_wins"        => $this->modelMatches->getValue("SUM(result_match)", Query::condition())
            );
            $data["percent"] = round(100*$data['count_wins']/$data['count_matches'], 2);
            $this->addContent("data", $data);

            $metagame = $this->modelPlayer->countArchetypesByTournamentId();
            $this->addContent("metagame", $metagame);

            $archetypes = $this->modelArchetypes->all();
            foreach ($archetypes as &$archetype) {
                $winrate = $this->modelMatches->getWinrateByArchetypeId($archetype['id_archetype']);
                foreach ($winrate as &$matchup) {
                    $deviation = StatsUtils::getStandardDeviation($matchup['percent'], $matchup['count']);
                    $matchup['deviation_up'] = round($matchup['percent'] + $deviation);
                    $matchup['deviation_down'] = round($matchup['percent'] - $deviation);
                }
                $archetype['winrates'] = $winrate;
            }
            $this->addContent("archetypes", $archetypes);

            return 0;
        }
    }
}