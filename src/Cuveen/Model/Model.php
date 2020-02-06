<?php


namespace Cuveen\Model;


class Model
{
    protected $db;

    protected $table;
    protected $primaryKey = 'id';
    protected $timestamp = true;
    protected $pageLimit = 20;
    protected $insert_id;

    public function __construct($db)
    {
        if(is_null($this->table)){
            $this->table = DatabaseTable::pluralize(mb_strtolower(new \ReflectionClass(get_called_class()))->getShortName());
        }
        $this->db = $db;
        $this->db->objectBuilder();
    }

    public function count()
    {
        return $this->db->count;
    }

    public function getOne($columns = '*')
    {
        return $this->db->getOne($table, $columns);
    }

    public function getValue($column, $limit = 1)
    {
        return $this->db->getValue($column, $limit = 1);
    }

    public function insert($data)
    {
        $result = $this->db->insert($table, $data);
        $this->insert_id = $this->db->getInsertId();
        return $result;
    }

    public function get($numRows = null, $columns = '*')
    {
        return $this->db->get($table, $numRows, $columns);
    }

    public function paginate($page, $fields = null)
    {
        return $this->db->paginate($table, $page, $fields);
    }

    public function delete($numRows = null)
    {
        return $this->db->delete($table, $numRows);
    }

    public function update($data, $numRows = null)
    {
        return $this->db->update($table, $data, $numRows);
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