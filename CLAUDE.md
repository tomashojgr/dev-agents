# dev-agents

PHP 8.4 composer package providing development workflow automation via AI-powered CLI agents. Agents are bash scripts — no PHP required on the host machine.

## Project Structure

```
agents/
  commit/da-commit                  # Generate conventional commit messages from staged diff
  spec/da-spec                      # Interactive AI discussion → generate TASK.md spec
  spec-approve/da-spec-approve      # Approve a task spec (sets waiting-for-coding)
  spec-continue/da-spec-continue    # Reopen spec discussion from existing TASK.md
  code/da-code                      # Create branch, implement autonomously, lint, push, open PR
  lint/da-lint                      # Run available PHP linters + AI error summary
  review/da-review                  # Read PR comments and address them autonomously
  release/da-release                # Bump semver, tag + push release with AI-generated changelog
src/
  Installer.php       # composer post-install/update: inject/update include in project Makefile
  Plugin.php          # Composer plugin entry point
templates/
  TASK.md             # Template for task specifications
Makefile.agents       # Make targets included into consumer project Makefile
composer.json
```

## Architecture Principles

- **Agents are bash scripts** — no PHP required on the host machine
- **PHP is only used in `src/`** (Installer, Plugin) which run during `composer install/update` where PHP is always available
- **No Anthropic API keys**: all AI calls go through the `claude` CLI (or another configured AI CLI)
- **Tasks live in `.tasks/<task-id>/TASK.md`** in the consumer project, not here
- **Agents are composable**: spec → spec-approve → code (auto-lint + PR) → review loop → release

## AI CLI Variables

Three Makefile variables control which AI command is used:

| Variable | Default | Used by |
|----------|---------|---------|
| `DA_AI_PRINT` | `claude --print` | Non-interactive AI calls (commit, release) |
| `DA_AI_RUN` | `claude` | Interactive AI session (spec) |
| `DA_AI_AUTO` | `claude --dangerously-skip-permissions` | Autonomous AI without permission prompts (code, review, lint-fix) |

## Task Lifecycle

```
spec-in-progress → waiting-for-spec-approval → waiting-for-coding
  → coding-in-progress → waiting-for-pr-review
  → review-in-progress → waiting-for-pr-review → ...
  → task-completed
```

Status is stored in `task.json` (separate from TASK.md spec):
```json
{
    "id": "task-001-example",
    "status": "waiting-for-coding",
    "created": "2025-01-01"
}
```

`da-code` only runs on `waiting-for-coding` tasks. TASK.md contains pure markdown spec with no frontmatter.

## TASK.md Format

Pure markdown, no frontmatter. Metadata lives in `task.json`.

```markdown
## Goal
One to two sentence description.

## Context
Why this is needed.

## Scope
- `src/SomeClass.php`
- `tests/`

## Acceptance Criteria
- [ ] Testable condition

## Implementation Notes
Hints for the implementer.

## Out of Scope
What not to touch.
```

## Coding Standards (bash agents)

- `set -euo pipefail` at the top of every agent
- `read -ra CMD <<< "${DA_AI_VAR:-default}"` for safe command splitting
- Error messages prefix: `❌`, success: `✅`, info: `ℹ️`, progress: `▶`
- Exit codes: 0 = success, 1 = user error or tool failure

## Adding a New Agent

1. Create `agents/<name>/da-<name>` (executable bash script)
2. Make it executable: `chmod +x agents/<name>/da-<name>`
3. Add to `"bin"` array in `composer.json`
4. Add make target in `Makefile.agents`
5. Document here and in README.md

## Testing Locally

```bash
composer install
# Run an agent directly (they're in vendor/bin after install)
vendor/bin/da-spec "test task description"
```
