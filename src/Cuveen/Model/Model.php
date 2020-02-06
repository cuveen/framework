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

    public function __construct($db)
    {
        if(is_null($this->table)){
            $this->table = DatabaseTable::pluralize(mb_strtolower($this->className(get_called_class())));
        }
        $this->singularName = DatabaseTable::singularize($this->table);
        $this->db = $db;
        $this->db->objectBuilder();
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
        return $this->db->get($this->table, $numRows, $columns);
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
        $this->db->where($joinTable, $joinCondition, $joinType);
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
}