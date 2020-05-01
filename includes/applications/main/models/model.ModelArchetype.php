<?php
namespace app\main\models {

    use core\application\BaseModel;
    use core\application\Core;
    use core\data\SimpleJSON;

    class ModelArchetype extends BaseModel {

        CONST ARCHETYPE_OTHER = "Other";

        public function __construct()
        {
            parent::__construct("archetypes", "id_archetype");
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