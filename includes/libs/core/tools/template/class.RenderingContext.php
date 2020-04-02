<?php
namespace core\tools\template
{

    use core\application\Core;
    use core\utils\Stack;

    /**
     * Class RenderingContext
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @package core\tools\template
     */
    class RenderingContext
    {
        /**
         * @var string
         */

        private $file;
        /**
         * @var array
         */
        private $data;

        /**
         * RenderingContext constructor.
         * @param null $pFile
         */
        public function __construct($pFile = null)
        {
            $this->file = $pFile;
            $this->data = array();
        }


        /**
         * @param string $pFile
         */
        public function setFile($pFile)
        {
            $this->file = $pFile;
        }


        /**
         * @param string $pName
         * @param mixed $pValue
         */
        public function assign($pName, &$pValue)
        {
            $this->data[$pName] = $pValue;
        }


        /**
         * @param string $pName
         */
        public function includeTpl($pName)
        {
            $tpl = new Template($this->data);
            Core::setupRenderer($tpl);
            $tpl->render($pName, true);
        }


        /**
         * @param array $pData
         */
        public function setData($pData)
        {
            $this->data = $pData;
        }


        /**
         * @param string $pName
         * @param array $pModifiers
         * @return mixed
         */
        public function get($pName, $pModifiers = array())
        {
            $value = Stack::get($pName, $this->data);
            if(!empty($pModifiers))
            {
                foreach($pModifiers as $m)
                {
                    if(is_callable($m)||($m = TemplateModifiers::get($m)))
                        $value = call_user_func($m, $value);
                }
            }
            return $value;
        }


        /**
         * @param bool $pDisplay
         * @return bool|string
         */
        public function render($pDisplay)
        {
            ob_start();
            include_once($this->file);
            $rendering = ob_get_contents();
            ob_end_clean();
            if($pDisplay)
            {
                echo $rendering;
                return true;
            }
            return $rendering;
        }
    }
}
