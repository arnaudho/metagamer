<?php
namespace core\models
{
    use core\application\BaseModel;
    use core\application\Configuration;
    use core\application\Core;
    use core\db\Query;

    /**
     * Model de gestion des authentifications
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package models
     */
    class ModelAuthentication extends BaseModel
    {
        static private $instance;

        static public $data;

        public function __construct()
        {
            parent::__construct(sprintf(Configuration::$authentication_tableName,Core::$application), Configuration::$authentication_tableId);
        }

        static public function isUser($pLogin, $pMdp)
        {
            if(empty($pLogin)||empty($pMdp))
                return false;

            $instance = self::getInstance();

            if($result = $instance->one(Query::condition()->andWhere(Configuration::$authentication_fieldLogin, Query::EQUAL, $pLogin)))
            {
                if($result[configuration::$authentication_fieldPassword] == $pMdp)
                {
                    self::$data = $result;
                    return true;
                }
            }
            return false;
        }

        /**
         * @return ModelAuthentication
         */
        static public function getInstance()
        {
            if(!self::$instance)
                self::$instance = new ModelAuthentication();
            return self::$instance;
        }
    }
}
