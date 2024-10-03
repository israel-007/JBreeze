<?php

namespace Json;

use jbreezeExceptions\ErrorHandler;

use Exception;

class jbreeze
{

    protected $data;          // Holds the original dataset
    protected $filteredData;  // Holds the filtered dataset
    protected $isUpdate = false; // Tracks if update was called
    protected $isDelete = false; // Tracks if delete was called
    protected $isInsert = false; // Tracks if insert was called
    protected $newValues = [];   // Stores the values for update or insert
    protected $primaryKey = null; // Stores the primary key for insert
    protected $jsonFilePath;     // Path to the JSON file (for saving)
    protected $existingKeys = []; // Stores the keys of the first dataset record
    protected $exceptions = [];  // To store all exception messages as they occur

    protected $config = []; // This configuration is sent to ErrorHandler

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    // Method to load data from a file path or raw JSON string
    public function data($input)
    {
        try {
            if (is_file($input)) {
                // If it's a file path, read the file and decode the JSON
                $this->jsonFilePath = $input;
                $jsonContent = file_get_contents($input);
                $this->data = json_decode($jsonContent, true);
            } else {
                // If it's a raw JSON string, decode it directly
                $this->data = json_decode($input, true);
            }

            if (!is_array($this->data)) {
                throw new Exception("JSON|INVALID");
            }

            // Order keys alphabetically for all existing records
            $this->data = array_map([$this, 'orderKeysAlphabetically'], $this->data);

            // Set the first record's keys as the reference for future inserts
            $this->existingKeys = array_keys(reset($this->data));
            $this->filteredData = $this->data; // Initially, filteredData is the whole data set

        } catch (Exception $e) {
            $this->logException($e->getMessage()); // Log the exception
        }

        return $this; // Enable chaining
    }

    // Chainable where clause
    public function where(array $parameters)
    {
        try {
            $this->filteredData = array_filter($this->filteredData, function ($item) use ($parameters) {
                foreach ($parameters as $key => $condition) {
                    // Use dot notation to get the nested value
                    $value = $this->getValueByDotNotation($item, $key);  // Access nested values using dot notation

                    $orConditions = array_map('trim', explode('||', $condition)); // Split by OR operator `||`
                    $matched = false;

                    foreach ($orConditions as $subCondition) {
                        // Check for comparison operators in the sub-condition
                        if (preg_match('/^([<>]=?|=|%)(.+)$/', $subCondition, $matches)) {
                            $operator = $matches[1];  // Operator like >, <, >=, etc.
                            $conditionValue = trim($matches[2]);  // Extract condition value

                            // Handle different comparison operators
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
                                case '%':  // LIKE condition
                                    if (stripos($value, $conditionValue) !== false) {
                                        $matched = true;
                                    }
                                    break;
                            }
                        } else {
                            // Default to equality comparison if no operator is present
                            if ($value == $subCondition) {
                                $matched = true;
                            }
                        }

                        // If one condition matches, stop checking
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
            $this->logException($e->getMessage());
        }

        return $this; // Enable chaining
    }

    // Chainable order clause
    public function order($column, $direction = 'DESC')
    {
        try {
            // Check if data exists
            if (empty($this->filteredData)) {
                throw new Exception("ORDER|NODATA");
            }

            // Sorting logic
            usort($this->filteredData, function ($a, $b) use ($column, $direction) {
                // Ensure the column exists in both rows
                if (!isset($a[$column]) || !isset($b[$column])) {
                    throw new Exception("ORDER|INVALIDCOLUMN: " . $column);
                }

                // Compare based on the direction
                if (strtoupper($direction) === 'ASC') {
                    return $a[$column] <=> $b[$column]; // Ascending order
                } else {
                    return $b[$column] <=> $a[$column]; // Descending order
                }
            });

        } catch (Exception $e) {
            $this->logException($e->getMessage()); // Log any exceptions
        }

        return $this; // Enable chaining
    }

    // Chaninable between clause
    public function between($key, $range = [])
    {
        try {
            // Ensure that the range contains exactly two values
            if (count($range) !== 2) {
                throw new Exception("BETWEEN|INVALIDRANGE");
            }

            // Extract the start and end of the range
            [$start, $end] = $range;

            // Filter the data to only include records where the key is between the start and end values
            $this->filteredData = array_filter($this->filteredData, function ($item) use ($key, $start, $end) {
                if (!isset($item[$key])) {
                    throw new Exception("BETWEEN|INVALIDKEY: " . $key);
                }

                return $item[$key] >= $start && $item[$key] <= $end;
            });

            if (empty($this->filteredData)) {
                throw new Exception("BETWEEN|NOTFOUND");
            }

        } catch (Exception $e) {
            $this->logException($e->getMessage()); // Log any exceptions
        }

        return $this; // Enable chaining
    }

    // Optional select clause
    public function select(array $keys = [])
    {
        try {
            if (!empty($keys)) {
                $this->filteredData = array_map(function ($item) use ($keys) {
                    $selected = [];
                    foreach ($keys as $key) {
                        $value = $this->getValueByDotNotation($item, $key);  // Access nested values
                        if ($value !== null) {
                            $selected[$key] = $value;
                        }
                    }
                    return $selected;
                }, $this->filteredData);
            }
        } catch (Exception $e) {
            $this->logException($e->getMessage());
        }

        return $this; // Enable chaining
    }

    protected function getValueByDotNotation($item, $key)
    {
        $keys = explode('.', $key);  // Split the key by dots (e.g., "town.town3" becomes ["town", "town3"])

        foreach ($keys as $innerKey) {
            if (isset($item[$innerKey])) {
                $item = $item[$innerKey];  // Drill down into the nested structure
            } else {
                return null;  // Return null if any key in the chain is not found
            }
        }

        return $item;  // Return the final value
    }

    protected function orderKeysAlphabetically($data)
    {
        // If $data is an array of records (multidimensional), apply sorting to each record
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            // Loop through each record and sort the keys
            return array_map(function ($item) {
                return $this->orderKeysAlphabetically($item);  // Recursively order each record's keys
            }, $data);
        }

        // Sort the keys of a single associative array
        ksort($data);

        // Check if the "id" key exists, and if so, move it to the front
        if (isset($data['id'])) {
            $idValue = $data['id'];
            unset($data['id']);  // Remove "id" key from its current position
            $data = ['id' => $idValue] + $data;  // Reinsert "id" at the beginning
        }

        return $data;
    }

    // Find by a specific key and value (returns first match)
    public function find($key, $value)
    {
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
                throw new Exception("KEY|NOTFOUND");
            }

        } catch (Exception $e) {
            $this->logException($e->getMessage());
        }

        return $this; // Enable chaining
    }

