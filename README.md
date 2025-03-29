# JBreeze

Jbreeze is a lightweight PHP library designed for seamless manipulation, querying, and handling of JSON data. With an intuitive, chainable API, it provides the ability to filter, update, insert, delete, and sort data directly from JSON files or raw JSON strings.

The library is equipped with robust error handling, supports complex filtering with dot notation (for nested JSON keys), and allows you to structure your data by automatically ordering keys. Whether you're working with large datasets or performing simple operations, jbreeze makes it easy to interact with JSON in an elegant and efficient way.

#

## Key Features:

* Chainable API for fluent interaction with JSON data
* Supports filtering with operators (>, <, =, %, etc.) and OR conditions (||)
* Advanced error handling and logging with customizable error messages
* Sorting and limiting results
* Insert, update, and delete functionality for JSON data
* Ability to handle both file-based and raw JSON string inputs
* Supports nested key access using dot notation
* Lightweight and simple to integrate into any PHP project
* jbreeze is designed to bring the convenience of SQL-like operations to your JSON data, making it easier to manage complex datasets without the need for a full database system.

#

## Installation

To install the jbreeze library, you must have Composer installed on your system.

### Step 1: Install via Composer
Run the following command in your terminal to install jbreeze into your project:

```bash

composer require jbreeze/jbreeze

```

### Step 2: Autoloading
The library follows PSR-4 autoloading standards, which Composer handles automatically. After installation, ensure that your project includes the Composer autoloader:

```php

 require 'vendor/autoload.php';

```

The Jbreeze library will be ready to use within your project.

#

## Getting Started
After installing jbreeze, you can start using it to load, query, and manipulate JSON data.

## Examples or Useage
Please visit [Intoduction Section](intro.md) for examples on how to use the library.

## Contributing
Contributions are welcomed to the jbreeze library! Whether you're fixing bugs, adding new features, improving documentation, or suggesting enhancements, your contributions are valuable.

Feel free to submit pull requests or open issues if you encounter any problems or have suggestions for improvement.

## Dependencies
This project does not rely on any external dependencies, making it easy to set up and use.

## License

This project is licensed under the [MIT License](LICENSE).
