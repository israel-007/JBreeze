<?php

namespace Jbreeze;

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
        $errorHandler = new ErrorHandler($this->config); // Initialize ErrorHandler

        try {
            if (is_file($input)) {
                $this->jsonFilePath = $input;

                // Lock the file for shared access
                $fileHandle = $this->lockFile(LOCK_SH);
                if (!$fileHandle) {
                    throw new Exception("FILE|LOCKFAILED");
                }

                // Read file contents
                $jsonContent = file_get_contents($input);
                fclose($fileHandle); // Close file after reading

                // Handle empty file scenario
                if (trim($jsonContent) === '') {
                    $this->data = []; // Empty dataset
                    $this->existingKeys = []; // No keys yet
                    return $this; // Allow chaining
                }

                // Decode JSON data
                $this->data = json_decode($jsonContent, true);
            } else {
                // If it's a raw JSON string, decode it directly
                $this->data = json_decode($input, true);
            }

            // Ensure valid data format
            if (!is_array($this->data)) {
                throw new Exception("JSON|INVALID");
            }

            // If data is empty, set empty keys for future inserts
            $this->existingKeys = empty($this->data) ? [] : array_keys(reset($this->data));

            // Initially, filteredData is the full dataset
            $this->filteredData = $this->data;

        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return $errorHandler->handle($e->getMessage());
        }

        return $this; // Enable method chaining
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
        $errorHandler = new ErrorHandler($this->config); // Initialize error handler

        try {
            if (empty($this->filteredData)) {
                throw new Exception("ORDER|NODATA");
            }

            // Check if column exists in all records
            foreach ($this->filteredData as $record) {
                if (!array_key_exists($column, $record)) {
                    throw new Exception("ORDER|INVALIDCOLUMN: " . $column);
                }
            }

            // Sort the data
            usort($this->filteredData, function ($a, $b) use ($column, $direction) {
                return strtoupper($direction) === 'ASC'
                    ? $a[$column] <=> $b[$column]
                    : $b[$column] <=> $a[$column];
            });

        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return $errorHandler->handle($e->getMessage());
        }

        return $this;
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
    // public function find(string $key, $value)
    // {
    //     try {
    //         $found = false;
    //         foreach ($this->data as $item) {
    //             if (isset($item[$key]) && $item[$key] == $value) {
    //                 $this->filteredData = [$item];
    //                 $found = true;
    //                 break;
    //             }
    //         }

    //         if (!$found) {
    //             throw new Exception("QUERY|NODATAFOUND");
    //         }

    //     } catch (Exception $e) {
    //         $this->logException($e->getMessage());
    //     }

    //     return $this; // Enable chaining
    // }

    public function find(string $key, $value)
    {
        try {
            $this->filteredData = array_filter($this->data, fn($item) => isset($item[$key]) && $item[$key] === $value);

            if (empty($this->filteredData)) {
                throw new Exception("QUERY|NODATAFOUND");
            }
        } catch (Exception $e) {
            $this->logException($e->getMessage());
        }

        return $this;
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
        $errorHandler = new ErrorHandler($this->config); // Initialize error handler

        try {
            $this->isInsert = true;
            $this->newValues = $newValues;
            $this->primaryKey = $primaryKey;

            // Check if dataset is empty
            if (empty($this->data)) {
                // If a primary key is specified, ensure it exists in the new values
                if ($primaryKey && !isset($newValues[$primaryKey])) {
                    $this->newValues[$primaryKey] = 1; // Start primary key from 1
                }

                $this->data[] = $this->newValues;

                // Save immediately if a JSON file exists
                if ($this->jsonFilePath) {
                    $this->saveToFile();
                }

                return $this;
            }

            // If there are existing records, validate the keys
            $lastRecord = end($this->data);
            $existingKeys = array_keys($lastRecord);

            $this->validateNewRecordKeys($newValues, $existingKeys);

            // Assign a new primary key value if applicable
            if ($primaryKey) {
                if (!$this->validatePrimaryKey($primaryKey, $existingKeys)) {
                    throw new Exception("KEY|INVALID");
                }

                // Get the next primary key only if there is existing data
                $this->newValues[$primaryKey] = $this->getNextPrimaryKeyValue($primaryKey);
            }

            // Order keys alphabetically
            $this->newValues = $this->orderKeysAlphabetically($this->newValues);

            // Add new record to data
            $this->data[] = $this->newValues;

            // If JSON file exists, save immediately
            if ($this->jsonFilePath) {
                $this->saveToFile();
            }

            return $this;

        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return $errorHandler->handle($e->getMessage()); // Log and return error
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
                // return $this->finalize();
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

            $this->resetFlags();

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

            try {
                // Lock the file for exclusive access
                $fileHandle = $this->lockFile(LOCK_EX);
                if (!$fileHandle) {
                    throw new Exception("FILE|LOCKFAILED");
                }

                // Optionally, if you still want to order keys for saving, do it here
                // $this->data = array_map([$this, 'orderKeysAlphabetically'], $this->data);

                // Write data using the locked file handle
                $saveResult = $this->saveToFile($fileHandle);

                // Unlock the file after saving
                $this->unlockFile($fileHandle);

                return $saveResult ? true : false;

            } catch (Exception $e) {
                $this->logException($e->getMessage());
                return false;
            }

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
    protected function saveToFile($fileHandle = null)
    {
        try {
            if ($this->jsonFilePath) {
                $jsonData = json_encode($this->data, JSON_PRETTY_PRINT);
                if ($fileHandle) {
                    // Truncate the file, rewind the pointer, and write the new content
                    ftruncate($fileHandle, 0);
                    rewind($fileHandle);
                    if (fwrite($fileHandle, $jsonData) === false) {
                        throw new Exception("FILE|SAVEERROR");
                    }
                } else {
                    // Fallback if no file handle is provided
                    if (file_put_contents($this->jsonFilePath, $jsonData) === false) {
                        throw new Exception("FILE|SAVEERROR");
                    }
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
        $errorHandler = new ErrorHandler($this->config); // Initialize error handler

        try {
            // Get valid integer values only
            $validKeys = array_filter($this->data, fn($record) => isset($record[$primaryKey]) && is_int($record[$primaryKey]));

            if (empty($validKeys)) {
                return 1; // Start from 1 if no valid primary keys exist
            }

            // Get the max valid primary key value
            $nextKey = max(array_column($validKeys, $primaryKey)) + 1;

            return $nextKey;

        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return $errorHandler->handle($e->getMessage()); // Log and return error
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
                $this->newValues[$missingKey] = '';
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
        $this->exceptions = [];

        // Ensure filteredData resets back to the full dataset
        $this->filteredData = $this->data;
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

    protected function lockFile($mode, $retryInterval = 100, $timeout = 5000)
    {
        $errorHandler = new ErrorHandler($this->config); // Initialize error handler

        try {
            if (!$this->jsonFilePath) {
                throw new Exception("FILE|NOPATH");
            }

            $fileHandle = fopen($this->jsonFilePath, 'c+');
            if (!$fileHandle) {
                throw new Exception("FILE|OPENFAILED");
            }

            $startTime = microtime(true);
            while (!flock($fileHandle, $mode)) {
                usleep($retryInterval * 1000);
                if ((microtime(true) - $startTime) * 1000 >= $timeout) {
                    fclose($fileHandle);
                    throw new Exception("FILE|LOCKTIMEOUT");
                }
            }

            return $fileHandle;

        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return $errorHandler->handle($e->getMessage()); // Log and return error response
        }
    }
    protected function unlockFile($fileHandle)
    {
        if ($fileHandle) {
            flock($fileHandle, LOCK_UN);  // Release the lock
            fclose($fileHandle);         // Close the file handle
        }
    }
}


