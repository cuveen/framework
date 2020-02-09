<?php


namespace Cuveen\Http;
use Cuveen\Session\Session;

class Redirect
{
    protected static $_instance;
    protected $request;
    protected $url;
    protected $content;
    protected $header;
    protected $messages;
    protected $session;
    public function __construct()
    {
        $this->request = Request::getInstance();
        $this->session = Session::getInstance();
        self::$_instance = $this;
    }

    public static function getInstance()
    {
        return self::$_instance;
    }

    public function to($uri = '')
    {
        $url = $this->request->url;
        if(!empty($uri)) {
            $pos = strpos($uri, 'http://');
            $poss = strpos($uri, 'https://');
            if($pos !== false || $poss !== false){
                $url = $uri;
            }
            else{
                $url .= $uri;
            }
        }
        $this->content = sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="0;url=\'%1$s\'" />
        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.
    </body>
</html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
        $this->header = 'Location: '.$url;
        $this->url = $url;
        return $this;
    }

    public function with($key, $value = null)
    {
        if(is_array($key)){
            $arrs = $key;
        }
        elseif(!is_null($value)){
            $arrs = [$key=>$value];
        }
        else{
            $arrs = [$key];
        }
        foreach($arrs as $key=>$arr){
            $this->session->flash($key, $arr);
        }
    }

    public function withCookies(array $cookies)
    {
        foreach ($cookies as $cookie) {
            Cookie::setcookie($cookie);
        }

        return $this;
    }

    protected function removeFilesFromInput(array $input)
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = $this->removeFilesFromInput($value);
            }
        }

        return $input;
    }

    public function withErrors(array $arrs, $key = 'default')
    {
        $this->session->flash('errors', $arrs);
        return $this;
    }

    public function back()
    {
        $this->url = ($this->request->referer)?$this->request->referer:$this->request->url;
        return $this->to($this->url);
    }

    public function router($name = '/', $params = null)
    {
        $url = router($name, $params);
        return $this->to($url);
    }

    public function __destruct()
    {
        if(!is_null($this->content)){
            echo $this->content;
        }
        if(!is_null($this->header)){
            header($this->header);
        }
    }

}