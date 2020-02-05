<?php

namespace Cuveen\File;

class File
{
    protected $file;
    protected static $_instance;

    public function __construct($file)
    {
        $this->file = $file;
        self::$_instance = $this;
    }

    public static function getInstance()
    {
        return self::$_instance;
    }

    public function getExt()
    {
        if(isset($this->file['name']) && $this->file['name'] != '') {
            $exs = explode('.', $this->file['name']);
            return strtolower(end($exs));
        }
        return false;
    }

    public function getSize()
    {
        if(isset($this->file['size']) && $this->file['size'] != '') {
            return $this->file['size'];
        }
        return 0;
    }

    public function getMime()
    {
        if(isset($this->file['type']) && $this->file['type'] != '') {
            return $this->file['type'];
        }
        return false;
    }

    public function getName()
    {
        if(isset($this->file['name']) && $this->file['name'] != '') {
            return $this->file['name'];
        }
        return false;
    }

    public function getTmp()
    {
        if(isset($this->file['tmp_name']) && $this->file['tmp_name'] != '') {
            return $this->file['tmp_name'];
        }
        return false;
    }

    public function acceptFile($files = array())
    {
        return in_array($this->getExt(), $files);
    }

    public function upload($dest_path = false, $name = false)
    {
        if(!$dest_path){
            $dest_path = realpath(getcwd());
        }
        $name = ($name)?$name.'.'.$this->getExt():$this->getName();
        $new_path = $dest_path.'/'.$name;
        $direct_file = str_replace('\\','/',str_replace($dest_path,'',$new_path));
        if(mb_substr($direct_file,0,1) == '/'){
            $direct_file = mb_substr($direct_file,1,mb_strlen($direct_file));
        }
        if(move_uploaded_file($this->file['tmp_name'],$new_path)){
            return array(
                'file' => $direct_file,
                'path' => $new_path,
                'name' => $name,
                'size' => $this->getSize()
            );
        }
        else return false;
    }
}