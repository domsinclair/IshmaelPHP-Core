# Ishmael PHP

![](ishmael.jpg)

Ishmael PHP is a lightweight, modular micro-framework for building HTTP-centric PHP applications. It emphasises an explicit bootstrap process, a clean routing layer, a PSR-style middleware pipeline, and pragmatic, context-rich logging — all with a small, readable core that is easy to extend and test.

Highlights:
- Modular design with a simple application skeleton and discoverable modules.
- Request/Response flow inspired by PSR-7/PSR-15 with middleware-first composition.
- Sensible, file-based configuration with safe defaults.
- First-class logging (Monolog) including processors and per-request IDs for traceability.
- Minimal surface area: prefer clarity over magic so you can understand and control the runtime.

To explore the framework in action, check out the guides under Documentation.

The first phase of development is now complete providing what is hopefully a solid, fully tested foundation upon which it can be expanded.

Added a feature that allows Modules to be differentiated between Production, Development or potentially both. This will allow developers to create Modules that have been designed principally to aid development.  In due course this could even allow for a distribution channel.

Added a simple means to cleanse the storage olders.  The main benefit of this will be felt in production.


Expect to see improvements in the database adapters and model creators so that proper foreign key relations and indexes are supported.

There should be a query builder, possibly even a visual query builder.

