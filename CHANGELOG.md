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

## Released `['Thursday, May 1, 2025']`

### Added

- `->min()` Returns the record with the smallest value in the specified column.
- `->max()` Returns the record with the largest value in the specified column.
- `->avg()` Calculates the average of all numeric values in the specified column.
- `->sum()` Calculates the sum of all numeric values in the specified column.
- Check the [Jbreeze ORM Readme](Jbreeze-ORM.md) for useage.

### Updated

- `delete()` The delete method was'nt saving the data back to the file in a clean and query-able way, this has been fixed `delete()` is now stable.
- `ErrorHandler` This class now allows the `handle()` method take additional error message along with the error code e.g `throw new Exception("BACKUP|INVALID - Backup file is invalid.");`

-----------------------------------------------------------------------