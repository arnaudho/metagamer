<?php
namespace core\tools\template
{

    use core\system\File;

    /**
     * Class Template
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 0.3
     * @package core\tools\template
     */
    class Template
    {
        /**
         * @var string
         */
        public $cacheDir;

        /**
         * @var string
         */
        public $templateDir;

        /**
         * @var string
         */
        public $templateFile;

        /**
         * @var string
         */
        private $templatePath;

        /**
         * @var string
         */
        private $cacheFile;

        /**
         * @var string
         */
        private $cachePath;

        /**
         * @var bool
         */
        public $safeMode = true;

        /**
         * @var bool
         */
        public $cacheEnabled = true;

        /**
         * @var RenderingContext
         */
        private $context;

        /**
         * @var number
         */
        private $step;

        /**
         * @var array
         */
        private $opened;


        /**
         * Template constructor.
         * @param null $pDefaultData
         */
        public function __construct($pDefaultData = null)
        {
            $this->context = new RenderingContext();
            if(!is_null($pDefaultData))
            {
                $this->context->setData($pDefaultData);
            }
        }


        /**
         * @param string $pName
         * @param mixed $pValue
         */
        public function assign($pName, &$pValue)
        {
            $this->context->assign($pName, $pValue);
        }


        /**
         * @param $pTemplateDir
         * @param $pCacheDir
         */
        public function setup($pTemplateDir, $pCacheDir)
        {
            $currentDir = dirname($_SERVER['SCRIPT_FILENAME']).'/';
            $this->templateDir = $currentDir.$pTemplateDir;
            $this->cacheDir = $pCacheDir;
        }


        /**
         * @param string $pTemplateFile
         * @param bool $pDisplay
         * @return bool|string
         */
        public function render($pTemplateFile, $pDisplay = true)
        {
            $this->templateFile = $pTemplateFile;
            $this->templatePath = $this->templateDir."/".$this->templateFile;
            $this->cacheFile = str_replace("/", "%", $this->templateFile).".php";
            $this->cachePath = $this->cacheDir."/".$this->cacheFile;

            $this->context->setFile($this->cachePath);

            if($this->pullFromCache())
            {
                return $this->execute($pDisplay);
            }

            $this->evaluate();
            return $this->execute($pDisplay);
        }


        /**
         * @return bool
         */
        private function pullFromCache()
        {
            if(!$this->cacheEnabled)
                return false;

            if(!file_exists($this->cachePath))
                return false;

            $cacheTime = filemtime($this->cachePath);

            /** Cache is no more valid */
            if($cacheTime < filemtime($this->templatePath))
                return false;

            return true;
        }


        /**
         * @param string $pContent
         */
        private function storeInCache($pContent)
        {
            if(!$this->cacheEnabled)
                return;

            if(file_exists($this->cachePath))
            {
                unlink($this->cachePath);
            }

            file_put_contents($this->cachePath, $pContent);
        }


        /**
         * @param bool $pDisplay
         * @return bool|string
         */
        private function execute($pDisplay = true)
        {
            return $this->context->render($pDisplay);
        }


        /**
         * Retrieve template source then evaluate it to a PHP compliant version
         */
        private function evaluate()
        {
            try
            {
                $content = File::read($this->templatePath);
            }
            catch (\Exception $e)
            {
                trigger_error("Le fichier '".$this->templateFile."' n'existe pas.", E_USER_WARNING);
                return;
            }
            
            $startTime = microtime(true);

            $otag = TemplateDictionary::$TAGS[0];
            $etag = TemplateDictionary::$TAGS[1];

            $content = $this->escapeBlock($content, $otag."*", "*".$etag);

            if($this->safeMode)
            {
                $content = $this->escapeBlock($content, "<?php", "?>");
            }

            $to = TemplateDictionary::$TAGS[0];
            $tc = TemplateDictionary::$TAGS[1];

            $blocks = "[a-z]+";

            $re_block = "/(\\".$to."(".$blocks.")|\\".$to."\/(".$blocks."))([^\\".$tc."]*)\\".$tc."/i";

            $re_vars = "/\\$([a-z0-9\_\.\|]+)/i";

            $content = preg_replace_callback($re_vars, function($pMatches)
            {
                $modifiers = explode('|', $pMatches[1]);
                $var = $this->extractVar(array_shift($modifiers), array_reverse($modifiers));
                return $var;
            }, $content);

            $re_vars = "/".$to."\\$([^".$tc."]+)".$tc."/i";

            $content = preg_replace_callback($re_vars, function($pMatches)
            {
                return "<?php echo \$".$pMatches[1]."; ?>";
            }, $content);

            $this->step = 0;
            $this->opened = [];
            $content = preg_replace_callback($re_block, function($pMatches){
                return $this->parseBlock($pMatches);
            }, $content);

            $endTime = microtime(true);

            trace("evaluate duration : ".($endTime-$startTime)." ");

            $this->storeInCache($content);
        }

