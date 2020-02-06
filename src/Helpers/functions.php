<?php
use Cuveen\Config\Config;
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

function router($name = '', $params = array())
{
    $request = Request::getInstance();
    if (is_array($request->routes)) {
        foreach ($request->routes as $route) {
            if (isset($route['name']) && $route['name'] == $name && isset($route['route']) && $route['route'] != '') {
                return $request->url($route['route']);
            }
        }
    } else return $request->url($name);
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

function csrf_field()
{
    $security = Security::getInstance();
    return '<input type="hidden" name="_token" value="' . $security->getToken() . '">';
}

function csrf_token()
{
    $security = Security::getInstance();
    return $security->getToken();
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