<?php


namespace Cuveen\Controller;
use Cuveen\App;
use Cuveen\Exception\CuveenException;

class Controller extends App
{
    public function model($model, $attr = false)
    {
        if(file_exists($this->base_path.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$model.'.php')){
            require_once ($this->base_path.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$model.'.php');
            $class = 'Cuveen\Model\\'.$model;
            return new $class($this->db);
        }
        else{
            return $this->exception('Can not find model '.$model);
        }
    }

    public function exception($message)
    {
        return new CuveenException($message);
    }
}