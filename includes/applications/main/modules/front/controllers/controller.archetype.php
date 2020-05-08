<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPlayer;
    use app\main\src\MetagamerBot;
    use core\application\Core;
    use core\application\DefaultFrontController;
    use core\application\Go;
    use core\db\Query;
    use core\utils\StatsUtils;

    class archetype extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelMatch;
        protected $modelArchetype;
        protected $modelCard;
        protected $modelFormat;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelMatch = new ModelMatch();
            $this->modelArchetype = new ModelArchetype();
            $this->modelCard = new ModelCard();
            $this->modelFormat = new ModelFormat();
        }

        public function index () {
            $this->addContent("list_archetypes", $this->modelArchetype->all());
            $this->addContent("list_formats", $this->modelFormat->all());
            $analysis_cond = Query::condition();
            $format_cond = Query::condition();
            $archetype = array();
            $format = array();
            if (
                $_GET['id_archetype'] &&
                ($archetype = $this->modelArchetype->getTupleById($_GET['id_archetype'])) &&
                $_GET['id_format'] &&
                ($format = $this->modelFormat->getTupleById($_GET['id_format']))
            ) {
                $analysis_cond = Query::condition()
                    ->andWhere("id_archetype", Query::EQUAL, $archetype['id_archetype'])
                    ->andWhere("id_format", Query::EQUAL, $format['id_format']);
                $format_cond = Query::condition()
                    ->andWhere("id_format", Query::EQUAL, $format['id_format']);
            }
            if ($archetype && $format) {
                $this->addContent("archetype", $archetype);
                $this->addContent("format", $format);
                $cards = $this->modelCard->getPlayedCards($analysis_cond);
                // TODO : keep winrate even through card filters ?
                $stats = $this->modelMatch->getWinrateByArchetypeId($archetype['id_archetype'], $format_cond);
                $deviation = StatsUtils::getStandardDeviation($stats['winrate'], $stats['total']);
                $stats['deviation_up']   = $stats['winrate'] + $deviation;
                $stats['deviation_down'] = $stats['winrate'] - $deviation;
                $this->addContent("global", $stats);
                foreach ($cards as &$card) {
                    if ($card['avg_main'] > 0) {
                        $winrate = $this->modelMatch->getWinrateByArchetypeId(
                            $archetype['id_archetype'],
                            Query::condition()
                                ->andCondition($format_cond)
                                ->andWhere("id_card", Query::EQUAL, $card['id_card'])
                                ->andWhere("count_main", Query::UPPER, 0)
                        );
                        $card['winrate_main'] = $winrate['winrate'];
                        $card['total_main'] = $winrate['total'];
                        $deviation = StatsUtils::getStandardDeviation($winrate['winrate'], $winrate['total']);
                        $card['deviation_up_main']   = $card['winrate_main'] + $deviation;
                        $card['deviation_down_main'] = $card['winrate_main'] - $deviation;
                    }
                    if ($card['avg_side'] > 0) {
                        $winrate = $this->modelMatch->getWinrateByArchetypeId(
                            $archetype['id_archetype'],
                            Query::condition()
                                ->andCondition($format_cond)
                                ->andWhere("id_card", Query::EQUAL, $card['id_card'])
                                ->andWhere("count_side", Query::UPPER, 0)
                        );
                        $card['winrate_side'] = $winrate['winrate'];
                        $card['total_side'] = $winrate['total'];
                        $deviation = StatsUtils::getStandardDeviation($winrate['winrate'], $winrate['total']);
                        $card['deviation_up_side']   = $card['winrate_side'] + $deviation;
                        $card['deviation_down_side'] = $card['winrate_side'] - $deviation;
                    }
                }

                $count_players = $this->modelPlayer->countPlayers($analysis_cond);
                $this->addContent("count_players", $count_players);
                $this->addContent("cards", $cards);

            }
        }

        // TODO WIP
        public function evaluate () {
            if(!Core::$request_async || !isset($_POST['url'])) {
                Go::to404();
            }
            // evaluate given decklist
            $bot = new MetagamerBot("Decklist");
            $name_archetype = $bot->parsePlayer(0, $_POST['url'], false, false);
            $this->addContent("archetype", $name_archetype);
            return $name_archetype;
        }
    }
}