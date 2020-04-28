<?php
namespace app\main\src;

use app\main\models\ModelArchetype;
use app\main\models\ModelMatch;
use app\main\models\ModelPeople;
use app\main\models\ModelPlayer;
use app\main\models\ModelTournament;
use core\db\Query;

class MetagamerBot extends BotController
{
    CONST UPLOADS = 'files/uploads/';
    CONST BYE = "(Bye, or no result found)";
    CONST MAPPING_PAIRINGS = array(
        1 => "table",
        2 => "player",
        3 => "points",
        4 => "opponent"
    );
    CONST MAPPING_DECKLISTS = array(
        1 => "arenaid",
        2 => "discordid",
        3 => "url"
    );
    CONST MAPPING_HISTORY = array(
        1 => "round",
        2 => "playerid",
        3 => "result"
    );

    public $tournament;
    protected $modelPlayer;

    public function __construct($pName)
    {
        $this->modelPlayer = new ModelPlayer();
        parent::__construct($pName);
    }

    /*
     * Reevaluate archetypes for a given tournament
     */
    public function evaluateArchetypes ($pUrl) {
        $data = $this->callUrl($pUrl);
        if (empty($data)) {
            trace_r("PARSING ERROR : URL " . $pUrl . " not found");
            return false;
        }

        // get tournament name
        preg_match_all('/<h1[^>]*>([^<]*)<.*Submitted decklists.*<\/h1>/Umis', $data, $output_array);
        $name_tournament = trim($output_array[1][0]);
        if (empty($name_tournament)) {
            trace_r("Tournament name not found");
            return false;
        }

        $mTournament = new ModelTournament();

        if (!$tournament = $mTournament->one(Query::condition()->andWhere("name_tournament", Query::EQUAL, $name_tournament))) {
            trace_r("Tournament does not exist");
            return false;
        }
        $this->tournament = $tournament['id_tournament'];

        $decklists = array();
        preg_match_all('/<tr[^>]*>[^<]*<td>([^<]*)<\/td>[^<]*<td>([^<]*)<\/td>[^<]*<td><a[^>]*href="([^"]*)"[^>]*>[^<]*<\/a><\/td>[^<]*<\/tr>/Usi', $data, $output_array);
        unset($output_array[0]);
        foreach ($output_array as $key => $element) {
            foreach ($element as $num => $player) {
                $decklists[$num][self::MAPPING_DECKLISTS[$key]] = $player;
            }
        }

        // update players archetypes
        foreach ($decklists as $decklist) {
            $id_player = $this->modelPlayer->getPlayerIdByTournamentIdArenaId($this->tournament, $decklist['arenaid']);
            if ($id_player) {
                $this->parsePlayer($id_player, $decklist['url'], false);
            } else {
                trace_r("WARNING - player " . $decklist['arenaid'] . " not found for tournament #" . $this->tournament);
            }
        }
        return true;
    }

    public function parseDecklists ($pUrl, $pIdFormat) {
        $data = $this->callUrl($pUrl);
        if (empty($data)) {
            trace_r("PARSING ERROR : URL " . $pUrl . " not found");
            return false;
        }

        // get tournament name
        preg_match_all('/<h1[^>]*>([^<]*)<.*Submitted decklists.*<\/h1>/Umis', $data, $output_array);
        $name_tournament = trim($output_array[1][0]);
        if (empty($name_tournament)) {
            trace_r("Tournament name not found");
            return false;
        }

        $mTournament = new ModelTournament();

        if ($mTournament->one(Query::condition()->andWhere("name_tournament", Query::EQUAL, $name_tournament))) {
            trace_r("Tournament already exists");
            return false;
        }
        $mTournament->insert(
            array(
                "name_tournament" => $name_tournament,
                "id_format"       => $pIdFormat,
                "url_tournament"  => $pUrl
            )
        );
        $this->tournament = $mTournament->getInsertId();

        $decklists = array();
        preg_match_all('/<tr[^>]*>[^<]*<td>([^<]*)<\/td>[^<]*<td>([^<]*)<\/td>[^<]*<td><a[^>]*href="([^"]*)"[^>]*>[^<]*<\/a><\/td>[^<]*<\/tr>/Usi', $data, $output_array);
        unset($output_array[0]);
        foreach ($output_array as $key => $element) {
            foreach ($element as $num => $player) {
                $decklists[$num][self::MAPPING_DECKLISTS[$key]] = $player;
            }
        }

        // insert people if needed
        $mPeople = new ModelPeople();
        $insert_people = array();
        $list_people = array();
        foreach ($decklists as $decklist) {
            $list_people[] = $decklist['arenaid'];
            if (!$mPeople->count(Query::condition()->andWhere("arena_id", Query::EQUAL, $decklist['arenaid']))) {
                $insert_people[] = array(
                    "arena_id" => $decklist['arenaid'],
                    "discord_id" => $decklist['discordid']
                );
            }
        }
        if ($insert_people) {
            $mPeople->insertMultiple($insert_people);
        }
        // get people ids
        $all_people = $mPeople->all(Query::condition()->andWhere("arena_id", Query::IN, "('" . implode("', '", $list_people) . "')", false));

        $ids_people = array();
        foreach ($all_people as $people) {
            $ids_people[strtolower($people['arena_id'])] = $people['id_people'];
        }

        // insert players -- update decklists later
        foreach ($decklists as $key => $player) {
            if (isset($ids_people[strtolower($player['arenaid'])])) {
                $this->modelPlayer->insert(
                    array(
                        "id_tournament"   => $this->tournament,
                        "id_people"       => $ids_people[strtolower($player['arenaid'])],
                        "decklist_player" => $player['url']
                    )
                );
                $id_player = $this->modelPlayer->getInsertId();

                $decklists[$key]['id_player'] = $id_player;
            } else {
                trace_r("WARNING - player not found for tournament #" . $this->tournament . " : " . $player['arenaid']);
            }
        }

        // get player archetypes & match history
        foreach ($decklists as $player) {
            // TODO fix : Undefined index: id_player (reimport tournament #65 to debug)
            if (!array_key_exists('id_player', $player)) {
                trace_r("WARNING - undefined id_player for tournament #" . $this->tournament);
                trace_r($player);
                continue;
            }
            if (!$this->parsePlayer($player['id_player'], $player['url'])) {
                // remove player from DB
                $this->modelPlayer->deleteById($player['id_player']);
            }
        }

        return true;
    }

