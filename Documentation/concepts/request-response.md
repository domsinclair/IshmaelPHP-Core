# Request & Response

Ishmael will introduce small Request and Response classes to simplify working with HTTP data.

- Request: wraps method, path, query, headers, and body accessors
- Response: holds status, headers, and body content, with helpers for JSON/HTML

These abstractions make middleware and testing easier. Until then, controllers may access superglobals as needed.
