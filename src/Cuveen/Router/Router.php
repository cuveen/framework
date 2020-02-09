<?php
namespace Cuveen\Router;

use Cuveen\Config\Config;
use Cuveen\Http\Request;

/***
 *  Class Router
 *
 * @method $this get($parttern, $fn)
 * @method $this post($parttern, $fn)
 * @method $this put($parttern, $fn)
 * @method $this delete($parttern, $fn)
 * @method $this options($parttern, $fn)
 * @method $this patch($parttern, $fn)
 * @method $this head($parttern, $fn)
 * */
class Router {
    private $controller_path;
    private $middleware_path;
    private $namespace;
    private $baseRoute = '';
    private $requestedMethod;
    private $serverBasePath;
    public $current_router;
    protected static $_instance;

    protected $patterns = [
        ':id' => '(\d+)',
        ':number' => '(\d+)',
        ':any' => '([^/]+)',
        ':all' => '(.*)',
        ':string' => '(\w+)',
        ':slug' => '([\w\-_]+)',
    ];


    protected $routes = [];

    private $validMethods = ['ANY','GET','POST','PUT','DELETE','OPTIONS','PATCH','HEAD'];

    public function __construct()
    {
        self::$_instance = $this;
    }

    public static function getInstance()
    {
        return self::$_instance;
    }

    public function getBasePath()
    {
        // Check if server base path is defined, if not define it.
        if ($this->serverBasePath === null) {
            $this->serverBasePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
        }

        return $this->serverBasePath;
    }


