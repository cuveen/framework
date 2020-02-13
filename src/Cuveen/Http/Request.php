<?php
namespace Cuveen\Http;
use Cuveen\File\File;
use Cuveen\Helper\Arr;
use Cuveen\Session\Session;
use Cuveen\Validator\Validator;
class Request
{
    public $ip;
    public $url;
    public $referer;
    public $method;
    public $fullurl;
    protected static $_instance;
    public $routes;
    public $route;
    public $domain;

    /**
     * @var string $validMethods Valid methods for Requests
     */
    public static $validMethods = 'GET|POST|PUT|DELETE|HEAD|OPTIONS|PATCH|ANY|AJAX|XPOST|XPUT|XDELETE|XPATCH';

    public function __construct()
    {
        $this->url = $this->url();
        $this->ip = $this->ip();
        $this->domain = $this->domain();
        $this->referer = $this->referer();
        $this->method = isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET';
        $this->fullurl = $this->full_url();
        self::$_instance = $this;

    }

    public function domain()
    {
        $parse = parse_url($this->url);
        return $parse['host'];
    }


    public static function getInstance()
    {
        return self::$_instance;
    }

    public function validate($arrs = array())
    {
        return Validator::make($arrs);
    }

    public function ValidateIpAddress($ip_addr)
    {
        $preg = '#^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}' .
            '(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$#';

        if(preg_match($preg, $ip_addr))
        {
            //now all the intger values are separated
            $parts = explode('.', $ip_addr);
            if (count($parts) == 4) {
                foreach ($parts as $part) {
                    if ($part > 255 || $part < 0) {
                        //error
                    }
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function ip(){
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->ValidateIpAddress($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
                $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($iplist as $ip) {
                    if ($this->ValidateIpAddress($ip))
                        return $ip;
                }
            } else {
                if ($this->ValidateIpAddress($_SERVER['HTTP_X_FORWARDED_FOR']))
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->ValidateIpAddress($_SERVER['HTTP_X_FORWARDED']))
            return $_SERVER['HTTP_X_FORWARDED'];
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->ValidateIpAddress($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->ValidateIpAddress($_SERVER['HTTP_FORWARDED_FOR']))
            return $_SERVER['HTTP_FORWARDED_FOR'];
        if (!empty($_SERVER['HTTP_FORWARDED']) && $this->ValidateIpAddress($_SERVER['HTTP_FORWARDED']))
            return $_SERVER['HTTP_FORWARDED'];
        return isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'';
    }

    public function browser() {
        $ub    = '';
        $u_agent  = $_SERVER['HTTP_USER_AGENT'];
        $bname    = 'Unknown';
        $platform = 'Unknown';
        $version  = '';
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }
        if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
            $bname = 'Internet Explorer';
            $ub    = 'MSIE';
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub    = 'Firefox';
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            $bname = 'Google Chrome';
            $ub    = 'Chrome';
        } elseif (preg_match('/Safari/i', $u_agent)) {
            $bname = 'Apple Safari';
            $ub    = 'Safari';
        } elseif (preg_match('/Opera/i', $u_agent)) {
            $bname = 'Opera';
            $ub    = 'Opera';
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            $bname = 'Netscape';
            $ub    = 'Netscape';
        }
        $known   = array(
            'Version',
            $ub,
            'other'
        );
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
        }
        $i = count($matches['browser']);
        if ($i != 1) {
            if (strripos($u_agent, 'Version') < strripos($u_agent, $ub)) {
                $version = $matches['version'][0];
            } else {
                $version = $matches['version'][1];
            }
        } else {
            $version = $matches['version'][0];
        }
        if ($version == null || $version == "") {
            $version = '?';
        }
        return array(
            'userAgent' => $u_agent,
            'name' => $bname,
            'version' => $version,
            'platform' => $platform,
            'pattern' => $pattern
        );
    }

    public function device() {
        $deviceName = '';
        $userAgent    = $_SERVER['HTTP_USER_AGENT'];
        $devicesTypes = array(
            'computer' => array(
                'msie 10',
                'msie 9',
                'msie 8',
                'windows.*firefox',
                'windows.*chrome',
                'x11.*chrome',
                'x11.*firefox',
                'macintosh.*chrome',
                'macintosh.*firefox',
                'opera'
            ),
            'tablet' => array(
                'tablet',
                'android',
                'ipad',
                'tablet.*firefox'
            ),
            'mobile' => array(
                'mobile ',
                'android.*mobile',
                'iphone',
                'ipod',
                'opera mobi',
                'opera mini'
            ),
            'bot' => array(
                'googlebot',
                'mediapartners-google',
                'adsbot-google',
                'duckduckbot',
                'msnbot',
                'bingbot',
                'ask',
                'facebook',
                'yahoo',
                'addthis'
            )
        );
        foreach ($devicesTypes as $deviceType => $devices) {
            foreach ($devices as $device) {
                if (preg_match('/' . $device . '/i', $userAgent)) {
                    $deviceName = $deviceType;
                }
            }
        }
        return ucfirst($deviceName);
    }

    public function device_token()
    {
        $finger_print               = array();
        $browser                    = $this->browser();
        $finger_print['ip']         = $this->ip;
        $finger_print['browser']    = $browser['name'] . " " . $browser['version'];
        $finger_print['os']         = $browser['platform'];
        $finger_print['deviceType'] = $this->device();
        $device                     = serialize($finger_print);
        return $device;
    }

    public function getAuthorization()
    {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (function_exists('apache_request_headers')) {
                $requestHeaders = apache_request_headers();
                $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
                if (isset($requestHeaders['Authorization'])) {
                    $_SERVER['HTTP_AUTHORIZATION'] = trim($requestHeaders['Authorization']);
                }
            }
        }
        return !empty($_SERVER['HTTP_AUTHORIZATION'])?str_replace('Bearer ','',$_SERVER['HTTP_AUTHORIZATION']):false;
    }

