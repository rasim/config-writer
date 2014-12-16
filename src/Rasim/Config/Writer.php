<?php

namespace Rasim\Config;

use Illuminate\Config\LoaderInterface;
use Illuminate\Config\Repository as RepositoryBase;

class Writer extends RepositoryBase{

  public function __construct(LoaderInterface $loader,  $environment,$defaultPath)
    {
        parent::__construct($loader, $environment);
        $this->defaultPath = $defaultPath;
		$this->loader = $loader;
        $this->files = $loader->getFilesystem();
    }

    public function writer($item, $value, $environment, $group, $namespace = null,$path= null)
    {
        $path = $this->getPath($environment, $group, $item, $namespace,$path);
        if (!$path)
            return false;

        $contents = $this->files->get($path);
        $contents = $this->toContent($contents, [$item => $value]);
        return !($this->files->put($path, $contents) === false);
    }

    private function getPath($environment, $group, $item, $namespace = null,$path= null)
    {

        $hints = $this->loader->getNamespaces();
        $config = "";
        if($path=="")
            $config = "config";

        if (is_null($namespace)) {
            $path = $this->defaultPath."/".$path.$config;
        }
        elseif (isset($this->hints[$namespace])) {
            $path = $this->hints[$namespace]."/".$path.$config;
        }
        
        $file = "{$path}/{$environment}/{$group}.php";

        if ( $this->files->exists($file) &&
             $this->hasKey($file, $item)
        )
            return $file;
        $file = "{$path}/{$group}.php";

        if ($this->files->exists($file))
            return $file;
        return null;
    }
    
    private function hasKey($path, $key)
    {
        $contents = file_get_contents($path);
        $vars = eval('?>'.$contents);
        $keys = explode('.', $key);
        $isset = false;
        while ($key = array_shift($keys)) {
            $isset = isset($vars[$key]);
        }
        return $isset;
    }

    public function toFile($filePath, $newValues, $useValidation = true)
    {
        $contents = file_get_contents($filePath);
        $contents = $this->toContent($contents, $newValues, $useValidation);
        file_put_contents($filePath, $contents);
        return $contents;
    }

    public function toContent($contents, $newValues, $useValidation = true)
    {
        $contents = $this->parseContent($contents, $newValues);
        if ($useValidation) {
            $result = eval('?>'.$contents);
                foreach ($newValues as $key => $expectedValue) {
                    $parts = explode('.', $key);
                    $array = $result;
                    foreach ($parts as $part) {
                        if (!is_array($array) || !array_key_exists($part, $array))
                            throw new Exception(sprintf('Unable to rewrite key "%s" in config, does it exist?', $key));
                        $array = $array[$part];
                    }
                    $actualValue = $array;
                if ($actualValue != $expectedValue)
                    throw new Exception(sprintf('Unable to rewrite key "%s" in config, rewrite failed', $key));
            }
        }
        return $contents;
    }

    private function parseContent($contents, $newValues)
    {
        $patterns = array();
        $replacements = array();
        foreach ($newValues as $path => $value) {
            $items = explode('.', $path);
            $key = array_pop($items);
            if (is_string($value) && strpos($value, "'") === false) {
                $replaceValue = "'".$value."'";
            }
            elseif (is_string($value) && strpos($value, '"') === false) {
                $replaceValue = '"'.$value.'"';
            }
            elseif (is_bool($value)) {
                $replaceValue = ($value ? 'true' : 'false');
            }
            elseif (is_null($value)) {
                $replaceValue = 'null';
            }
            else {
                $replaceValue = $value;
            }
            $patterns[] = $this->buildStringExpression($key, $items);
            $replacements[] = '${1}${2}'.$replaceValue;
            $patterns[] = $this->buildStringExpression($key, $items, '"');
            $replacements[] = '${1}${2}'.$replaceValue;
            $patterns[] = $this->buildConstantExpression($key, $items);
            $replacements[] = '${1}${2}'.$replaceValue;
        }
        return preg_replace($patterns, $replacements, $contents, 1);
    }

    private function buildStringExpression($targetKey, $arrayItems = array(), $quoteChar = "'")
    {
        $expression = array();
        $expression[] = $this->buildArrayOpeningExpression($arrayItems);
        $expression[] = '([\'|"]'.$targetKey.'[\'|"]\s*=>\s*)['.$quoteChar.']';
        $expression[] = '([^'.$quoteChar.']*)';
        $expression[] = '['.$quoteChar.']';
        return '/' . implode('', $expression) . '/';
    }

    private function buildConstantExpression($targetKey, $arrayItems = array())
    {
        $expression = array();
        $expression[] = $this->buildArrayOpeningExpression($arrayItems);
        $expression[] = '([\'|"]'.$targetKey.'[\'|"]\s*=>\s*)';
        $expression[] = '([tT][rR][uU][eE]|[fF][aA][lL][sS][eE]|[nN][uU][lL]{2}|[\d]+)';
        return '/' . implode('', $expression) . '/';
    }

    private function buildArrayOpeningExpression($arrayItems)
    {
        if (count($arrayItems)) {
            $itemOpen = array();
            foreach ($arrayItems as $item) {
                $itemOpen[] = '[\'|"]'.$item.'[\'|"]\s*=>\s*(?:[aA][rR]{2}[aA][yY]\(|[\[])';
            }
            $result = '(' . implode('[\s\S]*', $itemOpen) . '[\s\S]*?)';
        }
        else {
            $result = '()';
        }
        return $result;
    }

    public function write($keys, $values, $paths = NULL)
    {

       

        if(count($keys)>1){
            $path = array();
            foreach ($keys as $index => $key) {

                if(empty($paths[$index]))
                    $paths[$index] = "";

                list($namespace, $group, $item) = $this->parseKey($key);
                $result = $this->writer($item, $values[$index], $this->environment, $group, $namespace,$paths[$index]);
                if(!$result) throw new \Exception('File could not be written to');
                $this->set($key, $values[$index]);
            }

        }else{

            list($namespace, $group, $item) = $this->parseKey($keys);
            $result = $this->writer($item, $values, $this->environment, $group, $namespace,$paths);
            if(!$result) throw new \Exception('File could not be written to');
            $this->set($keys, $values);

        }

    }

}
