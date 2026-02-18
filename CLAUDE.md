# dev-agents

PHP 8.4 composer package providing development workflow automation via specialized CLI agents that call the `claude` CLI (not the API).

## Project Structure

```
agents/
  commit/da-commit    # Generate conventional commit messages from staged diff
  spec/da-spec        # Generate TASK.md specifications from a goal string
  approve/da-approve  # Approve a task spec (sets status: approved)
  code/da-code        # Launch Claude Code to implement an approved task
  lint/da-lint        # Run available PHP linters + AI error summary
  release/da-release  # Bump semver, tag release with AI-generated changelog
src/
  TaskLoader.php      # Parse .tasks/<id>/TASK.md into structured array
  Installer.php       # composer post-install: inject include into project Makefile
templates/
  TASK.md             # Template for task specifications
Makefile.agents       # Make targets included into consumer project Makefile
composer.json
```

## Architecture Principles

- **Agents are PHP CLI scripts** (`#!/usr/bin/env php`) that shell out to `claude --print "..."` for non-interactive AI calls
- **`da-code` is the exception**: it runs `claude "<prompt>"` interactively (Claude Code, not `--print`)
- **No Anthropic API keys**: all AI calls go through the `claude` CLI tool
- **Tasks live in `.tasks/<task-id>/TASK.md`** in the consumer project, not here
- **Agents are composable**: spec → approve → code → commit → lint → release

## Task Lifecycle

```
draft → approved → done
```

Status is stored as a YAML frontmatter field in TASK.md:
```
status: draft | approved | done
```

`da-code` only runs on `approved` tasks. It marks tasks as `done` when Claude Code exits.

## TASK.md Format

```markdown
---
id: task-001-example
status: approved
created: 2025-01-01
---

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

`TaskLoader::parse()` extracts: `status`, `goal`, `scope` (list items under ## Scope), `raw`.

## Coding Standards

- PHP 8.4, strict types on every file
- No external runtime dependencies (only `composer/composer` in require-dev for Installer)
- Agents use `passthru()` for interactive commands (preserving TTY), `shell_exec()` for captured output
- `escapeshellarg()` on all user-provided strings passed to shell
- Error messages prefix: `❌`, success: `✅`, info: `ℹ️`, progress: `▶`
- Exit codes: 0 = success, 1 = user error or tool failure

## Adding a New Agent

1. Create `agents/<name>/da-<name>` (executable PHP script)
2. Add to `"bin"` array in `composer.json`
3. Add make target in `Makefile.agents`
4. Document here

## Testing Locally

```bash
composer install
# Symlink or PATH the agent you're working on
php agents/spec/da-spec "test task description"
```
