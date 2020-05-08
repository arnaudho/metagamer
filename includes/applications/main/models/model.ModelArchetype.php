<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\application\Core;
    use core\data\SimpleJSON;
    use core\db\Query;

    class ModelArchetype extends BaseModel {

        CONST ARCHETYPE_OTHER = "Other";

        public function __construct()
        {
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