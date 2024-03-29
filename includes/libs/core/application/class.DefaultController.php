<?php
namespace core\application
{
	use core\tools\form\Form;
    use core\application\event\EventDispatcher;
	use core\tools\Request;
	use core\tools\template\Template;
	use core\utils\StringUtils;

	/**
	 * Controller de base
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.1
	 * @package application
	 * @subpackage controller
	 */
	class DefaultController extends EventDispatcher
	{
		CONST MESSAGE_ERROR = "danger";
		CONST MESSAGE_WARNING = "warning";
		CONST MESSAGE_INFO = "info";
		CONST MESSAGE_SUCCESS = "success";

		/**
		 * Tableau associatif des données qu'on souhaite envoyer &agrave; la vue.
		 * @var array
		 */
		private $content = array();


		/**
		 * Tableau associatif des données contenues entre les Balises Head de la vue.
		 * @var array
		 */
		private $head = array("title"=>"", "description"=>"");


		/**
		 * Tableau associatif des formulaires qu'on souhaite envoyer &agrave; la vue.
		 * @var array
		 */
		private $forms = array();


		/**
		 * Tableau associatif des messages qu'on souhaite envoyer &agrave; la vue.
		 * @var array
		 */
		protected $messages = array();


		/**
		 * Nom de la vue (template) qu'on souhaite afficher
		 * @var String
		 */
		private $template = "";

		/**
		 * @static
		 * @param  $pControllerName
		 * @param  $pActionName
		 * @param  $pUrl
		 * @return bool
		 */
		static public function isFromDB($pControllerName, $pActionName, $pUrl)
		{
			/** MUST BE OVERRIDEN */
			return false;
		}


		/**
		 * Méthode static d'initialisation des données avant l'envoi &agrave; la vue dans le cas de contenu dynamique
		 * @return void
		 */
		public function prepareFromDB()
		{
			/** MUST BE OVERRIDEN */
			Go::to404();
		}


        /**
         * Méthode par défaut de page introuvable
         */
        public function notFound()
        {
            if(get_called_class() != __CLASS__)
            {
                Go::to404();
            }
            $this->setTemplate(null, null, 'notFound.tpl');
        }

		public function callUrl ($pUrl, $pMethod = "GET") {
			$data = "";
			$start = microtime(true);
			$r = new Request($pUrl);
			$r->setMethod($pMethod);
			$r->setOption(CURLOPT_SSL_VERIFYPEER, false);
			try {
				$data = $r->execute();
			} catch (\Exception $e) {
				$msg = $e->getMessage();
				trace_r($msg);
			}
			$end = microtime(true);
			$time = round($end - $start, 3);
			trace("REST Request <b>[" . $r->getResponseHTTPCode() . "]</b> (" . date("H:i:s", $start)." - ".StringUtils::convertToOctets(mb_strlen($data, "UTF-8"))." - ".$time."s) : <a href='".$pUrl."' target='_blank'>".$pUrl.'</a>');
			return $data;
		}

		/**
		 * Méthode public de rendu de la page en cours
		 * @param bool $pDisplay
		 * @return string
		 */
		public function render($pDisplay = true)
		{
			$conf = get_class_vars('core\\application\\Configuration');
			$conf['server_url'] = Configuration::$server_url;
			$conf['server_domain'] = Configuration::$server_domain;
			$conf['server_folder'] = Configuration::$server_folder;
            $terms = Dictionary::terms();
			$globalVars = $this->getGlobalVars();
            $t = new Template();
            $t->assign("configuration", $conf);
			foreach($globalVars as $n=>&$v)
			{
                $t->assign($n, $v);
			}
            $global = array('get'=>$_GET, 'post'=>$_POST);
            $t->assign('global', $global);
            $t->assign("dictionary", $terms);
            $t->assign("request_async", Core::$request_async);
            $t->assign("form", $this->forms);
            $t->assign("messages", $this->messages);
            Core::setupRenderer($t);
            $t->render($this->template, $pDisplay);
            return true;
		}


