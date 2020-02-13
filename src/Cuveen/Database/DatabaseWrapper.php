<?php
namespace Cuveen\Database;
/**
 * Subclass of Cuveen's Database class that supports
 * returning instances of a specified class rather
 * than raw instances of the Database class.
 *
 * You shouldn't need to interact with this class
 * directly. It is used internally by the Model base
 * class.
 *
 *
 * The methods documented below are magic methods that conform to PSR-1.
 * This documentation exposes these methods to doc generators and IDEs.
 * @see http://www.php-fig.org/psr/psr-1/
 *
 * @method void setClassName($class_name)
 * @method static \DatabaseWrapper forTable($table_name, $connection_name = parent::DEFAULT_CONNECTION)
 */
class DatabaseWrapper extends Database {

    /**
     * The wrapped find_one and find_many classes will
     * return an instance or instances of this class.
     *
     * @var string $_class_name
     */
    protected $_class_name;

    protected $_timestamp;

    protected $_created_at;

    protected $_updated_at;

    /**
     * Set the name of the class which the wrapped
     * methods should return instances of.
     *
     * @param  string $class_name
     * @return void
     */
    public function set_class_name($class_name) {
        $this->_class_name = $class_name;
    }
    public function set_timestamp($timestamp) {
        $this->_timestamp = $timestamp;
    }
    public function set_created_at($created_at) {
        $this->_created_at = $created_at;
    }
    public function set_updated_at($updated_at) {
        $this->_updated_at = $updated_at;
    }


    /**
     * Add a custom filter to the method chain specified on the
     * model class. This allows custom queries to be added
     * to models. The filter should take an instance of the
     * ORM wrapper as its first argument and return an instance
     * of the ORM wrapper. Any arguments passed to this method
     * after the name of the filter will be passed to the called
     * filter function as arguments after the ORM class.
     *
     * @return ORMWrapper
     */
    public function filter() {
        $args = func_get_args();
        $filter_function = array_shift($args);
        array_unshift($args, $this);
        if (method_exists($this->_class_name, $filter_function)) {
            return call_user_func_array(array($this->_class_name, $filter_function), $args);
        }
    }

    /**
     * Factory method, return an instance of this
     * class bound to the supplied table name.
     *
     * A repeat of content in parent::for_table, so that
     * created class is ORMWrapper, not ORM
     *
     * @param  string $table_name
     * @param  string $connection_name
     * @return ORMWrapper
     */
    public static function for_table($table_name, $connection_name = parent::DEFAULT_CONNECTION) {
        self::_setup_db($connection_name);
        return new self($table_name, array(), $connection_name);
    }

    /**
     * Method to create an instance of the model class
     * associated with this wrapper and populate
     * it with the supplied Idiorm instance.
     *
     * @param  ORM $orm
     * @return bool|Model
     */
    protected function _create_model_instance($orm) {
        if ($orm === false) {
            return false;
        }
        $orm->_timestamp = $this->_timestamp;
        $orm->_created_at = $this->_created_at;
        $orm->_updated_at = $this->_updated_at;
        $model = new $this->_class_name();
        $model->set_orm($orm);
        return $model;
    }

    /**
     * Wrap Idiorm's find_one method to return
     * an instance of the class associated with
     * this wrapper instead of the raw ORM class.
     *
     * @param  null|integer $id
     * @return Model
     */
    public function find($id=null) {
        return $this->_create_model_instance(parent::find_one($id));
    }

    public function paginate($perpage = 10)
    {
        $results = parent::_paginate($perpage);
        foreach($results as $key => $result) {
            $results[$key] = $this->_create_model_instance($result);
        }
        return $results;
    }

    /**
     * Wrap Idiorm's find_many method to return
     * an array of instances of the class associated
     * with this wrapper instead of the raw ORM class.
     *
     * @return Array
     */
    public function get() {
        $results = parent::find_many();
        foreach($results as $key => $result) {
            $results[$key] = $this->_create_model_instance($result);
        }
        return $results;
    }

    /**
     * Wrap Idiorm's create method to return an
     * empty instance of the class associated with
     * this wrapper instead of the raw ORM class.
     *
     * @return ORMWrapper|bool
     */
    public function create($data=null) {
        return $this->_create_model_instance(parent::create($data));
    }
}