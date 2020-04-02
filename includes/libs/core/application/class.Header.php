<?php
namespace core\application
{
	/**
	 * Class Header
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .4
	 * @package core\application
	 */
	class Header
	{
        static private $pool = array();

        /**
         * Méthode de gestion systématique des headers HTTP de cache
         * @param string $pETag     jeton
         * @param int $pDuration    Durée du cache
         */
        static public function handleCache($pETag, $pDuration)
        {
            self::$pool["Cache-Control"] = "max-age=".$pDuration.", public";
            self::$pool["ETag"] = $pETag;


            if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && isset($_SERVER['HTTP_IF_NONE_MATCH']))
            {
                $if_modified_since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                $if_none_match = $_SERVER['HTTP_IF_NONE_MATCH'];
                $expires = $if_modified_since+$pDuration;

                if($if_none_match == $pETag && (time() < $expires))
                {
                    self::$pool['HTTP/1.1 304 Not Modified'] = '';
                    self::$pool["Expires"] = gmdate("D, d M Y H:i:s", $expires)." GMT";
                    self::write();
                    Core::endApplication();
                }
            }

            self::$pool["Last-Modified"] = gmdate("D, d M Y H:i:s", time())." GMT";
            self::$pool["Expires"] = gmdate("D, d M Y H:i:s", time() + $pDuration)." GMT";
            self::write();
        }

        /**
         * Méthode de définition des différents header en fonction du pool prédéfinit
         */
        static private function write()
        {
            foreach(self::$pool as $n=>$v)
            {
                if(empty($v))
                {
                    header($n);
                }
                else
                {
                    header($n.": ".$v);
                }
            }
        }

		/**
		 * Se charge d'effectuer une redirection HTTP
		 * @param string $pURL	Url cible de la redirection
		 * @param int $pCode	Code HTTP à envoyer par défaut 301
		 */
		static public function location($pURL ,$pCode = 301)
		{
			header("Location:".$pURL, true, $pCode);
            Core::endApplication();
		}

		/**
		 * Modifie le MIME Type présent dans l'entête HTTP de la réponse
		 * @param string $pValue	ex : text/html, application/xml...
		 * @param string|bool $pCharset	Définit le charset à spécifier
		 */
		static public function contentType($pValue, $pCharset = false)
		{
			if(!$pCharset)
				$pCharset = Configuration::$global_encoding;
			header("Content-Type: ".$pValue."; charset=".$pCharset);
		}

        /**
         * @param string $pValue
         */
        static public function contentEncoding($pValue)
        {
            header("Content-Encoding: ".$pValue);
        }

        /**
         * @param int $pValue
         */
        static public function contentLength($pValue)
        {
            header("Content-Length: ".$pValue);
        }

		/**
		 * Modifie le status de l'entête HTTP de la réponse
		 * @param string $pValue
		 */
		static public function status($pValue)
		{
			header("status: ".$pValue);
		}

		/**
		 * @param string $pValue
		 */
		static public function http($pValue)
		{
			header("HTTP/".$pValue);
		}
	}
}