    public function parsePlayer ($pIdPlayer, $pUrl, $pParseMatchHistory = true) {
        $deck = $this->callUrl($pUrl);
        preg_match_all('/<table[^>]*id="maindeck"[^>]*>.*cardname.*<\/table>/Uims', $deck, $output_array);
        $decklist = $output_array[0][0];
        $history = "";
        if ($pParseMatchHistory) {
            preg_match_all('/history.*<table[^>]*>.*opponent.*<\/table>/Uims', $deck, $output_array);
            if (!$output_array[0]) {
                trace_r("Player " . $pIdPlayer . " ignored -- no match history");
                return false;
            }
            $history = $output_array[0][0];
        }
        $name_archetype = ModelArchetype::decklistMapper($decklist);

        // insert archetype if needed
        $mArchetype = new ModelArchetype();
        $archetype = $mArchetype->one(Query::condition()->andWhere("name_archetype", Query::EQUAL, $name_archetype));
        if ($archetype) {
            $id_archetype = $archetype['id_archetype'];
        } else {
            $mArchetype->insert(
                array(
                    "name_archetype" => $name_archetype
                )
            );
            $id_archetype = $mArchetype->getInsertId();
        }

        // update player archetype
        $this->modelPlayer->updateById(
            $pIdPlayer,
            array(
                "id_archetype"    => $id_archetype
            )
        );

        if ($pParseMatchHistory) {
            $this->parseMatchHistory($pIdPlayer, $history);
        }
        return true;
    }

    public function parseMatchHistory ($pIdPlayer, $pHistory) {
        $matches = array();
        preg_match_all('/<tr[^>]*>[^<]*<td[^>]*>([^<]*)<\/td>[^<]*<td[^>]*>([^<]*)<\/td>[^<]*<td[^>]*>([^<]*)<\/td>.*<\/tr>/Uism', $pHistory, $output_array);
        unset($output_array[0]);
        foreach ($output_array as $key => $column) {
            foreach ($column as $num => $content) {
                $id = self::MAPPING_HISTORY[$key];
                switch ($id) {
                    case "playerid":
                    case "result":
                        $content = trim($content);
                        break;
                }
                $matches[$num][$id] = $content;
            }
        }
        $mMatches = new ModelMatch();
        $insert_matches = array();
        // insert matches
        foreach ($matches as $match) {
            $ignore_match = false;
            // parse result
            switch ($match['result']) {
                case "2-0":
                case "2-1":
                    $match['result'] = 1;
                    break;
                case "0-2":
                case "1-2":
                    $match['result'] = 0;
                    break;
                default:
                    $ignore_match = true;
            }
            if ($ignore_match) {
                continue;
            }

            if ($match['playerid'] == self::BYE) {
                continue;
            }
            preg_match('/\s*(.+#[0-9]+),/', $match['playerid'], $output_array);
            if (!array_key_exists(1, $output_array)) {
                trace_r("WARNING -- Player match error");
                trace_r($match);
                continue;
            }
            $opponent_arena_id = $output_array[1];
            $opponent_player_id = $this->modelPlayer->getPlayerIdByTournamentIdArenaId($this->tournament, $opponent_arena_id);
            if ($opponent_player_id) {
                $insert_matches[] = array(
                    "id_player" => $pIdPlayer,
                    "opponent_id_player" => $opponent_player_id,
                    "result_match" => $match['result']
                );
            } else {
                trace_r("WARNING - player not found for tournament #" . $this->tournament . " : " . $opponent_arena_id);
                trace_r($match);
            }
        }

        if ($insert_matches) {
            $mMatches->insertMultiple($insert_matches);
        } else {
            trace_r("WARNING - No matches to insert for player " . $pIdPlayer);
        }
        return true;
    }

    /*
    public function parseStandings ($pFile)
    {
        $full_path = self::UPLOADS . $pFile . '.html';
        if (!file_exists($full_path)) {
            trace_r("PARSING ERROR : file " . $full_path . " not found");
            return false;
        }
        $pairings = File::read($full_path);
        $standings = array();
        return true;
    }

    public function parsePairings ($pFile) {
        $full_path = self::UPLOADS . $pFile . '.html';
        if (!file_exists($full_path)) {
            trace_r("PARSING ERROR : file " . $full_path . " not found");
            return false;
        }
        $pairings = File::read($full_path);
        $matches = array();
        preg_match_all('/<table[^>]*>.*opponent.*<tbody>(.*)<\/tbody>.*<\/table>/Usi', $pairings, $output_array);
        $pairings_table = $output_array[1][0];

        preg_match_all('/<tr[^>]*>[^<]*<td>([^<]*)<\/td>[^<]*<td>([^<]*)<\/td>[^<]*<td>([^<]*)<\/td>[^<]*<td>([^<]*)<\/td>[^<]*<\/tr>/Usi', $pairings_table, $output_array);
        unset($output_array[0]);
        foreach ($output_array as $key => $element) {
            foreach ($element as $num => $table) {
                $matches[$num][self::MAPPING_PAIRINGS[$key]] = $table;
            }
        }
        return true;
    }
    */
}