    public function referer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER']: $this->url();
    }

    public function has($attr = false)
    {
        if($attr && isset($_REQUEST[$attr]) && $_REQUEST[$attr] != ''){
            return true;
        }
        return false;
    }

    public function get($attr)
    {
        if($attr && $attr != '' && isset($_REQUEST[$attr]) && $_REQUEST[$attr] != ''){
            return $this->cleanInput($_REQUEST[$attr]);
        }
        return '';
    }

    public function post($attr)
    {
        if($attr && $attr != '' && isset($_POST[$attr]) && $_POST[$attr] != ''){
            return $this->cleanInput($_POST[$attr]);
        }
        return false;
    }

    public function is_ajax()
    {
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
            return true;
        }
        return false;
    }

    public function url($path = false){
        if($path && mb_substr($path,0,1) == '/'){
            $path = mb_substr($path,1,mb_strlen($path));
        }
        $base_url = str_replace('\\','/',realpath(getcwd()));
        $base_url = str_replace($_SERVER['DOCUMENT_ROOT'],'',$base_url);
        if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])){
            $url = $_SERVER['HTTP_X_FORWARDED_PROTO'].'://';
        }
        else{
            $url = !empty($_SERVER['HTTPS']) ? "https://" : "http://";
        }
        $url .= (isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'').$base_url.'/'.$path;
        if(!$path){
            $this->url = $url;
        }
        return $url;
    }

    public function full_url()
    {
        $s = &$_SERVER;
        $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
        $protocol = isset($s['SERVER_PROTOCOL'])?$s['SERVER_PROTOCOL']:'';
        $sp = strtolower($protocol);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port = isset($s['SERVER_PORT'])?$s['SERVER_PORT']:80;
        $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
        $host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
        $sv = isset($s['SERVER_NAME'])?$s['SERVER_NAME']:'localhost';
        $host = isset($host) ? $host : $sv . $port;
        $url = $protocol . '://' . $host . (isset($s['REQUEST_URI'])?$s['REQUEST_URI']:'');
        return $url;
    }



    public function file($attr)
    {
        if($attr && $attr != '' && isset($_FILES[$attr]) && $_FILES[$attr] != ''){
            if(isset($_FILES[$attr]['error']) && is_array($_FILES[$attr]['error'])){
                $files = array();
                foreach($_FILES[$attr]['name'] as $key=>$val){
                    if($_FILES[$attr]['error'][$key] === 0) {
                        array_push($files, array(
                            'name' => $val,
                            'type' => $_FILES[$attr]['type'][$key],
                            'tmp_name' => $_FILES[$attr]['tmp_name'][$key],
                            'error' => $_FILES[$attr]['error'][$key],
                            'size' => $_FILES[$attr]['size'][$key]
                        ));
                    }
                }
                return $files;
            }
            else{
                $file = new File($_FILES[$attr]);
                return $file;
            }
        }
        return false;
    }

    public function hasFile($attr = false)
    {
        if($attr && isset($_FILES[$attr]) && isset($_FILES[$attr]['error'])){
            if(is_array($_FILES[$attr]['error'])){
                $is_error = false;
                foreach($_FILES[$attr]['error'] as $error){
                    if($error === 0){
                        $is_error = true;
                    }
                }
                return $is_error;
            }
            elseif($_FILES[$attr]['error'] === 0){
                return true;
            }
        }
        return false;
    }

    protected function cleanInput($data = false)
    {
        if($data){
            if(is_array($data)){
                $secure_data = array();
                foreach($data as $key=>$item){
                    $secure_data[$key] = $this->cleanInput($item);
                }
                return $secure_data;
            }
            else return stripslashes(trim(htmlspecialchars($data)));
        }
        return $data;
    }

    /**
     * Request method validation
     *
     * @param string $data
     * @param string $method
     *
     * @return bool
     */
    public static function validMethod($data, $method)
    {
        $valid = false;
        if (strstr($data, '|')) {
            foreach (explode('|', $data) as $value) {
                $valid = self::checkMethods($value, $method);
                if ($valid) {
                    break;
                }
            }
        } else {
            $valid = self::checkMethods($data, $method);
        }

        return $valid;
    }

    /**
     * Get the request method used, taking overrides into account
     *
     * @return string
     */
    public static function getRequestMethod()
    {
        // Take the method as found in $_SERVER
        $method = $_SERVER['REQUEST_METHOD'];
        // If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
        // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        if ($method === 'HEAD') {
            ob_start();
            $method = 'GET';
        } elseif ($method === 'POST') {
            $headers = self::getRequestHeaders();
            if (isset($headers['X-HTTP-Method-Override']) &&
                in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'])) {
                $method = $headers['X-HTTP-Method-Override'];
            } elseif (! empty($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            }
        }

        return $method;
    }

    /**
     * check method valid
     *
     * @param string $value
     * @param string $method
     *
     * @return bool
     */
    protected static function checkMethods($value, $method)
    {
        if (in_array($value, explode('|', self::$validMethods))) {
            if (self::isAjax() && $value === 'AJAX') {
                return true;
            }

            if (self::isAjax() && strpos($value, 'X') === 0 && $method === ltrim($value, 'X')) {
                return true;
            }

            if (in_array($value, [$method, 'ANY'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check ajax request
     *
     * @return bool
     */
    protected static function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    }

    /**
     * Get all request headers
     *
     * @return array
     */
    protected static function getRequestHeaders()
    {
        // If getallheaders() is available, use that
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        // Method getallheaders() not available: manually extract 'm
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_' || $name === 'CONTENT_TYPE' || $name === 'CONTENT_LENGTH') {
                $headerKey = str_replace(
                    [' ', 'Http'],
                    ['-', 'HTTP'],
                    ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                );
                $headers[$headerKey] = $value;
            }
        }

        return $headers;
    }


}