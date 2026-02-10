# Multi-Role Orchestration Guide

Ishmael leverages a state-aware orchestration model to ensure high-quality, deterministic code generation. Instead of a single "do everything" AI, Ishmael uses specialized roles that work in sequence, producing verifiable artifacts at each stage.

## 1. Why Roles Exist

Large-scale software development requires clear boundaries between requirement gathering, technical design, implementation, and quality assurance. By separating these concerns into specialized roles, Ishmael achieves:

- **Verification**: Each stage can be independently reviewed and validated.
- **Precision**: Roles are constrained to their specific area of expertise, reducing hallucinations.
- **Consistency**: Technical designs must follow framework conventions before a single line of code is written.
- **Traceability**: Decisions made during analysis or design are preserved as permanent artifacts.

## 2. Orchestration Modes

Ishmael provides two ways to work with these roles. You can toggle between them using the `ish:mcp:mode` tool.

### 2.1 Quick Mode (Default)
**Workflow**: `Analyst` → `Developer`

Designed for trivial tasks like simple bug fixes, UI tweaks, or minor text changes. It bypasses the formal Architecture and Review stages for maximum speed.

### 2.2 Standard Mode
**Workflow**: `Analyst` → `Architect` → `Developer` → `Reviewer`

The recommended mode for all new features, refactoring, or building Feature Packs. It enforces a rigorous pipeline to ensure long-term maintainability.

---

## 3. The Roles Explained

### 3.1 The Analyst (`role:analyst`)
- **Focus**: Problem definition and requirement gathering.
- **Workflow**: Interrogates the user and project context to produce an unambiguous problem statement.
- **Deliverables**: `analysis.problem.md` (narrative) and `analysis.problem.json` (schema).
- **Constraints**: Does not suggest technologies or design code.

### 3.2 The Architect (`role:architect`)
- **Focus**: Converting requirements into technical design.
- **Workflow**: Maps approved requirements to Ishmael conventions, identifies dependencies, and defines data schemas.
- **Deliverables**: `architecture.design.md` and `architecture.design.json`.
- **Constraints**: Does not write production code; must use `ish:featurePack:registry` to leverage existing capabilities.

### 3.3 The Developer (`role:developer`)
- **Focus**: Verbatim implementation of the design.
- **Workflow**: Generates PHP/JS/SQL code based strictly on the Architect's design. Focuses on high-quality documentation and intent-explaining comments.
- **Deliverables**: Source code, `implementation.notes.md`, and `implementation.manifest.json`.
- **Constraints**: Cannot introduce features not present in the design.

### 3.4 The Reviewer (`role:reviewer`)
- **Focus**: Validation and compliance.
- **Workflow**: Compares the implementation against the original intent and technical design. Checks for documentation quality and license compliance.
- **Deliverables**: `review.report.md` and `review.report.json`.
- **Constraints**: Cannot edit code or change the architecture.

---

## 4. The Workflow Lifecycle

Ishmael maintains a state machine in `.ishmael/mcp_state.json` to guide you through the process.

1.  **Initiation (`INIT`)**: You start here. Only the Analyst is available.
2.  **Analysis (`ANALYSIS_COMPLETE`)**: Once requirements are locked, the Architect becomes available.
3.  **Design (`ARCHITECTURE_COMPLETE`)**: Once the design is finalized, the Developer takes over.
4.  **Implementation (`IMPLEMENTATION_IN_PROGRESS`)**: Code is being generated.
5.  **Review (`IMPLEMENTATION_COMPLETE`)**: Once code is done, the Reviewer validates the work.
6.  **Finalization**:
    - If issues are found, state moves to `ITERATION_REQUIRED` (returning to Developer or Architect).
    - If approved, state moves to `ACCEPTED` and then resets to `INIT` for the next task.

## 5. Artifact Immutability

To prevent "scope creep" and silent regressions, Ishmael enforces **artifact immutability**:
- Once you move from Analysis to Architecture, the requirements are **locked**.
- If the Architect discovers a flaw in the requirements, the system must explicitly "Roll Back" to the Analyst role to unlock and update the analysis.
- This ensures that every line of code has a clear, approved lineage back to the original requirement.

## 6. Getting Started

By default, Ishmael is in **Quick Mode**. To experience the full professional pipeline:
1. Call the `ish:mcp:mode` tool and set `mode` to `"standard"`.
2. Start your task by engaging the `role:analyst` prompt.
3. Follow the sequence as prompted by your IDE or the MCP server.
