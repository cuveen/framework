<?php
namespace Cuveen\Session;

use Cuveen\Config\Config;
use Cuveen\Helper\Arr;
use Cuveen\Http\Request;

class Session {
    protected static $_instance;
    protected $config;
    public function __construct()
    {
        $this->config = Config::getInstance();
        self::$_instance = $this;
    }

    public static function getInstance()
    {
        return self::$_instance;
    }

    public function start()
    {
        if (session_status() != PHP_SESSION_NONE) {
            return;
        }
        ini_set('session.hash_bits_per_character', 5);
        ini_set('session.serialize_handler', 'php_serialize');
        ini_set('session.use_only_cookies', 1);
        $cookieParams = session_get_cookie_params();
        $lifetime = (!empty($this->config->get('session.lifetime')))?$this->config->get('session.lifetime'):120;
        $path = (!empty($this->config->get('session.files')))?$this->config->get('session.files'):$this->config->get('base_path');
        $request = Request::getInstance();
        session_set_cookie_params(
            $lifetime,
            $path,
            $request->domain,
            false,
            true
        );
        session_name(strtolower('cuveen-framework-app'));
        session_start();
    }

    public function get($key = false, $default = null)
    {
        if($key && isset($_SESSION[$key]) && $_SESSION[$key] != ''){
            return $_SESSION[$key];
        }
        return $default;
    }

    public function put($key = false, $val = false)
    {
        if($key && $val){
            $_SESSION[$key] = $val;
        }
    }

    public function forget($key = false)
    {
        if($key){
            unset($_SESSION[$key]);
        }
    }

    public function has($key)
    {
        return (isset($_SESSION[$key]))?true: false;
    }

    public function destroy()
    {
        session_destroy();
    }

    public function push($key, $value)
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->put($key, $array);
    }

    public function flash($key, $value)
    {
        $this->put($key, $value);

        $this->push('flash.new', $key);

        $this->removeFromOldFlashData([$key]);
    }

    protected function removeFromOldFlashData(array $keys)
    {
        $this->put('flash.old', array_diff($this->get('flash.old', []), $keys));
    }

    public function flashInput(array $value)
    {
        $this->flash('_old_input', $value);
    }

    public function hasOldInput($key = null)
    {
        $old = $this->getOldInput($key);

        return is_null($key) ? count($old) > 0 : ! is_null($old);
    }

    public function getOldInput($key = null, $default = null)
    {
        $input = $this->get('_old_input', []);
        return Arr::get($input, $key, $default);
    }
}