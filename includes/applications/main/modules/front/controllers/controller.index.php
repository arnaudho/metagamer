<?php
namespace app\main\controllers\front
{
    use core\application\Application;
    use core\application\DefaultController;
    use core\application\Go;
    use core\application\Header;
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

            Go::to("home");
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
                    if (isset($_SESSION['redirect']) && !preg_match('/favicon/', $_SESSION['redirect'], $output_array)) {
                        $redirect_url = $_SESSION['redirect'];
                        unset($_SESSION['redirect']);
                        Header::location($redirect_url);
                    }
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
