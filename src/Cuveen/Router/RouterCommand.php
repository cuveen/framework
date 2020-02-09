<?php
namespace Cuveen\Router;
use Cuveen\Exception\CuveenException;

class RouterCommand
{
    /**
     * Run Route Middlewares
     *
     * @param $command
     *
     * @return mixed|void
     * @throws
     */
    public function middleware($route)
    {

        if(isset($route['middlewares']) && is_array($route['middlewares']) && count($route['middlewares']) > 0){
            foreach($route['middlewares'] as $middleware){
                $command = explode(':', $middleware);
                $params = [];
                if (count($command) > 1) {
                    $params = explode(',', $command[1]);
                }
                $middlewareClass = strpos($command[0], 'Cuveen\Middleware') !== false?$command[0]:'Cuveen\Middleware\\'.$command[0];
                if(class_exists($middlewareClass) && method_exists($middlewareClass, 'handle')){
                    $response = call_user_func_array([$middlewareClass, 'handle'], $params);
                    if($response !== true){
                        echo $response;
                        exit;
                    }
                    return $response;
                }
                return new CuveenException('handle() method is not found in <b>'.$middlewareClass.'</b> class.');
            }
        }
        return;
    }
}