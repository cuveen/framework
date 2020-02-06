<?php


namespace Cuveen\Model;


class Model
{
    protected $db;

    protected $table;
    protected $primaryKey = 'id';
    protected $timestamp = true;
    protected $pageLimit = 20;
    protected $singularName;
    protected $insert_id;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /*Relationship variables*/
    private $_relationships = array();
    public $separate_subqueries = TRUE;
    protected $after_get = array();
    protected $relation;
    protected $foreign_key;
    protected $local_key;
    protected $options;


    public function __construct($db)
    {
        if(is_null($this->table)){
            $this->table = DatabaseTable::pluralize(mb_strtolower($this->className(get_called_class())));
        }
        $this->singularName = DatabaseTable::singularize($this->table);
        $this->db = $db;
    }

    public function limit($num)
    {
        $this->pageLimit = $num;
        return $this;
    }

    public function count()
    {
        return $this->db->count;
    }

    public function className($name)
    {
        $class = explode( '\\', $name );
        $class = end( $class );
        return $class;
    }

    public function getOne($columns = '*')
    {
        return $this->db->getOne($this->table, $columns);
    }

    public function getValue($column, $limit = 1)
    {
        return $this->db->getValue($column, $limit = 1);
    }

    public function insert($data)
    {
        if($this->timestamp) {
            if (!isset($data[self::CREATED_AT]) || empty($data[self::CREATED_AT])) {
                $data[self::CREATED_AT] = date('Y-m-d H:i:s');
            }
            if (!isset($data[self::UPDATED_AT]) || empty($data[self::UPDATED_AT])) {
                $data[self::CREATED_AT] = date('Y-m-d H:i:s');
            }
        }
        $result = $this->db->insert($this->table, $data);
        $this->insert_id = $this->db->getInsertId();
        return $result;
    }

    public function get($numRows = null, $columns = '*')
    {
        $this->trigger('before_get');
        $data = $this->db->get($this->table, $numRows, $columns);
        $data = $this->trigger('after_get',$data);
        return $data;
    }

    public function totalPages()
    {
        return $this->db->totalPages;
    }

    public function paginate($page, $fields = null)
    {
        $this->db->pageLimit  = $this->pageLimit;
        return $this->db->paginate($this->table, $page, $fields);
    }

    public function delete($numRows = null)
    {
        return $this->db->delete($this->table, $numRows);
    }

    public function update($data, $numRows = null)
    {
        if($this->timestamp) {
            if (!isset($data[self::UPDATED_AT]) || empty($data[self::UPDATED_AT])) {
                $data[self::CREATED_AT] = date('Y-m-d H:i:s');
            }
        }
        return $this->db->update($this->table, $data, $numRows);
    }

