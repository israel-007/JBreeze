<?php

namespace jbreezeExceptions;

class ErrorHandler
{
    private $errorMessages = [];
    private $logFile;
    private $displayErrors;
    private $environment;
    private $returnType;

    /**
     * Constructor to initialize the error handler with a configuration array.
     * @param array $config Configuration options (log_file, display_errors, environment).
     */
    public function __construct($config = [])
    {
        // Load external error messages from JSON file
        $this->errorMessages = $this->loadErrorMessages(__DIR__ . '/../log/error_messages.json');
        // Set configurations with defaults
        $this->logFile = $config['log_file'] ?? __DIR__ . '/../log/error_log.txt';
        $this->displayErrors = $config['display_errors'] ?? false;
        $this->environment = $config['environment'] ?? 'production';
        $this->returnType = $config['returnType'];
    }

    /**
     * Load error messages from an external JSON file.
     * @param string $filePath Path to the error messages JSON file.
     * @return array Array of error messages.
     */
    private function loadErrorMessages($filePath)
    {
        if (file_exists($filePath)) {
            $jsonData = file_get_contents($filePath);
            return json_decode($jsonData, true);
        }
        return [];
    }

    /**
     * Handles error(s) passed in as a string or array.
     * @param string|array $errors The error(s) to handle.
     */
    public function handle($errors)
    {
        // Convert to array if a single error is passed
        if (!is_array($errors)) {
            $errors = [$errors];
        }

        $jsonResponse = [];

        // Loop through each error and process it
        foreach ($errors as $error) {
            // Map the shortcode to a human-readable message
            $message = $this->mapErrorMessage($error);
            // Log the error (with shortcode)
            $this->logError($error, $message);
            // Add the shortcode and message to the JSON response
            $jsonResponse[] = [
                'code' => $error,
                'message' => $message
            ];
        }

        // Include metadata (status, timestamp)
        $response = [
            'status' => 'error',
            'errors' => $jsonResponse,
            'timestamp' => date('c'), // ISO 8601 timestamp
        ];

        // Send the response as JSON
        return $this->sendJsonResponse($response, $error);
    }

    /**
     * Maps the error shortcode to a detailed human-readable message.
     * @param string $error The error shortcode.
     * @return string The human-readable error message.
     */
    private function mapErrorMessage($error)
    {
        // Return the mapped error message or a default message if not found
        return isset($this->errorMessages[$error])
            ? $this->errorMessages[$error]
            : "Unknown error: $error";
    }

    /**
     * Logs the error to the error log file, including the shortcode and message.
     * @param string $shortcode The error shortcode.
     * @param string $message The error message.
     */
    private function logError($shortcode, $message)
    {
        if ($this->environment === 'production') {
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Shortcode: $shortcode | Message: $message" . PHP_EOL;
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        }
    }

    /**
     * Sends the error messages as a JSON response.
     * @param array $jsonResponse The structured JSON response with metadata.
     */
    private function sendJsonResponse($jsonResponse, $code)
    {
        // header('Content-Type: application/json');

        // Optionally display errors in non-production environments
        if ($this->displayErrors || $this->environment !== 'production') {

            echo json_encode($jsonResponse, JSON_PRETTY_PRINT);

        } else {

            // In production, we can hide detailed errors
            $responseTemplate =
            [
                'status' => 'error',
                'code' => $code,
                'message' => 'An error occurred. Please contact support.',
                'timestamp' => date('c')
            ];

            switch ($this->returnType) {
                case 'array':
                    return $responseTemplate;  // Return response as an array

                case 'json':
                default:
                    // Convert the response to JSON
                    return json_encode($responseTemplate, JSON_PRETTY_PRINT);
            }

            
        }
    }

    public function GetErrorsLog()
    {
        // Check if the log file exists
        if (!file_exists($this->logFile)) {
            return [];
        }

        // Read the entire contents of the log file
        $logContents = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Split each log line into an array
        $logsArray = [];
        foreach ($logContents as $line) {
            // Assuming log format: "[timestamp] Shortcode: CODE | Message: MESSAGE"
            preg_match('/^\[(.*?)\] Shortcode: (.*?) \| Message: (.*)$/', $line, $matches);
            if (count($matches) === 4) {
                $logsArray[] = [
                    'code' => $matches[2],
                    'message' => $matches[3],
                    'timestamp' => $matches[1]
                ];
            }
        }

        return $logsArray;
    }

}
