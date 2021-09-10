<?php
namespace app\main\controllers\front
{

    use app\main\models\ModelArchetype;
    use app\main\models\ModelFormat;
    use app\main\models\ModelPlayer;
    use core\application\DefaultController;

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

        // TODO get colors from archetype aggregate instead of last decklist
        // TODO get produced mana from lands only ?
        public function archetypeColors () {
            $this->setTemplate("index", "index");
            if (
                !isset($_GET['id_format']) ||
                !$format = $this->modelFormat->getFormatById($_GET['id_format'])
            ) {
                return false;
            }
            $archetypes = $this->modelArchetype->getArchetypesByIdFormat($_GET['id_format']);
            $update_archetypes = 0;
            foreach ($archetypes as $archetype) {
                if ($archetype['colors_archetype'] == "") {
                    // get last decklist for archetype
                    $id_decklist = $this->modelPlayer->getLastDecklistIdByArchetypeId($archetype['id_archetype']);

                    trace_r($archetype['name_archetype'] . " : " . $id_decklist);
                    // get decklist colors
                    if ($id_decklist) {
                        $colors = $this->modelPlayer->getColorsByDecklistId($id_decklist);
                        if ($this->modelArchetype->updateById($archetype['id_archetype'], array("colors_archetype"  => implode("", $colors)))) {
                            $update_archetypes++;
                        }
                    }
                }
            }
            trace_r("UDPATE archetype colors : $update_archetypes");
            echo "$update_archetypes archetypes updated ! You can now close this page and refresh the dashboard.";
            return true;
        }
    }
}
