<?php

namespace jbreezeExceptions;

class ErrorHandler
{
    private $errorMessages = []; // Log each error message
    private $logFile; // Hold the log file name and path
    private $displayErrors; // Decide either to display the error or not
    private $environment; // Hold envirnment. Either production or development
    private $returnType; // Define what the response should return. Either array or json


    /**
     * Initializes the error handler with a configuration array.
     * 
     * @param array $config Configuration options for the error handler.
     */
    public function __construct(array $config = [])
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
     * Loads error messages from an external JSON file.
     * 
     * @param string $filePath The path to the JSON file.
     * @return array The array of error messages.
     */    
    private function loadErrorMessages(string $filePath)
    {
        if (file_exists($filePath)) {
            $jsonData = file_get_contents($filePath);
            return json_decode($jsonData, true);
        }
        return [];
    }

    /**
     * Handles one or more errors, logs them, and returns the appropriate response.
     * 
     * @param string|array $errors A single error string or an array of error strings.
     * @return mixed The response, formatted as JSON or an array based on configuration.
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
        
        $response = [
            'status' => 'error',
            'errors' => $jsonResponse,
            'timestamp' => date('c'),
        ];
        
        return $this->sendResponse($response, $error);
    }

    /**
     * Maps an error shortcode to a human-readable message.
     * 
     * @param string $error The error shortcode.
     * @return string The corresponding human-readable error message.
     */
    private function mapErrorMessage(string $error)
    {
        return isset($this->errorMessages[$error])
            ? $this->errorMessages[$error]
            : "Unknown error: $error";
    }

    /**
     * Logs the error to a file in production environments.
     * 
     * @param string $shortcode The error shortcode.
     * @param string $message The detailed error message.
     * @return void
     */
    private function logError(string $shortcode, string $message)
    {
        if ($this->environment === 'production') {
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Shortcode: $shortcode | Message: $message" . PHP_EOL;
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        }
    }

    /**
     * Sends the error response, either in JSON or array format.
     * 
     * @param array $response The error response data.
     * @param string $code The error code to include in the response.
     * @return mixed The formatted response, either as an array or JSON string.
     */
    private function sendResponse(array $Response, string $code)
    {
        // Optionally display errors in non-production environments
        if ($this->displayErrors || $this->environment !== 'production') {

            switch ($this->returnType) {
                case 'array':
                    return $Response;  // Return response as an array

                case 'json':
                default:
                    return json_encode($Response, JSON_PRETTY_PRINT); // Convert the response to JSON
            }

        } else {

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
                    return json_encode($responseTemplate, JSON_PRETTY_PRINT); // Convert the response to JSON
            }

        }
    }

    /**
     * Retrieves the error log contents and parses them into an array.
     * 
     * @return array The parsed error log entries with timestamp, code, and message.
     */
    public function GetErrorsLog()
    {
        // Check if the log file exists
        if (!file_exists($this->logFile)) {
            return [];
        }

        // Read the contents of the log file
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
