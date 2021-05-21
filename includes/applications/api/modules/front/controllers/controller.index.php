<?php
namespace app\api\controllers\front
{
    use app\main\models\ModelArchetype;
    use app\main\models\ModelCard;
    use app\main\models\ModelFormat;
    use app\main\models\ModelMatch;
    use app\main\models\ModelPeople;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use core\application\Core;
    use core\application\RestController;
    use core\data\SimpleJSON;
    use core\db\Query;
    use core\utils\StatsUtils;

    class index extends RestController
    {
        protected $modelCard;
        protected $modelFormat;
        protected $modelPlayer;
        protected $modelPeople;
        protected $modelMatches;
        protected $modelTournament;
        protected $modelArchetype;

        public function __construct()
        {
            $this->format = self::FORMAT_JSON;
            $this->modelCard = new ModelCard();
            $this->modelFormat = new ModelFormat();
            $this->modelPlayer = new ModelPlayer();
            $this->modelPeople = new ModelPeople();
            $this->modelMatches = new ModelMatch();
            $this->modelTournament = new ModelTournament();
            $this->modelArchetype = new ModelArchetype();
            parent::__construct();
        }

        public function matrix () {
            $data = array();
            $matrix_cond = null;
            $format = null;
            $tournament = null;

            if (
                Core::checkRequiredGetVars('id_tournament') &&
                $tournament = $this->modelTournament->getTupleById($_GET['id_tournament'])
            ) {
                $matrix_cond = Query::condition()
                    ->andWhere("tournaments.id_tournament", Query::EQUAL, $tournament['id_tournament']);
                $data = $tournament;
            } elseif (
                Core::checkRequiredGetVars('id_format') &&
                $format = $this->modelFormat->getTupleById($_GET['id_format'])
            ) {
                $matrix_cond = Query::condition()
                    ->andWhere("tournaments.id_format", Query::EQUAL, $format['id_format']);
                $data = $format;
                // TODO -- add limit last 2 weeks
            } else {
                $this->throwError(
                    422, "Missing parameter [id_format|id_tournament] or entity not found"
                );
            }
            $size = 10;
            if (Core::checkRequiredGetVars('size') && $_GET['size'] > 0 && $_GET['size'] < 20) {
                $size = $_GET['size'];
            }

            $data['count_players'] = $this->modelPlayer->countPlayers($matrix_cond);
            $data['count_matches'] = round($this->modelMatches->countMatches($matrix_cond) / 2);
            $metagame = $this->modelPlayer->countArchetypes($matrix_cond);
            $other = $this->modelArchetype->getTupleById(ModelArchetype::ARCHETYPE_OTHER_ID);


            // limit matrix size
            $count = 0;
            $count_other = 0;
            $archetypes = array();
            $order_archetypes = array();
            $other_archetypes = array();

            $header = array("Winrate vs. metagame");
            foreach ($metagame as $archetype) {
                $order_archetypes[] = $archetype['id_archetype'];
                // if count < size OR is 'Other' :
                if (++$count < $size || $archetype['id_archetype'] == $other['id_archetype']) {
                    // add in order_archetypes AND archetypes
                    $archetypes[] = $archetype;
                } else {
                    $other_archetypes[$archetype['id_archetype']] = $archetype['id_archetype'];
                    $count_other += $archetype['count'];
                }
            }
            unset($metagame);

            // add correct count to 'Other' archetype
            if ($count_other > 0) {
                $other_id = null;
                foreach ($archetypes as $key => $archetype) {
                    if ($archetype['id_archetype'] == ModelArchetype::ARCHETYPE_OTHER_ID) {
                        $other_id = $key;
                        break;
                    }
                }
                if (is_null($other_id)) {
                    // TODO fetch 'Other' for current id_type_format
                    $other = $this->modelArchetype->getTupleById(ModelArchetype::ARCHETYPE_OTHER_ID);
                    $other_id = 999;
                    $archetypes[$other_id] = $other;
                    $order_archetypes[] = $other['id_archetype'];
                }

                $archetypes[$other_id]['count'] += $count_other;
                $archetypes[$other_id]['percent'] = round(100 * $archetypes[$other_id]['count'] / $data['count_players'], 1);
            }

            foreach ($archetypes as $key => $archetype) {
                // get winrates
                $winrate = $this->modelMatches->getFullWinrateByArchetypeId($archetype['id_archetype'], $matrix_cond, $order_archetypes, $other_archetypes);
                foreach ($winrate as $m => $matchup) {
                    // divide mirror count
                    if ($matchup['id_archetype'] == $archetype['id_archetype']) {
                        $winrate[$m]['count'] = ceil($matchup['count'] / 2);
                    }
                    if (isset($matchup['percent']) && isset($matchup['count']) && $matchup['count'] != 0) {
                        // League Weekend : count x2
                        $deviation = StatsUtils::getStandardDeviation($matchup['percent'], $matchup['count'], StatsUtils::Z95);
                        $winrate[$m]['deviation'] = $deviation;
                        $winrate[$m]['deviation_up'] = round($matchup['percent'] + $deviation);
                        if ($winrate[$m]['deviation_up'] > 100) {
                            $winrate[$m]['deviation_up'] = 100;
                        }
                        $winrate[$m]['deviation_down'] = round($matchup['percent'] - $deviation);
                        if ($winrate[$m]['deviation_down'] < 0) {
                            $winrate[$m]['deviation_down'] = 0;
                        }
                    } else {
                        $winrate[$m]['deviation_down'] = 0;
                        $winrate[$m]['deviation_up'] = 100;
                    }
                }
                $archetypes[$key]['winrates'] = $winrate;
                $header[] = "vs. " . $archetype['name_archetype'];
            }

            $data['header'] = $header;
            $data['archetypes'] = $archetypes;

            if ($format) {
                $data['tournaments'] = $this->modelTournament->all($matrix_cond, "id_tournament, name_tournament, date_tournament");
            }

            $this->content = SimpleJSON::encode($data, JSON_UNESCAPED_SLASHES);
        }