    public function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        $this->db->where($whereProp,$whereValue,$operator,$cond);
        return $this;
    }

    public function whereIn($whereProp, $whereValue = 'DBNULL')
    {
        $this->where($whereProp,$whereValue,'IN');
        return $this;
    }

    public function WhereNotIn($whereProp, $whereValue = 'DBNULL')
    {
        $this->where($whereProp,$whereValue,'NOT IN');
        return $this;
    }

    public function like($whereProp, $whereValue = 'DBNULL')
    {
        $this->where($whereProp, $whereValue, 'like');
        return $this;
    }

    public function orWhere($whereProp, $whereValue = 'DBNULL', $operator = '=')
    {
        $this->where($whereProp, $whereValue, $operator, 'OR');
        return $this;
    }

    public function having($havingProp, $havingValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        return $this->db->having($havingProp, $havingValue, $operator, $cond);
    }

    public function orHaving($havingProp, $havingValue = null, $operator = null)
    {
        return $this->having($havingProp, $havingValue, $operator, 'OR');
    }

    public function join($joinTable, $joinCondition, $joinType = '')
    {
        $this->db->join($joinTable, $joinCondition, $joinType);
        return $this;
    }

    public function orderBy($orderByField, $orderbyDirection = "DESC", $customFieldsOrRegExp = null)
    {
        $this->db->orderBy($orderByField, $orderbyDirection, $customFieldsOrRegExp);
        return $this;
    }
    
    public function groupBy($groupByField)
    {
        $this->db->groupBy($groupByField);
        return $this;
    }

    /** RELATIONSHIPS */

    /**
     * public function with($requests)
     * allows the user to retrieve records from other interconnected tables depending on the relations defined before the constructor
     * @param string $requests
     * @param bool $separate_subqueries
     * @return $this
     */
    public function with($requests)
    {
        $requests = explode(',', $requests);
        if(!is_array($requests)) $requests[0] = $requests;
        foreach($requests as $request)
        {
            $this->_relationships[$request] = $this->$request();
        }
        $this->after_get[] = 'join_temporary_results';
        return $this;
    }

    public function trigger($event, $data = array(), $last = TRUE)
    {
        if (isset($this->$event) && is_array($this->$event))
        {
            foreach ($this->$event as $method)
            {
                if (strpos($method, '('))
                {
                    preg_match('/([a-zA-Z0-9\_\-]+)(\(([a-zA-Z0-9\_\-\., ]+)\))?/', $method, $matches);
                    $method = $matches[1];
                    $this->callback_parameters = explode(',', $matches[3]);
                }
                $data = call_user_func_array(array($this, $method), array($data, $last));
            }
        }
        return $data;
    }

    public function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * protected function join_temporary_results($data)
     * Joins the subquery results to the main $data
     * @param $data
     * @return mixed
     */
    protected function join_temporary_results($data)
    {
        $data = (sizeof($data)==1) ? array([0]=>$data) : $data;
        $data = json_decode(json_encode($data), TRUE);
        foreach($this->_relationships as $relation_key=>$relation){
            $local_key_values = array();

            foreach($data as $key => $element)
            {
                $local_key_values[$key] = $element[$relation->local_key];
            }
            if($relation->relation == 'hasMany' || $relation->relation == 'hasOne') {
                $sub_results = $relation->where($relation->foreign_key, $local_key_values, 'IN');
            }
            $limit = null;
            if(isset($relation->options) && is_array($relation->options)){
                foreach($relation->options as $key=>$item){
                    if($key == 'order'){
                        $order_by = is_array($item)?$item[0]:$item;
                        $order_sort = (is_array($item) && isset($item[1]))?$item[1]:'ASC';
                        $this->orderBy($order_by, $order_sort);
                    }
                    if($key == 'where' || $key == 'like'){
                        if(is_array($item) && count($item) > 0){
                            if($this->isAssoc($item)){
                                foreach($item as $keyw => $value){
                                    $this->$key($keyw, $value);
                                }
                            }
                            elseif(count($item) == 2){
                                $this->$key($item[0],$item[1]);
                            }
                            elseif($key == 'where'){
                                $this->where($item[0]);
                            }
                        }
                        elseif($key == 'where' && !is_array($item)){
                            $this->where($item);
                        }
                    }
                    if($key == 'limit'){
                        $limit = $item;
                    }
                }
            }
            $sub_results = $sub_results->get($limit);
            foreach($sub_results as $result)
            {
                if(in_array($result[$relation->foreign_key], $local_key_values))
                {
                    $reverse_values = array_flip($local_key_values);
                    if($relation->relation=='hasOne') {
                        foreach($data as $keyd =>$item){
                            if($item[$relation->local_key] == $result[$relation->foreign_key]){
                                $data[$keyd][$relation_key] = $result;
                            }
                        }
                    }
                    else
                    {
                        $data[$reverse_values[$result[$relation->foreign_key]]][$relation_key][] = $result;
                    }
                }
            }
            unset($this->_relationships[$relation_key]);
        }
        return json_decode(json_encode($data), FALSE);
    }

    public function hasMany($model, $foreign_key = null, $local_key = null)
    {
        if(class_exists($model)) {
            $class_name = $this->className($model);
            $class = new $model($this->db);
            $foreign_key = is_null($foreign_key)?DatabaseTable::singularize($this->table).'_'.$this->primaryKey:$foreign_key;
            $local_key = is_null($local_key)?$class->primaryKey:$local_key;
            $class->foreign_key = $foreign_key;
            $class->local_key = $local_key;
            $class->relation = 'hasMany';
            return $class;
        }
    }

    public function hasOne($model, $foreign_key = null, $local_key = null)
    {
        if(class_exists($model)) {
            $class_name = $this->className($model);
            $class = new $model($this->db);
            $local_key = is_null($local_key)?DatabaseTable::singularize($class->table).'_'.$class->primaryKey:$foreign_key;
            $foreign_key = is_null($foreign_key)?$class->primaryKey:$local_key;
            $class->foreign_key = $foreign_key;
            $class->local_key = $local_key;
            $class->relation = 'hasOne';
            return $class;
        }
    }

    public function belongsTo($model, $foreign_key = null, $local_key = null)
    {

    }

    public function belongsToMany($model, $foreign_table = null, $local_key = null, $foreign_key)
    {

    }

    public function option($options = [])
    {
        $this->options = $options;
        return $this;
    }
}