    // Mark data for update, actual update will be done in run()
    public function update(array $newValues)
    {
        $this->isUpdate = true;
        $this->newValues = $newValues;
        return $this; // Enable chaining
    }

    // Mark data for insertion, actual insert will be done in run()
    public function insert(array $newValues, $primaryKey = null)
    {
        try {
            $this->isInsert = true;
            $this->newValues = $newValues;
            $this->primaryKey = $primaryKey;

            // Get the last record in the dataset and use its keys as the schema reference
            $lastRecord = end($this->data);
            if (!$lastRecord) {
                throw new Exception("DATA|EMPTY"); // No data in the dataset
            }
            $existingKeys = array_keys($lastRecord);  // Keys from the last record

            // Validate the new record's keys against the last record's keys
            $this->validateNewRecordKeys($newValues, $existingKeys);

            // If a primary key is provided, validate and assign the next key
            if ($primaryKey) {
                // Validate that the primary key exists and its values are integers
                if (!$this->validatePrimaryKey($primaryKey, $existingKeys)) {
                    throw new Exception("KEY|INVALID");
                }

                // Get the next available primary key value
                $newKeyValue = $this->getNextPrimaryKeyValue($primaryKey);
                if ($newKeyValue === false) {
                    throw new Exception("KEY|INVALID");
                }

                // Add the primary key to the new values
                $this->newValues[$primaryKey] = $newKeyValue;
            }

            // Order the keys of the new record alphabetically
            $this->newValues = $this->orderKeysAlphabetically($this->newValues);

            return $this; // Enable chaining
        } catch (Exception $e) {
            $this->logException($e->getMessage()); // Log the exception
            return $this;  // Return $this even after exception to maintain chain
        }
    }

    // Mark data for deletion, actual deletion will be done in run()
    public function delete()
    {
        $this->isDelete = true;
        return $this; // Enable chaining
    }

    // Limit the number of results
    public function limit($count)
    {
        try {
            $this->filteredData = array_slice($this->filteredData, 0, $count);
        } catch (Exception $e) {
            $this->logException($e->getMessage());
        }
        return $this; // Enable chaining
    }

    // Run the query, execute insert/update/delete if needed, return result in format or true/false for operations
    public function run($returnType = 'json')
    {

        $errorHandler = new ErrorHandler($this->config);

        try {
            // Check if there are any logged exceptions
            if (!empty($this->exceptions)) {
                // Call ErrorHandler and send all exceptions

                return $errorHandler->handle($this->exceptions); // Stop execution, return null to prevent further processing
            }

            // Handle insert logic
            if ($this->isInsert) {
                $this->data[] = $this->newValues; // Add new data to the dataset
                $this->resetFlags();
                return $this->finalize(); // Save to file or return updated data
            }

            // Handle update logic
            if ($this->isUpdate) {
                if (empty($this->filteredData)) {
                    throw new Exception("UPDATE|NOTFOUND");
                }

                foreach ($this->data as &$item) {
                    foreach ($this->filteredData as $filteredItem) {
                        if ($item == $filteredItem) {
                            $item = array_merge($item, $this->newValues); // Perform the update
                        }
                    }
                }
                $this->resetFlags();
                return $this->finalize(); // Save to file or return updated data
            }

            // Handle delete logic
            if ($this->isDelete) {
                if (empty($this->filteredData)) {
                    throw new Exception("DELETE|NOTFOUND");
                }

                $originalCount = count($this->data);
                $this->data = array_filter($this->data, function ($item) {
                    return !in_array($item, $this->filteredData);
                });
                $newCount = count($this->data);

                $this->resetFlags();
                return $this->finalize($newCount < $originalCount); // Save to file or return true/false if deleted
            }

            // If no insert/update/delete, just return the filtered results

            // Prepare the response
            $response = [
                'status' => 'success',
                'result' => $this->filteredData,  // The filtered data
                'timestamp' => date('c')          // ISO 8601 formatted timestamp
            ];

            $responseRaw = [
                'status' => 'success',
                'result' => $this->data,
                'timestamp' => date('c')
            ];


            // Handle different return types
            switch ($returnType) {
                case 'array':
                    return $response;  // Return response as an array

                case 'raw':
                    // Return raw unfiltered data in 'result', keeping other response fields
                    return $responseRaw;

                case 'json':
                default:
                    // Convert the response to JSON
                    return json_encode($response, JSON_PRETTY_PRINT);
            }

        } catch (Exception $e) {
            $this->logException($e->getMessage()); // Log any last exception
            // Handle all logged exceptions
            return $errorHandler->handle($this->exceptions); // Stop execution, return null to prevent further processing
        }
    }

