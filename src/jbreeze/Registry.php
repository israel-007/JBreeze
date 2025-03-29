<?php

namespace Registry;

class Registry
{
    protected static $config = [];
    protected static $tablesDir = '';

    /**
     * Initializes table classes and optional configuration.
     *
     * @param string $modelssDir Directory containing model files.
     * @param string $tablesDir Directory containing Json files.
     * @param array $config Configuration settings for JB_Model.
     */
    public static function init($modelssDir, $tablesDir, array $config = [])
    {
        // Load all table classes
        foreach (glob($modelssDir . '/*.php') as $table) {
            require_once($table);
        }

        // Store data directory
        static::$tablesDir = $tablesDir;

        // Apply config globally
        static::initConfig($config);
    }

    /**
     * Initializes global configuration for JB_Model.
     *
     * @param array $config Configuration settings.
     */
    public static function initConfig(array $config)
    {
        static::$config = $config;
    }

    /**
     * Retrieves global configuration.
     *
     * @return array Current configuration.
     */
    public static function getConfig()
    {
        return static::$config;
    }

    /**
     * Gets the full path to a JSON data file for a given table.
     *
     * @param string $table Table name.
     * @return string|null Full path to the JSON file, or null if not found.
     */
    public static function getDataPath($table)
    {
        if (!empty(static::$tablesDir)) {
            $filePath = static::$tablesDir . '/' . $table . '.json';
            return file_exists($filePath) ? $filePath : null;
        }
        return null;
    }
}
