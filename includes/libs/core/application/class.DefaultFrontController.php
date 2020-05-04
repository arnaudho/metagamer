<?php

namespace core\application
{
    /**
     * Controller front office de base
     * @package core\application
     */
    class DefaultFrontController extends DefaultController implements InterfaceController {

        public function __construct()
        {
            $authHandler = Application::getInstance()->authenticationHandler;
            if(!call_user_func_array(array($authHandler, 'is'), array($authHandler::USER)))
                Go::to();
            Autoload::addComponent("Metagamer");
            /*
             * TODO : handle menu
            $menu = new Menu(Core::$path_to_application.'/modules/back/menu.json');
            $menu->redirectToDefaultItem();
            */
        }

        public function index()
        {
            Go::to("index", "index");
        }

        /**
         * @param bool $pDisplay
         * @return string
         */
        public function render($pDisplay = true)
        {
            return parent::render($pDisplay);
        }
    }
}