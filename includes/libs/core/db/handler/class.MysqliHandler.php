<?php
namespace core\db\handler
{

    use core\db\InterfaceDatabaseHandler;
    use core\tools\debugger\Debugger;
	use \mysqli_result;
	use \mysqli;

	/**
	 * Couche d'abstraction &agrave; la base de données (type mysql improved)
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.0
	 * @package core\db\handler
	 */
	class MysqliHandler implements InterfaceDatabaseHandler
	{
        /**
         * Chemin d'acc&egrave;s &agrave; la base de données
         * @var String
         */
        protected $host;


        /**
         * Nom d'utilisateur
         * @var String
         */
        protected $user;


        /**
         * Mot de passe d'acc&egrave;s &agrave; la base de données
         * @var String
         */
        protected $mdp;

        /**
         * Nom de la base de données
         * @var String
         */
        protected $bdd;

		/**
		 * Instance mysqli
		 * @var mysqli
		 */
		private $mysqliInstance;


        /**
         * @param $pHost
         * @param $pUser
         * @param $pPassword
         * @param $pName
         */
        public function __construct($pHost, $pUser, $pPassword, $pName)
        {
            $this->host = $pHost;
            $this->user = $pUser;
            $this->mdp = $pPassword;
            $this->bdd = $pName;
            $this->connect();
        }


        /**
         *
         */
		protected function close()
		{
            if($this->mysqliInstance->connect_error)
                return;
			if($this->mysqliInstance->store_result())
				$this->mysqliInstance->store_result()->free();
			$this->mysqliInstance->close();
		}


        /**
         *
         */
		protected function connect()
		{
            $this->mysqliInstance = new mysqli($this->host, $this->user, $this->mdp, $this->bdd, 3308);
			if($this->mysqliInstance->connect_error)
				trigger_error("Connexion au serveur de gestion de base de données impossible", E_USER_ERROR);
		}


		/**
		 * Méthode permettant de récupérer les donnée d'une requêtes SQL
		 * Renvoie les données renvoyées sous forme d'un tableau associatif
		 * @param String $pQuery				Requête SQL brute
		 * @return array
		 */
		public function getResult($pQuery)
		{
			$result = $this->execute($pQuery);
			if(!$result)
				trigger_error("Une erreur est apparue lors de la requête <b>".$pQuery."</b><<br/>Error ".$this->getErrorNumber()." : <i>".$this->getError()."</i>", E_USER_ERROR);
			$return = array();
			while($donnee = $result->fetch_assoc())
			{
				array_push($return, $donnee);
			}
			$result->free();
			return $return;
		}


		/**
		 * @return string
		 */
		public function getError()
		{
			return $this->mysqliInstance->error;
		}


		/**
		 * @return int
		 */
		public function getErrorNumber()
		{
			return $this->mysqliInstance->errno;
		}


		/**
		 * Méthode de récupération de la clé primaire générée &agrave; la suite d'une insertion
		 * @return Number
		 */
		public function getInsertId()
		{
			return $this->mysqliInstance->insert_id;
		}


		/**
		 * Méthode permettant de centraliser les commandes &agrave; effectuer avant l'excécution d'une requête
		 * @param String $pQuery				Requête &agrave; excécuter
		 * @return mysqli_result
		 */
		public function execute($pQuery)
		{
			$time_start = microtime(true);
			$res = $this->mysqliInstance->query($pQuery);
			$time_end = microtime(true);
			$execution_time = ($time_end - $time_start)/60;
			$message = $pQuery . "<br/>execution time : <b>".(round($execution_time, 3))." sec</b>";
			Debugger::query($message, "db", $this->bdd);
			return $res;
		}


		/**
		 * ToString()
		 * @return String
		 */
		public function __toString()
		{
			return '[Object MysqliHandler database="'.$this->bdd.'" user="'.$this->user.'"]';
		}
	}
}
