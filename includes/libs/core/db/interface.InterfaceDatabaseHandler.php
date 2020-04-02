<?php
namespace core\db
{

	/**
	 * Interface pour les gestionnaires de base de données
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .3
	 * @package db
	 */
	interface InterfaceDatabaseHandler
	{
		/**
		 * Méthode d'execution d'une Requêtes SQL
		 * @param String $pQuery				Requêtes SQL brute
		 * @return resource
		 */
		public function execute($pQuery);


		/**
		 * Méthode permettant de récupérer les donnée d'une requêtes SQL
		 * Renvoie les données renvoyées sous forme d'un tableau associatif
		 * @param String $pQuery				Requêtes SQL brute
		 * @return array
		 */
		public function getResult($pQuery);


		/**
		 * Méthode de récupération de lé clé primaire venant d'être générée par la base de données
		 * @return int
		 */
		public function getInsertId();

		/**
		 * @abstract
		 * @return int
		 */
		public function getErrorNumber();

		/**
		 * @abstract
		 * @return string
		 */
		public function getError();
	}
}
