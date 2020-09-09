<?php

namespace app\main\controllers\front {

    use app\main\models\ModelCard;
    use core\application\DefaultFrontController;
    use core\data\SimpleJSON;
    use core\db\Query;

    class card extends DefaultFrontController
    {
        protected $modelCard;

        public function __construct()
        {
            parent::__construct();
            $this->modelCard = new ModelCard();
        }

        public function import()
        {
            $this->setTemplate("index", "index");

            $sets = array(
                // HISTORIC
                "XLN", "RIX", "DOM", "M19", "HA1", "HA2", "HA3", "JMP", "AKR",
                // STANDARD
                "RNA", "GRN", "WAR", "M20", "ELD", "THB", "IKO", "M21"
            );
            $set = strtoupper($_GET['set']);

            if ($set && in_array($set, $sets)) {

                $calls = array(
                    "https://api.scryfall.com/cards/search?order=cmc&q=set%3A$set&page=1",
                    "https://api.scryfall.com/cards/search?order=cmc&q=set%3A$set&page=2",
                    "https://api.scryfall.com/cards/search?order=cmc&q=set%3A$set&page=3"
                );

                $cards = array();
                foreach ($calls as $url) {
                    $d = $this->callUrl($url);
                    $d = SimpleJSON::decode($d);

                    if (array_key_exists('data', $d)) {
                        $cards = array_merge($cards, $d['data']);
                    }
                }
                trace_r("Importing set '$set' -- " . count($cards) . " cards");

                foreach ($cards as $card) {
                    $exists = $this->modelCard->one(Query::condition()
                        ->andWhere("name_card", Query::EQUAL, $card['name'])
                    );
                    $card['name'] = str_replace("&#39;", "'", $card['name']);
                    $type_card = explode("—", $card['type_line']);
                    $card_data = array(
                        "mana_cost_card" => $card['mana_cost'],
                        "color_card" => implode("", $card['colors']),
                        "type_card" => trim($type_card[0]),
                        "cmc_card" => $card['cmc'],
                        "set_card" => $card['set'],
                        "image_card" => $card['image_uris']['png']
                    );
                    if (isset($card['produced_mana'])) {
                        $card_data['produced_mana_card'] = implode("", $card['produced_mana']);
                    }
                    if ($exists) {
                        // update card
                        $this->modelCard->updateById($exists['id_card'], $card_data);
                    } else {
                        $card_data['name_card'] = $card['name'];
                        $this->modelCard->insert($card_data);
                    }
                }
            } else {
                trace_r("Set '$set' not recognized");
            }

            return true;
        }
    }
}