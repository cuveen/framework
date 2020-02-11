<?php
namespace Cuveen\Database;

use Cuveen\Config\Config;

class Migration{

    protected $table;

    protected $columns = [];

    protected $engine = 'InnoDB';

    protected $charset = 'utf8';

    protected $collation = 'utf8_unicode_ci';

    protected $dropifexist = false;

    public function create($table, $fn = false)
    {
        if(is_callable($table)){
            $fn = $table;
        }
        $config = Config::getInstance();
        $prefix = $config->get('database.connections.mysql.prefix');
        if(is_string($table) && is_null($this->table)){
            $this->table = $prefix.$table;
        }
        if(is_callable($fn)){
            call_user_func_array($fn, [$this]);
            $this->run();
        }
    }

    public function table($table)
    {
        $this->table = $table;
    }

    public function dropIfExist($table = false)
    {
        if(!$table){
            $table = $this->table;
        }
        if($table) {
            $this->table = $table;
            $query = "DROP TABLE IF EXISTS `{$table}`";
            Database::rawExecute($query);
        }
        $this->dropifexist = true;
        return $this;
    }

    private function run(){
        if(count($this->columns) > 0 && !is_null($this->table)) {
            $columns = $this->columns;
            $query = "CREATE TABLE `{$this->table}` (";
            $auto = 0;
            $i = 0;
            $unique_line = '';
            $index_line = '';
            foreach ($this->columns as $name => $config) {
                $i++;
                if ($config['type'] == 'SET') {
                    if (isset($config['length']) && !empty($config['length']) && is_array($config['length'])) {
                        $lists = $config['length'];
                        $config['length'] = '';
                        foreach ($lists as $key => $item) {
                            $config['length'] .= ($key == 0) ? "'{$item}'" : ",'{$item}'";
                        }
                    } else {
                        throw new \Exception('SET equivalent column need values');
                    }
                }
                if ($config['type'] == 'INT') {
                    $config['length'] = 11;
                }
                if ($config['type'] == 'BIGINT') {
                    $config['length'] = 20;
                }
                if ($config['type'] == 'SMALLINT') {
                    $config['length'] = 6;
                }
                if ($config['type'] == 'MEDIUMINT') {
                    $config['length'] = 9;
                }
                if ($config['type'] == 'TINYINT') {
                    $config['length'] = 4;
                }
                if ($config['type'] == 'YEAR') {
                    $config['length'] = 4;
                }
                $lengthCol = isset($config['length']) && !empty($config['length']) ? '(' . $config['length'] . ')' : '';

                if ($config['type'] == 'BLOB' || $config['type'] == 'DATE' || $config['type'] == 'TEXT' || $config['type'] == 'BOOLEAN' || $config['type'] == 'MEDIUMTEXT' || $config['type'] == 'LONGTEXT') {
                    $lengthCol = '';
                }

                if ($config['type'] == 'TIMESTAMP') {
                    $lengthCol = '';
                }
                if ($i > 1) {
                    $query .= ',';
                }
                $query .= "`{$name}` " . $config['type'] . $lengthCol;
                if (isset($config['unsigned']) && $config['unsigned'] == true) {
                    $query .= ' UNSIGNED';
                }
                if (is_null($config['default'])) {
                    $query .= " DEFAULT NULL";
                } elseif (isset($config['default']) && !empty($config['default'])) {
                    $default = ($config['default'] == 'NULL') ? $config['default'] : "'{$config['default']}'";
                    $query .= " DEFAULT " . $default;
                } else {
                    $query .= ($config['nullable']) ? ' NULL' : ' NOT NULL';
                }
                if (isset($config['auto']) && $config['auto'] == true && $auto == 0) {
                    if ($auto == 0) {
                        $query .= ' PRIMARY KEY AUTO_INCREMENT';
                        $auto++;
                    } else {
                        throw new \Exception('Incorrect table definition; there can be only one auto column and it must be defined as a key');
                    }
                }
                if (isset($config['comment']) && !empty($config['comment'])) {
                    $query .= " COMMENT '{$config['comment']}'";
                }
                if (isset($config['unique']) && $config['unique'] == true) {
                    if (empty($unique_line)) {
                        $unique_line .= 'CONSTRAINT ' . $this->table . '_unique UNIQUE (' . $name;
                    } else {
                        $unique_line = ',' . $name;
                    }
                }
                if (isset($config['index']) && $config['index'] == true) {
                    if (empty($index_line)) {
                        $index_line .= 'INDEX ' . $this->table . '_idx (' . $name;
                    } else {
                        $index_line = ',' . $name;
                    }
                }
            }
            $query .= (!empty($unique_line)) ? ',' . $unique_line . ')' : '';
            $query .= (!empty($index_line)) ? ',' . $index_line . ')' : '';
            $query .= ") ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation};";
            Database::rawExecute($query);
        }
        $this->table = null;
        $this->columns = [];
    }

