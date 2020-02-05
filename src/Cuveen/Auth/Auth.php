<?php


namespace Cuveen\Auth;


use Cuveen\Database\DB;
use Cuveen\Session\Session;

class Auth
{
    protected static $_instance;
    protected static $table = 'users';

    public function __construct()
    {
        self::$_instance = $this;
    }

    public static function table($table = 'users')
    {
        self::$table = $table;
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
            $db = DB::getInstance();
            $db->objectBuilder();
            $db->where('id',$session->get('__CUVEEN_USER_LOGGED_IN_ID'));
            $user = $db->getOne(self::$table);
            if($db->count){
                if(property_exists($user, 'password')){
                    unset($user->password);
                }
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
        $db = DB::getInstance();
        if(count($credentials) > 0 && isset($credentials['password']) && !empty($credentials['password'])){
            $db->objectBuilder();
            foreach($credentials as $key => $val){
                if($key != 'password'){
                    $db->where($key, $val);
                }
            }
            $user = $db->getOne(self::$table);
            if($db->count && password_verify($credentials['password'], $user->password)){
                $session->put('__CUVEEN_USER_LOGGED_IN', true);
                $session->put('__CUVEEN_USER_LOGGED_IN_ID', $user->id);
                return true;
            }
        }
        return false;
    }

    public static function create($user_info = array())
    {
        if(count($user_info) > 0){
            if(isset($user_info['password']) && !empty($user_info['password'])){
                $user_info['password'] = password_hash($user_info['password'], PASSWORD_BCRYPT);
            }
            if(isset($user_info['id']) && !empty($user_info['id'])){
                unset($user_info['id']);
            }
            $db = DB::getInstance();
            $check = $db->insert(self::$table, $user_info);
            if($check){
                return $db->getInsertId();
            }

        }
        return false;
    }
}