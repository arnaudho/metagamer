<?php
namespace core\tools\docs
{
    use core\data\Encoding;
    use core\system\Folder;

    /**
     * Class Documentor
     * Permet le parsing des PHPDoc de classes PHP
     * Gère la génération d'une documentation statique HTML
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.0
     * @package core\tools\docs
     */
    class Documentor
    {
        /**
         * @var array
         */
        private $packages = array();

        public function __construct()
        {

        }

        public function parseClass($pClassName)
        {
            $reflec = new \ReflectionClass($pClassName);
            $classInfo = array('details'=>$this->parseDocComment($reflec->getDocComment()),
                                'methods'=>array());
            $allMethods = $reflec->getMethods(\ReflectionMethod::IS_PUBLIC|\ReflectionMethod::IS_PROTECTED);
            $methods = array();
            for($i = 0, $max = count($allMethods); $i<$max;$i++)
            {
                $method = $allMethods[$i];
                $methods[] = array('name'=>$method->getName(), 'details'=>$this->parseDocComment($method->getDocComment()), 'public'=>$method->isPublic(), 'protected'=>$method->isProtected());
            }
            $this->sortName($methods);
            $classInfo['methods'] = $methods;

            $allProps = $reflec->getProperties(\ReflectionProperty::IS_PUBLIC|\ReflectionProperty::IS_PROTECTED);
            $props = array();
            for($i = 0, $max = count($allProps); $i<$max;$i++)
            {
                $prop = $allProps[$i];
                $props[] = array('name'=>$prop->getName(), 'value'=>'','details'=>$this->parseDocComment($prop->getDocComment()), 'public'=>$prop->isPublic(), 'protected'=>$prop->isProtected());
            }

            $this->sortName($props);
            $classInfo['properties'] = $props;

            return $classInfo;
        }

        public function parsePackage($pPath, $pPackage, $pOrigin = true)
        {
            $classes = array();
            $excluded_ext = '/\.(tpl|tpl\.php|ttf)$/';
            $r = Folder::read($pPath, false);

            foreach($r as $name=>$folder)
            {
                if(is_file($folder['path']))
                {
                    $file = $folder['path'];
                    if(preg_match($excluded_ext, $file, $matches))
                        continue;

                    include_once($file);
                    continue;
                }
                $this->parsePackage($folder['path'], $pPackage.'\\'.$name, false);
            }

            if($pOrigin)
            {
                $declared_classes = get_declared_classes();

                foreach($declared_classes as $classe)
                {
                    if(preg_match('/^'.$pPackage.'/', $classe, $matches))
                    {
                        $details = $this->parseClass($classe);
                        $classes[$classe] = $details;
                    }

                }
            }

            $this->packages = array_merge($this->packages, $classes);

        }

        public function output($pFolder)
        {
            Folder::deleteRecursive($pFolder);
            Folder::create($pFolder);
            Folder::create($pFolder.'/classes');

            $smarty = new \Smarty();
            $smarty->clear_all_assign();
            $smartyDir = "includes/libs/core/tools/docs/templates/_cache/";
            $smarty->template_dir = "includes/libs/core/tools/docs/templates";
            $smarty->cache_dir = $smartyDir;
            $smarty->compile_dir = $smartyDir;

            $classIndex = array();

            foreach($this->packages as $className=>$details)
            {

                $parts = explode("\\", $className);
                $class = array_pop($parts);
                while(in_array($class, $classIndex) && !empty($parts))
                    $class = $class.'\\'.array_pop($parts);

                $file = 'classes/'.str_replace('\\', '_', $class).'.html';

                $classIndex[] = array('name'=>$class, 'href'=>$file);

                $smarty->clear_all_assign();
                $details['name'] = $class;
                $smarty->assign('details', $details);
                file_put_contents($pFolder.$file, Encoding::BOM().$smarty->fetch("template.class_details.tpl"));
            }

            $this->sortName($classIndex);

            $prefixed_ndx = array();
            foreach($classIndex as $class)
            {
                $firstLetter = strtoupper(substr($class['name'], 0, 1));
                if(!array_key_exists($firstLetter, $prefixed_ndx))
                    $prefixed_ndx[$firstLetter] = array();
                $prefixed_ndx[$firstLetter][] = $class;
            }
            $classIndex = $prefixed_ndx;

            $smarty->clear_all_assign();
            $smarty->assign('classIndex', $classIndex);
            file_put_contents($pFolder.'/classes.html', Encoding::BOM().$smarty->fetch("template.classes.tpl"));

            $smarty->clear_all_assign();
            file_put_contents($pFolder.'/index.html', Encoding::BOM().$smarty->fetch("template.index.tpl"));
        }

        private function sortName(&$pArray)
        {
            if(!function_exists('core\\tools\\docs\\documentor_cmp_fn'))
            {
                function documentor_cmp_fn($a, $b)
                {
                    return strcmp(strtolower($a['name']), strtolower($b['name']));
                }
            }
            usort($pArray, 'core\\tools\\docs\\documentor_cmp_fn');
        }

        public function parseDocComment($pComments)
        {

            $description = array();
            if(preg_match_all('/\s+\* ([^@].+)\n/i', $pComments, $matches))
            {
                $description = $matches[1];
            }
            $parameters = array();
            if(preg_match_all('/@param\s*([a-z\|]+)\s*\$([a-z\_]+)\s*([^\*]*)\n/i', $pComments, $matches))
            {
                foreach($matches[0] as $i=>$m)
                {
                    $parameters[] = array(
                        "type"=>$matches[1][$i],
                        "name"=>$matches[2][$i],
                        "desc"=>$matches[3][$i]
                    );
                }
            }


            $author = false;
            if(preg_match('/@author\s*([a-z\|\s]+)\s*\<([^\>]+)\>/i', $pComments, $matches))
            {
                $author = array(
                    "name"=>$matches[1],
                    "email"=>$matches[2]
                );
            }

            $return = array('type'=>$this->extractDocVar('return', $pComments));

            $version = $this->extractDocVar('version', $pComments);

            $alias = $this->extractDocVar('alias', $pComments);

            $type = $this->extractDocVar('var', $pComments);

            $date = false;
            if(preg_match('/@date\s*([0-9]{4})([0-9]{2})([0-9]{2})\s*/i', $pComments, $matches))
            {
                if(count($matches)==4)
                    $date = $matches[3]."/".$matches[2]."/".$matches[1];
            }

            $annexe = array();
            if(preg_match('/@annexe\s*([a-z\_]+)\s*([^@].+)\n/i', $pComments, $matches))
            {
                $annexe = array(
                    'name'=>$matches[1],
                    'content'=>$matches[2]
                );
            }

            return array(
                "return"=>$return,
                "parameters"=>$parameters,
                "description"=>$description,
                "version"=>$version,
                "date"=>$date,
                'alias'=>$alias,
                'annexe'=>$annexe,
                'author'=>$author,
                'type'=>$type
            );
        }

        private function extractDocVar($pVarName, $pComments)
        {
            if(preg_match('/@'.$pVarName.'\s*([0-9a-z\_]+)\s*/i', $pComments, $matches))
            {
                return $matches[1];
            }
            return false;
        }
    }
}
