# How to Create a Module

This recipe shows how to create a new module in the SkeletonApp.

1. Create a folder under `SkeletonApp/Modules/<YourModule>` with subfolders `Controllers/`, `Models/`, `Views/`.
2. Add a `routes.php` in the module root returning an array of regex -> handler mappings.
3. Create a controller class in `Controllers/` (e.g., `HomeController.php`).
4. Visit the route in your browser to test.

Example `routes.php`:

```php
<?php
return [
    '^$' => 'HomeController@index',
];
```