    public function getCurrentUri()
    {
        // Get the current Request URI and remove rewrite base path from it (= allows one to run the router in a sub folder)
        $uri = substr(rawurldecode($_SERVER['REQUEST_URI']), strlen($this->getBasePath()));

        // Don't take query params into account on the URL
        if (strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        // Remove trailing slash + enforce a slash at the start
        return '/' . trim($uri, '/');
    }

    public function name($name)
    {
        if(!is_string($name)){
            return $this;
        }

        $current = end($this->routes);
        $current['name'] = $name;
        array_pop($this->routes);
        array_push($this->routes, $current);
        return $this;
    }

    public function setNamespace($namespace)
    {
        if(is_string($namespace)){
            $this->namespace = $namespace;
        }
    }

    public function middleware($middlewares)
    {
        if(is_null($middlewares)){
            return $this;
        }

        if(is_string($middlewares)){
            $middlewares = explode(',',$middlewares);
        }

        $current = end($this->routes);
        $current['middlewares'] = $middlewares;
        array_pop($this->routes);
        array_push($this->routes, $current);
        return $this;
        
    }
    /**
     * Get the request method used, taking overrides into account.
     *
     * @return string The Request method to handle
     */
    public function getRequestMethod()
    {
        // Take the method as found in $_SERVER
        $method = $_SERVER['REQUEST_METHOD'];

        // If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
        // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_start();
            $method = 'GET';
        }

        // If it's a POST request, check for a method override header
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $headers = $this->getRequestHeaders();
            if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH'])) {
                $method = $headers['X-HTTP-Method-Override'];
            }
        }

        return $method;
    }

    public function where($param, $rule)
    {
        $current = end($this->routes);
        if(isset($current['params'][$param]) && is_array($current['params'][$param])){
            $current['params'][$param]['rule'] = $rule;
        }
        array_pop($this->routes);
        array_push($this->routes, $current);
        return $this;
    }

    public function __call($method, $params)
    {
        if(\count($params) == 2){
            if (!in_array(strtoupper($method),  $this->validMethods)) {
                return new \Exception($method.' is not valid');
            }
            $pattern = $this->baseRoute.'/'.trim($params[0], '/');
            $route = $this->baseRoute ? rtrim($pattern, '/') : $pattern;
            $fn = $params[1];
            $fn = is_string($fn) && strpos($fn, $this->namespace) == false?$this->namespace.'\\'.$fn:$fn;
            $name = is_string($fn)?strtolower(preg_replace('/[^\w]/i', '.', str_replace($this->namespace, '', $fn))):null;
            $route_params = [];
            if(strpos($route, ':') != false){
                foreach(explode('/',$route) as $item){
                    if(strpos($item, ':') !== false){
                        $is_required = strpos($item, '?') !== false?false: true;
                        $key = str_replace(':','',$item);
                        $key = str_replace('?','',$key);
                        $route_params[$key]['required'] = $is_required;
                    }
                }
            }
            array_push($this->routes, ['method' => strtoupper($method), 'route' => $route, 'name' => $name, 'callback' => $fn,'params'=>$route_params]);
            return $this;
        }
        else{
            throw new \Exception('Route structure wrong');
        }
    }

    public function group($baseRoute, $fn)
    {
        $curBaseRoute = $this->baseRoute;
        $this->baseRoute .= substr($baseRoute,0,1) == '/'?$baseRoute:'/'.$baseRoute;
        call_user_func($fn);
        $this->baseRoute = $curBaseRoute;
    }

    public function runRoute($route)
    {
        // Route Rule
        $count_params = count($route['params']);
        $params = [];
        var_dump($route);
        if(isset($route['params']) && is_array($route['params']) && $count_params > 0){
            foreach($route['params'] as $param=>$item){
                if($item['required'] && empty($item['value'])){
                    throw new \Exception($param.' is required');
                }
                elseif(!empty($item['value']) && !empty($item['rule']) && !preg_match("/^{$item['rule']}$/", $item['value'])){
                    throw new \Exception($param.' is not valid');
                }
                if(!empty($item['value'])){
                    $params[$param] = $item['value'];
                }
            }
        }
        var_dump($params);
        $this->runRouteMiddleware($route);
        $this->runRouteCommand($route['callback'],$params);
    }

    public function listRoutes()
    {
        $this->requestedMethod = $this->getRequestMethod();
        $method = $this->requestedMethod;
        $searches = array_keys($this->patterns);
        $replaces = array_values($this->patterns);

        $uri = $this->getCurrentUri();
        $routes = array_column($this->routes, 'route');
        $foundRoute = false;
        if(in_array($uri, $routes)){
            $currentRoute = array_filter($this->routes, function ($r) use ($method, $uri) {
                return Request::validMethod($r['method'], $method) && $r['route'] === $uri;
            });
            $currentRoute = current($currentRoute);
            $foundRoute = true;
            $this->current_router = $currentRoute;
        }
        else{
            foreach($this->routes as $item){
                $route = $item['route'];

                if (strstr($route, ':') !== false) {
                    //$route = str_replace($searches, $replaces, $route);
                    foreach($item['params'] as $keyp=>$param){
                        $replace = !empty($param['rule'])?$param['rule']:'(.*)';
                        $route = str_replace(':'.$keyp, $replace, $route);
                    }
                }

                $checkFull = preg_match('#^' . $route . '$#', $uri, $matched);
                if(!$checkFull) {
                    $route1 = '';
                    $route2 = '';
                    $exs = explode('/', $route);
                    foreach ($exs as $key => $check) {
                        if ($key == count($exs) - 1 && strpos($check, '?') !== false) {
                            $route2 .= $route1.'/'.str_replace('?','', $check);
                        } else {
                            $route1 .= '/' . $check;
                        }
                    }
                    $route1 = str_replace('//', '/', $route1);
                    $route2 = str_replace('//', '/', $route2);
                    $checkFull = preg_match('#^' . $route1 . '$#', $uri, $matched);
                    if(!$checkFull){
                        $checkFull = preg_match('#^' . $route2 . '$#', $uri, $matched);
                    }
                }
                if($checkFull){
                    if($method == $item['method']){
                        $foundRoute = true;
                        array_shift($matched);
                        $matched = array_map(function ($value) {
                            return trim(urldecode($value));
                        }, $matched);
                        $i=0;
                        foreach($item['params'] as $key=>$param){
                            $item['params'][$key]['value'] = isset($matched[$i])?$matched[$i]:'';
                            $i++;
                        }
                        $this->current_router = $item;
                    }
                }
            }
        }
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * RouterCommand class
     *
     * @return RouterCommand
     */
    public function routerCommand()
    {
        return new RouterCommand();
    }

    public function runRouteMiddleware($route)
    {
        $config = Config::getInstance();
        $middlewareConfig = $config->get('middleware');
        $middlewareExcepts = (isset($middlewareConfig['except']) && is_array($middlewareConfig['except']))?$middlewareConfig['except']:[];
        foreach($middlewareExcepts as $key=>$item){
            $middlewareExcepts[$key] = strpos($item, '@') !== false?(strpos($item, $this->namespace) !== false?$item:$this->namespace.'\\'.$item):$item;
        }
        $callBack = is_object($route['callback'])?'Closure':(strpos($route['callback'], $this->namespace) !== false?$route['callback']:$this->namespace.'\\'.$route['callback']);
        if(!in_array($route['name'], $middlewareExcepts) && !in_array($route['callback'], $middlewareExcepts)) {
            $this->routerCommand()->middleware($route);
        }
    }

    public function runRouteCommand($callback, $params = null)
    {
        if(!is_object($callback)) {
            $segments = explode('@', $callback);
            $controllerClass = $segments[0];
            $method = $segments[1];
            if(class_exists($controllerClass) && method_exists($controllerClass, $method)){
                $controller = new $controllerClass();
                return $this->runMethodWithParams([$controller, $method], $params);
            }
        }
        else{
            return call_user_func_array($callback, $params);
        }
    }

    public function runMethodWithParams($function, $params = null)
    {
        return call_user_func_array($function, (!is_null($params) ? $params : []));
    }

}