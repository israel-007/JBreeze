<?php

namespace JB_Model;

use Exception;
use jbreeze\jbreeze_init;
use jbreezeExceptions\ErrorHandler;
use Registry\Registry; // Import Registry class

class JB_Model extends jbreeze_init
{
    protected static $tableName;  // Table name
    protected static $schema = []; // Schema defined in child classes
    protected static $instances = []; // Store an instance per table

    protected static $url;

    /**
     * Initialize model instance (singleton per table)
     */
    protected static function getInstance()
    {
        $calledClass = get_called_class(); // E.g., Users

        // Use tableName if defined, otherwise derive from class name
        $table = static::$tableName ?? strtolower(str_replace("JB_", "", $calledClass));

        // Get JSON file path from Registry
        // Check if the class has a $url property
        $url = property_exists($calledClass, 'url') ? static::$url : null;
        $jsonFile = $url ?: Registry::getDataPath($table);

        // Ensure JSON file exists
        if (!$jsonFile || !file_exists($jsonFile) && !$url) {
            throw new Exception("DATA|FILENOTFOUND");
        }

        // Merge default config with global config from Registry
        $config = array_merge([
            'structured' => true
        ], Registry::getConfig());

        // If an instance of this table does not exist, create it
        if (!isset(static::$instances[$calledClass])) {
            $instance = new static($config);

            if ($url) {
                $instance->jb_init_data($url);
            } else {
                $instance->jb_init_data($jsonFile);
            }

            static::$instances[$calledClass] = $instance;
        }

        return static::$instances[$calledClass];
    }

    public static function structuredData(bool $mode)
    {
        return static::getInstance()->jb_init_dataStructure($mode);
    }

    /**
     * Get the current configuration for this model instance.
     *
     * @return array Current configuration settings.
     */
    public static function getConfig()
    {
        return static::getInstance()->config;
    }

    public static function select(array $columns = [])
    {
        return static::getInstance()->jb_init_select($columns);
    }

    public static function where(array $conditions)
    {
        return static::getInstance()->jb_init_where($conditions);
    }

    public static function find($id)
    {
        return static::getInstance()->jb_init_where(['id' => $id]);
    }

    public static function between(string $column, array $range)
    {
        return static::getInstance()->jb_init_between($column, $range);
    }

    public static function insert(array $newValues, ?string $primaryKey = null)
    {
        return static::getInstance()->schemaInsert($newValues, $primaryKey);
    }

    public static function update(array $newValues)
    {
        return static::getInstance()->jb_init_update($newValues);
    }

    public static function delete()
    {
        return static::getInstance()->jb_init_delete();
    }

    public static function limit(int $count)
    {
        return static::getInstance()->jb_init_limit($count);
    }

    public static function count()
    {
        return static::getInstance()->jb_init_count();
    }

    public static function first()
    {
        return static::getInstance()->jb_init_first();
    }

    public static function last()
    {
        return static::getInstance()->jb_init_last();
    }

    public static function duplicate($id)
    {
        return static::getInstance()->jb_init_duplicate($id);
    }

    public static function distinct($columns)
    {
        return static::getInstance()->jb_init_distinct($columns);
    }

    public static function min($column)
    {
        return static::getInstance()->jb_init_min($column);
    }

    public static function max($column)
    {
        return static::getInstance()->jb_init_max($column);
    }

    public static function avg($column)
    {
        return static::getInstance()->jb_init_avg($column);
    }

    public static function sum($column)
    {
        return static::getInstance()->jb_init_sum($column);
    }

    public static function run(string $returnType = 'json')
    {
        $instance = static::getInstance();
        $errorHandler = new ErrorHandler($instance->config);

        // Check if there are any logged exceptions
        if (!empty($instance->exceptions)) {
            return $errorHandler->handle($instance->exceptions);
        }

        return $instance->jb_init_run($returnType);
    }

