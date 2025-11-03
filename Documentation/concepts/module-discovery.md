# Module Discovery

Ishmael discovers modules under a configured Modules directory (see SkeletonApp/Modules). Each module can provide its own controllers, models, views, and routes via a `routes.php` file.

The `ModuleManager` scans module directories at boot and registers any routes it finds.
