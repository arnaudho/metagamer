<?php
namespace app\main\controllers\front
{

    use app\main\models\ModelArchetype;
    use app\main\models\ModelFormat;
    use app\main\models\ModelPlayer;
    use core\application\DefaultController;
    use core\db\Query;

    class job extends DefaultController
    {
        protected $modelFormat;
        protected $modelPlayer;
        protected $modelArchetype;

        public function __construct()
        {
            $this->modelFormat = new ModelFormat();
            $this->modelPlayer = new ModelPlayer();
            $this->modelArchetype = new ModelArchetype();
        }

        public function index()
        {
            // list available jobs ?
            $this->setTemplate("index", "index");
        }

        // TODO set job template


        // TODO get colors from archetype aggregate instead of last decklist
        // TODO get produced mana from lands only ?
        public function archetypeColors () {
            $this->setTemplate("index", "index");
            if (
                !isset($_GET['id_format']) ||
                !$format = $this->modelFormat->getFormatById($_GET['id_format'])
            ) {
                echo "Format not found";
                return false;
            }
            $archetypes = $this->modelArchetype->getArchetypesByIdFormat($_GET['id_format']);
            $update_archetypes = 0;
            foreach ($archetypes as $archetype) {
                if ($archetype['id_archetype'] == ModelArchetype::ARCHETYPE_OTHER_ID && $archetype['colors_archetype'] != "WUBRG") {
                    $this->modelArchetype->updateById($archetype['id_archetype'], array("colors_archetype"  => "WUBRG"));
                } else {
                    // get last decklist for archetype
                    $id_decklist = $this->modelPlayer->getLastDecklistIdByArchetypeId($archetype['id_archetype']);

                    trace_r($archetype['name_archetype'] . " : " . $id_decklist);
                    // get decklist colors
                    if ($id_decklist) {
                        $colors = $this->modelPlayer->getColorsByDecklistId($id_decklist);
                        if ($this->modelArchetype->updateById($archetype['id_archetype'], array("colors_archetype" => implode("", $colors)))) {
                            $update_archetypes++;
                        }
                    }
                }
            }
            trace_r("UDPATE archetype colors : $update_archetypes");
            echo "<h3>$update_archetypes archetypes updated ! You can now close this page and refresh the dashboard.</h3>";
            return true;
        }

        public function refreshArchetypes () {
            $this->setTemplate("index", "index");
            if (
                !isset($_GET['id_format']) ||
                !$format = $this->modelFormat->getFormatById($_GET['id_format'])
            ) {
                echo "Format not found";
                return false;
            }

            $count_refresh = 0;
            $players_cond = Query::condition();
//            $players_cond->andWhere("id_archetype", Query::EQUAL, 163);
            $last_format = $this->modelFormat->getLastFormatByIdTypeFormat($format['id_type_format']);
            if ($format['id_format'] != $last_format['id_format']) {
                echo '<h4>WARNING : The format you are trying to update is not the most recent one';
                return false;
            }

            echo("<h3>Refresh tournament archetypes : " . $format['name_format'] . "</h3>");
            $players = $this->modelPlayer->allByFormat($format['id_format'], $players_cond, "players.id_archetype");
            foreach ($players as $player) {
                $new_archetype = $this->modelArchetype->evaluatePlayerArchetype($player['id_player'], $format['id_type_format']);
                if ($new_archetype && $new_archetype['id_archetype'] && $player['id_archetype'] != $new_archetype['id_archetype']) {
                    echo("<p>Update : " . $player['name_archetype'] . " => " . $new_archetype['name_archetype'] . " (<a href='http://complots.org/deck/id:" . $player['id_player'] . "/'>decklist</a>)</p>");
                    trace_r("Update : " . $player['name_archetype'] . " => " . $new_archetype['name_archetype'] . " (<a href='http://complots.org/deck/id:" . $player['id_player'] . "/'>decklist</a>)");
                    $count_refresh++;
                }
            }
            echo("<h3>Refresh tournament archetypes : $count_refresh</h3>");
            trace_r("Refresh tournament archetypes : $count_refresh");
            return true;
        }
    }
}
