<?php
use Cuveen\Config\Config;
use Cuveen\Helper\Arr;
use Cuveen\Helper\Collection;
use Cuveen\Http\Redirect;
use Cuveen\Http\Request;
use Cuveen\Auth\Auth;
use Cuveen\Http\Response;
use Cuveen\Validator\Validator;
use Cuveen\Hash\Security;
use Cuveen\View\View;
use Cuveen\Helper\Env;

function env($key, $default = null)
{
    return Env::get($key, $default);
}

function request()
{
    return Request::getInstance();
}

function storage_path($path){
    $base_path = realpath(getcwd());
    if(!is_dir($base_path.'/storage')){
        mkdir($base_path.'/storage', 0777);
    }
    if(!is_dir($base_path.'/storage/'.$path)){
        mkdir($base_path.'/storage/'.$path, 0777);
    }
    return realpath($base_path.'/storage/'.$path);
}

function session(){
    return \Cuveen\Session\Session::getInstance();
}

function config($attr = false)
{
    $config = Config::getInstance();
    if ($attr) {
        return $config->get($attr);
    }
    return $config->get();
}

function url($path = ''){
    $request = Request::getInstance();
    if(strpos($path, 'https://') === false && strpos($path, 'http://') === false){
        return $request->url($path);
    }
}

function router($name = '', $params = [])
{
    $path = $name;
    $request = Request::getInstance();
    if (is_array($request->routes)) {
        foreach ($request->routes as $route) {
            if($route['name'] == $name){
                $path = $route['pattern'];
                if(count($route['fields']) > 0){
                    foreach($route['fields'] as $key=>$field){
                        if(isset($field['required']) && $field['required'] && (!isset($params[$key]) || empty($params[$key]))){
                            throw new Exception($key.' is required');
                        }
                        elseif(isset($field['required']) && $field['required'] == false && isset($params[$key]) && !empty($params[$key])){
                            $path = str_replace('{'.$key.'?}',$params[$key], $path);
                        }
                        elseif(isset($field['required']) && $field['required'] && isset($params[$key]) && !empty($params[$key])){
                            $path = str_replace('{'.$key.'}',$params[$key], $path);
                        }
                        else{
                            $path = str_replace('{'.$key.'?}','', $path);
                            $path = str_replace('{'.$key.'}','', $path);
                        }
                    }
                }
                return $request->url($path);
            }
        }
        return $request->url($path);
    } else return $request->url($path);
}

function redirect()
{
    $redirect = new Redirect();
    return $redirect;
}

function value($value)
{
    return $value instanceof Closure ? $value() : $value;
}

function data_get($target, $key, $default = null)
{
    if (is_null($key)) {
        return $target;
    }

    $key = is_array($key) ? $key : explode('.', $key);

    foreach ($key as $segment) {
        if (is_array($target)) {
            if (! array_key_exists($segment, $target)) {
                return value($default);
            }

            $target = $target[$segment];
        } elseif ($target instanceof ArrayAccess) {
            if (! isset($target[$segment])) {
                return value($default);
            }

            $target = $target[$segment];
        } elseif (is_object($target)) {
            if (! isset($target->{$segment})) {
                return value($default);
            }

            $target = $target->{$segment};
        } else {
            return value($default);
        }
    }

    return $target;
}

function auth()
{
    return Auth::getInstance();
}

function is_cli()
{
    if ( defined('STDIN') )
    {
        return true;
    }

    if ( php_sapi_name() === 'cli' )
    {
        return true;
    }

    if ( array_key_exists('SHELL', $_ENV) ) {
        return true;
    }

    if ( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0)
    {
        return true;
    }

    if ( !array_key_exists('REQUEST_METHOD', $_SERVER) )
    {
        return true;
    }

    return false;
}

function csrf_field()
{
    $security = Security::getInstance();
    return '<input type="hidden" name="'.$security->get_csrf_token_name().'" value="' . $security->get_csrf_hash() . '">';
}

function csrf_token()
{
    $security = Security::getInstance();
    return $security->get_csrf_hash();
}

function validate($arrs = array())
{
    return Validator::make($arrs);
}

function view($view, $variables = []){
    $render = View::getInstance();
    echo $render->render($view, $variables);
}
function response($content = '', $code = 200)
{
    $response = new Response($content, $code);
    return $response;
}
function app(){
    return new Cuveen\Controller\Controller();
}

function data_set(&$target, $key, $value, $overwrite = true)
{
    $segments = is_array($key) ? $key : explode('.', $key);

    if (($segment = array_shift($segments)) === '*') {
        if (! Arr::accessible($target)) {
            $target = [];
        }

        if ($segments) {
            foreach ($target as &$inner) {
                data_set($inner, $segments, $value, $overwrite);
            }
        } elseif ($overwrite) {
            foreach ($target as &$inner) {
                $inner = $value;
            }
        }
    } elseif (Arr::accessible($target)) {
        if ($segments) {
            if (! Arr::exists($target, $segment)) {
                $target[$segment] = [];
            }

            data_set($target[$segment], $segments, $value, $overwrite);
        } elseif ($overwrite || ! Arr::exists($target, $segment)) {
            $target[$segment] = $value;
        }
    } elseif (is_object($target)) {
        if ($segments) {
            if (! isset($target->{$segment})) {
                $target->{$segment} = [];
            }

            data_set($target->{$segment}, $segments, $value, $overwrite);
        } elseif ($overwrite || ! isset($target->{$segment})) {
            $target->{$segment} = $value;
        }
    } else {
        $target = [];

        if ($segments) {
            data_set($target[$segment], $segments, $value, $overwrite);
        } elseif ($overwrite) {
            $target[$segment] = $value;
        }
    }

    return $target;
}

function data_fill(&$target, $key, $value)
{
    return data_set($target, $key, $value, false);
}

function collect($value = null)
{
    return new Collection($value);
}