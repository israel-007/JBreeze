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

    /**
     * Constructor to initialize configuration.
     * 
     * @param array $config Configuration options for the class.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Loads data from a file or raw JSON string.
     * 
     * @param string $input JSON string or path to a file.
     * @return self
     * @throws Exception If the input is invalid or JSON decoding fails.
     */
    public function data(string $input)
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

    /**
     * Filters the data based on the given parameters.
     * Supports dot notation for nested values.
     * 
     * @param array $parameters Key-value conditions to filter data.
     * @return self
     * @throws Exception If no matching data is found.
     */
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
                throw new Exception("QUERY|NODATAFOUND");
            }

        } catch (Exception $e) {
            $this->logException($e->getMessage());
        }

        return $this; // Enable chaining
    }

    /**
     * Orders the filtered data by a specified column and direction.
     * 
     * @param string $column The column to sort by.
     * @param string $direction Sort direction ('ASC' or 'DESC'). Default is 'DESC'.
     * @return self
     * @throws Exception If the column doesn't exist or if data is missing.
     */
    public function order(string $column, string $direction = 'DESC')
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

    /**
     * Filters data where a specific key's value falls within the given range.
     * 
     * @param string $key The key to apply the range filter to.
     * @param array $range An array containing two values (start and end).
     * @return self
     * @throws Exception If the range is invalid or the key is not found.
     */
    public function between(string $key, array $range = [])
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

    /**
     * Selects specific keys from the filtered data.
     * 
     * @param array $keys An array of keys to include in the result.
     * @return self
     * @throws Exception If any key is not found in the data.
     */
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

    /**
     * Retrieves a value from a nested array using dot notation.
     * 
     * @param array $item The data array to search.
     * @param string $key The dot-notated key to retrieve the value.
     * @return mixed The value at the specified key, or null if not found.
     */
    protected function getValueByDotNotation(array $item, string $key)
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

    /**
     * Orders the keys of a dataset alphabetically.
     * Moves the 'id' key to the front if it exists.
     * 
     * @param array $data The dataset to be ordered.
     * @return array The dataset with keys ordered alphabetically.
     */
    protected function orderKeysAlphabetically(array $data)
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

    /**
     * Finds and filters the dataset for a specific key-value pair.
     * 
     * @param string $key The key to search for.
     * @param mixed $value The value to match.
     * @return self
     * @throws Exception If no matching data is found.
     */
    public function find(string $key, $value)
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
                throw new Exception("QUERY|NODATAFOUND");
            }

        } catch (Exception $e) {
            $this->logException($e->getMessage());
        }

        return $this; // Enable chaining
    }

    /**
     * Marks data for update with new values.
     * Actual update happens when run() is called.
     * 
     * @param array $newValues The new values to update.
     * @return self
     */
    public function update(array $newValues)
    {
        $this->isUpdate = true;
        $this->newValues = $newValues;
        return $this; // Enable chaining
    }

    /**
     * Marks data for insertion with new values.
     * 
     * @param array $newValues The new record values.
     * @param string|null $primaryKey The primary key field to auto-increment (optional).
     * @return self
     * @throws Exception If the insert operation fails (e.g., invalid keys).
     */
    public function insert(array $newValues, ?string $primaryKey = null)
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

    /**
     * Marks the filtered data for deletion.
     * Actual deletion happens when run() is called.
     * 
     * @return self
     */
    public function delete()
    {
        $this->isDelete = true;
        return $this; // Enable chaining
    }

    /**
     * Limits the number of results returned from the filtered data.
     * 
     * @param int $count The number of results to return.
     * @return self
     */
    public function limit(int $count)
    {
        try {
            $this->filteredData = array_slice($this->filteredData, 0, $count);
        } catch (Exception $e) {
            $this->logException($e->getMessage());
        }
        return $this; // Enable chaining
    }

    /**
     * Executes the query and performs any pending insert, update, or delete operations.
     * Returns the filtered dataset in the specified format.
     * 
     * @param string $returnType The format of the return data ('json' or 'array'). Default is 'json'.
     * @return mixed The filtered dataset in the specified format.
     * @throws Exception If an error occurs during execution.
     */
    public function run(string $returnType = 'json')
    {
        $this->config['returnType'] = $returnType;
        $errorHandler = new ErrorHandler($this->config);

        try {
            if (!empty($this->exceptions)) {
                // Return an error response using ErrorHandler
                return $errorHandler->handle($this->exceptions);
            }

            if ($this->isInsert) {
                $this->data[] = $this->newValues;
                $this->resetFlags();
                return $this->finalize();
            }

            if ($this->isUpdate) {
                if (empty($this->filteredData)) {
                    throw new Exception("UPDATE|NOTFOUND");
                }

                foreach ($this->data as &$item) {
                    foreach ($this->filteredData as $filteredItem) {
                        if ($item == $filteredItem) {
                            $item = array_merge($item, $this->newValues);
                        }
                    }
                }
                $this->resetFlags();
                return $this->finalize();
            }

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
                return $this->finalize($newCount < $originalCount);
            }

            // Ensure filtered data is not empty before returning results
            if (empty($this->filteredData)) {
                throw new Exception("QUERY|NODATAFOUND");
            }

            $response = [
                'status' => 'success',
                'result' => $this->filteredData,
                'timestamp' => date('c')
            ];

            return $returnType === 'array' ? $response : json_encode($response, JSON_PRETTY_PRINT);

        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return $errorHandler->handle($this->exceptions);
        }
    }


    /**
     * Finalizes the operation by saving the updated dataset to a file or returning the result.
     * 
     * @param bool $operationResult True if the operation (insert/update/delete) succeeded.
     * @return mixed JSON encoded data or true/false based on file save success.
     */
    protected function finalize(bool $operationResult = true)
    {
        if ($this->jsonFilePath) {
            // Order keys alphabetically for all records before saving
            $this->data = array_map([$this, 'orderKeysAlphabetically'], $this->data);
            return $this->saveToFile() ? true : false;
        } else {
            return $operationResult ? json_encode($this->data, JSON_PRETTY_PRINT) : false;
        }
    }

    /**
     * Saves the current dataset to a JSON file.
     * 
     * @return bool True on success, false on failure.
     * @throws Exception If an error occurs during file saving.
     */
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

    /**
     * Retrieves the next available value for a primary key.
     * 
     * @param string $primaryKey The primary key field.
     * @return int|false The next primary key value, or false if an error occurs.
     * @throws Exception If the primary key value is invalid.
     */
    protected function getNextPrimaryKeyValue(string $primaryKey)
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

    /**
     * Validates that the primary key exists and has integer values in the dataset.
     * 
     * @param string $primaryKey The primary key field.
     * @param array $existingKeys The existing keys in the dataset.
     * @return bool True if the primary key is valid, false otherwise.
     * @throws Exception If the primary key is invalid.
     */
    protected function validatePrimaryKey(string $primaryKey, array $existingKeys)
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

    /**
     * Validates that new record keys match the existing dataset keys.
     * Automatically adds missing keys with null values.
     * 
     * @param array $newValues The new record values.
     * @param array $existingKeys The keys from the existing dataset.
     * @throws Exception If extra keys are found in the new record.
     */
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

    /**
     * Resets all flags and the filtered data after an operation.
     * 
     * @return void
     */
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

    /**
     * Returns the count of filtered results.
     * 
     * @return int The number of filtered records.
     */
    public function count()
    {
        return count($this->filteredData);
    }

    /**
     * Logs an exception message to the internal exceptions array.
     * 
     * @param string $message The exception message.
     * @return void
     */
    protected function logException(string $message)
    {
        $this->exceptions[] = $message; // Add the exception to the list
    }

    /**
     * Retrieves the error log from the ErrorHandler.
     * 
     * @return array The array of logged errors.
     */
    public static function errorslog(){

        $errorHandler = new ErrorHandler();

        return $errorHandler->GetErrorsLog();

    }
}


