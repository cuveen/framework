<?php


namespace Cuveen\Controller;
use Cuveen\Auth\Auth;
use Cuveen\Config\Config;
use Cuveen\Database\Database;
use Cuveen\Exception\CuveenException;
use Cuveen\Hash\Security;
use Cuveen\Http\Request;
use Cuveen\Model\Model;
use Cuveen\Router\Router;
use Cuveen\Session\Session;
use Cuveen\View\View;

class Controller
{
    protected $request;
    protected $session;
    protected $security;
    protected $config;
    protected $router;
    protected $auth;
    protected $view;
    protected $base_path;
    public function __construct()
    {
        $this->request = Request::getInstance();
        $this->security = Security::getInstance();
        $this->config = Config::getInstance();
        $this->session = Session::getInstance();
        $this->auth = Auth::getInstance();
        $this->view = View::getInstance();
        $this->router = Router::getInstance();
        $this->base_path = $this->config->get('base_path');
    }
    public function model($model, $attr = false)
    {
        if(file_exists($this->base_path.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$model.'.php')){
            $name = $attr?$attr:mb_strtolower($model).'_model';
            $this->$name = Model::factory($model);
            return $this;
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