        public function search () {
            $results = array();
            $item = 'all';
            if (!Core::checkRequiredGetVars('term')) {
                $this->throwError(
                    422, "Parameter [term] not found"
                );
            }
            $term = trim($_GET['term']);
            // if type item specified
            if (Core::checkRequiredGetVars('item')) {
                $item = $_GET['item'];
            }
            $limit_results = Core::checkRequiredGetVars('results') ? $_GET['results'] : ($item == 'all' ? 0 : 10);
            
            if ($item == 'all' || $item == 'archetype') {
                $archetypes = $this->modelPlayer->searchPlayerByDecklistName($term, false, $limit_results);
                $count_archetypes = $this->modelPlayer->searchPlayerByDecklistName($term, true);
                $results['archetype'] = array(
                    "label" => "Archetypes",
                    "elements" => $archetypes,
                    "count" => $count_archetypes
                );
            }
            if ($item == 'all' || $item == 'format') {
                $formats = $this->modelFormat->searchFormatByName($term, false, $limit_results);
                $count_formats= $this->modelFormat->searchFormatByName($term, true);
                $results['format'] = array(
                    "label" => "Formats",
                    "elements" => $formats,
                    "count" => $count_formats
                );
            }
            if ($item == 'all' || $item == 'player') {
                $players = $this->modelPeople->searchPeopleByName($term, false, $limit_results);
                $count_players = $this->modelPeople->searchPeopleByName($term, true);
                $results['player'] = array(
                    "label" => "Players",
                    "elements" => $players,
                    "count" => $count_players
                );
            }
            if ($item == 'all' || $item == 'card') {
                $cards = $this->modelCard->searchCardsByName($term, false, $limit_results);
                $count_cards = $this->modelCard->searchCardsByName($term, true);
                $results['card'] = array(
                    "label" => "Cards",
                    "elements" => $cards,
                    "count" => $count_cards
                );
            }
            if ($item == 'all' || $item == 'tournament') {
                $tournaments = $this->modelTournament->searchTournamentsByName($term, $limit_results);
                $count_tournaments = $this->modelTournament->count(Query::condition()->andWhere("name_tournament", Query::LIKE, "%" . $term . "%"));

                foreach ($tournaments as $key => $tournament) {
                    $tournaments[$key]['date_tournament'] = date('j M Y', strtotime($tournament['date_tournament']));
                }
                $results['tournament'] = array(
                    "label" => "Tournaments",
                    "elements" => $tournaments,
                    "count" => $count_tournaments
                );
            }
            if ($item != 'all') {
                $results = $results[$item];
            }

            $this->content = SimpleJSON::encode($results, JSON_UNESCAPED_SLASHES);
        }
    }
}