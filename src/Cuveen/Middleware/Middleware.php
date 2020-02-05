<?php


namespace Cuveen\Middleware;


use Cuveen\Config\Config;

class Middleware
{
    protected $except = [
        'CsrfToken' =>      []
    ];
    private $middleware = [];
    public function __construct()
    {
        $config = Config::getInstance();
        $this->getAll($config->get('base_path'));
        foreach ($this->middleware as $name) {
            // If a middleware exception exists
            if (isset($this->except[$name])) {
                foreach ($this->except[$name] as $route) {
                    // If the route has match anything rule (*)
                    if (substr($route, -1) == '*') {
                        // If the current path matches a route exception
                        if (stripos($_GET['url'], str_replace('*', '', $route)) === 0) {
                            return;
                        }
                    } // If the current path matches a route exception
                    elseif (isset($_GET['url']) && $_GET['url'] == $route) {
                        return;
                    }
                }
            }
            require_once($config->get('base_path') . '/middlewares/' . $name . '.php');

            $class = 'Cuveen\Middleware\\' . $name;

            new $class;
        }
    }

    private function getAll($base_path)
    {
        if ($handle = opendir($base_path . '/middlewares/')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                    $name = pathinfo($entry);
                    $this->middleware[] = $name['filename'];
                }
            }
            closedir($handle);
        }
    }
}