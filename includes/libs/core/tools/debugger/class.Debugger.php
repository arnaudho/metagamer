<?php
namespace core\tools\debugger
{
	use core\application\PrivateClass;
	use core\application\Singleton;
	use core\application\Core;
	use core\application\Configuration;
    use core\tools\template\RenderingContext;
    use core\utils\Logs;
	use core\application\Autoload;
	use core\application\Header;
	use \Exception;

	/**
	 * Class Debugger - Permet de centraliser les éventuelles "sorties" permettant de debugger l'application
	 * 			Gère :
	 * 				- les Erreurs
	 * 				- les Exceptions
	 * 				- les Sorties
	 * 				- la liste des Requêtes SQL
	 * 				- les variables globales $_GET, $_POST, $_SESSION
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .5
	 * @package tools
	 * @subpackage debugger
	 */
	class Debugger extends Singleton
	{
		/**
		 * Constante d'exception soulevée par l'utilisateur
		 */
		const E_USER_EXCEPTION = -1;

		/**
		 * Temps nécessaire &agrave; l'excecution de l'ensemble de l'application
		 * @var Number
		 */
		private $timeToGenerate;

		/**
		 * @var Number
		 */
		private $memUsage;

		/**
		 * Variable permettant de définir si le debugger est ouvert par défault ou non
		 * @var Boolean
		 */
		static private $open = false;

		/**
		 * @var string
		 */
		static private $state = "odd";

		/**
		 * @var string
		 */
		private $consoles = "";

		/**
		 * @var array
		 */
		private $count = array(
			"trace"=>0,
			"notice"=>0,
			"warning"=>0,
			"error"=>0,
			"query"=>0,
			"get"=>0,
			"post"=>0,
			"session"=>0,
			"cookie"=>0
		);

		/**
		 * @static
		 * @param $pClass
		 * @param $pMessage
		 * @param $pFile
		 * @param $pLine
		 * @return void
		 */
		static private function addToConsole($pClass, $pMessage, $pFile, $pLine)
		{
            /** @var Debugger $i */
            $i = self::getInstance();
			$time = explode(".", microtime(true));
			if (!isset($time[1])) $time[1] = "000";
			$decalage = (60 * 60) * ((date("I") == 0) ?1:2);
			$i->count[$pClass]++;
			$pClass .= " ".self::$state;
			self::$state = self::$state == "odd"?"even":"odd";
            $i->consoles .= "<tr class='".$pClass."'><td class='date'>".(gmdate("H:i:s", $time[0] + $decalage).",".$time[1])."</td><td class='".$pClass."'>&nbsp;&nbsp;</td><td class='message'>".$pMessage."</td><td class='file'>".$pFile.":".$pLine."</td></tr>";
		}

		/**
		 * Méthode d'ajout d'une sortie &agrave; la variable dédiée &agrave; cet effet
		 * @param String $pString					Chaine de caract&egrave;re &agrave; afficher
		 * @param Boolean $pOpen [optional]			Définit si le debugger est ouvert par défault
		 * @return void
		 */
		static public function trace($pString, $pOpen = false)
		{
			if(!self::$open&&$pOpen===true)
				self::$open = true;
			if(is_bool($pString))
				$pString = $pString ? "true":"false";
			if(empty($pString))
				$pString = '<i>Debugger::trace("");</i>';
			$context = debug_backtrace();
			$indice = 0;
			for($i=0, $max = count($context);$i<$max;$i++)
			{
				if(!isset($context[$i]["class"])
					&&($context[$i]["function"]==="trace"
						||$context[$i]["function"]==="trace_r"))
				{
					$indice = $i;
					$i = $max;
				}
			}
			$file = pathinfo($context[$indice]["file"]);
			$file = $file["basename"];

			self::addToConsole("trace", $pString, $file, $context[$indice]["line"]);
		}

		/**
		 * Méthode permettant d'ajouter le contenu d'un tableau &agrave; la liste de sortie du Debugger
		 * @param array	 	$pArray					Tableau dont on souhaite afficher le contenu
		 * @param Boolean	$pOpen [optional]		Définit si le debugger est ouvert par défault
		 * @return void
		 */
		static public function traceR($pArray, $pOpen = false)
		{
			$string = "<pre>".print_r($pArray,true)."</pre>";
			self::trace($string,$pOpen);
		}

		/**
		 * @static
		 * @param $pQuery
		 * @param $pSource
		 * @param $pDb
		 */
		static public function query($pQuery, $pSource, $pDb)
		{
			self::addToConsole("query", $pQuery, $pSource, $pDb);
		}


		/**
		 * Méthode d'affichage du debugger
		 * @param bool $pDisplay
		 * @param bool $pError
		 * @return string
		 */
		public function render($pDisplay = true, $pError = false)
		{
            $dir_to_theme = "http://".Configuration::$server_domain."/".(isset(Configuration::$server_folder)?Configuration::$server_folder."/":"")."includes/libs/core/tools/debugger";
            $ctx = new RenderingContext("includes/libs/core/tools/debugger/templates/template.debugger.php");
            $ctx->assign('is_error', $pError);
            $ctx->assign('dir_to_theme', $dir_to_theme);
            $ctx->assign('dir_to_components', Core::$path_to_components);
            $ctx->assign('server_url', Configuration::$server_url);
			$globalVars = $this->getGlobalVars();
			foreach($globalVars as $n=>&$v)
                $ctx->assign($n, $v);
            return $ctx->render($pDisplay);
		}


