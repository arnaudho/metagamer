<?php

namespace core\application
{

    use core\tools\Menu;

    /**
     * Controller front office de base
     * @package core\application
     */
    class DefaultFrontController extends DefaultController implements InterfaceController {

        /**
         * @var Menu
         */
        protected $menu;

        public function __construct()
        {
            $authHandler = Application::getInstance()->authenticationHandler;
            if(!call_user_func_array(array($authHandler, 'is'), array($authHandler::USER))) {
                $_SESSION['redirect'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                Go::to("index", "login");
            }
            Autoload::addComponent("Metagamer");

            $data = $authHandler::$data;
            $this->menu = new Menu(Core::$path_to_application.'/modules/front/menu.json', $data['permissions_user']);
        }

        public function index()
        {
            $this->menu->redirectToDefaultItem();
//            Go::to("index", "index");
        }

        /**
         * @param bool $pDisplay
         * @return string
         */
        public function render($pDisplay = true)
        {
            $this->addContent('menu_items', $this->menu->retrieveItems());
            return parent::render($pDisplay);
        }

        /**
         * Méthode de page introuvable
         */
        public function notFound()
        {
            $this->setTemplate(null, null, 'notFound.tpl');
        }
    }
}