		/**
		 * @return array
		 */
		public function getGlobalVars()
		{
			$is = array();
            $authHandler = Application::getInstance()->authenticationHandler;
            foreach($authHandler::$permissions as $name=>$value)
                $is[$name] = $authHandler::$data&&$authHandler::is($name);
			return array(
				"path_to_theme"=>Core::$path_to_theme,
				"path_to_components"=>Core::$path_to_components,
				"scripts"=>Autoload::scripts(),
				"styles"=>Autoload::styles(),
				"head"=>$this->head,
				"forms"=>$this->forms,
				"messages"=>$this->messages,
				"content"=>$this->content,
				"user_is"=>$is,
				"controller"=>preg_replace("/\_/", "-", Core::$controller),
				"action"=>preg_replace("/\_/", "-", Core::$action)
			);
		}


		/**
		 * Méthode d'ajout de script &agrave; la vue.
		 * @param String $pScript				Nom du fichier JS
		 * @return void
		 */
		protected function addScript($pScript)
		{
			Autoload::addScript($pScript);
		}


		/**
		 * Méthode d'ajout de feuille de style &agrave; la vue
		 * @param String $pStyle				Nom du fichier CSS
		 * @return void
		 */
		protected function addStyle($pStyle)
		{
			Autoload::addStyle($pStyle);
		}


		/**
		 * Méthode d'ajout d'une variable de contenu envoyé &agrave; la vue
		 * @param String $pSmartyVar				Nom d'acc&egrave;s &agrave; la variable
		 * @param mixed $pContent					Valeur de la variable acc&egrave;s tout type (String, Object, array, int...)
		 * @return void
		 */
		protected function addContent($pSmartyVar, $pContent)
		{
			$this->content[$pSmartyVar]=$pContent;
		}

		/**
		 * Méthode de récupération du contenu d'une variable
		 * @param String $pSmartyVar
		 * @return mixed
		 */
		protected function getContent($pSmartyVar)
		{
			if(!isset($this->content[$pSmartyVar]))
				return "";
			return $this->content[$pSmartyVar];
		}


		/**
		 * @param $pMessage
		 * @param string $pType
		 */
		protected function addMessage($pMessage, $pType = self::MESSAGE_INFO)
		{
			if (
				$pType == self::MESSAGE_ERROR ||
				$pType == self::MESSAGE_WARNING ||
				$pType == self::MESSAGE_INFO ||
				$pType == self::MESSAGE_SUCCESS
			) {
				$this->messages[] = array(
					"type"    => $pType,
					"message" => $pMessage
				);
			}
		}


		/**
		 * Méthode d'ajout d'un formulaire envoyé &agrave; la vue
		 * @param String $pName				Nom d'acc&egrave;s au formulaire
		 * @param Form $pForm
		 * @return void
		 */
		protected function addForm($pName, Form &$pForm)
		{
			$pForm->prepareToView();
			$this->forms[$pName] = $pForm;
		}


		/**
		 * Méthode de définition de la valeur pour la balise Title contenue entre les balises Head de la vue
		 * @param String $pTitle				SEO : 5 mots de longueur moyenne pour 70 caract&egrave;res espace compris
		 * @return void
		 */
		public function setTitle($pTitle)
		{
			$this->head['title'] = $pTitle;
		}


		/**
		 * Méthode définition de la valeur pour la balise Meta - description - contenue entre les balises Head de la vue
		 * @param String $pDescription				SEO : 150 caract&egrave;res espace compris
		 * @return void
		 */
		public function setDescription($pDescription)
		{
			$this->head['description'] = $pDescription;
		}


		/**
		 * @param $pFolder
		 * @param $pName
		 * @param string $pFile
		 * @return void
		 */
		public function setTemplate($pFolder, $pName, $pFile = "")
		{
			if(!empty($pFile))
				$this->template = $pFile;
			else
				$this->template = $pFolder."/".$pName.".tpl";
		}

		/**
		 * Destructor
		 * @return void
		 */
		public function __destruct()
		{
			unset($this->forms);
			unset($this->content);
			unset($this->head);
			$this->removeAllEventListeners();
		}
	}
}