        private function parseBlock($pMatches)
        {
            $opener = !empty(trim($pMatches[2]));
            $name = $opener?$pMatches[2]:$pMatches[3];
            $params = trim($pMatches[4]);

            switch($name)
            {
                case "if":
                    if($opener)
                    {
                        return "<?php if(".$params."): ?>";
                    }
                    else
                    {
                        return "<?php endif; ?>";
                    }
                    break;
                case "foreach":
                    if($opener)
                    {
                        $this->step++;
                        $this->opened[$this->step] = true;

                        $default = ["key"=>'key', "item"=>'value'];

                        $this->parseParameters($params, $default);

                        $array_var = 'data_'.$this->step;
                        $var = '$'.$array_var.'='.$default["from"].';';

                        return '<?php '.$var.' if($'.$array_var.'&&is_array($'.$array_var.')&&!empty($'.$array_var.')):
foreach($'.$array_var.' as $'.$default['key'].'=>$'.$default['item'].'): $this->assign("'.$default['item'].'", $'.$default['item'].'); $this->assign("'.$default['key'].'", $'.$default['key'].'); ?>';
                    }
                    else
                    {
                        $array_var = 'data_'.$this->step;
                        $extra = isset($this->opened[$this->step])?"endforeach; unset(\$".$array_var."); ":"";
                        unset($this->opened[$this->step--]);
                        return "<?php ".$extra."endif; ?>";
                    }
                    break;
                case "foreachelse":
                    unset($this->opened[$this->step]);
                    $array_var = 'data_'.$this->step;
                    return "<?php endforeach; unset(\$".$array_var."); else: ?>";
                    break;
                case "else":
                    return "<?php else: ?>";
                    break;
                case "include":
                    $default = array();
                    $this->parseParameters($params, $default);
                    return "<?php \$this->includeTpl('".$default["file"]."'); ?>";
                    break;
                default:

                    $re_object = "/\\".TemplateDictionary::$TAGS[0]."([a-z0-9\\.\\_]+)(\\-\\>[a-z\\_]+)*([^\\".TemplateDictionary::$TAGS[1]."]+)*\\".TemplateDictionary::$TAGS[1]."/i";
                    preg_match($re_object, $pMatches[0], $matches);


                    if(isset($matches)&&!empty($matches)&&!empty($matches[1])&&!empty($matches[2]))
                    {
                        $p = "";
                        if(isset($matches[3])&&!empty($matches[3]))
                        {
                            $ptr = array();
                            $this->parseParameters($matches[3], $ptr);
                            foreach($ptr as $n=>$v)
                            {
                                if(empty($n)||empty($v))
                                    continue;
                                if(!empty($p))
                                    $p .= ', ';
                                $p .= '"'.$n.'"=>'.$v;
                            }
                            $p = "array(".$p.")";
                        }
                        return "<?php ".$this->extractVar($matches[1]).$matches[2]."(".$p."); ?>";
                    }
                    else
                    {

                    }

                    return $pMatches[0];
                    break;
            }
        }



        /**
         * @param string $content
         * @param string $pStartTag
         * @param string $pEndTag
         * @return mixed
         */
        private function escapeBlock($content, $pStartTag, $pEndTag)
        {
            while(($s = strpos($content, $pStartTag))!==false)
            {
                $e = strpos($content, $pEndTag)+2;
                $length = $e-$s;
                $content = substr_replace($content, "", $s, $length);
            }
            return $content;
        }


        /**
         * @param string $pString
         * @param array &$pParams
         */
        private function parseParameters($pString, &$pParams)
        {
            $p = explode(" ", $pString);
            foreach($p as $pv)
            {
                $v = explode("=", $pv);
                $value = trim($v[1]);
                $value = trim($value, '"');
                $pParams[trim($v[0])] = $value;
            }
        }


        /**
         * @param string $pId
         * @param array $pModifiers
         * @return string
         */
        private function extractVar($pId, $pModifiers = array())
        {
            $modifiers = "[]";
            if(!empty($pModifiers))
                $modifiers = "['".implode("','", $pModifiers)."']";
            return '$this->get("'.$pId.'",'.$modifiers.')';
        }
    }

    /**
     * Class TemplateDictionary
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @package core\tools\template
     */
    class TemplateDictionary
    {
        /**
         * @var array
         */
        static public $TAGS = ["{", "}"];

        /**
         * @var array
         */
        static public $BLOCKS = [
            "foreach",
            "if"
        ];

        /**
         * @var array
         */
        static public $NEUTRALS = [
            "foreachelse",
            "else"
        ];
    }
}
