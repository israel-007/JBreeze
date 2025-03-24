# Changelog
All notable changes to this project will be documented in this file.

## [released]

### Updated ['Monday, March 24, 2025']

- **data()** - Loads JSON data (file, string, URL)
- **setStructuredMode()** - Toggles between structured and unstructured mode - true/false
- **where()** - Now works with dot notation with deeply nested json data *where(['user.id' => '1'])*.
- **select()** - Now works with dot notation with deeply nested json data *select(['name', 'email', 'user.id'])*
- **insert()** - Makes sure all ommited keys are automatically filled with empty values to maintain the Json structure
- **update()** - It also works with dot notation with deeply nested json data *update(['user.id' => 5])*


