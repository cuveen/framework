<?php

namespace Cuveen\Validator;

use Cuveen\Database\Database;
use Cuveen\Helper\Arr;
use Cuveen\Http\Request;

class Validator {

    protected static $request;
    public $errors = [];
    protected static $valids = [
        'uri'           => '[A-Za-z0-9-\/_?&=]+',
        'url'           => '[A-Za-z0-9-:.\/_?&=#]+',
        'alpha'         => '[\p{L}]+',
        'words'         => '[\p{L}\s]+',
        'alphanum'      => '[\p{L}0-9]+',
        'int'           => '[0-9]+',
        'float'         => '[0-9\.,]+',
        'tel'           => '[0-9+\s()-]+',
        'text'          => '[\p{L}0-9\s-.,;:!"%&()?+\'°#\/@]+',
        'file'          => '[\p{L}\s0-9-_!%&()=\[\]#@,.;+]+\.[A-Za-z0-9]{2,4}',
        'folder'        => '[\p{L}\s0-9-_!%&()=\[\]#@,.;+]+',
        'address'       => '[\p{L}0-9\s.,()°-]+',
        'date_dmy'      => '[0-9]{1,2}\-[0-9]{1,2}\-[0-9]{4}',
        'date_ymd'      => '[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}',
        'email'         => '[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+[.]+[a-z-A-Z]'
    ];
    protected static $_instance;

    protected $messages = [];

    public function __construct()
    {
        self::$_instance = $this;
    }

    public static function getInstance()
    {
        return self::$_instance;
    }

    public function fails()
    {
        if(count($this->errors)){
            return true;
        }
        return false;
    }

    public function errors()
    {
        $errors = new Errors();
        $errors->errors = $this->errors;
        return $errors;
    }

    public function run($arrs = array())
    {
        $request = Request::getInstance();
        if(count($arrs) > 0){
            foreach($arrs as $key=>$rules){
                $value = $request->get($key);
                // loop rules
                $exs = explode('|', $rules);
                if(count($exs) > 0){
                    foreach($exs as $ex){
                        if($ex == 'file' && !$request->hasFile($key)){
                            $this->errors[$key]['file'] = 'Field '.$key.' is required';
                        }
                        if($ex == 'required' && (!$request->has($key) || empty($request->get($key)))){
                            $this->errors[$key]['required'] = 'Field '.$key.' is required';
                        }
                        if($ex == 'number' && !is_numeric($value)) {
                            $this->errors[$key]['number'] = 'Field '.$key.' is numberic';
                        }
                        if($ex == 'url' && !filter_var($value, FILTER_VALIDATE_URL)){
                            $this->errors[$key]['url'] = 'Field '.$key.' must be url';
                        }
                        if($ex == 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)){
                            $this->errors[$key]['email'] = 'Field '.$key.' must be email';
                        }
                        if($ex == 'alpha' && !filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^[a-zA-Z]+$/")))){
                            $this->errors[$key]['alpha'] = 'Field '.$key.' must be alphabets';
                        }
                        $pos = strpos($ex, ':');
                        if($pos !== false){
                            $dotexs = explode(':', $ex);
                            $rule = $dotexs[0];
                            $rule1 = @$dotexs[1];
                            if(!empty($rule1) && !empty($rule)) {
                                if ($rule == 'min' && strlen($value) < (int)$rule1) {
                                    $this->errors[$key]['min'] = 'Field ' . $key . ' minimum ' . $rule1 . ' characters';
                                }
                                if ($rule == 'max' && strlen($value) > (int)$rule1) {
                                    $this->errors[$key]['max'] = 'Field ' . $key . ' maximum ' . $rule1 . ' characters';
                                }
                                if ($rule == 'unique') {
                                    $tbexs = explode(',',$rule1);
                                    if(count($tbexs) >= 2){
                                        $table = $tbexs[0];
                                        $column = $tbexs[1];
                                        $sql = "SELECT * FROM `{$table}` WHERE ".$column."='{$value}'";
                                        if(isset($tbexs[2]) && $tbexs[2] != ''){
                                            $except = $tbexs[2];
                                            $idCol = (isset($tbexs[3]) && $tbexs[3] != '')?$tbexs[3]:'id';
                                            $db->where($idCol, $except, 'NOT IN');
                                            $sql .= " AND ".$idCol." NOT IN ('{$except}')";
                                        }
                                        $query = Database::raw_execute($sql);
                                        $statement = Database::getLastStatement();
                                        $result = $statement->fetch(\PDO::FETCH_ASSOC);
                                        if(count($result)){
                                            $this->errors[$key]['unique'] = $value.' already exist in database';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->customErrors($this->messages);
        }
        return $this;
    }

    public function customErrors($arrs = array())
    {
        $this->errors = Arr::merge($this->errors, $arrs);
        return $this;
    }

    public static function make($rules = array(), $messages = array())
    {
        $validator = new self();
        $validator->messages = $messages;
        return $validator->run($rules);
    }
}