    /**
     * Adjusts the JSON structure based on the latest schema.
     * - Creates a backup before modifying the file.
     * - Adds missing fields with default values.
     * - Removes fields that no longer exist in the schema.
     * - Renames multiple fields using a mapping array.
     *
     * @param array $renameFields Associative array of old field names to new field names.
     */
    public static function updateStructure(array $renameFields = [])
    {
        try {
            $instance = static::getInstance(); // Model instance

            if (empty(static::$schema)) {
                throw new Exception("SCHEMA|MISSING");
            }

            // Ensure data is loaded
            if (empty($instance->data)) {
                throw new Exception("DATA|EMPTY");
            }

            // Perform backup before making changes
            $instance->backupJsonFile();

            $updatedData = [];
            $schemaFields = array_keys(static::$schema); // List of fields in schema

            // Function to remove extra fields from nested arrays
            function removeExtraFields(&$record, $schemaFields, $parentKey = '')
            {
                foreach ($record as $key => &$value) {
                    $fullKey = $parentKey ? "$parentKey.$key" : $key;

                    // If key does NOT exist in the schema, remove it
                    if (!keyExistsInSchema($fullKey, $schemaFields)) {
                        unset($record[$key]);
                        continue;
                    }

                    // If value is an array, go deeper
                    if (is_array($value)) {
                        removeExtraFields($value, $schemaFields, $fullKey);

                        // If an empty array remains, remove it
                        if (empty($value)) {
                            unset($record[$key]);
                        }
                    }
                }
            }

            // Function to check if a key (including nested) exists in the schema
            function keyExistsInSchema($key, $schemaFields)
            {
                if (in_array($key, $schemaFields)) {
                    return true; // Match found
                }
                foreach ($schemaFields as $schemaKey) {
                    if (strpos($schemaKey, '.') !== false && str_starts_with($schemaKey, $key . '.')) {
                        return true; // Key is a valid parent of a nested schema key
                    }
                }
                return false;
            }

            // Function to rename nested keys using dot notation
            function renameNestedFields(&$record, $renameFields)
            {
                foreach ($renameFields as $oldKey => $newKey) {
                    $oldKeyParts = explode('.', $oldKey);
                    $newKeyParts = explode('.', $newKey);

                    if (count($oldKeyParts) > 1) { // Handle nested fields
                        $temp = &$record;
                        $found = true;

                        // Traverse into the nested array using reference
                        foreach ($oldKeyParts as $part) {
                            if (!isset($temp[$part]) || !is_array($temp)) {
                                $found = false;
                                break;
                            }
                            $temp = &$temp[$part];
                        }

                        // If key exists, rename it
                        if ($found) {
                            // Remove old key
                            unsetNestedKey($record, $oldKeyParts);

                            // Set new key
                            setNestedKey($record, $newKeyParts, $temp);
                        }
                    } elseif (isset($record[$oldKey])) { // Non-nested field
                        $record[$newKey] = $record[$oldKey];
                        unset($record[$oldKey]);
                    }
                }
            }

            // Function to delete a nested key
            function unsetNestedKey(&$record, $keyParts)
            {
                $temp = &$record;
                while (count($keyParts) > 1) {
                    $key = array_shift($keyParts);
                    if (!isset($temp[$key]) || !is_array($temp[$key])) {
                        return;
                    }
                    $temp = &$temp[$key];
                }
                unset($temp[array_shift($keyParts)]);
            }

            // Function to set a nested key
            function setNestedKey(&$record, $keyParts, $value)
            {
                $temp = &$record;
                foreach ($keyParts as $key) {
                    if (!isset($temp[$key]) || !is_array($temp[$key])) {
                        $temp[$key] = [];
                    }
                    $temp = &$temp[$key];
                }
                $temp = $value;
            }

            // Process each record individually
            foreach ($instance->data as $record) {
                // Apply renaming to this record before modifying structure
                renameNestedFields($record, $renameFields);

                // Ensure schema conformity (add missing fields)
                foreach (static::$schema as $field => $rules) {
                    if (!is_array($rules)) {
                        $rules = ['type' => $rules];
                    }

                    // Determine default value
                    $defaultValue = $rules['default'] ?? $instance->getDefaultEmptyValue($rules['type']);
                    if ($defaultValue === 'NOW()') {
                        $defaultValue = date('Y-m-d H:i:s');
                    }

                    // Process nested fields using dot notation
                    if (strpos($field, '.') !== false) {
                        $keys = explode('.', $field);
                        $temp = &$record;

                        foreach ($keys as $index => $key) {
                            if ($index === count($keys) - 1) {
                                if (!isset($temp[$key]) || is_array($temp[$key])) {
                                    $temp[$key] = $defaultValue;
                                }
                            } else {
                                if (!isset($temp[$key]) || !is_array($temp[$key])) {
                                    $temp[$key] = [];
                                }
                                $temp = &$temp[$key];
                            }
                        }
                    } else {
                        // If field is missing, add it with the default value
                        if (!array_key_exists($field, $record)) {
                            $record[$field] = $defaultValue;
                        }
                    }
                }

                // Remove extra fields
                removeExtraFields($record, $schemaFields);

                // Maintain consistent key order
                ksort($record);

                // Append updated record
                $updatedData[] = $record;
            }

            // Save updated data back to JSON file
            $instance->data = $updatedData;
            $instance->jb_saveToFile();

            return true;

        } catch (Exception $e) {
            $instance->logException($e->getMessage());
            return (new ErrorHandler($instance->config))->handle($e->getMessage());
        }
    }

