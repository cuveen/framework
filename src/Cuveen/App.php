<?php
namespace Cuveen;
use Cuveen\Hash\Security;
use Cuveen\Http\Cookie;
use Cuveen\Session\Session;
use Cuveen\Http\Request;
use Cuveen\Router\Router;
use Cuveen\View\View;
use Cuveen\Config\Config;
use Cuveen\Auth\Auth;
use Cuveen\Database\DB;
use Cuveen\Exception\CuveenException;
use Cuveen\Scheduler\Scheduler;
use Cuveen\Command\Command;
use Dotenv\Dotenv;

class App {
    const VERSION = '1.0.0';
    protected $db;
    protected $request;
    protected $session;
    protected $security;
    public $config;
    public $router;
    protected $auth;
    protected $view;
    public $base_path;
    public $app_path;
    public $scheduler;

    public function __construct()
    {
        set_exception_handler(array("Cuveen\\Exception\\CuveenException", "handleException"));
        set_error_handler(array("Cuveen\\Exception\\CuveenException", "handleError"));
        $base_path = realpath(getcwd());
        $this->app_path = __DIR__;
        $this->base_path = $base_path;
        $dotenv = Dotenv::create([$this->base_path]);
        $dotenv->load();
        $config = new Config($this->base_path);
        $this->request = new Request();
        $this->session = new Session();
        $this->scheduler = new Scheduler();
        /*START APP SESSION*/
        $this->session->start();
        $this->security = new Security();
        /*START DATABASE CONFIG*/
        if(!empty($config->get('database'))
            && $config->get('database.default') == 'mysql'
            && !empty($config->get('database.connections.mysql.host'))
            && !empty($config->get('database.connections.mysql.username'))
            && !empty($config->get('database.connections.mysql.password'))
            && !empty($config->get('database.connections.mysql.database'))
        ){
            $database_port = (!empty($config->get('database.connections.mysql.port')))? $config->get('database.connections.mysql.port'):3306;
            $database_charset = (!empty($config->get('database.connections.mysql.charset')))? $config->get('database.connections.mysql.charset'):'utf8';
            $this->db = new DB($config->get('database.connections.mysql.host'), $config->get('database.connections.mysql.username'), $config->get('database.connections.mysql.password'), $config->get('database.connections.mysql.database'), $database_port, $database_charset);
            if(!empty($config->get('database.connections.mysql.prefix'))){
                $this->db->setPrefix($config->get('database.connections.mysql.prefix'));
            }
        }
        $this->auth = new Auth();
        $this->config = $config;
        $cache_path = (!empty($this->config->get('cache.path')))?$this->config->get('cache.path'):DIRECTORY_SEPARATOR.'tmp';
        $controllers_path = (!empty($this->config->get('app.controllers')))?$this->config->get('app.controllers'):'controllers';
        $middlewares_path = (!empty($this->config->get('middleware.path')))?$this->config->get('middleware.path'):'middlewares';
        $router = new Router(array(
            'paths' => array(
                'controllers'=> $controllers_path,
                'middlewares'=> $middlewares_path
            ),
            'base_folder'=> $this->base_path,
            'cache' => $cache_path.DIRECTORY_SEPARATOR.'app.router.php'
        ));
        if(file_exists($this->base_path.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'router.php')){
            include($this->base_path.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'router.php');
        }
        $this->request->routes = $router->getRoutes();
        $this->router = $router;
    }

    public function run()
    {
        $this->security->DeleteUnnecessaryTokens();
        if(!$this->security->CountsTokens()){
            $this->security->GenerateTokens(3, 60);
        }
        $session_headers = session_get_cookie_params();
        Cookie::setcookie('XSRF-TOKEN', $this->security->getToken(), $session_headers['lifetime'], $session_headers['path']);
        Cookie::setcookie('cuveen_session', $this->security->getToken(), $session_headers['lifetime'], $session_headers['path'], $session_headers['secure'], $session_headers['httponly']);
        $view_path = (!empty($this->config->get('view.path')))?$this->config->get('view.path'):'views';
        $view_compiled = (!empty($this->config->get('view.compiled')))?$this->config->get('view.compiled'):$this->base_path.DIRECTORY_SEPARATOR.'compiled';
        $this->view = new View($this->base_path.DIRECTORY_SEPARATOR.$view_path,$view_compiled,View::MODE_AUTO);
        /*LOAD HELPER*/
        if (is_dir($this->base_path . DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR) && $handle = opendir($this->base_path . DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                    include($this->base_path.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.$entry);
                }
            }
            closedir($handle);
        }
        /*START ROUTING*/
        $this->router->run();
        if(!is_null($this->db)) {
            $this->db->disconnectAll();
        }
    }

    public function exeption($message)
    {
        return new CuveenException($message);
    }

    public function terminate()
    {
        $command = new Command($this);
        $command->run();
    }
}