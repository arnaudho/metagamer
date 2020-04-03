<?php
namespace app\main\models {

    use core\application\BaseModel;

    class ModelArchetype extends BaseModel {

        public function __construct()
        {
            parent::__construct("archetypes", "id_archetype");
        }

        /**
         * Returns archetype according to cards found in decklist
         */
        static public function decklistMapper ($pDecklist) {
            $mapping = array(
                "Simic Ramp" => array(
                    "Risen Reef"
                ),
                "Sultai Midrange" => array(
                    "Hydroid Krasis",
                    "Watery Grave"
                ),
                "Jeskai Fires" => array(
                    "Fires of Invention",
                    "Cavalier of Flame"
                ),
                "Bant MidRamp" => array(
                    "Teferi, Time Raveler",
                    "Breeding Pool"
                ),
                "Jund Sacrifice" => array(
                    "Korvold, Fae-Cursed King",
                    "Trail of Crumbs"
                ),
                "Rakdos Aristocrats" => array(
                    "Cauldron Familiar"
                ),
                "Temur Adventures" => array(
                    "Lucky Clover",
                    "Escape to the Wilds"
                ),
                "UW blink" => array(
                    "Thassa, Deep-Dwelling",
                    "Charming Prince"
                ),
                "UW controle" => array(
                    "Narset, Parter of Veils",
                    "The Birth of Meletis"
                ),
                "Monored aggro" => array(
                    "Torbran, Thane of Red Fell",
                    "Runaway Steam-Kin"
                ),
                "Temur Reclamation" => array(
                    "Wilderness Reclamation",
                    "Nightpack Ambusher"
                ),
                "Temur Flash" => array(
                    "Stomping Ground",
                    "Steam Vents",
                    "Breeding Pool"
                ),
                "Simic Flash" => array(
                    "Frilled Mystic"
                )
            );
            $archetype = "Other";
            foreach ($mapping as $name => $deck) {
                foreach ($deck as $key => $card) {
                    if (!preg_match_all('/' . $card . '/', $pDecklist, $output_array)) {
                        break;
                    }
                    if ($key == (count($deck) - 1)) {
                        $archetype = $name;
                        break 2;
                    }
                }
            }
            return $archetype;
        }
    }
}