    /**
     * Creates a backup of the current JSON file before updating the structure.
     * Can be called directly using Users::backupJsonFile().
     */
    public static function backupJsonFile()
    {
        try {
            $instance = static::getInstance();

            if (!$instance->jsonFilePath) {
                throw new Exception("BACKUP|NOFILE");
            }

            // Get the directory and filename
            $jsonDir = dirname($instance->jsonFilePath);
            $jsonFilename = basename($instance->jsonFilePath);

            // Define backup folder path
            $backupFolder = $jsonDir . '/backup';

            // Ensure backup folder exists
            if (!is_dir($backupFolder)) {
                mkdir($backupFolder, 0777, true);
            }

            // Define backup file path
            $backupFilePath = $backupFolder . '/backup_' . $jsonFilename;

            // Copy the original JSON file to the backup file
            if (!copy($instance->jsonFilePath, $backupFilePath)) {
                throw new Exception("BACKUP|FAILED");
            }

            // Log the backup operation
            $instance->logBackupOperation("Backup created: {$backupFilePath}");

            // return "BACKUP|SUCCESS: Backup created at {$backupFilePath}";

            return true;

        } catch (Exception $e) {
            $instance->logBackupOperation("Backup failed: " . $e->getMessage());
            $instance->logException($e->getMessage());
            return (new ErrorHandler($instance->config))->handle($e->getMessage());
        }
    }

    /**
     * Retrieves the contents of the backup file.
     *
     * @param string $returnType Specifies the format ('array' or 'json'). Default is 'json'.
     * @return mixed The backup data in the specified format, or an error message if the backup is missing.
     */
    public static function getBackup(string $returnType = 'json')
    {
        try {
            $instance = static::getInstance();

            if (!$instance->jsonFilePath) {
                throw new Exception("BACKUP|NOFILE");
            }

            // Get the directory and filename
            $jsonDir = dirname($instance->jsonFilePath);
            $jsonFilename = basename($instance->jsonFilePath);

            // Define backup file path
            $backupFilePath = $jsonDir . '/backup/backup_' . $jsonFilename;

            // Ensure backup file exists
            if (!file_exists($backupFilePath)) {
                throw new Exception("BACKUP|NOBACKUP");
            }

            // Read backup file contents
            $backupData = file_get_contents($backupFilePath);
            $decodedData = json_decode($backupData, true);

            if (!is_array($decodedData)) {
                throw new Exception("BACKUP|INVALID");
            }

            // Log retrieval action
            $instance->logBackupOperation("Backup retrieved: {$backupFilePath}");

            // Return data in requested format
            return $returnType === 'array' ? $decodedData : json_encode($decodedData, JSON_PRETTY_PRINT);

        } catch (Exception $e) {
            $instance->logBackupOperation("Backup retrieval failed: " . $e->getMessage());
            $instance->logException($e->getMessage());
            return (new ErrorHandler($instance->config))->handle($e->getMessage());
        }
    }

