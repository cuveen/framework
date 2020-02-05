<?php


namespace Cuveen\Exception;

use Exception;

class CuveenException
{
    /**
     * @var bool $debug Debug mode
     */
    public static $debug = false;

    /**
     * Create Exception Class.
     *
     * @param $message
     *
     * @return string
     * @throws Exception
     */

    public function __construct($message)
    {
        if (self::$debug) {
            throw new Exception($message, 1);
        } elseif($this->isAjax()){
            die(json_encode(array('status'=>'error','msg'=>$message)));
        }
        else{
            $html = '<div style="border:1px solid #ccc;margin: 20px;-webkit-box-shadow: 0px 0px 5px 0px rgba(50, 50, 50, 0.5);-moz-box-shadow:0px 0px 5px 0px rgba(50, 50, 50, 0.5);box-shadow:0px 0px 5px 0px rgba(50, 50, 50, 0.5);"><h2 style="padding:10px; font-size: 25px;margin:0px;border-bottom: 1px solid #ccc;">Opps! An error occurred.</h2><pre style="margin: 10px;overflow-x: auto;background: #f3f3f3;padding: 10px;border-radius: 3px;border: 1px solid #d4d4d4;">'.str_replace(getcwd(),'',$message).'</pre><p style="text-align: right;margin:10px;font-style: italic">by <span style="font-style: normal;font-weight: bold">Cuveen Framework</span></p></div>';
            die($html);

        }
    }

    public static function handleException($e)
    {
        $html = '<div style="border:1px solid #ccc;margin: 20px;-webkit-box-shadow: 0px 0px 5px 0px rgba(50, 50, 50, 0.5);-moz-box-shadow:0px 0px 5px 0px rgba(50, 50, 50, 0.5);box-shadow:0px 0px 5px 0px rgba(50, 50, 50, 0.5);"><h2 style="padding:10px; font-size: 25px;margin:0px;border-bottom: 1px solid #ccc;">Opps! An error occurred.</h2><pre style="margin: 10px;overflow-x: auto;background: #f3f3f3;padding: 10px;border-radius: 3px;border: 1px solid #d4d4d4;">'.$e->getMessage()."\n".str_replace(getcwd(),'',$e->getFile()).'('.$e->getLine().')'."\n".str_replace(getcwd(),'',$e->getTraceAsString()).'</pre><p style="text-align: right;margin:10px;font-style: italic">by <span style="font-style: normal;font-weight: bold">Cuveen Framework</span></p></div>';
        die($html);
    }

    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        $html = '<div style="border:1px solid #ccc;margin: 20px;-webkit-box-shadow: 0px 0px 5px 0px rgba(50, 50, 50, 0.5);-moz-box-shadow:0px 0px 5px 0px rgba(50, 50, 50, 0.5);box-shadow:0px 0px 5px 0px rgba(50, 50, 50, 0.5);"><h2 style="padding:10px; font-size: 25px;margin:0px;border-bottom: 1px solid #ccc;">Opps! An error occurred.</h2><pre style="margin: 10px;overflow-x: auto;background: #f3f3f3;padding: 10px;border-radius: 3px;border: 1px solid #d4d4d4;">'.str_replace(getcwd(),'',$errfile).'('.$errline.')'."\n".str_replace(getcwd(),'',$errstr).'</pre><p style="text-align: right;margin:10px;font-style: italic">by <span style="font-style: normal;font-weight: bold">Cuveen Framework</span></p></div>';
        die($html);
    }
    public function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    }
}