		/**
		 * @return array
		 */
		public function getGlobalVars()
		{
			$this->setTimeToGenerate(INIT_TIME, microtime(true));
			$this->setMemoryUsage(INIT_MEMORY, memory_get_usage(MEMORY_REAL_USAGE));
			$this->count["get"] = count($_GET);
			$this->count["post"] = count($_POST);
			$this->count["cookie"] = count($_COOKIE);
			$this->count["session"] = count($_SESSION);
			return array(
				"console"=>$this->consoles,
				"timeToGenerate"=>(round($this->timeToGenerate,3))." sec",
				"memUsage"=>$this->memUsage,
				"vars"=>array("get"=>print_r($_GET, true),
					"post"=>print_r($_POST, true),
					"cookie"=>print_r($_COOKIE, true),
					"session"=>print_r($_SESSION, true)
				),
				"count"=>$this->count,
				"open"=>self::$open
			);
		}


		/**
		 * Méthode de définition du temps nécessaire &agrave; l'excecution de l'application
		 * @param String $pStartTime		Microtime de début
		 * @param String $pEndTime          Microtime de fin
		 * @return void
		 */
		private function setTimeToGenerate($pStartTime, $pEndTime)
		{
			if(!$pEndTime)
				$pEndTime = microtime(true);
			$this->timeToGenerate = ($pEndTime - $pStartTime);
		}

		/**
		 * @param $pStartMem
		 * @param $pEndMem
		 */
		private function setMemoryUsage($pStartMem, $pEndMem)
		{
			$mem = $pEndMem - $pStartMem;
			$units = array("o", "ko", "Mo", "Go");
			$k = 0;
			while($units[$k++] && $mem>1024)
				$mem /= 1024;
			$mem = round($mem*100)/100;
			$this->memUsage = $mem." ".$units[--$k];
		}

		/**
		 * Gestionnaire des erreurs de scripts Php
		 * Peut stopper l'application en cas d'erreur bloquante
		 * @param Number $pErrorLevel						Niveau d'erreur
		 * @param String $pErrorMessage						Message renvoyé
		 * @param String $pErrorFile						Adresse du fichier qui a déclenché l'erreur
		 * @param Number $pErrorLine						Ligne où se trouve l'erreur
		 * @param String $pErrorContext						Contexte
		 * @return void
		 */
		static public function errorHandler($pErrorLevel, $pErrorMessage, $pErrorFile, $pErrorLine, $pErrorContext)
		{
			$stopApplication = false;
			switch($pErrorLevel)
			{
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
					$stopApplication = true;
					$type = "error";
					break;
				case E_WARNING:
				case E_CORE_WARNING:
				case E_COMPILE_WARNING:
				case E_USER_WARNING:
					$type = "warning";
					break;
				case E_NOTICE:
				case E_USER_NOTICE:
					$type = "notice";
					break;
				default:
				case self::E_USER_EXCEPTION:
					$stopApplication = true;
					$type = "error";
					break;
			}
			$pErrorFile = pathinfo($pErrorFile);
			$pErrorFile = $pErrorFile["basename"];
			if(preg_match('/href=/', $pErrorMessage, $matches))
				$pErrorMessage = preg_replace('/href=\'([a-z\.\-\_]*)\'/', 'href=\'http://www.php.net/$1\' target=\'_blank\'', $pErrorMessage);
			self::addToConsole($type, $pErrorMessage, $pErrorFile, $pErrorLine);
			if($stopApplication)
			{
				if(!Core::debug())
				{
					Logs::write($pErrorMessage." ".$pErrorFile." ".$pErrorLine." ".$pErrorContext, $pErrorLevel);
				}
				Header::contentType("text/html", Configuration::$global_encoding);
				self::$open = true;
				self::getInstance()->render(true, true);
                Core::endApplication();
			}
		}

		/**
		 * Gestionnaire d'exceptions soulevées lors de l'exécution du script
		 * @param Exception $pException
		 * @return void
		 */
		static public function exceptionHandler($pException)
		{
			self::errorHandler(self::E_USER_EXCEPTION, $pException->getMessage(), $pException->getFile(), $pException->getLine(), $pException->getFile());
		}

		/**
		 * @static
		 * @return void
		 */
		static public function prepare()
		{
			Autoload::addComponent("Debugger");
		}

		/**
		 * Construct
		 * @param $pInstance	PrivateClass
		 */
		public function __construct($pInstance)
		{
			if(!$pInstance instanceOf PrivateClass)
				trigger_error("Il est interdit d'instancier un objet de type <i>Singleton</i> - Merci d'utiliser la méthode static <i>".__CLASS__."::getInstance()</i>", E_USER_ERROR);
		}

		/**
		 * ToString()
		 * @return String
		 */
		public function __toString()
		{
			return "[Objet Debugger]";
		}
	}
}

namespace
{
	use core\tools\debugger\Debugger;

	function trace($pString, $pOpen = false)
	{
		Debugger::trace($pString, $pOpen);
	}

	function trace_r($pArray, $pOpen = false)
	{
		Debugger::traceR($pArray, $pOpen);
	}
}