    private function validColumn($column)
    {
        if(!preg_match("/^\\w+(?:\\.\\w+)?$/", $column, $matched)){
            throw new \Exception($column.' is not valid');
        }
        else{
            $this->columns[$column] = [
                'nullable' => false,
                'default' => '',
                'length' => 200,
                'type' => 'VARCHAR',
                'unsigned' => false,
                'unique' => false,
                'auto' => false,
                'comment' => false,
                'index' => false,
            ];
        }
    }

    public function index()
    {
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['index'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function bigIncrements($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'BIGINT';
        $currentColumn['auto'] = true;
        $currentColumn['unsigned'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function mediumIncrements($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'MEDIUMINT';
        $currentColumn['auto'] = true;
        $currentColumn['unsigned'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function smallIncrements($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'SMALLINT';
        $currentColumn['auto'] = true;
        $currentColumn['unsigned'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function tinyIncrements($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'TINYINT';
        $currentColumn['auto'] = true;
        $currentColumn['unsigned'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function increments($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'INT';
        $currentColumn['auto'] = true;
        $currentColumn['unsigned'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function integer($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'INT';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function unsignedInteger($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'INT';
        $currentColumn['unsigned'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function mediumInteger($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'MEDIUMINT';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function unsignedMediumInteger($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'MEDIUMINT';
        $currentColumn['unsigned'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function smallInteger($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'SMALLINT';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function unsignedSmallInteger($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'SMALLINT';
        $currentColumn['unsigned'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function tinyInteger($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'TINYINT';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function unsignedTinyInteger($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'TINYINT';
        $currentColumn['unsigned'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function unsignedBigInteger($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'BIGINT';
        $currentColumn['unsigned'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function json($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'JSON';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function lineString($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'LINESTRING';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function longText($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'LONGTEXT';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function mediumText($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'MEDIUMTEXT';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function text($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'TEXT';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function string($column, $length = 100)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'VARCHAR';
        $currentColumn['length'] = $length;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function binary($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'BLOB';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function boolean($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'BOOLEAN';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function date($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'DATE';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function dateTime($column, $digits = 0)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'DATETIME';
        $currentColumn['length'] = $digits;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function time($column, $digits = 0)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'TIME';
        $currentColumn['length'] = $digits;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function year($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'YEAR';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function decimal($column, $digits = 0, $decimal = 0)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'DECIMAL';
        $currentColumn['length'] = $digits.','.$decimal;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function unsignedDecimal($column, $digits = 0, $decimal = 0)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'DECIMAL';
        $currentColumn['unsigned'] = true;
        $currentColumn['length'] = $digits.','.$decimal;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function double($column, $digits = 0, $decimal = 0)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'DOUBLE';
        $currentColumn['length'] = $digits.','.$decimal;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function float($column, $digits = 0, $decimal = 0)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'FLOAT';
        $currentColumn['length'] = $digits.','.$decimal;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function geometry($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'GEOMETRY';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function geometryCollection($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'GEOMETRYCOLLECTION';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function default($default)
    {
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['default'] = $default;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function timestamp($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'TIMESTAMP';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function timestamps()
    {
        $this->columns['created_at']['type'] = 'TIMESTAMP';
        $this->columns['created_at']['nullable'] = true;
        $this->columns['created_at']['default'] = '';
        $this->columns['updated_at']['type'] = 'TIMESTAMP';
        $this->columns['updated_at']['nullable'] = true;
        $this->columns['updated_at']['default'] = '';
        return $this;
    }

    public function unique()
    {
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['unique'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function point($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'POINT';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function polygon($column)
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'POLYGON';
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function set($column, $arrs = [])
    {
        $this->validColumn($column);
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['type'] = 'SET';
        $currentColumn['length'] = $arrs;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function nullable()
    {
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['nullable'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function autoIncrement()
    {
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['auto'] = true;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

    public function comment($comment)
    {
        $currentColumn = end($this->columns);
        $key = key($this->columns);
        $currentColumn['comment'] = $comment;
        $this->columns[$key] = $currentColumn;
        return $this;
    }

}