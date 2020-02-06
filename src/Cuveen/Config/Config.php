<?php


namespace Cuveen\Config;


use Cuveen\Helper\Arr;

class Config
{
    protected static $_instance;
    public $config = [];
    public function __construct($base_path)
    {
        /*LOAD CONFIG*/
        $data = array();
        if ($handle = opendir($base_path . DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                    if($entry != 'router.php'){
                        if(!in_array($base_path . DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$entry,get_included_files())){
                            $file_name = str_replace('.php','',$entry);
                            $file_name = mb_strtolower($file_name);
                            $this->config[$file_name] = include ($base_path . DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$entry);
                        }
                    }
                }
            }
            closedir($handle);
        }
        $this->config['base_path'] = $base_path;
        self::$_instance = $this;
    }

    public static function getInstance()
    {
        return self::$_instance;
    }

    public function get($attr = false)
    {
        $list_array = Arr::dot($this->config);
        if($attr) {
            if (isset($list_array[$attr])) {
                return $list_array[$attr];
            } elseif (isset($this->config[$attr])) {
                return $this->config[$attr];
            }
            else return '';
        }
        else return $this->config;
    }
    protected function array_dot($array = array())
    {
        $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));
        $result = array();
        foreach ($ritit as $leafValue) {
            $keys = array();
            foreach (range(0, $ritit->getDepth()) as $depth) {
                $keys[] = $ritit->getSubIterator($depth)->key();
            }
            $result[ join('.', $keys) ] = $leafValue;
        }
        return $result;
    }

}