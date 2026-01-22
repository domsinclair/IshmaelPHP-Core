# Feature Pack Licensing: Concepts & Philosophy

## Introduction

IshmaelPHP introduces a unique, developer-first approach to feature pack licensing. Unlike traditional DRM (Digital Rights Management) systems that often hinder performance or reliability, Ishmael's licensing model is designed to support the sustainability of the ecosystem while remaining completely transparent to the production runtime.

It is important to clarify: **IshmaelPHP is, and will always remain, a free framework.** The licensing system is not designed to monetize the framework itself, but to provide a pragmatic infrastructure for feature pack authors to build sustainable products.

## The Core Philosophy: "Runtime Neutrality"

The most fundamental principle of IshmaelPHP licensing is **Runtime Neutrality**. 

> **The framework never decides what code may execute in production. It only decides which capabilities tooling may expose in development.**

This means:
- **Zero Performance Overhead**: There are no license checks, phone-home calls, or signature validations during a web request or production job execution.
- **Reliability**: An expired license or a missing activation token will *never* crash your live application.
- **Respect for the Developer**: We trust developers to respect the licenses of the tools they use, and we provide the infrastructure to make that respect easy to manage.

## Why License Feature Packs?

Ishmael's approach is rooted in pragmatism. A high-quality feature pack often represents hundreds or thousands of hours of expert development, testing, and maintenance.

By supporting licensing, Ishmael recognizes that:
1. **Sustainability**: Authors deserve a return on their effort. Financial incentives encourage authors to provide ongoing updates, bug fixes, and professional-grade support.
2. **Ecosystem Quality**: A pragmatic path to monetization attracts high-caliber developers who can solve complex problems that go beyond the scope of a standard library.
3. **Pragmatic Growth**: Allowing paid feature packs ensures that the tools available to Ishmael developers remain competitive and well-maintained over the long term.

## The Three Pillars of Licensing

### 1. The Licensing Model
Ishmael supports three primary models:
- **Community**: 100% free, no enforcement, unrestricted usage.
- **Commercial**: Paid features, usually governed by "Development-only" enforcement.
- **Dual**: A single pack that provides a functional Community core with optional Premium capabilities.

### 2. Capabilities, not Classes
We do not license files, classes, or namespaces. We license **Capabilities**.
A capability is a logical unit of value (e.g., `blog.admin-ui`, `seo.analyzer`). The framework provides a `Capability` API that tools (CLI, MCP, Admin Panels) use to check if a feature is available to the developer.

### 3. Environment Awareness
Licensing is strictly environment-aware:
- **Development/Local**: This is where enforcement happens. Tooling (like generators, migration runners, or AI assistants) checks for valid licenses.
- **Production**: Licensing is informational only. All enforcement is bypassed to ensure the application remains stable and performant.

## The Licensing Token
Commercial licenses are represented by signed JSON tokens. These tokens are:
- **Offline-First**: Validated locally using public-key cryptography. No internet connection is required for daily work.
- **Seat-Based**: Allows a developer to use the license across a limited number of machines (e.g., Work Desktop, Laptop).
- **Transparent**: The developer can always inspect the token to see what they are licensed for.

## Conclusion

By moving licensing out of the critical path of the application and into the developer's tooling, IshmaelPHP creates a professional marketplace that respects both the author's need for sustainability and the developer's need for performance and reliability.