    // Save the updated dataset back to the JSON file if file path is provided, otherwise return data
    protected function finalize($operationResult = true)
    {
        if ($this->jsonFilePath) {
            // Order keys alphabetically for all records before saving
            $this->data = array_map([$this, 'orderKeysAlphabetically'], $this->data);
            return $this->saveToFile() ? true : false;
        } else {
            return $operationResult ? json_encode($this->data, JSON_PRETTY_PRINT) : false;
        }
    }
    
    // Save the updated dataset to the JSON file
    protected function saveToFile()
    {
        try {
            if ($this->jsonFilePath) {
                if (file_put_contents($this->jsonFilePath, json_encode($this->data, JSON_PRETTY_PRINT)) === false) {
                    throw new Exception("FILE|SAVEERROR");
                }
            }
        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return false;
        }
        return true;
    }

    // Get the highest primary key
    protected function getNextPrimaryKeyValue($primaryKey)
    {
        try {
            $maxValue = null;

            // Iterate over the dataset to find the maximum value of the primary key
            foreach ($this->data as $record) {
                if (isset($record[$primaryKey]) && is_int($record[$primaryKey])) {
                    if ($maxValue === null || $record[$primaryKey] > $maxValue) {
                        $maxValue = $record[$primaryKey];
                    }
                } else if (isset($record[$primaryKey])) {
                    throw new Exception("KEY|INVALIDVALUE"); // Non-integer value found
                }
            }

            // Return the next primary key value by incrementing the max value
            return ($maxValue !== null) ? $maxValue + 1 : 1; // If no values found, return 1

        } catch (Exception $e) {
            $this->logException($e->getMessage()); // Log the exception
            return false; // Return false if something goes wrong
        }
    }

    // Validate that the primary key exists and is an integer field
    protected function validatePrimaryKey($primaryKey, array $existingKeys)
    {
        try {
            // Check if the primary key exists in the dataset keys
            if (!in_array($primaryKey, $existingKeys)) {
                throw new Exception("KEY|NOTFOUND");
            }

            // Validate that the primary key values are integers
            foreach ($this->data as $record) {
                if (isset($record[$primaryKey]) && !is_int($record[$primaryKey])) {
                    throw new Exception("KEY|INVALID");
                }
            }

            return true; // Validation successful
        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return false; // Validation failed
        }
    }

    // Validate that new record keys match the existing dataset
    protected function validateNewRecordKeys(array $newValues, array $existingKeys)
    {
        try {
            // Extract the keys of the new record
            $newKeys = array_keys($newValues);

            // Find missing keys in the new record (keys that exist in the last record but not in the new record)
            $missingKeys = array_diff($existingKeys, $newKeys);

            // Find any extra keys (keys that exist in the new record but not in the last record)
            $extraKeys = array_diff($newKeys, $existingKeys);

            // Throw exception if there are any extra keys that don't exist in the last record
            if (!empty($extraKeys)) {
                throw new Exception("INSERT|EXTRAKEY: " . implode(', ', $extraKeys));
            }

            // Automatically add missing keys with empty values (null)
            foreach ($missingKeys as $missingKey) {
                $this->newValues[$missingKey] = null;
            }

        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return $this;  // Ensure the method returns $this even after exception
        }
    }

    // Reset the flags and filtered data
    protected function resetFlags()
    {
        $this->isUpdate = false;
        $this->isDelete = false;
        $this->isInsert = false;
        $this->primaryKey = null;
        $this->newValues = [];
        $this->filteredData = $this->data; // Reset filtered data
        $this->exceptions = []; // Reset exceptions after handling them
    }

    // Count the results
    public function count()
    {
        return count($this->filteredData);
    }

    // Log exceptions
    protected function logException($message)
    {
        $this->exceptions[] = $message; // Add the exception to the list
    }

    public static function errorslog(){

        $errorHandler = new ErrorHandler();

        return $errorHandler->GetErrorsLog();

    }
}


