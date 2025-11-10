# Users Module (Example)

An example identity module demonstrating multi-table relations (users ←→ roles), secure password handling, a simple login flow, and a protected admin UI.

What it shows
- Password hashing and verification without an ORM
- Many-to-many relations via a pivot table (user_roles)
- Minimal Admin UI (HTML) + a tiny JSON list endpoint
- CSRF on forms and login throttling middleware
- Simple role-based gate middleware (RequireAdminMiddleware)

Routes
- GET /auth/login → loginForm
- POST /auth/login → login (rate limited)
- POST /auth/logout → logout (CSRF)
- GET /admin/users → list (requires Admin)
- GET /admin/users/create → create form (Admin)
- POST /admin/users → store (Admin + CSRF)
- GET /admin/users/{id}/edit → edit (Admin)
- POST /admin/users/{id} → update (Admin + CSRF)
- POST /admin/users/{id}/delete → delete (Admin + CSRF)
- POST /admin/users/{id}/attach-role → attach (Admin + CSRF)
- POST /admin/users/{id}/detach-role → detach (Admin + CSRF)
- GET /api/v1/users → list (JSON when used for API demos)

Database
Run migrations then seed roles and an admin user:
```
php ish migrate
php ish seed --class=Modules\\Users\\Database\\Seeders\\RolesSeeder
php ish seed --class=Modules\\Users\\Database\\Seeders\\AdminUserSeeder
```
Environment overrides for seeded admin:
- ISH_ADMIN_EMAIL (default: admin@example.com)
- ISH_ADMIN_PASSWORD (default: secret123)

Security notes
- Passwords are stored using PHP's password_hash with PASSWORD_DEFAULT
- Login form uses ThrottleMiddleware to slow brute force attempts
- Forms include CSRF fields; enable VerifyCsrfToken in your middleware pipeline
- Admin UI is protected by RequireAdminMiddleware which checks the session user_id and verifies the user has the 'admin' role

Installation (via examples CLI)
```
php ish examples:list
php ish examples:install Users
php ish migrate
php ish seed --class=Modules\\Users\\Database\\Seeders\\RolesSeeder
php ish seed --class=Modules\\Users\\Database\\Seeders\\AdminUserSeeder
```
Then navigate to /auth/login and sign in with the seeded credentials.

Middleware wiring
- CSRF: use Ishmael\\Core\\Http\\Middleware\\VerifyCsrfToken globally or per-route
- Throttling: used inline in routes for POST /auth/login
- Role gate: this module ships Modules\\Users\\Middleware\\RequireAdminMiddleware which is referenced directly in the admin route group

JSON usage
- A minimal GET /api/v1/users route is included for demos; adapt the controller to return JSON as needed in your app.
