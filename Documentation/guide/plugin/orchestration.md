# Orchestration: Modes and Pipelines

The Ishmael MCP plugin uses "orchestration" to manage the development process. This is represented by a **Pipeline Progress** indicator in the tool window, which shows the current stage of the active task.

## Orchestration Modes

You can switch between two primary modes depending on the complexity of the task. This can be done via the **Switch MCP Mode** button in the tool window toolbar or via chat commands.

### 1. Quick Mode (Default)
**Quick mode** is designed for rapid development of straightforward tasks. It uses a condensed pipeline:
*   **Analyst:** The AI analyzes the task.
*   **Developer:** The AI implements the code and verifies it.

This mode is ideal for bug fixes, minor UI changes, or adding simple features where a formal architectural review is unnecessary.

### 2. Standard Mode
**Standard mode** follows a more rigorous "Roles-Based" development lifecycle, ideal for complex features or architectural changes:
*   **Analyst:** Requirements gathering and impact analysis.
*   **Architect:** Designing the solution (models, routes, dependencies) before writing code.
*   **Developer:** Implementation and testing of the approved design.
*   **Reviewer:** Final validation and verification against requirements.

## The Pipeline Lifecycle

The pipeline is not just for show; it acts as a "gating" mechanism for the AI. Each stage must be completed before the next can begin.

### Pipeline Stages
1.  **INIT:** The starting state for a new task.
2.  **ANALYSIS_COMPLETE:** Requirements have been documented and approved.
3.  **ARCHITECTURE_COMPLETE:** The design (e.g., in an `architecture.md` file) has been finalized.
4.  **IMPLEMENTATION_IN_PROGRESS:** Coding is underway.
5.  **IMPLEMENTATION_COMPLETE:** Coding and testing are finished.
6.  **REVIEW_COMPLETE:** The implementation has been verified.
7.  **ACCEPTED:** The task is finished, and the state resets to **INIT** for the next feature.
8.  **ITERATION_REQUIRED:** If a review fails, the state can be moved back to Developer for fixes.

## Interacting with the Pipeline

### Automatic Progression
When the AI assistant uses specific tools (like submitting an analysis or finishing a review), the MCP server advances the state automatically. The plugin's **Pipeline Progress** indicator in the tool window will reflect these changes (with a periodic background check or manual refresh).

### Manual State Changes
You can manually direct the AI to change the mode or state using these chat commands:
*   `ish:mcp:mode mode="standard"` (or "quick")
*   `ish:mcp:transition state="ANALYSIS_COMPLETE"` (or any other valid stage)

### Why Use Orchestration?
By using these modes and pipelines, you ensure that the AI follows professional development practices:
*   **Thinking before doing:** The Architect stage prevents "cowboy coding" by the AI.
*   **Structured Context:** Each stage limits the AI's focus to what's relevant now, improving accuracy.
*   **Verification:** The Reviewer stage provides a final check, reducing the chance of bugs reaching production.
