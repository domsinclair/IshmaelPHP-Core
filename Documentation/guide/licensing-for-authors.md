# Licensing Guide for Feature Pack Authors

Designing a feature pack with licensing in mind is key to creating a successful product in the IshmaelPHP ecosystem. This guide helps you navigate the choices you need to make and how to implement license checks correctly.

## The Value of Licensing

Ishmael's licensing system is built on a simple realization: **Great software requires effort.** 

By offering a path to monetization, we aim to:
- **Reward Effort**: Authors who invest significant time into building complex features deserve to realize a return on that effort.
- **Ensure Longevity**: Paid licenses provide the financial stability required for authors to offer ongoing development, security updates, and high-quality support.
- **Drive Innovation**: Pragmatic support for commercial software encourages developers to tackle larger, more ambitious problems that free-only ecosystems might overlook.

## Choosing Your Licensing Model

Ishmael supports three models for your `feature-pack.json` manifest:

### 1. Community
- **Best for**: Open-source utilities, standard libraries, and small integrations.
- **Requirement**: No license enforcement. Users get everything for free.
- **Manifest**: `"model": "community"`, `"enforcement": "none"`.

### 2. Commercial
- **Best for**: Complex tools, premium UI components, and niche professional integrations.
- **Requirement**: Requires a valid license token for development use.
- **Manifest**: `"model": "commercial"`, `"enforcement": "development"`.

### 3. Dual (Recommended for Growth)
- **Best for**: Most professional packs. Provide a solid functional core for free, and charge for advanced features.
- **Requirement**: Distinguish between free and paid capabilities.
- **Manifest**: `"model": "dual"`.

## Defining Capabilities

Capabilities are the units of value in Ishmael. Instead of "locking" a whole package, you should identify specific features that require a license.

**Good Examples of Capabilities:**
- `blog.core` (Community) - Basic CRUD for posts.
- `blog.seo-tools` (Commercial) - Advanced SEO analysis and suggestions.
- `blog.import-export` (Commercial) - Tools to import from WordPress or Ghost.

**Rules for Capability Design:**
1. **Always provide a baseline**: A user should be able to install your pack and get basic value without a license.
2. **Stable Identifiers**: Use dot-notation (e.g., `vendor.feature`). These IDs are used in the manifest and the code.

## How to Implement License Checks

### The Golden Rule: Never Gate Runtime
Do **not** wrap your controllers, models, or core business logic in license checks. If a user has the code, it should run in production.

❌ **Wrong:**
```php
public function viewPost($id) {
    if (!Capability::isAvailable('blog.premium-theme')) {
        abort(403);
    }
}
```

### The Correct Way: Gate Tooling and UI
Gate the things that *create* or *manage* the features, not the features themselves.

✅ **Correct (CLI Command):**
```php
public function handle() {
    Capability::assert('blog.seo-tools');
    // ... run SEO analysis
}
```

✅ **Correct (Admin UI Component):**
```php
// In a View or Admin Controller
$canUseSeo = Capability::isAvailable('blog.seo-tools');
return View::make('admin.seo', ['enabled' => $canUseSeo]);
```

### Capability Enforcement API
The framework provides two primary methods:
- `Capability::isAvailable(string $id): bool`: Returns whether the capability is active (licensed or in trial).
- `Capability::assert(string $id): void`: Throws a `CapabilityException` if not available. This is ideal for CLI commands.

## Manifest Configuration

Your `feature-pack.json` is where you declare your licensing intent.

```json
{
  "licensing": {
    "model": "dual",
    "enforcement": "development",
    "trial": {
      "enabled": true,
      "duration_days": 14
    }
  },
  "capabilities": [
    {
      "id": "blog.core",
      "description": "Basic blogging features",
      "license": "community"
    },
    {
      "id": "blog.seo-tools",
      "description": "Advanced SEO analysis",
      "license": "commercial"
    }
  ]
}
```

## Commerce and Payment Handling

Ishmael is payment-provider agnostic, but for most authors, we strongly recommend using a **Merchant of Record (MoR)** such as [Paddle](https://paddle.com).

### Why a Merchant of Record (Paddle) is Important

Managing global sales is complex. Using an MoR like Paddle simplifies your journey by handling:
1. **Global Tax Compliance**: They automatically calculate, collect, and remit VAT, Sales Tax, and GST in every country where you sell. This removes a massive legal and accounting burden from the author.
2. **Fraud Prevention**: Professional-grade fraud detection and chargeback management are handled for you.
3. **Billing Infrastructure**: Subscriptions, renewals, and invoicing are all handled out of the box.

### The Issuance Flow

When a user purchases your pack via your chosen provider:
1. The provider sends a **Webhook** to the Ishmael Licensing Service.
2. Ishmael validates the payment and generates a **Signed License Token**.
3. The user receives the token, which they place in their project to unlock the capabilities.

## Best Practices
- **Education over Enforcement**: When a check fails, explain *why* and how the user can get a license or start a trial.
- **Trial Support**: Enable trials (`trial.enabled: true`) to lower the barrier for users to test your premium features.
- **Production Isolation**: Remember that all `Capability` checks return `true` in production environments (based on the `enforcement` scope). Don't rely on them for security; rely on them for licensing.
