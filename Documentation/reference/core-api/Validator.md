# Validator

- FQCN: `Ishmael\Core\Validation\Validator`
- Type: class

## Public Methods

- `validate(array $data, array $rules)`
- `validateRequest(array $rules, Ishmael\Core\Http\Request $request)`

## Supported Rules

- `required`: Field must be present and not empty.
- `string`: Must be a string.
- `int`: Must be an integer.
- `email`: Must be a valid email address.
- `min:n`: Minimum value (int) or length (string).
- `max:n`: Maximum value (int) or length (string). For files, this is size in Kilobytes.
- `in:a,b,c`: Must be one of the listed values.
- `regex:pattern`: Must match the regular expression.
- `file`: Must be a valid `UploadedFile`.
- `image`: Must be an image (jpg, png, gif, webp).
- `mimes:jpg,png`: Must have one of the specified extensions.
- `dimensions:min_width=100,min_height=100`: Validates image dimensions.
