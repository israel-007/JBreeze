# JBreeze

## Introduction

`Jbreeze` is a lightweight PHP library designed for seamless manipulation, querying, and handling of JSON data. With an intuitive, chainable API, it provides the ability to filter, update, insert, delete, and sort data directly from JSON files or raw JSON strings.

The library is equipped with robust error handling, supports complex filtering with dot notation (for nested JSON keys), and allows you to structure your data by automatically ordering keys. Whether you're working with large datasets or performing simple operations, jbreeze makes it easy to interact with JSON in an elegant and efficient way.

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

## Table of Contents

1. [Installation](#installation)
2. [Getting Started](#getting-started)
3. [Available Methods](#available-methods)
4. [Responses](#responses)
5. [Examples](#examples)
6. [Contributing](#contributing)
7. [License](#license)

## Installation

To install the jbreeze library, you must have Composer installed on your system.

### Step 1: Install via Composer
Run the following command in your terminal to install jbreeze into your project:

```bash

composer require json/jbreeze

```

### Step 2: Autoloading
The library follows PSR-4 autoloading standards, which Composer handles automatically. After installation, ensure that your project includes the Composer autoloader:

```php

 require 'vendor/autoload.php';

```

The Jbreeze library will be ready to use within your project.

## Getting Started
After installing jbreeze, you can start using it to load, query, and manipulate JSON data. This section will walk you through the basic usage of the library.

### Loading Data
To begin, you need to load JSON data from a file or a raw JSON string using the data() method.

> Example: Loading Data from a JSON File
```php

require 'vendor/autoload.php';

use Json\jbreeze;

$jbreeze = new jbreeze();
$jbreeze->data('path/to/your/file.json');

```

> Example: Loading Data from a Raw JSON String
```php

require 'vendor/autoload.php';

use Json\jbreeze;

$jsonString = '[{"id": 1, "name": "John Doe"}, {"id": 2, "name": "Jane Smith"}]';
$jbreeze = new jbreeze();
$jbreeze->data($jsonString);

```
### Querying Data
Once the data is loaded, you can start filtering and querying it using methods like `where()`, `order()`, and `limit()`.

> Example: Filtering Data with `where()`
```php

$filteredData = $jbreeze->where(['name' => 'John Doe'])->run();
print_r($filteredData);

```

> Example: Sorting Data with `order()`
```php

$sortedData = $jbreeze->order('name', 'ASC')->run();
print_r($sortedData);

```

### Modifying Data
In addition to querying, you can insert, update, and delete records in the JSON data.

> Example: Inserting Data
```php

$newRecord = ['name' => 'Alice Cooper', 'age' => 30];
$jbreeze->insert($newRecord, 'id')->run();

```

> Example: Updating Data
```php

$jbreeze->where(['id' => 1])->update(['name' => 'Johnathan Doe'])->run();

```

> Example: Deleting Data
```php

$jbreeze->where(['id' => 2])->delete()->run();

```

## Available Methods

The jbreeze library provides various methods to manipulate and query JSON data. Each method is designed to be chainable, allowing for a fluent interface.

### `data()`
```php

$jbreeze->data(string $input)

```
Loads JSON data from a file or raw JSON string.

### `select()`
```php

$jbreeze->select(array $keys = [])

```
Selects specific keys from the dataset, returning only those fields. `$keys:` An array of keys to select from the dataset.

> Example:
```php

$selectedData = $jbreeze->select(['name', 'age'])->run();

```

### `where()`
```php

$jbreeze->where(array $conditions)

```
Filters the JSON data based on specified conditions. Supports comparison operators like `=`, `>`, `<`, `>=`, `<=`, `%` (for "like" searches), and `||` (for "or" conditions).

> Example:
```php

$filteredData = $jbreeze->where(['age' => '>25'])->run();

```

### `order()`
```php

$jbreeze->order(string $column, string $direction = 'DESC')

```
Sorts the JSON data based on a specified column in ascending or descending order. `$column:` The column to sort by. `$direction:` Sort direction, either `'ASC'` or `'DESC'`.

> Example:
```php

$sortedData = $jbreeze->order('age', 'ASC')->run();

```

### `between()`
```php

$jbreeze->between(string $key, array $range)

```
Filters data where the specified key's value falls between two values. `$key:` The key to check. `$range:` An array containing the lower and upper bounds.

> Example:
```php

$filteredData = $jbreeze->between('age', [20, 30])->run();

```

### `find()`
```php

$jbreeze->find(string $key, mixed $value)

```
Finds the first record in the dataset where the specified key matches the value. `$key:` The key to search by. `$value:` The value to match.

> Example:
```php

$record = $jbreeze->find('id', 1)->run();

```

### `insert()`
```php

$jbreeze->insert(array $newValues, string|null $primaryKey = null)

```
Inserts a new record into the dataset. `$newValues:` The associative array representing the new record. `$primaryKey:` The primary key field (optional). If provided, the library will auto-increment this key.

> Example:
```php

$jbreeze->insert(['name' => 'Alice Cooper', 'age' => 30], 'id')->run();

```

### `update()`
```php

$jbreeze->update(array $newValues)

```
Updates the existing data that matches the previously applied filters. `$newValues:` An associative array of key-value pairs representing the new values.

> Example:
```php

$jbreeze->where(['id' => 1])->update(['name' => 'Johnathan Doe'])->run();

```

### `delete()`
```php

$jbreeze->delete()

```
Deletes records that match the previously applied filters.

> Example:
```php

$jbreeze->where(['id' => 2])->delete()->run();

```

### `limit()`
```php

$jbreeze->limit(int $count)

```
Limits the number of records returned from the filtered dataset. `$count:` The maximum number of records to return.

> Example:
```php

$limitedData = $jbreeze->limit(5)->run();

```

### `run()`
```php

$jbreeze->run(string $returnType = 'json')

```

Executes the query and returns the result or performs insert, update, or delete operations. The result can be returned as either JSON or an array, depending on the $returnType. `$returnType:` Specifies the format of the result (`'json'` or `'array'`). Defaults to `'json'`. 

Returns: The filtered data in the specified format or success/failure for insert/update/delete.

> Example:
```php

$result = $jbreeze->where(['age' => '>25'])->run('array');
print_r($result);

```

#
#

## Primary Key

The `insert()` method allows you to add new records to the JSON dataset. You can specify a primary key to auto-increment, but this is optional.

Parameters:
* $newValues: An associative array containing the new record's data.
* $primaryKey: (optional) The primary key field in the dataset (e.g., `'id'`). If provided, the primary key value will be automatically incremented based on the highest existing value in the dataset.

> Example Without Primary Key
If your dataset doesn't require an auto-incremented primary key, you can omit the second parameter:

```php

    $newRecord = ['name' => 'Alice Cooper', 'age' => 30];
    $jbreeze->insert($newRecord)->run();

```

In this case, the record will be inserted as-is, without any automatic primary key generation.

> Example With Primary Key
If your dataset uses a primary key (like `'id'`), you can specify this key to auto-increment. The library will find the highest existing value for the key and increment it by 1.

```php

$newRecord = ['name' => 'Alice Cooper', 'age' => 30];
$jbreeze->insert($newRecord, 'id')->run();

```

In this example, jbreeze will:

Find the highest existing value for the 'id' field in the dataset, automatically assign the next value to the new record, and then insert the new record with this auto-generated ID.

> [!NOTE]
> Important Notes:
> * Primary Key is Optional: The second parameter, `$primaryKey`, is completely optional. If you do not pass it, no auto-incrementing will occur, and the record will be inserted as you provide it.
> * Primary Key Validation: If a primary key is provided, jbreeze will validate that the key exists in the dataset and that its values are integers. If this is not the case, an error will be thrown.

## Dot Notation

Using Dot Notation
The jbreeze library supports dot notation for accessing and filtering nested data in JSON objects. Dot notation allows you to reference deeply nested keys within your JSON data by using a dot (.) as a separator between levels of the nested structure.

This is particularly useful when dealing with complex or multi-level JSON structures where data is stored in sub-objects or arrays. With dot notation, you can easily apply filters, access values, or perform other operations without needing to manually traverse the nested structure.

### Example JSON Structure
Suppose you have the following nested JSON data:
```json

[
    {
        "id": 1,
        "name": "John Doe",
        "address": {
            "city": "New York",
            "zip": 10001
        }
    },
    {
        "id": 2,
        "name": "Jane Smith",
        "address": {
            "city": "Los Angeles",
            "zip": 90001
        }
    }
]

```

### Accessing Nested Keys with Dot Notation
To filter or access nested keys like `city` or `zip` under the `address` field, you can use dot notation. For example, to filter all records where the city is "New York," you would write:
```php

$jbreeze->data($jsonString)
    ->where(['address.city' => 'New York'])
    ->run();

```


> Example: Using Dot Notation in Filtering
```php

$filteredData = $jbreeze->where(['address.zip' => 10001])->run();

```

In this example:

* The key `address.zip` uses dot notation to refer to the `zip` field inside the nested `address` object.
* The `where()` method will filter and return records where the `zip` code is `10001`.

Example: Using Dot Notation in a `select()` Query
You can also use dot notation to select specific nested values:
```php

$selectedData = $jbreeze->select(['name', 'address.city'])->run();

```

> This will return:
```json

[
    {
        "name": "John Doe",
        "address.city": "New York"
    },
    {
        "name": "Jane Smith",
        "address.city": "Los Angeles"
    }
]

```

Combining Dot Notation with Other Methods
Dot notation can be used seamlessly with other methods, such as `order()` and `between()`.

Example: Ordering by a Nested Key
You can sort data by a nested field using dot notation:
```php

$sortedData = $jbreeze->order('address.zip', 'ASC')->run();

```
This will sort the records based on the zip code inside the address object.


## Responses

The jbreeze library provides structured responses when querying or modifying data. These responses can be returned in either JSON or array format, depending on the `run()` method's argument.

JSON File vs. Raw JSON String
Regardless of whether you're working with a JSON file or a raw JSON string, the response format and structure are the same. The difference lies only in how the data is loaded (`file path` vs. `string`). The library abstracts this difference so that all interactions (queries, modifications) behave consistently.

### Example: Successful Filter or Modification
When filtering, sorting, or modifying data, the response will contain a success message, the result, and a timestamp.

JSON File Example

```php

$jbreeze->data('path/to/file.json')->where(['age' => '>25'])->run();

```

Raw JSON String Example
```php

$jsonString = '[{"id": 1, "name": "John Doe", "age": 25}, {"id": 2, "name": "Jane Smith", "age": 30}]';
$jbreeze->data($jsonString)->where(['age' => '>25'])->run();

```

> Expected Response (JSON Format):
For both the JSON file and raw JSON string, you would get the same result:
```json

{
    "status": "success",
    "result": [
        {
            "id": 2,
            "name": "Jane Smith",
            "age": 30
        }
    ],
    "timestamp": "2024-10-04T12:34:56+00:00"
}

```

* status: Describes the status of the operation (success).
* result: Contains the filtered or modified dataset.
* timestamp: The current time, formatted in ISO 8601.

### Example: Successful Modification (Insert/Update/Delete)
If you perform an insert, update, or delete, the response will also include a success message. For instance, after inserting a new record:

> Insert Example:
```php

$newRecord = ['name' => 'Alice Cooper', 'age' => 30];
$jbreeze->insert($newRecord, 'id')->run();

```

> Expected Response (JSON Format):
```json

{
    "status": "success",
    "result": [
        {
            "id": 1,
            "name": "John Doe",
            "age": 25
        },
        {
            "id": 2,
            "name": "Jane Smith",
            "age": 30
        },
        {
            "id": 3,
            "name": "Alice Cooper",
            "age": 30
        }
    ],
    "timestamp": "2024-10-04T12:34:56+00:00"
}

```

* result: Contains the updated dataset after the insert operation.

### Example: Error Handling
When an error occurs, jbreeze will return an error response. This response contains details about the error(s) encountered, including the error code, human-readable message, and timestamp.

> Example: Error Scenario (Invalid Filter Key)
```php

$jbreeze->data($jsonString)->where(['unknown_key' => 'value'])->run();

```

> Expected Error Response (JSON Format):
```json

{
    "status": "error",
    "errors": [
        {
            "code": "KEY|NOTFOUND",
            "message": "The key 'unknown_key' was not found."
        }
    ],
    "timestamp": "2024-10-04T12:34:56+00:00"
}

```

* status: Describes the status of the operation (error).
* errors: An array of errors encountered during execution.
    * code: The error code, which can be used for debugging or handling.
    * message: A human-readable message describing the error.
* timestamp: The time the error occurred, in ISO 8601 format.

> Example: Error Scenario (File Save Error)
If there is an issue when saving to a JSON file (e.g., file permissions), you might receive an error like this:
```json

{
    "status": "error",
    "errors": [
        {
            "code": "FILE|SAVEERROR",
            "message": "Failed to save changes to the JSON file."
        }
    ],
    "timestamp": "2024-10-04T12:34:56+00:00"
}

```

## Error Codes

The library provides a structured error-handling system using predefined error codes. These error codes help you quickly identify what went wrong during the execution of your queries or modifications. Each error code is associated with a specific issue, allowing for easier debugging and error tracking.

When an error occurs, the response will include:

* code: A short, machine-readable error code.
* message: A more detailed, human-readable description of the error.

### Codes & Description

```json

{
    "JSON|INVALID": "Invalid JSON data provided.",
    "KEY|NOTFOUND": "The specified key was not found in the dataset.",
    "KEY|INVALID": "The provided key is invalid. It must pass validation.",
    "KEY|INVALIDVALUE": "Invalid value for the key. Expected an integer value.",
    "INSERT|EXTRAKEY": "Attempted to insert a record with an extra key not allowed in the dataset.",
    "DATA|EMPTY": "The dataset is empty or no matching records were found.",
    "BETWEEN|INVALIDRANGE": "Invalid range provided in the between() method. Exactly two values are required.",
    "BETWEEN|INVALIDKEY": "The specified key in the between() method was not found in the dataset.",
    "BETWEEN|NOTFOUND": "No records were found for the specified range in the between() method.",
    "ORDER|NODATA": "No data available to order using the order() method.",
    "ORDER|INVALIDCOLUMN": "The specified column for sorting does not exist in the dataset.",
    "FILE|SAVEERROR": "An error occurred while trying to save the file. Check file permissions or file system issues.",
    "QUERY|NODATAFOUND": "No data found"
}

```

## Examples
Let’s use the following deeply nested JSON structure for all examples:
```json

[
    {
        "id": 1,
        "name": "John Doe",
        "address": {
            "city": "New York",
            "zip": {
                "code": 10001,
                "area": "Queens"
            }
        }
    },
    {
        "id": 2,
        "name": "Jane Smith",
        "address": {
            "city": "Los Angeles",
            "zip": {
                "code": 90001,
                "area": "Downtown"
            }
        }
    },
    {
        "id": 3,
        "name": "Bob Johnson",
        "address": {
            "city": "New York",
            "zip": {
                "code": 10002,
                "area": "Brooklyn"
            }
        }
    }
]

```

1. Filtering Data Based on Deeply Nested Fields
Let’s filter the records based on the `area` inside the nested `address.zip` structure, selecting only those where the `area` is `"Queens"`.

> Code:
```php

require 'vendor/autoload.php';

use Json\jbreeze;

$jsonString = '[{
    "id": 1, "name": "John Doe", "address": {"city": "New York", "zip": {"code": 10001, "area": "Queens"}}
  },
  {
    "id": 2, "name": "Jane Smith", "address": {"city": "Los Angeles", "zip": {"code": 90001, "area": "Downtown"}}
  },
  {
    "id": 3, "name": "Bob Johnson", "address": {"city": "New York", "zip": {"code": 10002, "area": "Brooklyn"}}
}]';

$jbreeze = new jbreeze();
$filteredData = $jbreeze->data($jsonString)
    ->where(['address.zip.area' => 'Queens'])
    ->run();

echo $filteredData;

```

> Expected Output (JSON):
```json

[
    {
        "id": 1,
        "name": "John Doe",
        "address": {
            "city": "New York",
            "zip": {
                "code": 10001,
                "area": "Queens"
            }
        }
    }
]

```

2. Updating a Deeply Nested Field
Let’s update the `city` for all records where the `zip.code` is `10002`. We will change the city from "New York" to "Brooklyn Heights."

> Code:
```php

require 'vendor/autoload.php';

use Json\jbreeze;
    
$jsonString = '[{
    "id": 1, "name": "John Doe", "address": {"city": "New York", "zip": {"code": 10001, "area": "Queens"}}
  },
  {
    "id": 2, "name": "Jane Smith", "address": {"city": "Los Angeles", "zip": {"code": 90001, "area": "Downtown"}}
  },
  {
    "id": 3, "name": "Bob Johnson", "address": {"city": "New York", "zip": {"code": 10002, "area": "Brooklyn"}}
}]';
    
$jbreeze = new jbreeze();
$jbreeze->data($jsonString)
    ->where(['address.zip.code' => 10002])
    ->update(['address.city' => 'Brooklyn Heights'])
    ->run();

```

> Expected Output (JSON):
```json

[
    {
        "id": 1,
        "name": "John Doe",
        "address": {
            "city": "New York",
            "zip": {
                "code": 10001,
                "area": "Queens"
            }
        }
    },
    {
        "id": 2,
        "name": "Jane Smith",
        "address": {
            "city": "Los Angeles",
            "zip": {
                "code": 90001,
                "area": "Downtown"
            }
        }
    },
    {
        "id": 3,
        "name": "Bob Johnson",
        "address": {
            "city": "Brooklyn Heights",
            "zip": {
                "code": 10002,
                "area": "Brooklyn"
            }
        }
    }
]

```

3. Selecting Specific Nested Fields
In this example, we’ll select only the `name` and the `code` field inside `address.zip` from the dataset.

> Code:
```php

require 'vendor/autoload.php';

use Json\jbreeze;

$jsonString = '[{
    "id": 1, "name": "John Doe", "address": {"city": "New York", "zip": {"code": 10001, "area": "Queens"}}
  },
  {
    "id": 2, "name": "Jane Smith", "address": {"city": "Los Angeles", "zip": {"code": 90001, "area": "Downtown"}}
  },
  {
    "id": 3, "name": "Bob Johnson", "address": {"city": "New York", "zip": {"code": 10002, "area": "Brooklyn"}}
}]';

$jbreeze = new jbreeze();
$selectedData = $jbreeze->data($jsonString)
    ->select(['name', 'address.zip.code'])
    ->run();

echo $selectedData;

```

> Expected Output (JSON):
```json

[
    {
        "name": "John Doe",
        "address.zip.code": 10001
    },
    {
        "name": "Jane Smith",
        "address.zip.code": 90001
    },
    {
        "name": "Bob Johnson",
        "address.zip.code": 10002
    }
]

```

## Contributing
Contributions are welcomed to the jbreeze library! Whether you're fixing bugs, adding new features, improving documentation, or suggesting enhancements, your contributions are valuable.

Feel free to submit pull requests or open issues if you encounter any problems or have suggestions for improvement.

## Dependencies
This project does not rely on any external dependencies, making it easy to set up and use.

## License

This project is licensed under the [MIT License](LICENSE).
