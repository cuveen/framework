<?php


namespace Cuveen\Validator;


use Cuveen\Helper\Arr;
use Cuveen\Helper\Str;
use Cuveen\Session\Session;

class Errors
{
    public $errors = [];

    public function __construct()
    {
        $session = Session::getInstance();
        $this->errors = $session->get('errors',[]);
        $session->forget('errors');
    }

    public function any()
    {
        return count($this->errors);
    }

    public function all()
    {
        if($this->errors) {
            return $this->errors['errors'];
        }
        return [];
    }

    public function first($key = false, $format = null)
    {
        $messages = is_null($key) ? $this->all() : $this->get($key, $format);
        $firstMessage = Arr::first($messages, null, '');


        return is_array($firstMessage) ? Arr::first($firstMessage) : $firstMessage;
    }

    public function isEmpty()
    {
        return ! $this->any();
    }

    public function has($key)
    {
        if ($this->isEmpty()) {
            return false;
        }

        if (is_null($key)) {
            return $this->any();
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $key) {
            if ($this->first($key) === '') {
                return false;
            }
        }

        return true;
    }

    public function get($key, $format = null)
    {
        if (array_key_exists($key, $this->errors['errors'])) {
            return $this->errors['errors'][$key];
        }

        if (Str::contains($key, '*')) {
            $key = str_replace('.*','',$key);
            return $this->get($key);
        }

        return [];
    }
}