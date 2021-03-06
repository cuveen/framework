<?php


namespace Cuveen\Http;

use Cuveen\Http\Cookie;
use Cuveen\Storage\Storage;

class Response
{
    protected $content;
    protected $code;
    protected $file = '';
    protected $data = '';
    protected $headers = [];
    protected $time = 0;
    protected static $instance;

    public function __construct($content = '', $code = 200)
    {
        $this->content = $content;
        $this->code = $code;
        self::$instance = $this;
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    public function code($code)
    {
        $this->code = $code;
        return $this;
    }

    public function json($array = [])
    {
        array_push($this->headers, ['type' => 'Content-Type', 'content' => 'application/json']);
        $this->content = json_encode($array, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function header($type, $content)
    {
        array_push($this->headers, ['type' => $type, 'content' => $content]);
        return $this;

    }

    public function cookie($name, $cookie)
    {
        Cookie::setcookie($name, $cookie);
        return $this;
    }

    public function view($file, $data = '', $code = 200)
    {
        $this->file = $file;
        $this->data = $data;
        $this->code = $code;
        return $this;
    }

    protected function http_response_code($code)
    {
        switch ($code) {
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 200: $text = 'OK'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            default:
                exit('Unknown http status code "' . htmlentities($code) . '"');
                break;
        }
        return $text;
    }

    public function download($filePath, $suggestedFilename = false, $mimeType = null)
    {
        $storage = new Storage();
        return $storage->download($filePath, $suggestedFilename, $mimeType);
    }

    public function __destruct()
    {
        if (count($this->headers) > 0) {
            foreach ($this->headers as $header) {
                header($header['type'] . ': ' . $header['content']);
            }
        }
        header('HTTP/1.1 ' . $this->code . ' ' . $this->http_response_code($this->code));
        if(!empty($this->file)){
            echo view($this->file, $this->data);
        }
        elseif(!empty($this->content)){
            echo $this->content;
        }
    }
}