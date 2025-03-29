# Jbreeze ORM

This library provides a fluent, Eloquent-style ORM for managing JSON-based data in PHP. It supports structured data validation, nested field updates, and remote JSON fetching via URL.

## Features

- Fluent API (select()->where()->update()->run())
- Schema-based validation (ensures correct data types and allowed values)
- Supports deeply nested fields (gender.other.format)
- Fetch JSON from local files or URLs (protected static $url)
- Automatic primary key handling ('primary_key' => true)
- Supports inserts, updates, deletes, filtering, ordering, and pagination

## Setup

> Initialize Registry

```php

use Registry\Registry;

Registry::initTables(__DIR__ . '/models', __DIR__ . '/tabels');

// /models - custom folder for models files
// /tabels - custom folder for json files

```

> Define a Table Model (Users.php)

Create a json file in tables/users.json

Create a file in models/Users.php:

```php

use JB_Model\JB_Model;

class Users extends JB_Model
{
    protected static $tableName = "users";

    // OR use a remote URL:
    // protected static $url = "https://example.com/users.json";

    public static $schema = [
        'id' => ['type' => 'int', 'primary_key' => true],
        'name' => ['type' => 'str', 'required' => true],
        'email' => ['type' => 'str', 'required' => true],
        'age' => ['type' => 'int'],
        'created_at' => ['type' => 'mixed', 'default' => 'NOW()']
    ];
}

```

- Defines the schema for users.json
- Schema hold the keys for quering the json file.
- If `$tableName` is missen, the table name becomes the class name, in this case that is `users` i.e `$tableName` is not complulsory if the class name matches the json file name.
- Schema enforcement is always applied, extra keys supplied during insert would throw an error enforcing structured json data.

## Usage
> Select All Users

```php

$result = Users::select()->run();

print_r($result);

```
- Returns all users from tables/users.json


> Find a User by ID

```php

$result = Users::where(['id' => 1])->run();

print_r($result);

```
- Returns the user where id = 1


> Insert a New User

```php

Users::insert([
    'name' => 'Yemi',
    'email' => 'yemi@gmail.com'
])->run();

```
- Automatically assigns an id
- Automatically fills missen fields with default value or empty string
- Throws an error if a key is not in the schema


> Update a User Field

```php

Users::where(['id' => 1])
    ->update(['email' => 'oluyemi@gmail.com'])
    ->run();

```
- Only updates email, keeping all other fields intact


> Delete a User

```php

Users::where(['id' => 1])->delete()->run();

```
- Removes the user with id = 1


> Order Users by Age

```php
$result = Users::order('age', 'ASC')->run();

print_r($result);

```
- Returns users sorted by age in ascending order


> Paginate Results (Limit)

```php

$result = Users::limit(5)->run();

print_r($result);

```
- Fetches the first 5 users


## Advanced Features

> Backup JSON Data

```php

Users::backupJsonFile();

```
- Creates backup_users.json inside tables/backup/


> Restore from Backup

```php

Users::restoreFromBackup();

```
- Restores users.json from the backup


> View Backup Contents

```php

print_r(Users::getBackup());

```
- Returns the contents of backup_users.json


> Delete Backup

```php

Users::deleteBackup();

```
- Deletes backup_users.json


> View Current Config

```php

print_r(Users::getConfig());

```
- Shows structured mode, return type, and other settings


## Handling Remote JSON Data

If $url is set inside Users, it will fetch JSON from the remote API:

```php

class Users extends JB_Model
{
    protected static $url = "https://example.com/api/users.json";
}

```
- Automatically fetches from https://example.com/api/users.json instead of users.json

## Available Schema Validations

These are all the validation rules available. Each field in a table schema can have one or more of these validations applied.

1. Type Validation (`type`)

Ensures that the value matches the expected data type.

| Type | Description | Example |
| --- | --- | --- |
| `int` | Integer (whole numbers) | `'age' => ['type' => 'int']` |
| `str` | String (text) | `'name' => ['type' => 'str']` |
| `bool` | Boolean (`true` or `false`) | `'is_active' => ['type' => 'bool']` |
| `float` / `double` | Floating-point number | `'price' => ['type' => 'float']` |
| `array` | An array | `'tags' => ['type' => 'array']` |
| `object` | An object | `'metadata' => ['type' => 'object']` |
| `mixed`	| Any type of value	| `'created_at' => ['type' => 'mixed']` |

- An error is throws if value is not the correct type.


2. Required Fields (`required`)

Marks a field as mandatory.

| Validation | Description | Example |
| --- | --- | --- |
| `required => true` | Field must be provided | `'email' => ['type' => 'str', 'required' => true]` |

- Throws an error if email is missing from the insert/update request.


3. Allowed Values (`allowed`)

Restricts the field to specific values.

| Validation | Description | Example |
| --- | --- | --- |
| `allowed` | Only specific values are allowed | `'status' => ['type' => 'str', 'allowed' => 'active']` |


- Throws an error if a value outside of `active`, `inactive`, or `pending` is provided.


4. String Length (`length`)

Limits the maximum number of characters allowed for a string.

| Validation | Description | Example |
| --- | --- | --- |
| `length` | Maximum string length | `'username' => ['type' => 'str', 'length' => 20]` |

- If the string exceeds 20 characters, it gets truncated.


5. Default Values (`default`)

Sets a default value when the field is missing.

| Validation | Description | Example |
| --- | --- | --- |
| `default` | Uses a default value if missing | `'role' => ['type' => 'str', 'default' => 'user']` |
| `default => 'NOW()'` | Inserts the current timestamp | `'created_at' => ['type' => 'mixed', 'default' => 'NOW()']` |

- If role is not provided, it defaults to "user".
- If created_at is not provided, it stores the current timestamp.


6. Primary Key (`primary_key`)

Defines the primary key for a table.

| Validation | Description | Example |
| --- | --- | --- |
| `primary_key` | Automatically increments | `'id' => ['type' => 'int', 'primary_key' => true]` |

- Auto-generates the next available ID.


7. Nested Fields Using Dot Notation

Supports deeply nested fields inside objects.

> Example:

```php 

'address.country' => ['type' => 'str'],
'address.city' => ['type' => 'str']

```

- Automatically builds nested structures when inserting/updating.


> Full Schema Example

```php

public static $schema = [
    'id' => ['type' => 'int', 'primary_key' => true],
    'name' => ['type' => 'str', 'required' => true],
    'email' => ['type' => 'str', 'required' => true],
    'age' => ['type' => 'int'],
    'status' => ['type' => 'str', 'allowed' => 'active|inactive|pending'],
    'username' => ['type' => 'str', 'length' => 15],
    'address.country' => ['type' => 'str', 'required' => true],
    'address.city' => ['type' => 'str', 'required' => true]
    'created_at' => ['type' => 'mixed', 'default' => 'NOW()']
];


```

