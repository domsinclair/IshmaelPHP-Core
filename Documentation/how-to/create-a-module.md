# How to Create a Module

This guide shows the recommended way to create a new module in IshmaelPHP using the CLI tools.

## 1. Scaffolding

Instead of creating folders manually, use the `ish` CLI.

```bash
php vendor/bin/ish make:module [ModuleName]
```

This creates:
- `Modules/[ModuleName]/Controllers/`
- `Modules/[ModuleName]/Models/`
- `Modules/[ModuleName]/Views/`
- `Modules/[ModuleName]/routes.php`
- `Modules/[ModuleName]/module.json`

## 2. Defining Routes

Open `Modules/[ModuleName]/routes.php`. It returns an array mapping regex patterns to controller actions.

```php
<?php
return [
    '^$' => 'HomeController@index',
    '^view/([0-9]+)$' => 'HomeController@show',
];
```

## 3. Creating Logic (The Service Layer)

Don't put business logic in your controller. Create a Service:

```bash
php vendor/bin/ish make:service [ModuleName]Service --module=[ModuleName]
```

## 4. Creating a Controller

```bash
php vendor/bin/ish make:controller [ModuleName]Controller --module=[ModuleName]
```

## 5. Summary Checklist for AI

When an AI helps you create a module, it should:
1.  Use `make:module` first.
2.  Suggest a `Service` for any logic.
3.  Ask you about your UI preferences (Tailwind, etc.) before writing views.
