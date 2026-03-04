# Working with Feature Packs

The Ishmael framework is built around the concept of **Feature Packs**—modular, reusable components that can be easily packaged and shared across different projects. The Ishmael MCP plugin provides specialized tools and workflows to manage the entire lifecycle of a Feature Pack.

## 1. Designing a Feature Pack

Before writing any code, you can use the **Plan Feature Pack** prompt from the Ishmael menu. This prompt guides the AI assistant through a structured design phase:

*   **Requirements Gathering:** Defining what the Feature Pack will do.
*   **Architecture Design:** Designing the models, services, and routes.
*   **Manifest Creation:** Defining the `ishmael.json` manifest, including dependencies and metadata.

This structured approach ensures that your Feature Pack is well-designed and follows Ishmael's modularity standards.

## 2. Scaffolding and Implementation

Once the design is approved, the AI can use the `ish:make:feature-pack` tool to scaffold the necessary directory structure and files. You can then use other Ishmael actions to add components to your pack:

*   **New Controller / Service / Migration:** Use the **New** group in the Ishmael menu to quickly add components directly to your Feature Pack.
*   **Shadow Dependency Check:** Use the plugin's built-in inspection to ensure that your Feature Pack's manifest correctly lists all its dependencies.

## 3. Packaging for Distribution

When your Feature Pack is ready, you can package it into a distributable ZIP file:

*   **Pack as Feature Pack:** Right-click on your module in the Project view and select **Pack as Ishmael Feature Pack...**.
*   **Validation:** The plugin will automatically run a series of checks (e.g., manifest validation, linting) before creating the package to ensure it's ready for distribution.

## 4. Publishing to the Registry

The final step is to share your Feature Pack with the community:

*   **Publish to Ishmael Registry:** Right-click on your packaged module and select **Publish to Ishmael Registry...**.
*   **Licensing and Tiers:** You can manage licensing and security tiers (e.g., Upgrading to Tier A) directly through the Ishmael menu, ensuring your intellectual property is protected.

## 5. Vendor Registration

To publish Feature Packs, you first need to be registered as an Ishmael Vendor. Use the **Register as Vendor...** action in the Ishmael menu to set up your vendor profile and obtain the necessary keys for secure publishing.
