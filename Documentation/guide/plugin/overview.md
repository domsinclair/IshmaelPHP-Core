# Ishmael MCP Plugin Overview

The Ishmael MCP (Model Context Protocol) plugin for PhpStorm is a powerful bridge between your IDE and AI assistants (like JetBrains AI Assistant or Junie). It enables a deeply integrated, AI-driven development workflow specifically tailored for the Ishmael framework.

## Core Purpose

The plugin's main goal is to provide AI assistants with "superpowers" by giving them direct, structured access to your Ishmael project's internal state, documentation, and tools. This moves AI collaboration from simple text generation to active project orchestration.

## Key Capabilities

### 1. Unified Project Visibility
The plugin automatically discovers and exposes your Ishmael project structure to the AI, including:
*   **Database Schema:** Real-time visibility into tables, columns, and relationships.
*   **Routes & Events:** Direct access to application entry points and event-driven logic.
*   **Service Container:** Understanding of available services and their dependencies.
*   **Documentation:** Direct injection of framework and project-specific docs into the AI's context.

### 2. Intelligent Orchestration (Pipelines)
The plugin implements a structured development pipeline that guides both the user and the AI through a professional software development lifecycle:
*   **Analysis:** Identifying requirements and impact.
*   **Architecture:** Designing the solution before coding.
*   **Implementation:** Writing the code.
*   **Review:** Verifying the changes.

### 3. Integrated Tool Window
The **Ishmael MCP** tool window provides a central hub for monitoring and controlling the project state:
*   **Status Tab:** Real-time view of the MCP server health and active orchestration stage.
*   **Database Tab:** Interactive schema browser.
*   **Events/Routes Tabs:** Lists of registered application components.
*   **Feature Packs:** Management of Ishmael modular components.
*   **Output/Log:** Detailed feedback from AI tool executions and MCP server communication.

### 4. Interactive AI Prompts
The plugin provides a library of pre-configured prompts that bridge the gap between IDE actions and chat interactions. Selecting a prompt in the IDE (e.g., "Plan Feature") prepares a specialized command for the AI assistant, ensuring it starts the task with the correct context and instructions.

## Guide Contents

To learn more about specific features of the Ishmael MCP plugin, explore the following guides:

*   **[Chat Interaction](chat-interaction.md):** How the AI assistant uses tools to interact with your project.
*   **[Orchestration & Pipelines](orchestration.md):** Understanding the development lifecycle managed by the plugin.
*   **[IDE Features](ide-features.md):** Detailed information on inspections, completion, and navigation.
*   **[Tool Window](tool-window.md):** A guide to the tabs and actions in the Ishmael MCP tool window.
*   **[Feature Packs](feature-packs.md):** Working with modular components and distribution.
*   **[Troubleshooting](troubleshooting.md):** Support and common fixes for the plugin and MCP server.
*   **[Configuration](configuration.md):** Customizing settings and server connections.

## How it Works

The plugin acts as a client to the **Ishmael MCP Server** (a PHP process running within your project).
1.  The **MCP Server** analyzes your PHP code, database, and configuration.
2.  The **Plugin** connects to this server via standard input/output (stdio).
3.  The **AI Assistant** queries the Plugin for information or requests it to perform actions (like running tests or creating files).
4.  The **Plugin** relays these requests to the MCP Server, which executes the actual logic in the context of your application.

This architecture ensures that the AI's suggestions are always grounded in the reality of your codebase, significantly reducing hallucinations and improving the quality of generated code.
