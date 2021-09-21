<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\application\Core;
    use core\data\SimpleJSON;
    use core\db\Query;

    class ModelArchetype extends BaseModel {

        CONST ARCHETYPE_OTHER = "Other";
        CONST ARCHETYPE_OTHER_ID = 3;
        protected $modelPlayer;
        protected $modelCard;

        public function __construct()
        {
            $this->modelPlayer = new ModelPlayer();
            $this->modelCard = new ModelCard();
            parent::__construct("archetypes", "id_archetype");
        }

        public function getArchetypesGroupsByFormat ($pIdFormat) {
            $archetypes = Query::select("name_archetype, name_deck, COUNT(*) AS count, decklist_player", $this->table)
                ->join("players", Query::JOIN_INNER, $this->table . "." . $this->id . " = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("id_format", Query::EQUAL, $pIdFormat)
                ->andWhere("name_deck", Query::NOT_EQUAL, "''", false)
                ->groupBy("name_archetype, name_deck")
                ->execute($this->handler);
            return $archetypes;
        }

        public function getFullWinrateByIdCardIdArchetype ($pIdCard, $pIdArchetype, $pIdFormat) {
            $winrates = Query::select("op.id_archetype, count_main, COUNT(1) AS total, SUM(result_match) AS wins", "players")
                ->join("player_card", Query::JOIN_OUTER_LEFT, "players.id_player = player_card.id_player AND id_card = $pIdCard")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament AND id_format = $pIdFormat AND players.id_archetype = $pIdArchetype")
                ->join("matches", Query::JOIN_INNER, "matches.id_player = players.id_player")
                ->join("players op", Query::JOIN_INNER, "matches.opponent_id_player = op.id_player")
                ->groupBy("op.id_archetype, count_main")
                ->execute($this->handler);
            return $winrates;
        }

        static public function getArchetypesRules ($pIdTypeFormat = ModelFormat::TYPE_FORMAT_STANDARD_ID) {
            if (array_key_exists($pIdTypeFormat, ModelFormat::MAPPING_TYPE_FORMAT)) {
                $archetyes_file = Core::$path_to_application."/src/archetypes_" . ModelFormat::MAPPING_TYPE_FORMAT[$pIdTypeFormat] . ".json";
            } else {
                trace_r("WARNING : Incorrect format specified for archetypes rules");
                return false;
            }

            try
            {
                $mapping = SimpleJSON::import($archetyes_file);
            }
            catch(\Exception $e)
            {
                trace_r("ERROR : incorrect format for archetypes.json file");
                return null;
            }
            if (!$mapping) {
                trace_r("ERROR : incorrect format for archetypes.json file");
                return null;
            }
            return $mapping;
        }

        public function decklistDiff ($pDeckA, $pDeckB) {
            $remainB = array();
            foreach ($pDeckB as $item) {
                $key = array_search($item, $pDeckA);
                if ($key !== false) {
                    unset($pDeckA[$key]);
                } else {
                    $remainB[] = $item;
                }
            }
            return array(
                "removed" => array_values($pDeckA),
                "added"   => $remainB
            );
        }

        public function evaluatePlayerArchetype ($pIdPlayer, $pIdTypeFormat, $pWrite = true) {
            $player = $this->modelPlayer->getTupleById($pIdPlayer);
            if (!$player) {
                trace_r("ERROR : Player $pIdPlayer not found");
                return false;
            }
            $name_archetype = self::ARCHETYPE_OTHER;

            // exclude sideboard cards for archetype mapping
                $cards = $this->modelCard->getPlayedCards(
                    Query::condition()
                        ->andWhere("player_card.id_player", Query::EQUAL, $pIdPlayer)
                        ->andWhere("player_card.count_main", Query::UPPER, 0, false)
                );

                // TODO add : if no cards found, call URL to get deck details
                if (!$cards) {
                    trace_r("ERROR : No cards found for player #$pIdPlayer");
                    return false;
                }
                $deck = "";
                foreach ($cards as $card) {
                    $deck .= $card['name_card'] . " 00 ";
                }
                $name_archetype = self::decklistMapper($deck, $pIdTypeFormat);


            if ($pWrite) {
                // insert archetype if needed
                $archetype = $this->one(Query::condition()->andWhere("name_archetype", Query::EQUAL, $name_archetype));
                if ($archetype) {
                    $id_archetype = $archetype['id_archetype'];
                } else {
                    $this->insert(
                        array(
                            "name_archetype" => $name_archetype
                        )
                    );
                    $id_archetype = $this->getInsertId();
                }
                $this->modelPlayer->updateById(
                    $pIdPlayer,
                    array(
                        "id_archetype" => $id_archetype
                    )
                );
            }

            return array(
                "id_archetype" => $id_archetype,
                "name_archetype" => $name_archetype
            );
        }

        public function allByFormat ($pIdFormat) {
            $archetypes = Query::select("DISTINCT archetypes.*", "archetypes")
                ->join("players", Query::JOIN_INNER, "players.id_archetype = archetypes.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament AND id_format = $pIdFormat")
                ->order("name_archetype")
                ->execute($this->handler);
            return $archetypes;
        }

        public function getByFormatByMinCount ($pIdFormat, $pMinCount, $pExcludeOther = true) {
            $q = Query::select("archetypes.*, COUNT(1) AS count_players", "archetypes")
                ->join("players", Query::JOIN_INNER, "players.id_archetype = archetypes.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "players.id_tournament = tournaments.id_tournament AND id_format = $pIdFormat")
                ->groupBy("name_archetype")
                ->having("COUNT(1) > $pMinCount", false)
                ->order("COUNT(1)", "DESC");
            if ($pExcludeOther) {
                $q->andWhere($this->table . "." . $this->id, Query::NOT_EQUAL, self::ARCHETYPE_OTHER_ID);
            }
            $archetypes = $q->execute($this->handler);
            return $archetypes;
        }

        public function getArchetypesByIdFormat($pIdFormat)
        {
            // get count players in format
            $count_players = Query::select("COUNT(DISTINCT players.id_player) AS nb", "players")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("tournaments.id_format", Query::EQUAL, $pIdFormat)
                ->execute($this->handler);
            $count_players = $count_players[0]['nb'];
            if (!$count_players) {
                return false;
            }
            $data = Query::select(
                "archetypes.id_archetype, name_archetype, colors_archetype", $this->table)
                ->join("players", Query::JOIN_INNER, "archetypes.id_archetype = players.id_archetype")
                ->join("tournaments", Query::JOIN_INNER, "tournaments.id_tournament = players.id_tournament")
                ->andWhere("tournaments.id_format", Query::EQUAL, $pIdFormat)
                ->groupBy("archetypes.id_archetype")
                ->execute($this->handler);
            return $data;
        }

        /*
         * KARSTEN algo #1
         *
         * Sort all played cards by copies and count players
         * Then add (up to 60 cards) each copies, most popular first
        */
        // work on manabase splits (ex if 10 lists play a 5-5 split on snow-covered basics
        // and some cards are played in 6 copies, the land count could be wrong in some corner cases)
        public function getAggregateList ($pCondition, $pMaindeck = true) {
            if (!$pCondition) {
                return false;
            }
            $aggregate = array();
            $count_aggregate = 0;

            // sideboard count
            $archetype_card_count = 15;
            if ($pMaindeck) {
                // get card count for archetype
                $archetype_card_count = $this->modelCard->getCardCount($pCondition);
                if ($archetype_card_count[0]['count_players'] < 5 * $archetype_card_count[1]['count_players']) {
                    trace_r("WARNING : multiple cards count in archetype");
                    trace_r($archetype_card_count);
                }
                $archetype_card_count = $archetype_card_count[0]['count_cards'];
            }


            $cards = $this->modelCard->getPlayedCardsByCopies($pCondition, $pMaindeck);
            $current_card = "--";
            $current_player_count = 0;
            // add placeholder to check last card for filling copies
            $cards[] = array('name_card' => "---");
            $fill_copies = array();
            foreach ($cards as $key => $card) {
                if (!isset($card['copie_n'])) {
                    continue;
                }
                if ($current_card != $card['name_card']) {
                    $current_card = $card['name_card'];
                    $current_player_count = $card['count_players_main'];
                } else {
                    $current_player_count += $card['count_players_main'];
                    $cards[$key]['count_players_main'] = $current_player_count;
                }

                // if next card is the same AND with copie_n != N-1, manually add missing copies into the combined list
                if (
                    isset($cards[$key+1]) &&
                    $cards[$key+1]['name_card'] == $card['name_card'] &&
                    $cards[$key+1]['copie_n'] != ($card['copie_n'] - 1)
                ) {
                    for ($i = ($cards[$key+1]['copie_n'] + 1); $i < $card['copie_n']; $i++) {
                        $tmp_card = $card;
                        $tmp_card['copie_n'] = $i;
                        $tmp_card['count_players_main'] = $current_player_count;
                        $fill_copies[] = $tmp_card;
                    }
                }

                // if next card is not same name AND card was only played in N copies, manually add copies 1 to N-1 into the combined list
                if ($card['copie_n'] != 1 &&
                    isset($cards[$key+1]) &&
                    $cards[$key+1]['name_card'] != $card['name_card']
                ) {
                    for ($i = 1; $i < $card['copie_n']; $i++) {
                        $tmp_card = $card;
                        $tmp_card['copie_n'] = $i;
                        $tmp_card['count_players_main'] = $current_player_count;
                        $fill_copies[] = $tmp_card;
                    }
                }
            }
            $cards = array_merge($cards, $fill_copies);

            // sort copies by player count
            usort($cards, array($this, "sortCardsByPlayerCount"));

            // TODO check split versions -- e.g SB Thoughtseize without any black source / 10 Forest + 6 Snow-covered Forest
            // http://complots.org/archetype/aggregatelist/?id_archetype=182&id_format=57

            if ($pMaindeck) {
                // TODO check lands count

                // get average lands count
                /*
                SELECT AVG(toto) FROM (SELECT SUM(IF(type_card LIKE '%land%', count_main, 0)) AS toto
                FROM player_card
                INNER JOIN players p ON p.id_player = player_card.id_player
                INNER JOIN cards ON cards.id_card = player_card.id_card
                INNER JOIN tournaments ON p.id_tournament = tournaments.id_tournament
                WHERE count_main != '0' AND (id_archetype = '65' AND id_format = '57')
                GROUP BY p.id_player HAVING SUM(count_main) = 60) tmp
                 */
                // when adding a card, if lands threshold is excessed AND card is land, THEN continue
            }

            foreach ($cards as $card) {
                $aggregate[] = $card['name_card'];
                if (++$count_aggregate >= $archetype_card_count) {
                    break;
                }
            }
            sort($aggregate);
            return $aggregate;
        }

        protected function sortCardsByPlayerCount ($pA, $pB) {
            if (!array_key_exists('count_players_main', $pA)) {
                return 1;
            }
            if (!array_key_exists('count_players_main', $pB)) {
                return -1;
            }
            return $pA['count_players_main'] == $pB['count_players_main'] ?
                ($pA['name_card'] > $pB['name_card'] ? 1 : -1) :
                ($pA['count_players_main'] < $pB['count_players_main'] ? 1 : -1);
        }

        /**
         * Returns archetype according to cards found in decklist
         * @param $pDecklist
         * @param $pIdTypeFormat
         * @return int|null|string
         */
        static public function decklistMapper ($pDecklist, $pIdTypeFormat) {
            $archetype = null;
            $mapping = self::getArchetypesRules($pIdTypeFormat);
            if ($mapping) {
                $archetype = self::ARCHETYPE_OTHER;

                // TODO match exact card name ?
                foreach ($mapping as $name => $deck) {
                    if (!array_key_exists('contains', $deck)) {
                        continue;
                    }
                    $next = false;
                    foreach ($deck['contains'] as $key => $card) {
                        $card = str_replace("/", "\/", $card);
                        if (!preg_match_all('/' . $card . '/i', $pDecklist, $output_array)) {
                            $next = true;
                            break;
                        }
                    }
                    if ($next) {
                        continue;
                    }
                    if (array_key_exists('exclude', $deck)) {
                        foreach ($deck['exclude'] as $key => $card) {
                            $card = str_replace("/", "\/", $card);
                            if (preg_match_all('/' . $card . '/i', $pDecklist, $output_array)) {
                                $next = true;
                                break;
                            }
                        }
                    }
                    if (!$next) {
                        $archetype = $name;
                        break;
                    }
                }
            }
            return $archetype;
        }
    }
}