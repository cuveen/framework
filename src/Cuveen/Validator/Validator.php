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
                $rules = explode('|', $rules);
                if(count($rules) > 0){
                    /*Check Is File Request*/
                    $value = $request->get($key);
                    $files = $request->file($key);
                    foreach($rules as $rule){
                        if($rule == 'required' && !$value && !$files){
                            $this->errors[$key]['required'] = 'The '.$key.' is required';
                        }
                        if($rule == 'file' && !$files){
                            $this->errors[$key]['required'] = 'The '.$key.' must be a file';
                        }
                        if($rule == 'number' && !is_numeric($value)) {
                            $this->errors[$key]['number'] = 'The '.$key.' is numberic';
                        }
                        if($rule == 'url' && !filter_var($value, FILTER_VALIDATE_URL)){
                            $this->errors[$key]['url'] = 'The '.$key.' must be url';
                        }
                        if($rule == 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)){
                            $this->errors[$key]['email'] = 'The '.$key.' must be email';
                        }
                        if($rule == 'alpha' && !filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^[a-zA-Z]+$/")))){
                            $this->errors[$key]['alpha'] = 'The '.$key.' must be alphabets';
                        }
                        if($rule == 'confirmed'){
                            $value_confirmed = $request->get($key.'_confirmation');
                            if($value_confirmed != $value){
                                $this->errors[$key]['confirmed'] = 'The '.$key.' confirmation does not match.';
                            }
                        }
                        if($rule == 'image'){
                            if(is_object($files) && !in_array($files->getMime(), ['image/gif','image/jpeg','image/png','image/bmp'])){
                                $this->errors[$key]['image'] = 'The '.$key.' must be image';
                            }
                            if(is_array($files)){
                                foreach($files as $keyf=>$file){
                                    if(!in_array($file->getMime(), ['image/gif','image/jpeg','image/png','image/bmp'])){
                                        $this->errors[$key]['image'][] = 'The '.$key.'-'.($keyf+1).' must be image';
                                    }
                                }
                            }
                        }
                        if($rule == 'string' && $value){
                            if(is_string($value) && is_numeric($value)){
                                $this->errors[$key]['string'] = 'The '.$key.' must be a string.';
                            }
                        }
                        if(strpos($rule, ':') !== false){
                            $dotexs = explode(':', $rule);
                            $rule1 = $dotexs[0];
                            $rule2 = @$dotexs[1];
                            if(!empty($rule2) && !empty($rule1)) {
                                if (is_string($value) && $rule1 == 'min' && mb_strlen($value) < (int)$rule2) {
                                    $this->errors[$key]['min'] = 'The ' . $key . ' minimum ' . $rule2 . ' characters';
                                }
                                if(is_string($value) && is_numeric($value) && $rule1 == 'min' && (int)$value < (int)$rule2){
                                    $this->errors[$key]['max'] = 'The ' . $key . ' is minimum ' . $rule2;
                                }
                                if(is_array($value) && $rule1 == 'min' && count($value) < (int)$rule2){
                                    $this->errors[$key]['max'] = 'The ' . $key . ' is minimum ' . $rule2.' arrays';
                                }

                                if (is_string($value) &&!is_numeric($value) && $rule1 == 'max' && mb_strlen($value) > (int)$rule2) {
                                    $this->errors[$key]['max'] = 'The ' . $key . '  may not be greater than ' . $rule2 . ' characters';
                                }
                                if(is_string($value) && is_numeric($value) && $rule1 == 'max' && (int)$value > (int)$rule2){
                                    $this->errors[$key]['max'] = 'The ' . $key . '  may not be greater than ' . $rule2;
                                }
                                if(is_array($value) && $rule1 == 'max' && count($value) > (int)$rule2){
                                    $this->errors[$key]['max'] = 'The ' . $key . ' may not have more than ' . $rule2.' items';
                                }

                                if(is_object($files) && $files->getSize() > (int)$rule2*1000){
                                    $this->errors[$key]['max'] = 'The ' . $key . ' must be less than or equal ' . $rule2.'KB';
                                }
                                if(is_array($files)){
                                    foreach($files as $keyf=>$file){
                                        if($file->getSize() > (int)$rule2*1000){
                                            $this->errors[$key]['max'][] = 'The ' . $key . '-'.($keyf+1).' must be less than or equal ' . $rule2.'KB';
                                        }
                                    }
                                }

                                if($rule1 == 'mimetypes' && (is_array($files) || is_object($files))){
                                    $listRules = explode(',', $rule2);
                                    if(is_object($files) && !in_array($files->getMime(), $listRules)){
                                        $this->errors[$key]['mimetypes'] = 'The '.$key.' must be a file of type '.$rule2;
                                    }
                                    if(is_array($files)){
                                        foreach($files as $keyf=>$file){
                                            if(!in_array($file->getMime(), $listRules)){
                                                $this->errors[$key]['mimetypes'][] = 'The '.$key.'-'.($keyf+1).' must be a file of type '.$rule2;
                                            }
                                        }
                                    }
                                }
                                if($rule1 == 'mimes' && (is_array($files) || is_object($files))){
                                    $listMimes = explode(',',$rule2);
                                    if(is_object($files) && !in_array($files->getExt(), $listMimes)){
                                        $this->errors[$key]['mimes'] = 'The '.$key.' must be a file of type: '.$rule2;
                                    }
                                    if(is_array($files)){
                                        foreach($files as $keyf=>$file){
                                            if(!in_array($file->getExt(), $listMimes)){
                                                $this->errors[$key]['mimes'][] = 'The '.$key.'-'.($keyf+1).' must be a file of type: '.$rule2;
                                            }
                                        }
                                    }
                                }

                                if ($rule1 == 'unique') {
                                    $tbexs = explode(',',$rule2);
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
                                if($rule1 == 'size'){
                                    if(is_string($value) &&!is_numeric($value) && mb_strlen($value) != (int)$rule2){
                                        $this->errors[$key]['size'] = 'The '.$key.' must be exactly '.$rule2;
                                    }
                                    if(is_numeric($value) && is_string($value) && $value != $rule2){
                                        $this->errors[$key]['size'] = 'The '.$key.' must be exactly '.$rule2;
                                    }
                                    if(is_array($value) && count($value) != (int)$rule2){
                                        $this->errors[$key]['size'] = 'The '.$key.' must be exactly '.$rule2;
                                    }
                                    if(is_object($files) && $files->getSize() != (int)$rule2*1000){
                                        $this->errors[$key]['size'] = 'The '.$key.' must be '.$rule2.'KB';
                                    }
                                    if(is_array($files)){
                                        foreach($files as $keyf=>$file){
                                            if($file->getSize() != (int)$rule2){
                                                $this->errors[$key]['size'][] = 'The '.$key.'-'.($keyf+1).' must be '.$rule2.'KB';
                                            }
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