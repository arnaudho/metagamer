<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\application\Core;
    use core\db\Query;

    class ModelPlayer extends BaseModel {

        CONST TAG_MPL = "mpl";
        CONST TAG_RIVALS = "rivals";

        public function __construct()
        {
            parent::__construct("players", "id_player");
        }

        public function getPlayerWithTypeFormatById ($pId, $pFields = "players.*, id_type_format")
        {
            $res = Query::select($pFields, $this->table)
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                ->join("formats", Query::JOIN_INNER, "formats.id_format = tournaments.id_format")
                ->andWhere($this->id, Query::EQUAL, $pId)
                ->limit(0, 1)
                ->execute($this->handler);
            if(!isset($res[0]))
                return null;
            return $res[0];
        }

        public function allByFormat ($pIdFormat, $pCond = null, $pFields = "") {
            if (!$pCond) {
                $cond = Query::condition();
            } else {
                $cond = clone $pCond;
            }
            $fields = "players.id_player, SUM(result_match) AS wins, COUNT(result_match) AS total";
            if ($pFields) {
                $fields = $pFields . ", $fields";
            }
            $players = Query::select($fields, $this->table)
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament AND id_format = $pIdFormat")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->andCondition($cond)
                ->groupBy("players.id_player")
                ->order("players.id_player")
                ->execute($this->handler);
            return $players;
        }

        public function getDataByPlayerId ($pIdPlayer) {
            $data = Query::select(
                "players.id_player, tournaments.id_tournament, name_tournament, name_format, name_archetype, decklist_player,
                    formats.id_type_format, arena_id, IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins,
                    COUNT(result_match) AS matches, pc.count_cards_main, pc.count_cards_side", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people AND players.id_player = $pIdPlayer")
                ->join("archetypes", Query::JOIN_OUTER_LEFT, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->join("(SELECT id_player, SUM(count_main) AS count_cards_main, SUM(count_side) AS count_cards_side FROM player_card GROUP BY id_player) AS pc", Query::JOIN_OUTER_LEFT, "pc.id_player = players.id_player")
                ->groupBy("players.id_player")
                ->limit(0, 1)
                ->execute($this->handler);
            return $data[0];
        }

        public function getPlayersByTournamentId ($pIdTournament) {
            $q = Query::select("players.id_player, arena_id, name_deck, name_archetype, image_archetype,
                IF (SUM(result_match) IS NULL, 0, SUM(result_match))AS wins,
                COUNT(result_match) AS matches", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->andWhere("players.id_tournament", Query::EQUAL, $pIdTournament)
                ->groupBy("players.id_player")
                ->order("arena_id");
            return $q->execute($this->handler);
        }

        public function searchPlayerByDecklistName ($pName, $pCount = false, $pLimit = 10) {
            $q = Query::select("name_archetype, name_format, image_archetype, COUNT(1) AS count", $this->table)
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->andWhere("archetypes.name_archetype", Query::LIKE, "'%" . $pName . "%'", false)
                ->groupBy("archetypes.id_archetype, formats.id_format")
                ->order("COUNT(1)", "DESC");
            if ($pCount) {
                $q = Query::select("COUNT(1) AS count", "(" . $q->get(false) . ") tmp");
            } else {
                $q->limit(0, $pLimit);
            }
            $data = $q->execute($this->handler);
            return $pCount ? $data[0]['count'] : $data;
        }

        public function searchPlayerByArenaId ($pArenaId) {
            $data = Query::select(
                "players.id_player, tournaments.id_tournament, name_tournament, name_format, name_archetype,
                    arena_id, discord_id, IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins,
                    COUNT(result_match) AS matches", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people AND arena_id LIKE '%" . $pArenaId . "%'")
                ->join("archetypes", Query::JOIN_OUTER_LEFT, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->groupBy("players.id_player")
                ->order("formats.id_format DESC, arena_id, date_tournament")
                ->limit(0, 300)
                ->execute($this->handler);
            return $data;
        }

        public function searchPlayerByCardId ($pIdCard, $pCondition = null) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $q = Query::select("players.id_player, tournaments.id_tournament, name_tournament, name_format,
                    DATE_FORMAT(date_tournament, '%d %b %Y') AS date_tournament, name_archetype, arena_id,
                    count_main, count_side, IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins,
                    COUNT(result_match) AS matches", $this->table)
                ->join("player_card", Query::JOIN_INNER, "player_card.id_player = players.id_player AND id_card = $pIdCard")
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people")
                ->join("archetypes", Query::JOIN_OUTER_LEFT, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->andCondition($pCondition)
                ->groupBy("players.id_player")
                ->order("tournaments.date_tournament", "DESC")
                ->limit(0, 20);
            return $q->execute($this->handler);
        }

        public function countArchetypes ($pCondition = null) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $q = Query::select("archetypes.id_archetype, name_archetype, image_archetype, COUNT(1) AS count", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = " . $this->table . ".id_tournament")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = " . $this->table . ".id_archetype")
                ->andCondition($pCondition)
                // exclude archetypes from dashboard
                //->andWhere("archetypes.id_archetype", Query::NOT_IN, "(13, 73, 77, 83, 84, 92, 93, 94)", false)
                ->groupBy("name_archetype")
                ->order("FIELD (players.id_archetype, " . ModelArchetype::ARCHETYPE_OTHER_ID . "), COUNT(1)", "DESC");
            $data = $q->execute($this->handler);
            $sum = 0;
            foreach ($data as $d) {
                $sum += $d['count'];
            }
            foreach ($data as &$d) {
                $d['percent'] = round(100*$d['count']/$sum, 1);
            }
            return $data;
        }

        public function getDecklists ($pCondition, $pDecklistNames = false) {
            $fields = "IF (SUM(result_match) IS NULL, 0, SUM(result_match))AS wins, COUNT(result_match) AS total, p.id_player, people.arena_id,
                    tournaments.id_format, tournaments.id_tournament, name_tournament,
                    DATE_FORMAT(date_tournament, '%d %b %Y') AS date_tournament";
            if ($pDecklistNames) {
                $fields .= ", p.name_deck";
            }
            return Query::select($fields, "players p")
                ->join("people", Query::JOIN_INNER, "people.id_people = p.id_people")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = p.id_tournament")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = p.id_player")
                ->andCondition($pCondition)
                ->groupBy("p.id_player")
                ->order("name_deck")
                ->execute($this->handler);
        }

        public function getProLeaguePointsByEvent ($pTag = ModelPlayer::TAG_MPL, $pIdTournament = null) {
            $players = array();
            $player_points = array();
            if (is_null($pIdTournament) || in_array($pIdTournament, ModelTournament::LEAGUE_TOURNAMENT_IDS)) {
                $league_weekend_ids = implode(", ", ModelTournament::LEAGUE_TOURNAMENT_IDS);
                if (in_array($pIdTournament, ModelTournament::LEAGUE_TOURNAMENT_IDS)) {
                    $league_weekend_ids = $pIdTournament;
                }
                $fields = "people.id_people, players.id_player, tag_player, country_player, arena_id AS name_player,
                    rank_player AS old_rank_player, SUM(result_match) AS points_player, COUNT(result_match) AS total_matches";
                if (is_null($pIdTournament)) {
                    $fields .= ", ROUND(SUM(result_match)/COUNT(result_match)*100, 2) AS winrate";
                }
                $result = Query::select($fields, $this->table)
                    ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people")
                    ->join("matches", Query::JOIN_INNER, "matches.id_player = players.id_player")
                    ->join("player_tag", Query::JOIN_INNER, "player_tag.id_people = people.id_people AND tag_player = '" . $pTag . "'")
                    ->andWhere("id_tournament", Query::IN, "($league_weekend_ids)", false)
                    ->groupBy("people.id_people")
                    ->order("points_player DESC, total_matches, arena_id")
                    ->execute($this->handler);
                foreach ($result as $player) {
                    $players[$player['id_people']] = $player;
                }
            }

            if (is_null($pIdTournament) || in_array($pIdTournament, ModelTournament::PT_TOURNAMENT_IDS)) {
                $tournament_ids = implode(", ", ModelTournament::PT_TOURNAMENT_IDS);
                if (in_array($pIdTournament, ModelTournament::PT_TOURNAMENT_IDS)) {
                    $tournament_ids = $pIdTournament;
                }
                $result = Query::select("people.id_people, tag_player, arena_id AS name_player, SUM(points_player) AS points_player", "player_points")
                    ->join("people", Query::JOIN_INNER, "people.id_people = player_points.id_people")
                    ->join("player_tag", Query::JOIN_INNER, "player_tag.id_people = people.id_people AND tag_player = '" . $pTag . "'")
                    ->andWhere("id_tournament", Query::IN, "($tournament_ids)", false)
                    ->groupBy("people.id_people")
                    ->execute($this->handler);
                foreach ($result as $player) {
                    $player_points[$player['id_people']] = $player;
                }
            }

            if (empty($players)) {
                $players = $player_points;
            } elseif (!empty($player_points)) {
                // SUM player_points with league weekend results
                foreach ($player_points as $player_point) {
                    if (array_key_exists($player_point['id_people'], $players)) {
                        $players[$player_point['id_people']]['points_player'] += $player_point['points_player'];
                    } else {
                        trace_r("ERROR : player " . $player_point['id_people'] . " not found");
                        trace_r($players);
                    }
                }
                // sort again by points_player
                uasort($players, array($this, "sortPlayerByPoints"));
            }

            return $players;
        }

        public function getLeaderboard ($pTag = ModelPlayer::TAG_MPL, $pDetailed = true) {
            $players = $this->getProLeaguePointsByEvent($pTag);

            $position = 0;
            $tie_position = 0;
            $record = 0;

            $path = Core::$path_to_components . '/metagamer/imgs/';
            // add finishes
            $finish = $pTag == ModelPlayer::TAG_RIVALS ?
                array(
                    1 => array("count" => 4, "image" => $path . 'worlds.png'),
                    5 => array("count" => 16, "image" => $path . 'mpl_gauntlet.png'),
                    21 => array("count" => 12, "image" => $path . 'rivals_gauntlet.png'),
                    33 => array("count" => 4, "image" => $path . 'rivals.png', "width" => 100),
                    37 => array("count" => 12, "image" => $path . 'challenger.png')
                ) :
                array(
                    1 => array("count" => 4, "image" => $path . 'worlds.png'),
                    5 => array("count" => 8, "image" => $path . 'mpl_gauntlet.png'),
                    13 => array("count" => 4, "image" => $path . 'rivals_gauntlet.png'),
                    17 => array("count" => 8, "image" => $path . 'rivals.png', "width" => 100),
                );
            $count_players = 1;

            foreach ($players as $key => $player) {
                if ($count_players <= 4) {
                    $players[$key]['mpl_next'] = 1;
                }
                if (array_key_exists($count_players, $finish)) {
                    $players[$key]['finish_player'] = $finish[$count_players];
                }
                $player_record = $player['points_player'];
                $count_players++;
                $position++;
                if ($record != $player_record) {
                    $tie_position = $position;
                }
                $record = $player_record;
                $players[$key]['rank_player'] = $tie_position;
            }
            if ($pDetailed) {
                // get wins by player by tournament
                foreach (ModelTournament::LEAGUE_TOURNAMENT_IDS as $id_tournament) {
                    $detail = $this->getProLeaguePointsByEvent($pTag, $id_tournament);
                    foreach ($detail as $pl) {
                        $players[$pl['id_people']]['detail'][$id_tournament] = $pl['points_player'] . "-" . ($pl['total_matches'] - $pl['points_player']);
                    }
                }
                foreach (ModelTournament::PT_TOURNAMENT_IDS as $id_tournament) {
                    $detail = $this->getProLeaguePointsByEvent($pTag, $id_tournament);
                    foreach ($detail as $pl) {
                        $players[$pl['id_people']]['detail'][$id_tournament] = $pl['points_player'];
                    }
                }

                // players points behind
                $levels = $pTag == ModelPlayer::TAG_RIVALS ?
                    array(4, 36) :
                    array(4, 16);
                $levels_points = array();
                $player_keys = array_keys($players);
                foreach ($levels as $level) {
                    $levels_points[] = $players[$player_keys[($level-1)]]['points_player'];
                }

                $levels_points[] = 0;
                foreach ($players as $key => $player) {
                    // find next level
                    $last_level = null;
                    foreach ($levels_points as $level_point) {
                        if ($player['points_player'] > $level_point) {
                            if ($last_level) {
                                $players[$key]['points_behind'] = $last_level - $player['points_player'];
                            } else {
                                $players[$key]['points_behind'] = "-";
                            }
                            break;
                        }
                        $last_level = $level_point;
                    }
                }
            }

            return $players;
        }

        // TODO set tiebreakers
        protected function sortPlayerByPoints ($pA, $pB) {
            return $pA['points_player'] == $pB['points_player'] ?
                ($pA['total_matches'] > $pB['total_matches'] ? 1 : -1) :
                ($pA['points_player'] < $pB['points_player'] ? 1 : -1);
        }

        // TODO beware of &#039;
        public function getPlayerIdByTournamentIdArenaId ($pTournamentId, $pArenaId) {
            $id_player = Query::select("id_player", $this->table)
                ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " .
                    $pTournamentId . " AND people.arena_id = '" . $pArenaId . "'")
                ->execute($this->handler);
            if (!$id_player && preg_match('/^([^#]+)#([0-9]+)/', $pArenaId, $output_array)) {
                $id_player = Query::select("id_player", $this->table)
                    ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " .
                        $pTournamentId . " AND (people.arena_id LIKE '%#" . $output_array[2] . "' OR people.arena_id LIKE '" . $output_array[1] . "#%')")
                    ->execute($this->handler);
            } elseif (!$id_player) {
                $id_player = Query::select("id_player", $this->table)
                    ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " .
                        $pTournamentId . " AND people.discord_id = '" . $pArenaId . "'")
                    ->execute($this->handler);
            }
            // cancel if several players could match
            if (count($id_player) > 1) {
                $id_player = array();
            }
            return $id_player ? $id_player[0]['id_player'] : null;
        }

        public function getPlayerByFormatId ($pIdFormat, $pFields = "players.*") {
            $players = Query::select($pFields, "players")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("id_format", Query::EQUAL, $pIdFormat)
                ->execute($this->handler);
            return $players;
        }

        public function countPlayersWithoutDecklist () {
            return $this->count(
                Query::condition()
                    ->andWhere("id_archetype", Query::IS, "NULL", false)
            );
        }

        public function countPlayers ($pCond = null, $pRulesCondition = null) {
            if (!$pCond) {
                $pCond = Query::condition();
            }
            $q = Query::select("count(1) as nb", "players p")
                ->join("tournaments", Query::JOIN_INNER, "p.id_tournament = tournaments.id_tournament")
                ->setCondition(clone $pCond);
            if ($pRulesCondition) {
                $rules_cond = clone $pRulesCondition;
                $q->andCondition($rules_cond);
            }
            $data = $q->execute($this->handler);
            return $data[0]["nb"];
        }

        public function deletePlayerById ($pIdPlayer) {
            $id_player = $this->one(Query::condition()->andWhere("id_player", Query::EQUAL, $pIdPlayer), "id_player");
            if (!$id_player) {
                return false;
            }
            $id_player = $id_player["id_player"];
            Query::delete()
                ->from("matches")
                ->andCondition(
                    Query::condition()
                        ->orWhere("id_player", Query::EQUAL, $id_player)
                        ->orWhere("opponent_id_player", Query::EQUAL, $id_player)
                )->execute($this->handler);
            Query::delete()
                ->from("player_card")
                ->andCondition(
                    Query::condition()
                        ->andWhere("id_player", Query::EQUAL, $id_player)
                )
                ->execute($this->handler);
            return $this->deleteById($id_player);
        }

        public function cleanDuplicatePlayers ($pCondition) {
            $cleaned_duplicates = 0;
            // Clean duplicate decklists
            $duplicates = $this->getDuplicatePlayers($pCondition);
            $delete_players = array();
            foreach ($duplicates as $duplicate) {
                if (!array_key_exists($duplicate['decklist_player'], $delete_players) || $delete_players[$duplicate['decklist_player']]['count'] > $duplicate['count']) {
                    $delete_players[$duplicate['decklist_player']] = array(
                        "id_player" => $duplicate['id_player'],
                        "count"     => $duplicate['count']
                    );
                }
            }
            foreach ($delete_players as $player) {
                if ($this->deletePlayerById($player['id_player'])) {
                    $cleaned_duplicates++;
                }
            }
            return $cleaned_duplicates;
        }

        public function countDuplicatePlayers ($pCondition) {
            return count($this->getDuplicatePlayers($pCondition));
        }

        public function getDuplicatePlayers ($pCondition) {
            $subquery = Query::select("decklist_player", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andCondition($pCondition)
                ->groupBy("decklist_player")
                ->having("COUNT(1) > 1", false)
                ->get(false);
            $duplicates = Query::select("players.id_player, decklist_player, COUNT(1) AS count", $this->table)
                ->join("matches", Query::JOIN_INNER, "players.id_player = matches.id_player")
                ->andWhere("decklist_player", Query::IN, "(" . $subquery . ")", false)
                ->groupBy("id_player")
                ->execute($this->handler);
            return $duplicates;
        }
    }
}