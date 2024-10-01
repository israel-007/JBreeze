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

            }else {

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

    public function where(array $parameters)
    {
        try {
            $this->filteredData = array_filter($this->filteredData, function ($item) use ($parameters) {
                foreach ($parameters as $key => $condition) {
                    
                    $value = $key;

                    $orConditions = array_map('trim', explode('||', $condition));
                    $matched = false;

                    foreach ($orConditions as $subCondition) {
                        
                        if (preg_match('/^([<>]=?|=|%)(.+)$/', $subCondition, $matches)) {
                            $operator = $matches[1];
                            $conditionValue = trim($matches[2]);

                            switch ($operator) {
                                case '>':
                                    if ($value > $conditionValue) {
                                        $matched = true;
                                    }
                                    break;
                                case '<':
                                    if ($value < $conditionValue) {
                                        $matched = true;
                                    }
                                    break;
                                case '>=':
                                    if ($value >= $conditionValue) {
                                        $matched = true;
                                    }
                                    break;
                                case '<=':
                                    if ($value <= $conditionValue) {
                                        $matched = true;
                                    }
                                    break;
                                case '=':
                                    if ($value == $conditionValue) {
                                        $matched = true;
                                    }
                                    break;
                                case '%':
                                    if (stripos($value, $conditionValue) !== false) {
                                        $matched = true;
                                    }
                                    break;
                            }
                        } else {
                            
                            if ($value == $subCondition) {
                                $matched = true;
                            }
                        }
                        
                        if ($matched) {
                            break;
                        }
                    }

                    if (!$matched) {
                        return false;
                    }
                }
                return true;
            });

            if (empty($this->filteredData)) {
                throw new Exception("KEY|NOTFOUND");
            }

        } catch (Exception $e) {
            $this->exceptions[] = $e->getMessage();
        }

        return $this;
    }

    public function order()
    {
    }

    public function between()
    {
    }

    public function select(array $keys = [])
    {
        try {
            if (!empty($keys)) {
                $this->filteredData = array_map(function ($item) use ($keys) {
                    $selected = [];
                    foreach ($keys as $key) {
                        $value = $key;
                        if ($value !== null) {
                            $selected[$key] = $value;
                        }
                    }
                    return $selected;
                }, $this->filteredData);
            }
        } catch (Exception $e) {
            $this->exceptions[] = $e->getMessage();
        }

        return $this;
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

        return $this;

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

