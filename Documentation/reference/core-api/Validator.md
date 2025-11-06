# Validator

Namespace: `Ishmael\Core\Validation`  
Source: `IshmaelPHP-Core\app\Core\Validation\Validator.php`

Validator provides minimal validation with common rules and i18n-ready codes.

### Public methods
- `validate(array $data, array $rules): array` — Validate given data against rules and return sanitized data or throw.
- `validateRequest(array $rules, ?Ishmael\Core\Http\Request $request = NULL): array` — Helper to validate the current request input (query overrides body).
