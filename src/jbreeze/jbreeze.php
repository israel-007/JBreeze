<?php

namespace Jbreeze;

use jbreeze\Jbreeze_init;

class jbreeze extends Jbreeze_init
{
    protected $jb_instance;

    public function __construct(array $config = [])
    {
        $this->jb_instance = new jbreeze_init($config);
    }

    public function structuredData(bool $mode)
    {
        $this->jb_instance = $this->jb_instance->jb_init_dataStructure($mode);
        return $this;
    }

    public function data(string $input)
    {
        $this->jb_instance = $this->jb_instance->jb_init_data($input);
        return $this;
    }

    public function select(array $keys = [])
    {
        $this->jb_instance = $this->jb_instance->jb_init_select($keys);
        return $this;
    }

    public function where(array $parameters)
    {
        $this->jb_instance = $this->jb_instance->jb_init_where($parameters);
        return $this;
    }
    public function order(string $column, string $direction = 'DESC')
    {
        $this->jb_instance = $this->jb_instance->jb_init_order($column, $direction);
        return $this;
    }
    public function between(string $key, array $range = [])
    {
        $this->jb_instance = $this->jb_instance->jb_init_between($key, $range);
        return $this;
    }
    public function find(string $key, $value)
    {
        $this->jb_instance = $this->jb_instance->jb_init_find($key, $value);
        return $this;
    }
    public function update(array $newValues)
    {
        $this->jb_instance = $this->jb_instance->jb_init_update($newValues);
        return $this;
    }
    public function insert(array $newValues, ?string $primaryKey = null)
    {
        $this->jb_instance = $this->jb_instance->jb_init_insert($newValues, $primaryKey);
        return $this;
    }
    public function delete()
    {
        $this->jb_instance = $this->jb_instance->jb_init_delete();
        return $this;
    }
    public function count()
    {
        echo $this->jb_instance->jb_init_count();
    }
    public function limit(int $count)
    {
        $this->jb_instance = $this->jb_instance->jb_init_limit($count);
        return $this;
    }
    public function distinct(array $columns)
    {
        $this->jb_instance = $this->jb_instance->jb_init_distinct($columns);
        return $this;
    }
    public function duplicate($id){
        $this->jb_instance = $this->jb_instance->jb_init_duplicate($id);
        return $this;
    }
    public function first()
    {
        $this->jb_instance = $this->jb_instance->jb_init_first();
        return $this;
    }
    public function last()
    {
        $this->jb_instance = $this->jb_instance->jb_init_last();
        return $this;
    }
    public function errorslog()
    {
        return $this->jb_instance->jb_init_errorslog();
    }

    public function run(string $returnType = 'json')
    {
        return $this->jb_instance->jb_init_run($returnType);
    }
}