    /**
     * Deletes the backup file.
     *
     * @return string Success message or error message if backup does not exist.
     */
    public static function deleteBackup()
    {
        try {
            $instance = static::getInstance();

            if (!$instance->jsonFilePath) {
                throw new Exception("BACKUP|NOFILE");
            }

            // Get the directory and filename
            $jsonDir = dirname($instance->jsonFilePath);
            $jsonFilename = basename($instance->jsonFilePath);

            // Define backup file path
            $backupFilePath = $jsonDir . '/backup/backup_' . $jsonFilename;

            // Ensure backup file exists before deleting
            if (!file_exists($backupFilePath)) {
                throw new Exception("BACKUP|NOBACKUP");
            }

            // Attempt to delete the backup file
            if (!unlink($backupFilePath)) {
                throw new Exception("BACKUP|DELETEFAILED");
            }

            // Log the deletion action
            $instance->logBackupOperation("Backup deleted: {$backupFilePath}");

            // return "BACKUP|DELETED: Backup file successfully deleted.";

            return true;

        } catch (Exception $e) {
            $instance->logBackupOperation("Backup deletion failed: " . $e->getMessage());
            $instance->logException($e->getMessage());
            return (new ErrorHandler($instance->config))->handle($e->getMessage());
        }
    }

    /**
     * Restores the JSON file from the latest backup.
     * Can be called using Users::restoreFromBackup().
     */
    public static function restoreFromBackup()
    {
        try {
            $instance = static::getInstance();

            if (!$instance->jsonFilePath) {
                throw new Exception("RESTORE|NOFILE");
            }

            // Get the directory and filename
            $jsonDir = dirname($instance->jsonFilePath);
            $jsonFilename = basename($instance->jsonFilePath);

            // Define backup file path
            $backupFilePath = $jsonDir . '/backup/backup_' . $jsonFilename;

            // Ensure backup file exists
            if (!file_exists($backupFilePath)) {
                throw new Exception("RESTORE|NOBACKUP");
            }

            // Restore the backup by copying it over the original file
            if (!copy($backupFilePath, $instance->jsonFilePath)) {
                throw new Exception("RESTORE|FAILED");
            }

            // Log the restore operation
            $instance->logBackupOperation("Restore successful: {$backupFilePath} → {$instance->jsonFilePath}");

            // return "RESTORE|SUCCESS: Restored {$jsonFilename} from backup.";

            return true;

        } catch (Exception $e) {
            $instance->logBackupOperation("Restore failed: " . $e->getMessage());
            $instance->logException($e->getMessage());
            return (new ErrorHandler($instance->config))->handle($e->getMessage());
        }
    }

    /**
     * Logs backup and restore operations in a separate file.
     */
    protected function logBackupOperation($message)
    {
        try {
            // Get the directory and filename
            $jsonDir = dirname($this->jsonFilePath);
            $jsonFilename = basename($this->jsonFilePath, '.json');

            // Define backup log folder path
            $logFolder = $jsonDir . '/backupOperations';

            // Ensure log folder exists
            if (!is_dir($logFolder)) {
                mkdir($logFolder, 0777, true);
            }

            // Define log file path
            $logFilePath = $logFolder . '/' . $jsonFilename . '.log';

            // Append log entry
            $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
            file_put_contents($logFilePath, $logMessage, FILE_APPEND);

        } catch (Exception $e) {
            $this->logException("LOGGING|FAILED");
        }
    }

    // --------------------------------------------------------------------------------------------------------------------------------


