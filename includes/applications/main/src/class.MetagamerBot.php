<?php
namespace app\main\src;

use app\main\models\ModelArchetype;
use app\main\models\ModelCard;
use app\main\models\ModelMatch;
use app\main\models\ModelPeople;
use app\main\models\ModelPlayer;
use app\main\models\ModelTournament;
use core\db\Query;

class MetagamerBot extends BotController
{
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
        5 => "url"
    );
    CONST MAPPING_HISTORY = array(
        1 => "round",
        2 => "playerid",
        3 => "result"
    );

    public $tournament;
    protected $modelPlayer;
    protected $modelCard;

    public function __construct($pName)
    {
        $this->modelPlayer = new ModelPlayer();
        $this->modelCard = new ModelCard();
        parent::__construct($pName);
    }

    public function mapTournaments () {
        $tournaments = array();
        for ($id = 10960; $id < 11000; $id++) {
            $pUrl = "https://my.cfbevents.com/deck/$id";
            $data = $this->callUrl($pUrl);
            preg_match_all('/(MagicFest Online Season \d).*<h1[^>]*>([^<]*)<.*Submitted decklists.*<\/h1>.*View decklist/Umis', $data, $output_array);
            if (array_key_exists(0, $output_array[1])) {
                $name_tournament = trim($output_array[1][0]) . " - " . trim($output_array[2][0]);
                $tournaments[$id] = $name_tournament;
            }
        }
        foreach ($tournaments as $id => $tournament) {
            trace_r("TOURNAMENT #$id : <a href='https://my.cfbevents.com/deck/$id'>$tournament</a>");
        }
        // if id tournament is not saved already, parse tournament decklists
    }

    /*
     * Reevaluate archetypes for a given tournament
     */
    public function evaluateArchetypes ($pUrl) {
        return false;
        // TODO - deprecated - rework decklist parsing with cards
        if (!preg_match('/^https?:\/\/my.cfbevents.com\/deck\/([\d]+)$/ix', $pUrl, $output_array)) {
            trace_r("ERROR : URL " . $pUrl . " incorrect");
            return false;
        }
        $this->tournament = $output_array[1];
        $mTournament = new ModelTournament();

        if (!$tournament = $mTournament->getTupleById($this->tournament)) {
            trace_r("Tournament #" . $this->tournament . " does not exist");
            return false;
        }

        $data = $this->callUrl($pUrl);
        if (empty($data)) {
            trace_r("PARSING ERROR : URL " . $pUrl . " not found");
            return false;
        }

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
        if (!preg_match('/^https?:\/\/my.cfbevents.com\/deck\/([\d]+)$/ix', $pUrl, $output_array)) {
            trace_r("ERROR : URL " . $pUrl . " incorrect");
            return false;
        }
        $this->tournament = $output_array[1];
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
            $name_tournament = "Tournament #" . $this->tournament;
        }

        $mTournament = new ModelTournament();

        if ($mTournament->one(
            Query::condition()
                ->andWhere("id_tournament", Query::EQUAL, $this->tournament)
        )) {
            trace_r("Tournament " . $name_tournament . " (id #" . $this->tournament . ") already exists");
            return false;
        }
        $mTournament->insert(
            array(
                "id_tournament"   => $this->tournament,
                "name_tournament" => $name_tournament,
                "id_format"       => $pIdFormat
            )
        );

        $decklists = array();
        preg_match_all('/<tr[^>]*>[^<]*<td>([^<]*)<\/td>[^<]*<td>([^<]*)<\/td>[^<]*(<td>([^<]*)<\/td>[^<]*)?<td>[^<]*<a[^>]*href="([^"]*)"[^>]*>[^<]*<\/a>[^<]*<\/td>[^<]*<\/tr>/Usi', $data, $output_array);
        unset($output_array[0]);
        foreach ($output_array as $key => $element) {
            if (array_key_exists($key, self::MAPPING_DECKLISTS)) {
                foreach ($element as $num => $player) {
                    $decklists[$num][self::MAPPING_DECKLISTS[$key]] = $player;
                }
            }
        }

        // insert people if needed
        $mPeople = new ModelPeople();
        $insert_people = array();
        $list_people = array();

        // TODO MPL + Rivals League Weekends : name + firstname

        foreach ($decklists as $key => $decklist) {
            $decklists[$key]["arenaid"]   = $decklist['discordid'] . ' ' . $decklist['arenaid'];
            $decklists[$key]["discordid"] = $decklist['discordid'] . ' ' . $decklist['arenaid'];
        }

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
            if (!array_key_exists('id_player', $player)) {
                trace_r("WARNING - undefined id_player for tournament #" . $this->tournament);
                trace_r($player);
                continue;
            }
            if (!$this->parsePlayer($player['id_player'], $player['url'])) {
                // remove player from DB
                // TODO MPL + Rivals League Weekends : no match history given
//                $this->modelPlayer->deleteById($player['id_player']);
            }
        }

        return true;
    }

    public function parsePlayer ($pIdPlayer, $pUrl, $pParseMatchHistory = true, $pWrite = true) {
        $deck = $this->callUrl($pUrl);
        preg_match_all('/<h1[^>]*>([^<]*)<\/h1>[^\}]+<table[^>]*id="maindeck"[^>]*>.*cardname(.*)<table[^>]*id="sideboard"[^>]*>.*cardname(.*)<\/table>/Uims', $deck, $output_array);
        if (!array_key_exists(0, $output_array[1])) {
            trace_r("ERROR Decklist parsing : no decklist found for url : " . $pUrl);
            return false;
        }
        $deck_name = $output_array[1][0];
        $deck_main = $output_array[2][0];
        $deck_side = $output_array[3][0];

        // TODO remove this call, use mapping with cards instead
        $name_archetype = ModelArchetype::decklistMapper($deck_main);

        if ($pWrite) {
            // quickfix quote bug (&#039; on CFB / &#39; on MTGmelee)
            $deck_main = str_replace("&#039;", "'", $deck_main);
            $deck_side = str_replace("&#039;", "'", $deck_side);
            // insert archetype if needed
            $mArchetype = new ModelArchetype();
            $archetype = $mArchetype->one(Query::condition()->andWhere("name_archetype", Query::EQUAL, $name_archetype));
            // TODO use modelArchetype::evaluatePlayerArchetype instead, after cards are inserted
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
                    "id_archetype" => $id_archetype,
                    "name_deck" => $deck_name
                )
            );

            // insert cards
            $cards = array();
            preg_match_all('/(<tr[^>]*>\s*<td[^>]*>([\d\s]+)<\/td>\s*<td[^>]*>([^<]+)(\([^<]+)?<\/td>\s*<\/tr>\s*)+/Uims', $deck_main, $parsing_main);
            if (!array_key_exists(0, $parsing_main[3])) {
                trace_r("ERROR Decklist parsing : no maindeck found for url : " . $pUrl);
                return false;
            }
            preg_match_all('/(<tr[^>]*>\s*<td[^>]*>([\d\s]+)<\/td>\s*<td[^>]*>([^<]+)<\/td>\s*<\/tr>\s*)+/Uims', $deck_side, $parsing_side);
            if (!array_key_exists(0, $parsing_side[3])) {
                trace_r("ERROR Decklist parsing : no sideboard found for url : " . $pUrl);
                return false;
            }
            $full_deck = array_merge($parsing_main[3], $parsing_side[3]);
            foreach ($full_deck as &$card) {
                $card = trim($card);
            }

            // get cards ids
            $id_cards = array();
            $all_cards = $this->modelCard->all(
                Query::condition()
                    ->andWhere("name_card", Query::IN, '("' . implode('","', $full_deck) . '")', false),
                "id_card, name_card");
            foreach ($all_cards as $card) {
                $id_cards[$card['name_card']] = $card['id_card'];
            }
            $deck_count = 0;
            foreach ($parsing_main[3] as $key => $card_name) {
                $cards[$card_name] = array(
                    "id_player"  => $pIdPlayer,
                    "id_card"    => $id_cards[$card_name],
                    "count_main" => $parsing_main[2][$key],
                    "count_side" => 0
                );
                $deck_count += $parsing_main[2][$key];
            }
            foreach ($parsing_side[3] as $key => $card_name) {
                if (array_key_exists($card_name, $cards)) {
                    $cards[$card_name]['count_side'] = $parsing_side[2][$key];
                } else {
                    $cards[$card_name] = array(
                        "id_player"  => $pIdPlayer,
                        "id_card"    => $id_cards[$card_name],
                        "count_main" => 0,
                        "count_side" => $parsing_side[2][$key]
                    );
                }
            }
            if ($deck_count < 60) {
                trace_r("Deck < 60 cards for player $pIdPlayer (<a href='$pUrl'>See decklist</a>)");
            }
            $cards = array_values($cards);
            $this->modelCard->insertPlayerCards($cards);

            // TODO evaluate player archetypes based on player_cards in SB

            if ($pParseMatchHistory) {
                preg_match_all('/history.*<table[^>]*>.*opponent.*<\/table>/Uims', $deck, $output_array);
                if (!$output_array[0]) {
                    trace_r("Player " . $pIdPlayer . " ignored -- no match history (check <a href='$pUrl'>decklist</a> for more details)");
                    return false;
                }
                $history = $output_array[0][0];
                $this->parseMatchHistory($pIdPlayer, $history);
            }
        }
        return $name_archetype;
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
                case "1-0":
                    $match['result'] = 1;
                    break;
                case "0-2":
                case "1-2":
                case "0-1":
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
            // TODO search by discord_id if player not found ?
            // check if we can pass discord_id to function
            $opponent_arena_id = $output_array[1];
            $opponent_player_id = $this->modelPlayer->getPlayerIdByTournamentIdArenaId($this->tournament, $opponent_arena_id);
            if ($opponent_player_id) {
                $insert_matches[] = array(
                    "id_player" => $pIdPlayer,
                    "opponent_id_player" => $opponent_player_id,
                    "result_match" => $match['result']
                );
                // insert opposing match as well
                $insert_matches[] = array(
                    "id_player" => $opponent_player_id,
                    "opponent_id_player" => $pIdPlayer,
                    "result_match" => intval(!$match['result'])
                );
            } else {
                trace_r("WARNING - player not found for tournament #" . $this->tournament . " : " . $opponent_arena_id);
                trace_r($match);
            }
        }

        if ($insert_matches) {
            $mMatches->replaceMultiple($insert_matches);
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