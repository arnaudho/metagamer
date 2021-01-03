<?php
namespace app\main\src;

use app\main\models\ModelArchetype;
use app\main\models\ModelCard;
use app\main\models\ModelFormat;
use app\main\models\ModelMatch;
use app\main\models\ModelPeople;
use app\main\models\ModelPlayer;
use app\main\models\ModelTournament;
use core\db\Query;

class BattlefyBot extends BotController
{
    // TODO fix accents in player names (D&eacute;ia#00654)
    // TODO Fix case in player name (zlazh#47315 Zlazh#47315)

    public $tournament;
    protected $modelTournament;
    protected $modelArchetype;
    protected $modelPeople;
    protected $modelPlayer;
    protected $modelMatch;
    protected $modelCard;

    public function __construct($pName)
    {
        $this->modelTournament = new ModelTournament();
        $this->modelArchetype = new ModelArchetype();
        $this->modelPeople = new ModelPeople();
        $this->modelPlayer = new ModelPlayer();
        $this->modelMatch = new ModelMatch();
        $this->modelCard = new ModelCard();
        parent::__construct($pName);
    }

    // Parse decklist by player ID
    public function parseDecklist ($pIdPlayer, $pDecklistData, $pUriDecklist = null, $pDecklistName = null, $pUserName = null, $pWrite = true) {
        $player = $this->modelPlayer->getPlayerWithTypeFormatById($pIdPlayer);
        if (!$player) {
            return false;
        }

        // update player decklist name & url
        $this->modelPlayer->updateById(
            $player['id_player'],
            array(
                "decklist_player" => $pUriDecklist,
                "name_deck" => $pDecklistName
            )
        );
        if ($pUserName) {
            $this->modelPeople->updateByPlayerId($player['id_player'], array("discord_id" => $pUserName));
        }

        if (!$pDecklistData) {
            return false;
        }

        // handle different MDFC format
        $pDecklistData = str_replace("///", "//", $pDecklistData);
        preg_match_all('/Deck(.*)(Sideboard(.*))?\z/Uims', $pDecklistData, $output_array);
        $deck_main = trim($output_array[1][0]);
        $deck_side = trim($output_array[3][0]);

        preg_match_all("/(\d+)\s+([^\d()]+)(\r|\n)*/ims", $deck_main, $parsing_main);

        // insert cards
        $cards = array();
        if (!array_key_exists(0, $parsing_main[2])) {
            trace_r("ERROR Decklist parsing : no maindeck found for url : <a href='$pUriDecklist'>" . $player['decklist_player'] . "</a>");
            $this->addMessage("ERROR Decklist parsing : no maindeck found for player <a href='$pUriDecklist' target='_blank'>" . $player['decklist_player'] . "</a>", self::MESSAGE_ERROR);
            return false;
        }
        preg_match_all("/(\d+)\s+([^\d()]+)(\r|\n)*/ims", $deck_side, $parsing_side);
        if (!array_key_exists(0, $parsing_side[2]) || empty($deck_side)) {
            trace_r("ERROR Decklist parsing : no sideboard found for url : <a href='$pUriDecklist'>" . $player['decklist_player'] . "</a>");
            $this->addMessage("WARNING : Decklist parsing : no sideboard found for player <a href='$pUriDecklist' target='_blank'>" . $player['decklist_player'] . "</a>", self::MESSAGE_WARNING);
        }

        foreach ($parsing_main[2] as $key => $card) {
            $parsing_main[2][$key] = trim($card);
            if ($parsing_main[2][$key] == "Lurrus of the Dream Den") {
                $parsing_main[2][$key] = "Lurrus of the Dream-Den";
            }
        }
        foreach ($parsing_side[2] as $key => $card) {
            $parsing_side[2][$key] = trim($card);
            if ($parsing_side[2][$key] == "Lurrus of the Dream Den") {
                $parsing_side[2][$key] = "Lurrus of the Dream-Den";
            }
        }
        $full_deck = array_unique(array_merge($parsing_main[2], $parsing_side[2]));
        foreach ($full_deck as $key => $card) {
            $full_deck[$key] = $card;
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

        // check if we have the correct count of cards in DB, if not search for split cards
        // (MTG Melee clean decklist does not display second part of Adventure cards)
        if (count($id_cards) != count($full_deck)) {
            foreach ($full_deck as $card) {
                if (!array_key_exists($card, $id_cards)) {
                    if ($c = $this->modelCard->one(
                        Query::condition()
                            ->andWhere("name_card", Query::LIKE, "$card%"),
                        "id_card, name_card"
                    )) {
                        $id_cards[$card] = $c['id_card'];
                    } else {
                        trace_r("WARNING : Unknown card in decklist : $card");
                        $this->addMessage("WARNING : Unknown card in decklist : $card (<a href='$pUriDecklist' target='_blank'>See decklist</a>)", self::MESSAGE_ERROR);
                    }
                }
            }
        }
        $deck_count = 0;
        foreach ($parsing_main[2] as $key => $card_name) {
            if (array_key_exists($card_name, $cards)) {
                $cards[$card_name]['count_main'] += $parsing_main[1][$key];
            } else {
                $cards[$card_name] = array(
                    "id_player"  => $pIdPlayer,
                    "id_card"    => $id_cards[$card_name],
                    "count_main" => $parsing_main[1][$key],
                    "count_side" => 0
                );
            }
            $deck_count += $parsing_main[1][$key];
        }
        if (array_key_exists(0, $parsing_side[2])) {
            foreach ($parsing_side[2] as $key => $card_name) {
                if (array_key_exists($card_name, $cards)) {
                    $cards[$card_name]['count_side'] += $parsing_side[1][$key];
                } else {
                    $cards[$card_name] = array(
                        "id_player" => $pIdPlayer,
                        "id_card" => $id_cards[$card_name],
                        "count_main" => 0,
                        "count_side" => $parsing_side[1][$key]
                    );
                }
            }
        }
        if ($deck_count < 60) {
            trace_r("Deck < 60 cards for player $pIdPlayer (<a href='$pUriDecklist'>See decklist</a>)");
            $this->addMessage("Deck < 60 cards for player $pIdPlayer (<a href='$pUriDecklist' target='_blank'>See decklist</a>)", self::MESSAGE_ERROR);
        }
        $cards = array_values($cards);

        if ($pWrite) {
            $this->modelCard->insertPlayerCards($cards);
        }

        // TODO set decklist url as well
        $this->modelArchetype->evaluatePlayerArchetype($pIdPlayer, $player['id_type_format'], $pWrite);
        return true;
    }

    // Parse round data (JSON)
    public function parseRound ($pData, $pFormat = null, $pTournamentName = null, $pTournamentDate = null) {
        if (!$pData[0]['TournamentId']) {
            $this->addMessage("Tournament ID not found", self::MESSAGE_ERROR);
            return false;
        }

        // insert tournament if needed
        $this->tournament = $pData[0]['TournamentId'];
        if (!$this->modelTournament->getTupleById($this->tournament)) {
            $mFormat = new ModelFormat();
            if (!$pFormat) {
                $this->addMessage("Please specify a format to create new tournament", self::MESSAGE_ERROR);
                return false;
            }
            $id_format = $mFormat->getTupleById($pFormat);
            $id_format = $id_format['id_format'];
            $new_tournament = array(
                "id_tournament"   => $this->tournament,
                "name_tournament" => $pTournamentName ? $pTournamentName : "Battlefy - Tournament #" . $this->tournament,
                "id_format"       => $id_format
            );
            if ($pTournamentDate) {
                $new_tournament['date_tournament'] = $pTournamentDate;
            }
            $this->modelTournament->insert($new_tournament);
        }

        foreach ($pData as $key => $pairing) {
            if ($pairing['Player1']) {
                if(preg_match('/([^#]+)#?/', $pairing['Player1'], $output_array)) {
                    $pData[$key]['Player1Username'] = htmlentities($output_array[1], ENT_QUOTES);
                }
                $pData[$key]['Player1'] = htmlentities($pairing['Player1'], ENT_QUOTES);
            }
            if ($pairing['Player2']) {
                if(preg_match('/([^#]+)#?/', $pairing['Player2'], $output_array)) {
                    $pData[$key]['Player2Username'] = htmlentities($output_array[1], ENT_QUOTES);
                }
                $pData[$key]['Player2'] = htmlentities($pairing['Player2'], ENT_QUOTES);
            }
        }

        $ids_people = array();
        $insert_people = array();
        $list_players = array();
        $new_players = array();

        $insert_players = array();
        $insert_matches = array();

        // FOREACH pairings
        // SEARCH player1
        // IF id_player found
        // push in list_players
        // ELSE push in new_players
        // SEARCH player2
        // same
        foreach ($pData as $pairing) {
            if ($pairing['Player1']) {
                $id_player = $this->modelPlayer->getPlayerIdByTournamentIdArenaId($this->tournament, $pairing['Player1']);
                if ($id_player) {
                    $list_players[$pairing['Player1']] = $id_player;
                } else {
                    $new_players[$pairing['Player1']] = array(
                        "decklist_player" => $pairing['Player1'],
                        "name_deck" => "",
                        "arena_id" => $pairing['Player1'],
                        "discord_id" => $pairing['Player1Username']
                        // TODO set custom field here ?
                    );
                }
            }
            if ($pairing['Player2']) {
                // if Player1 has a bye, Player2 is null
                $id_player = $this->modelPlayer->getPlayerIdByTournamentIdArenaId($this->tournament, $pairing['Player2']);
                if ($id_player) {
                    $list_players[$pairing['Player2']] = $id_player;
                } else {
                    $new_players[$pairing['Player2']] = array(
                        "decklist_player" => $pairing['Player2'],
                        "name_deck" => "",
                        "arena_id" => $pairing['Player2'],
                        "discord_id" => $pairing['Player2Username']
                    );
                }
            }
        }

        // IF new_players NOT EMPTY
        // FOREACH new_players
        // IF people NOT FOUND
        // push in insert_people
        // INSERT multiple : insert_people
        // get list people by arena_id (from new_players)
        // INSERT multiple : new_players (with id_people)
        // get list players (from new players)
        // merge with list_players
        if ($new_players) {
            $new_players_arena_ids = array_column($new_players, "arena_id");
            $all_people = $this->modelPeople->all(
                Query::condition()
                    ->andWhere("arena_id", Query::IN, "('" . implode("', '", $new_players_arena_ids) . "')", false),
                "id_people, arena_id"
            );
            foreach ($all_people as $people) {
                $ids_people[strtolower($people['arena_id'])] = $people['id_people'];
            }

            foreach ($new_players as $new_player) {
                if (!array_key_exists(strtolower($new_player['arena_id']), $ids_people)) {
                    $insert_people[] = array(
                        "arena_id"   => $new_player['arena_id'],
                        "discord_id" => $new_player['discord_id']
                    );
                }
            }
            if ($insert_people) {
                $this->modelPeople->insertMultiple(array_values($insert_people));
            }
            // get all people to insert in players
            $all_people = $this->modelPeople->all(
                Query::condition()
                    ->andWhere("arena_id", Query::IN, "('" . implode("', '", $new_players_arena_ids) . "')", false),
                "id_people, arena_id"
            );

            $ids_people = array();
            foreach ($all_people as $people) {
                $ids_people[strtolower($people['arena_id'])] = $people['id_people'];
            }
            foreach ($new_players as $player) {
                if (array_key_exists(strtolower($player['arena_id']), $ids_people)) {
                    $insert_players[] = array(
                        "id_tournament" => $this->tournament,
                        "id_people" => $ids_people[strtolower($player['arena_id'])],
                        "name_deck" => $player['name_deck'],
                        "decklist_player" => $player['decklist_player']
                    );
                }
            }
            if ($insert_players) {
                $this->modelPlayer->insertMultiple($insert_players);
                // add new players to list_players
                foreach ($new_players as $new_player) {
                    $id_player = $this->modelPlayer->getPlayerIdByTournamentIdArenaId($this->tournament, $new_player['arena_id']);
                    if ($id_player) {
                        $list_players[$new_player['arena_id']] = $id_player;
                    }
                }
            }
        }

        // FOREACH pairings
        // INSERT match player1 vs player 2
        // INSERT match player2 vs player 1 (opposite result)
        foreach ($pData as $pairing) {
            $result_match = $pairing['Result'] == 1 ? 1 : 0;
            if ($pairing['Result'] !== false &&
                $pairing['Player1'] &&
                $pairing['Player2'] &&
                array_key_exists($pairing['Player1'], $list_players) &&
                array_key_exists($pairing['Player2'], $list_players)
            ) {
                $insert_matches[] = array(
                    "id_player" => $list_players[$pairing['Player1']],
                    "opponent_id_player" => $list_players[$pairing['Player2']],
                    "result_match" => $result_match,
                    "round_number" => $pairing['RoundNumber']
                );
                $insert_matches[] = array(
                    "id_player" => $list_players[$pairing['Player2']],
                    "opponent_id_player" => $list_players[$pairing['Player1']],
                    "result_match" => intval(!$result_match),
                    "round_number" => $pairing['RoundNumber']
                );
            } else {
                trace_r("Incorrect match : ");
                trace_r($pairing);
                trace_r($list_players[$pairing['Player1']]);
                trace_r($list_players[$pairing['Player2']]);
            }
        };

        if ($insert_matches) {
            $this->modelMatch->replaceMultiple($insert_matches);
        } else {
            trace_r("WARNING - No matches to insert for round " . $pairing['RoundNumber'] . "in tournament #" . $this->tournament);
            $this->addMessage("WARNING - No matches to insert for round " . $pairing['RoundNumber'] . "in tournament #", self::MESSAGE_ERROR);
            return false;
        }

        return true;
    }

}