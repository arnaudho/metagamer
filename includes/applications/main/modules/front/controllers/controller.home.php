<?php
namespace app\main\controllers\front {

    use app\main\models\ModelArchetype;
    use app\main\models\ModelFormat;
    use app\main\models\ModelPlayer;
    use app\main\models\ModelTournament;
    use app\main\models\ModelTypeFormat;
    use core\application\DefaultFrontController;
    use core\application\routing\RoutingHandler;
    use core\db\Query;

    class home extends DefaultFrontController
    {
        protected $modelPlayer;
        protected $modelTournament;
        protected $modelTypeFormat;
        protected $modelFormat;

        public function __construct()
        {
            parent::__construct();
            $this->modelPlayer = new ModelPlayer();
            $this->modelFormat = new ModelFormat();
            $this->modelTournament = new ModelTournament();
        }

        public function index () {}
    }
}