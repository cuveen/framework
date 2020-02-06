<?php


namespace Cuveen\Controller;
use Cuveen\Auth\Auth;
use Cuveen\Config\Config;
use Cuveen\Database\DB;
use Cuveen\Exception\CuveenException;
use Cuveen\Hash\Security;
use Cuveen\Http\Request;
use Cuveen\Router\Router;
use Cuveen\Session\Session;
use Cuveen\View\View;

class Controller
{
    protected $db;
    protected $request;
    protected $session;
    protected $security;
    public $config;
    public $router;
    protected $auth;
    protected $view;
    public function __construct()
    {
        $this->db = DB::getInstance();
        $this->request = Request::getInstance();
        $this->security = Security::getInstance();
        $this->config = Config::getInstance();
        $this->session = Session::getInstance();
        $this->auth = Auth::getInstance();
        $this->view = View::getInstance();
    }
    public function model($model, $attr = false)
    {
        if(file_exists($this->base_path.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$model.'.php')){
            require_once ($this->base_path.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$model.'.php');
            $class = 'Cuveen\Model\\'.$model;
            return new $class($this->db);
        }
        else{
            return $this->exception('Can not find model '.$model);
        }
    }

    public function exception($message)
    {
        return new CuveenException($message);
    }
}