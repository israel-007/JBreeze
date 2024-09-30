<?php

class JBreeze
{

    protected $data;

    protected $jsonFilePath;

    protected $existingKeys = [];

    protected $filteredData;

    protected $exceptions = [];


    public function data($data)
    {

        try {
            if (is_file($data)) {

                $this->jsonFilePath = $data;
                $jsonContent = file_get_contents($data);
                $this->data = json_decode($jsonContent, true);

            }else if(@get_headers($data)){

                $jsonContent = file_get_contents($data);
                $this->data = json_decode($jsonContent, true);

            } else {
                
                $this->data = json_decode($data, true);

            }

            if (!is_array($this->data)) {
                throw new Exception("JSON|INVALID");
            }

            $this->existingKeys = array_keys(reset($this->data));
            $this->filteredData = $this->data;

        } catch (Exception $e) {
            $this->exceptions[] = $e->getMessage();
        }

        return $this;

    }

    public function where()
    {
    }

    public function order()
    {
    }

    public function between()
    {
    }

    public function select()
    {
    }

    public function find($key, $value)
    {
        if (empty($this->data)) {
            return $this; 
        }

        try {
            $found = false;
            foreach ($this->data as $item) {
                if (isset($item[$key]) && $item[$key] == $value) {
                    $this->filteredData = [$item];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception("DATA|EMPTY");
            }

        } catch (Exception $e) {
            $this->exceptions[] = $e->getMessage();
        }

        return $this; // Enable chaining

    }

    public function update()
    {
    }

    public function insert()
    {
    }

    public function delete()
    {
    }

    public function limit()
    {
    }

    public function count()
    {
    }

    public function run()
    {

        if(!empty($this->exceptions)){
            return json_encode($this->exceptions, JSON_PRETTY_PRINT);
        }

        if(!empty($this->filteredData)){

            return json_encode($this->filteredData, JSON_PRETTY_PRINT);

        }else{

            return '[]';

        }

    }

    protected function saveToFile()
    {
    }

}




$JBreeze = new JBreeze();

