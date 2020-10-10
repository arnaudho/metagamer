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

        public function getArchetypesRules () {
            $archetyes_file = Core::$path_to_application."/src/archetypes.json";

            try
            {
                $mapping = SimpleJSON::import($archetyes_file);
            }
            catch(\Exception $e)
            {
                return null;
            }
            return $mapping;
        }

        public function evaluatePlayerArchetype ($pIdPlayer, $pWrite = true) {
            $player = $this->modelPlayer->getTupleById($pIdPlayer);
            if (!$player) {
                trace_r("ERROR : Player $pIdPlayer not found");
                return false;
            }
            $cards = $this->modelCard->getPlayedCards(Query::condition()->andWhere("player_card.id_player", Query::EQUAL, $pIdPlayer));

            // TODO add : if no cards found, call URL to get deck details
            if (!$cards) {
                trace_r("ERROR : No cards found for player #$pIdPlayer");
                return false;
            }
            $deck = "";
            foreach ($cards as $card) {
                $deck .= $card['name_card'] . " 00 ";
            }
            $name_archetype = self::decklistMapper($deck);

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
                ->execute();
            return $archetypes;
        }

        /**
         * Returns archetype according to cards found in decklist
         */
        static public function decklistMapper ($pDecklist) {
            $archetyes_file = Core::$path_to_application."/src/archetypes.json";

            try
            {
                $mapping = SimpleJSON::import($archetyes_file);
            }
            catch(\Exception $e)
            {
                return null;
            }
            $archetype = self::ARCHETYPE_OTHER;
            foreach ($mapping as $name => $deck) {
                if (!array_key_exists('contains', $deck)) {
                    continue;
                }
                $next = false;
                foreach ($deck['contains'] as $key => $card) {
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
            return $archetype;
        }
    }
}