<?php


namespace Cuveen\Auth;


use Cuveen\Config\Config;
use Cuveen\Model\Model;
use Cuveen\Session\Session;

class Auth
{
    protected static $_instance;
    protected static $_user_class;

    public function __construct()
    {
        $config = Config::getInstance();
        self::$_user_class = $config->get('app.user_class');
        self::$_instance = $this;
    }

    public function getPrimaryKey($class)
    {
        $real_class = strpos($class,'Cuveen\Model') !== false?$class:'Cuveen\Model\\'.$class;
        if(!property_exists($class_name, $property)){
            return 'id';
        }
        $properties = get_class_vars($real_class);
        return $properties['_id_column'];
    }

    public static function getInstance()
    {
        return self::$_instance;
    }

    public static function check()
    {
        $session = Session::getInstance();
        if($session->has('__CUVEEN_USER_LOGGED_IN') && $session->get('__CUVEEN_USER_LOGGED_IN') == true && $session->has('__CUVEEN_USER_LOGGED_IN_ID') && is_numeric($session->get('__CUVEEN_USER_LOGGED_IN_ID'))){
            return true;
        }
        else return false;
    }

    public static function user_id()
    {
        $session = Session::getInstance();
        if($session->has('__CUVEEN_USER_LOGGED_IN') && $session->get('__CUVEEN_USER_LOGGED_IN') == true && $session->has('__CUVEEN_USER_LOGGED_IN_ID') && is_numeric($session->get('__CUVEEN_USER_LOGGED_IN_ID'))){
            return $session->get('__CUVEEN_USER_LOGGED_IN_ID');
        }
        else return false;
    }


    public static function user()
    {
        $session = Session::getInstance();
        if($session->has('__CUVEEN_USER_LOGGED_IN') && $session->get('__CUVEEN_USER_LOGGED_IN') == true && $session->has('__CUVEEN_USER_LOGGED_IN_ID') && is_numeric($session->get('__CUVEEN_USER_LOGGED_IN_ID'))){
            $user_model = app()->model(self::$_user_class);
            $user_primaryKey = self::getPrimaryKey(self::$_user_class);
            $user = $user_model->where($user_primaryKey,$session->get('__CUVEEN_USER_LOGGED_IN_ID'))->find();
            if($user_model->count()){
                return $user;
            }
            return false;
        }
        else return false;
    }

    public static function logout()
    {
        $session = Session::getInstance();
        $session->destroy();
    }

    public static function attempt($credentials = array())
    {
        $session = Session::getInstance();
        $user_model = app()->model(self::$_user_class);
        $user_primaryKey = self::getPrimaryKey(self::$_user_class);
        if(count($credentials) > 0 && isset($credentials['password']) && !empty($credentials['password'])){
            foreach($credentials as $key => $val){
                if($key != 'password'){
                    $user_model->where($key, $val);
                }
            }
            $user = $user_model->find();
            if($user_model->count() && password_verify($credentials['password'], $user->password)){
                $session->put('__CUVEEN_USER_LOGGED_IN', true);
                $session->put('__CUVEEN_USER_LOGGED_IN_ID', $user->$user_primaryKey);
                return true;
            }
        }
        return false;
    }
}