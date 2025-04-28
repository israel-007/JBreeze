# Changelog
All notable changes to this project will be documented in this file.

## Released `['Monday, March 24, 2025']`

### Updated

- `data()` - Loads JSON data (file, string, URL)
- `setStructuredMode()` - Toggles between structured and unstructured mode - true/false
- `where()` - Now works with dot notation with deeply nested Json data `where(['user.id' => '1'])`.
- `select()` - Now works with dot notation with deeply nested Json data `select(['name', 'email', 'user.id'])`
- `insert()` - Makes sure all ommited keys are automatically filled with empty values to maintain the Json structure
- `update()` - It also works with dot notation with deeply nested Json data `update(['user.id' => 5])`

-----------------------------------------------------------------------

## Released `['Wednesday, March 26, 2025']`

### Added

- `Jbreeze ORM` Eloquent-style ORM for managing JSON-based data.
- `Registry` For managing the ORM files and runtime settings.
- `JB_Model` Inherits all methods from the jbreeze class.
- Check the [Jbreeze ORM Readme](Jbreeze-ORM.md) for useage.

### Updated

- Error messages has been extended check [readme file](README.md)

-----------------------------------------------------------------------

## Released `['Monday, April 28, 2025']`

### Added

- `->first()` Select's first item from the filltered data
- `->last()` Select's last item from the filltered data
- `->distinct([])` This returns unique values for a set columns
- `->duplicate($id)` Duplicates a record by ID and assigns a new primary key
- Check the [Jbreeze ORM Readme](Jbreeze-ORM.md) for useage.

### Updated

- NONE

-----------------------------------------------------------------------
