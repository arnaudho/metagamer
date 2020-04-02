<?php
namespace app\main\controllers\back
{

    use core\application\Application;
    use core\application\DefaultController;
	use core\application\InterfaceController;
	use core\application\Go;
	use core\application\Core;
	use core\tools\Menu;
	use core\tools\form\Form;
	use core\utils\Logs;

	class index extends DefaultController implements InterfaceController
	{
		public function __construct()
		{

		}

		public function index()
		{
            $authHandler = Application::getInstance()->authenticationHandler;
            if(!call_user_func_array(array($authHandler, 'is'), array($authHandler::ADMIN)))
				Go::to("index", "login");
			$menu = new Menu(Core::$path_to_application.'/modules/back/menu.json');
			$menu->redirectToDefaultItem();
		}

		public function login()
		{
            $authHandler = Application::getInstance()->authenticationHandler;
			if(call_user_func_array(array($authHandler, 'is'), array($authHandler::ADMIN)))
				Go::to();
			$this->setTitle("Espace d'adminitration | Connexion");
			$form = new Form("login");
			if($form->isValid())
			{
				$data = $form->getValues();
                $authHandlerInst = call_user_func_array(array($authHandler, 'getInstance'), array());
				if($authHandlerInst->setAdminSession($data["login"], $data["mdp"]))
				{
					Go::to();
				}
				else
				{
					Logs::write("Tentative de connexion au backoffice <".$data["login"].":".$data["mdp"].">", Logs::WARNING);
					$this->addContent("error", "Le login ou le mot de passe est incorrect");
				}
			}
			else
				$this->addContent("error", $form->getError());
			$this->addForm("login", $form);
		}

		public function logout()
		{
            $authHandler = Application::getInstance()->authenticationHandler;
            call_user_func_array(array($authHandler, 'unsetUserSession'), array());
			Go::to();
		}
	}
}
