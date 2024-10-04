<?php

namespace jbreezeExceptions;

class ErrorHandler
{
    private $errorMessages = []; // Log each error message
    private $logFile; // Hold the log file name and path
    private $displayErrors; // Decide either to display the error or not
    private $environment; // Hold envirnment. Either production or development
    private $returnType; // Define what the response should return. Either array or json

   
    //  Constructor to initialize the error handler with a configuration array.
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

    // Load error messages from an external JSON file.
    private function loadErrorMessages($filePath)
    {
        if (file_exists($filePath)) {
            $jsonData = file_get_contents($filePath);
            return json_decode($jsonData, true);
        }
        return [];
    }

    // Handles error(s) passed in as a string or array.
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

    // Maps the error shortcode to a detailed human-readable message.
    private function mapErrorMessage($error)
    {
        return isset($this->errorMessages[$error])
            ? $this->errorMessages[$error]
            : "Unknown error: $error";
    }

    // Logs the error to the error log file, including the shortcode and message.
    private function logError($shortcode, $message)
    {
        if ($this->environment === 'production') {
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Shortcode: $shortcode | Message: $message" . PHP_EOL;
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        }
    }

    // Sends the error messages.
    private function sendResponse($Response, $code)
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