    /**
     * Validates data against schema before inserting
     */
    protected function validateData(array &$data)
    {
        if (empty(static::$schema)) {
            return 'success'; // No schema, no validation
        }

        $errorHandler = new ErrorHandler($this->config);
        $errors = []; // Store all validation errors

        try {

            // Ensure all provided keys exist in the schema
            $schemaKeys = array_keys(static::$schema);
            foreach ($data as $key => $value) {
                if (!in_array($key, $schemaKeys)) {
                    $errors[] = "INSERT|EXTRAKEY";
                }
            }

            foreach (static::$schema as $key => $rules) {
                if (!is_array($rules)) {
                    $rules = ['type' => $rules];
                }

                // Process schema rules
                $type = $rules['type'] ?? 'str';
                $required = $rules['required'] ?? false;
                $isPrimaryKey = $rules['primary_key'] ?? false;
                $default = $rules['default'] ?? null;
                $allowedValues = isset($rules['allowed']) ? explode('|', $rules['allowed']) : [];
                $maxLength = isset($rules['length']) ? (int) $rules['length'] : null;

                // Normalize type names
                if ($type === 'integer')
                    $type = 'int';
                if ($type === 'string')
                    $type = 'str';
                if ($type === 'mixed')
                    $type = 'mixed'; // Mixed allows any type

                // Handle primary key auto-increment
                if ($isPrimaryKey && !isset($data[$key])) {
                    $data[$key] = $this->jb_getNextPrimaryKeyValue($key);
                    continue;
                }

                // If field is required and missing, log an error
                if ($required && !isset($data[$key])) {
                    $errors[] = "VALIDATION|MISSING_FIELD";
                    continue;
                }

                // Process default values
                if (!isset($data[$key]) && $default !== null) {
                    if ($default === 'NOW()') {
                        $data[$key] = date('Y-m-d H:i:s');
                    } else {
                        $data[$key] = $default;
                    }
                }

                // If field is missing but NOT required and no default, set empty value
                if (!isset($data[$key])) {
                    $data[$key] = $this->getDefaultEmptyValue($type);
                }

                // Ensure correct data type (if not "mixed")
                if ($type !== 'mixed' && gettype($data[$key]) !== $this->convertTypeToPHP($type)) {
                    $errors[] = "VALIDATION|TYPE_MISMATCH";
                }

                // Validate allowed values if specified
                if (!empty($allowedValues) && !in_array($data[$key], $allowedValues, true)) {
                    $errors[] = "VALIDATION|INVALID_VALUE";
                }

                // Enforce max length on string values
                if ($type === 'str' && $maxLength !== null && mb_strlen($data[$key]) > $maxLength) {
                    $data[$key] = mb_substr($data[$key], 0, $maxLength); // Truncate to max length
                }
            }

            // If errors exist, log and return them
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->logException($error);
                }
                return $errorHandler->handle($errors);
            }

            return 'success';

        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return $errorHandler->handle($e->getMessage());
        }
    }
    protected function convertTypeToPHP(string $type)
    {
        $typeMap = [
            'int' => 'integer',
            'str' => 'string',
            'bool' => 'boolean',
            'float' => 'double',
            'mixed' => 'mixed'
        ];
        return $typeMap[$type] ?? 'string'; // Default to string
    }

    /**
     * Override insert() to apply schema validation
     */
    public function schemaInsert(array $newValues, ?string $primaryKey = null)
    {
        try {

            $validate = $this->validateData($newValues);

            if ($validate == 'success') {

                return parent::jb_init_insert($newValues, $primaryKey);

            }

            return $this;

        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return (new ErrorHandler($this->config))->handle($e->getMessage());
        }
    }

    /**
     * Returns a default empty value based on the expected type.
     */
    protected function getDefaultEmptyValue(string $type)
    {
        switch ($type) {
            case 'integer':
                return 0;
            case 'double':
                return 0.0;
            case 'boolean':
                return false;
            case 'array':
                return [];
            case 'object':
                return (object) [];
            default:
                return ''; // Default empty string
        }
    }
}
