<?php
namespace app\main\controllers\front
{
    use app\main\models\ModelTournament;
    use app\main\src\MetagamerBot;
    use core\application\Application;
    use core\application\DefaultController;
    use core\application\Go;
    use core\tools\form\Form;
    use core\utils\Logs;

    class index extends DefaultController
    {
        public function __construct()
        {

        }

        public function index()
        {
            $authHandler = Application::getInstance()->authenticationHandler;
            if(!call_user_func_array(array($authHandler, 'is'), array($authHandler::USER))) {
                Go::to("index", "login");
            }

            Go::to("dashboard", "index");
        }

        public function login()
        {
            $authHandler = Application::getInstance()->authenticationHandler;
            if(call_user_func_array(array($authHandler, 'is'), array($authHandler::USER))) {
                Go::to();
            }
            $this->setTitle("Connexion");
            $form = new Form("login");
            if($form->isValid())
            {
                $data = $form->getValues();
                $authHandlerInst = call_user_func_array(array($authHandler, 'getInstance'), array());
                if($authHandlerInst->setUserSession($data["login"], $data["mdp"]))
                {
                    Go::to();
                }
                else
                {
                    Logs::write("Tentative de connexion <".$data["login"].":".$data["mdp"].">", Logs::WARNING);
                    $this->addContent("error", "Incorrect login or password");
                }
            }
            else
                $this->addContent("error", $form->getError());
            $this->addForm("login", $form);
        }

        public function logout() {
            $authHandler = Application::getInstance()->authenticationHandler;
            call_user_func_array(array($authHandler, 'unsetUserSession'), array());
            Go::to();
        }
    }
}
