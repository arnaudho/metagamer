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

        public function getPlayerByCond($pCond = null, $pFields = "*")
        {
            if (!$pCond) {
                $cond = Query::condition();
            } else {
                $cond = clone $pCond;
            }
            $data = Query::select($pFields, $this->table)
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                ->join("matches", Query::JOIN_INNER, "matches.id_player = players.id_player")
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people")
                ->andCondition($cond)
                ->groupBy("players.id_player")
                ->execute($this->handler);
            return $data;
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

        // TODO decklist result : handle draws and player finish
        // check in getDecklistsByCardId method
        public function getDecklistsByCondition ($pCond, $pFilter = false) {
            if (!$pCond) {
                $cond = Query::condition();
            } else {
                $cond = clone $pCond;
            }
            $q = Query::select(
                "players.id_player AS id_decklist, people.id_people AS id_player, arena_id AS name_player,
                    tournaments.id_tournament, name_tournament, formats.id_format, name_format,
                    archetypes.id_archetype, name_archetype, image_archetype, colors_archetype,
                    IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins_decklist,
                    IF(SUM(result_match) IS NULL, COUNT(result_match), COUNT(result_match)-SUM(result_match)) AS loss_decklist,
                    IF(SUM(result_match) IS NULL, '0-0', CONCAT(SUM(result_match), '-', COUNT(result_match)-SUM(result_match))) AS result_decklist",
                $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people")
                ->join("archetypes", Query::JOIN_OUTER_LEFT, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->andCondition($cond)
                ->groupBy("players.id_player")
                ->order("tournaments.date_tournament DESC, wins_decklist DESC, COUNT(result_match)");
            if ($pFilter) {
                $q->having("COUNT(result_match) > 0 AND wins_decklist >= COUNT(result_match)/2", false);
            }
            $data = $q->execute($this->handler);
            if (empty($data)) {
                return array();
            }
            $mCard = new ModelCard();
            foreach ($data as $key => $item) {
                $cards = $mCard->getDecklistCards($item['id_decklist']);
                $data[$key]['export_arena'] = $this->convertToArenaFormat($cards);
                $data[$key]["cards"] = $cards;
            }
            return $data;
        }

        /*
         * DEPRECATED -- generic method used instead
         */
        public function getDecklistById ($pIdDecklist) {
            $data = Query::select(
                "players.id_player AS id_decklist, people.id_people AS id_player, arena_id AS name_player,
                    tournaments.id_tournament, name_tournament, formats.id_format, name_format,
                    archetypes.id_archetype, name_archetype,
                    IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins_decklist,
                    IF(SUM(result_match) IS NULL, COUNT(result_match), COUNT(result_match)-SUM(result_match)) AS loss_decklist,
                    COUNT(result_match) AS matches_decklist", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people AND players.id_player = $pIdDecklist")
                ->join("archetypes", Query::JOIN_OUTER_LEFT, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->groupBy("players.id_player")
                ->limit(0, 1)
                ->execute($this->handler);
            if (empty($data)) {
                return false;
            }
            $data = $data[0];
            $mCard = new ModelCard();
            $cards = $mCard->getDecklistCards($pIdDecklist);
            $data['export_arena'] = $this->convertToArenaFormat($cards);
            $data["cards"] = $cards;
            return $data;
        }

        // TODO decklists by archetype / tournament : restrict on result
        // by card & player : get full data

        // we don't specify id_format here, because an id_archetype should be specifid to a format
        // TODO handle tournament icons
        /*
         * DEPRECATED -- generic method used instead
         */
        public function getDecklistsByIdArchetype ($pIdArchetype) {
            $data = Query::select(
                "players.id_player AS id_decklist, people.id_people AS id_player, arena_id AS name_player,
                    tournaments.id_tournament, name_tournament, IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins_decklist,
                    COUNT(result_match) AS matches_decklist, date_tournament", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype AND archetypes.id_archetype = $pIdArchetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->groupBy("players.id_player")
                ->having("matches_decklist > 0 AND wins_decklist >= matches_decklist/2", false)
                ->order("tournaments.date_tournament DESC, wins_decklist DESC, matches_decklist")
                ->execute($this->handler);
            return $data;
        }

        // TODO add player finish (top8, 1st place, etc.)
        // ORDER BY player finish then record
        /*
         * DEPRECATED -- generic method used instead
         */
        public function getDecklistsByIdTournament ($pIdTournament) {
            $data = Query::select(
                "players.id_player AS id_decklist, people.id_people AS id_player, arena_id AS name_player, image_archetype,
                    archetypes.id_archetype, name_archetype, IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins_decklist,
                    COUNT(result_match) AS matches_decklist, date_tournament", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament AND tournaments.id_tournament = $pIdTournament")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->groupBy("players.id_player")
                ->having("matches_decklist > 0 AND wins_decklist >= matches_decklist/2", false)
                ->order("wins_decklist DESC, matches_decklist")
                ->execute($this->handler);
            return $data;
        }

        /*
         * DEPRECATED -- generic method used instead
         */
        public function getDecklistsByIdPlayer ($pIdPlayer) {
            $data = Query::select(
                "players.id_player AS id_decklist, tournaments.id_tournament, name_tournament, image_archetype,
                    formats.id_format, name_format, archetypes.id_archetype, name_archetype,
                    IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins_decklist,
                    COUNT(result_match) AS matches_decklist, date_tournament", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people AND people.id_people = $pIdPlayer")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->groupBy("players.id_player")
                ->order("tournaments.date_tournament", "DESC")
                ->execute($this->handler);
            return $data;
        }

        // TODO handle results limit
        public function getDecklistsByIdCard ($pIdCard) {
            $data = Query::select(
                "players.id_player AS id_decklist, people.id_people AS id_player, arena_id AS name_player,
                    tournaments.id_tournament, name_tournament, date_tournament, image_archetype, count_main, count_side,
                    formats.id_format, name_format, archetypes.id_archetype, name_archetype, colors_archetype,
                    IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins_decklist,
                    IF(SUM(result_match) IS NULL, COUNT(result_match), COUNT(result_match)-SUM(result_match)) AS loss_decklist,
                    IF(SUM(result_match) IS NULL, '0-0', CONCAT(SUM(result_match), '-', COUNT(result_match)-SUM(result_match))) AS result_decklist", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format AND formats.id_type_format != " . ModelFormat::TYPE_FORMAT_LIMITED_ID)
                ->join("player_card", Query::JOIN_INNER, "player_card.id_player = players.id_player AND id_card = $pIdCard")
                ->groupBy("players.id_player")
                ->order("tournaments.date_tournament", "DESC")
                ->limit(0, 200)
                ->execute($this->handler);
            $mCard = new ModelCard();
            foreach ($data as $key => $item) {
                $cards = $mCard->getDecklistCards($item['id_decklist']);
                $data[$key]['export_arena'] = $this->convertToArenaFormat($cards);
                $data[$key]["cards"] = $cards;
            }
            return $data;
        }

        // TODO add Companion
        public function convertToArenaFormat ($cards) {
            $maindeck = "";
            $sideboard = "";
            foreach ($cards as $card) {
                $card['name_card'] = explode("/", $card['name_card'], 2);
                $card['name_card'] = $card['name_card'][0];
                if ($card['count_main'] > 0) {
                    $maindeck .= $card['count_main'] . " " . $card['name_card'] . "\r\n";
                }
                if ($card['count_side'] > 0) {
                    $sideboard .= $card['count_side'] . " " . $card['name_card'] . "\r\n";
                }
            }
            return $maindeck . "\r\n\r\n" . $sideboard;
        }

        public function convertToTextFormat ($cards) {
            $maindeck = "";
            $sideboard = "";
            foreach ($cards as $card) {
                $card['name_card'] = explode("/", $card['name_card'], 2);
                $card['name_card'] = $card['name_card'][0];
                if ($card['count_main'] > 0) {
                    $maindeck .= $card['count_main'] . " " . $card['name_card'] . "\r\n";
                }
                if ($card['count_side'] > 0) {
                    $sideboard .= $card['count_side'] . " " . $card['name_card'] . "\r\n";
                }
            }
            return $maindeck . "\r\nSideboard\r\n" . $sideboard;
        }

        public function allByFormat ($pIdFormat, $pCond = null, $pFields = "") {
            if (!$pCond) {
                $cond = Query::condition();
            } else {
                $cond = clone $pCond;
            }
            $fields = "players.id_player, name_archetype, SUM(result_match) AS wins, COUNT(result_match) AS total";
            if ($pFields) {
                $fields = $pFields . ", $fields";
            }
            $players = Query::select($fields, $this->table)
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament AND id_format = $pIdFormat")
                ->join("archetypes", Query::JOIN_OUTER_LEFT, "players.id_archetype = archetypes.id_archetype")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->andCondition($cond)
                ->groupBy("players.id_player")
                ->order("players.id_player")
                ->execute($this->handler);
            return $players;
        }

        public function getDataByPlayerId ($pIdPlayer) {
            $data = Query::select(
                "players.id_player, tournaments.id_tournament, name_tournament, name_format, name_archetype, decklist_player, name_deck,
                    formats.id_type_format, arena_id, IF(SUM(result_match) IS NULL, 0, SUM(result_match)) AS wins,
                    COUNT(result_match) AS matches, pc.count_cards_main, pc.count_cards_side", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people AND players.id_player = $pIdPlayer")
                ->join("archetypes", Query::JOIN_OUTER_LEFT, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->join("formats", Query::JOIN_INNER, "tournaments.id_format = formats.id_format")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->join("(SELECT id_player, SUM(count_main) AS count_cards_main, SUM(count_side) AS count_cards_side FROM player_card WHERE id_card != 0 GROUP BY id_player) AS pc",
                    Query::JOIN_OUTER_LEFT, "pc.id_player = players.id_player")
                ->groupBy("players.id_player")
                ->limit(0, 1)
                ->execute($this->handler);
            return $data[0];
        }

        public function getPlayersByTournamentId ($pIdTournament) {
            $q = Query::select("players.id_player, arena_id, name_deck, name_archetype, image_archetype,
                IF (SUM(result_match) IS NULL, 0, SUM(result_match))AS wins, tag_player, country_player,
                COUNT(result_match) AS matches", $this->table)
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->join("player_tag", Query::JOIN_OUTER_LEFT, "player_tag.id_people = people.id_people")
                ->andWhere("players.id_tournament", Query::EQUAL, $pIdTournament)
                ->groupBy("players.id_player")
                ->order("wins DESC, matches, name_archetype, arena_id");
            return $q->execute($this->handler);
        }

        public function searchPlayerByDecklistName ($pName, $pCount = false, $pLimit = 10) {
            $q = Query::select("archetypes.id_archetype, name_archetype, formats.id_format, name_format, image_archetype, COUNT(1) AS count_players", $this->table)
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
                ->order("formats.id_format DESC, arena_id, date_tournament", "DESC")
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
                ->limit(0, 50);
            return $q->execute($this->handler);
        }

        public function countArchetypes ($pCondition = null, $pWinrate = false, $pExcludeMirror = true, $pLimit = null) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $select_fields = "archetypes.id_archetype, name_archetype, image_archetype, colors_archetype, COUNT(DISTINCT players.id_player) AS count";
            if ($pWinrate) {
                $select_fields .= ", ROUND(SUM(IF (op.id_player IS NULL, 0, result_match))/COUNT(op.id_player), 3) AS winrate_archetype, COUNT(op.id_player) AS total_matches_archetype";
            }
            $q = Query::select($select_fields, $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = " . $this->table . ".id_tournament")
                ->join("archetypes", Query::JOIN_INNER, "archetypes.id_archetype = " . $this->table . ".id_archetype")
                ->andCondition($pCondition)
                // exclude archetypes from dashboard
                //->andWhere("archetypes.id_archetype", Query::NOT_IN, "(13, 73, 77, 83, 84, 92, 93, 94)", false)
                ->groupBy("name_archetype")
                ->order("FIELD (players.id_archetype, " . ModelArchetype::ARCHETYPE_OTHER_ID . "), count DESC, name_archetype");
            if ($pWinrate) {
                $q->join("matches", Query::JOIN_INNER, "matches.id_player = " . $this->table . ".id_player");
                if ($pExcludeMirror) {
                    $q->join("players op", Query::JOIN_OUTER_LEFT, "matches.opponent_id_player = op.id_player AND players.id_archetype != op.id_archetype");
                }
            }
            $data = $q->execute($this->handler);
            $sum = 0;
            foreach ($data as $d) {
                $sum += $d['count'];
            }
            foreach ($data as &$d) {
                $d['percent'] = round(100*$d['count']/$sum, 1);
            }
            if ($pLimit) {
                $data = array_slice($data, 0, $pLimit);
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

        // TODO sort colors
        // TODO get produced mana only from lands
        /**
         * Get decklists colors
         * ** do not handle colorless (C) mana for now
         * ** get maindeck + sideboard cards
         * @param $pIdDecklist
         * @return array
         */
        public function getColorsByDecklistId ($pIdDecklist) {
            $colors = array("W","U","B","R","G", "C");
            $data = Query::select("GROUP_CONCAT(mana_cost_card SEPARATOR '') AS mana_costs, GROUP_CONCAT(produced_mana_card SEPARATOR '') AS produced_mana", "player_card")
                ->join("cards", Query::JOIN_INNER, "cards.id_card = player_card.id_card AND count_main > 0")
                ->andWhere("id_player", Query::EQUAL, $pIdDecklist)
                ->execute($this->handler);

            $produced_mana = array();
            if ($data[0]['produced_mana']) {
                $chars = count_chars($data[0]['produced_mana'], 1);
                foreach ($chars as $key => $char) {
                    $produced_mana[] = chr($key);
                }
            } else {
                trace_r("ERROR : no produced mana found");
            }
            $mana_costs = array();
            if ($data[0]['mana_costs']) {
                $chars = count_chars($data[0]['mana_costs'], 1);
                foreach ($chars as $key => $char) {
                    if (in_array(chr($key), $colors)) {
                        $mana_costs[] = chr($key);
                    }
                }
            } else {
                trace_r("ERROR : no mana costs found");
            }

            $deck_colors = array_intersect($mana_costs, $produced_mana);
            // sort by colors
            $deck_colors = array_intersect($colors, $deck_colors);
            return $deck_colors;
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

        public function getPlayersByCountry ($pIdCountry, $pIdTournaments = array()) {
            $players = Query::select("people.id_people, arena_id, country_player, players.id_tournament, name_tournament,
                tag_player, IF (name_archetype = 'Other', name_deck, name_archetype) AS player_archetype,
                SUM(result_match) AS wins, COUNT(result_match) AS total", $this->table)
                ->join("matches", Query::JOIN_OUTER_LEFT, "matches.id_player = players.id_player")
                ->join("people", Query::JOIN_INNER, "people.id_people = players.id_people")
                ->join("player_tag", Query::JOIN_INNER, "people.id_people = player_tag.id_people")
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament")
                ->join("archetypes", Query::JOIN_INNER, "players.id_archetype = archetypes.id_archetype")
                ->andWhere("country_player", Query::EQUAL, $pIdCountry)
                ->andWhere("players.id_tournament", Query::IN, "(" . implode(",", $pIdTournaments) . ")", false)
                ->groupBy("people.id_people, players.id_tournament")
                ->order("people.arena_id, players.id_tournament")
                ->execute($this->handler);
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

        public function getPlayerIdByTournamentIdArenaId ($pTournamentId, $pArenaId, $pSecondArenaId = "", $pTagPlayer = "") {
            $q = Query::select("id_player", $this->table)
                ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " .
                    $pTournamentId . " AND people.arena_id = '" . $pArenaId . "'");
            if ($pTagPlayer) {
                $q->join("player_tag", Query::JOIN_INNER, "player_tag.id_people = people.id_people")
                    ->andWhere("tag_player", Query::EQUAL, $pTagPlayer);
            }
            $id_player = $q->execute($this->handler);
            if (!$id_player && $pSecondArenaId != "") {
                $id_player = Query::select("id_player", $this->table)
                    ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " .
                        $pTournamentId . " AND people.discord_id = '" . $pSecondArenaId . "'")
                    ->execute($this->handler);
            }
            if (!$id_player && preg_match('/^([^#]+)#([0-9]+)/', $pArenaId, $output_array)) {
                $id_player = Query::select("id_player", $this->table)
                    ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " .
                        $pTournamentId . " AND (people.arena_id LIKE '%#" . $output_array[2] . "' OR people.arena_id LIKE '" . $output_array[1] . "#%')")
                    ->execute($this->handler);
            } elseif (!$id_player) {
                $id_player = Query::select("id_player", $this->table)
                    ->join("people", Query::JOIN_INNER, $this->table . ".id_people = people.id_people AND players.id_tournament = " .
                        $pTournamentId . " AND (people.discord_id = '$pArenaId' OR people.arena_id = '" . htmlentities($pArenaId, 0, 'UTF-8') .
                        "' OR people.arena_id = '" . str_replace("&#039;", "'", $pArenaId) . "')")
                    ->execute($this->handler);
            }
            // cancel if several players could match
            if (count($id_player) > 1) {
                trace_r("WARNING : several players found for tournament $pTournamentId");
                trace_r($id_player);
                $id_player = array();
            }
            return $id_player ? $id_player[0]['id_player'] : null;
        }

        public function getLastDecklistIdByArchetypeId ($pIdArchetype, $pIdFormat = null) {
            $q = Query::select("id_player", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("players.id_archetype", Query::EQUAL, $pIdArchetype)
                ->order("date_tournament", "DESC")
                ->limit(0, 1);
            if ($pIdFormat) {
                $q->andWhere("tournaments.id_format", Query::EQUAL, $pIdFormat);
            }
            $id_decklist = $q->execute($this->handler);
            return $id_decklist ? $id_decklist[0]['id_player'] : null;
        }

        public function getPlayerByFormatId ($pIdFormat, $pFields = "players.*") {
            $players = Query::select($pFields, "players")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("id_format", Query::EQUAL, $pIdFormat)
                ->execute($this->handler);
            return $players;
        }

        public function countPlayersWithoutDecklist ($pCondition = null) {
            if(!$pCondition)
                $pCondition = Query::condition();
            $res = Query::select("COUNT(1) AS nb", $this->table)
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("id_archetype", Query::IS, "NULL", false)
                ->andCondition($pCondition)
                ->execute($this->handler);
            return $res[0]['nb'];
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
            return $data[0]['nb'];
        }

        public function countPlayersByIdFormat ($pIdFormat, $pCond = null) {
            if (!$pCond) {
                $pCond = Query::condition();
            }
            $q = Query::select("count(1) as nb", "players p")
                ->join("tournaments", Query::JOIN_INNER, "p.id_tournament = tournaments.id_tournament")
                ->setCondition(clone $pCond)
                ->andWhere("tournaments.id_format", Query::EQUAL, $pIdFormat);
            $data = $q->execute($this->handler);
            return $data[0]['nb'];
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
                ->join("matches", Query::JOIN_OUTER_LEFT, "players.id_player = matches.id_player")
                ->andWhere("decklist_player", Query::IN, "(" . $subquery . ")", false)
                ->groupBy("id_player")
                ->execute($this->handler);
            return $duplicates;
        }
    }
}