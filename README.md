# dev-agents

Composer package for PHP 8.4 projects providing development workflow automation via specialized CLI agents built on [Claude Code](https://claude.ai/code).

## Requirements

- PHP 8.4+
- [`claude` CLI](https://claude.ai/code) installed and authenticated (or another configured AI CLI – see Configuration)

## Installation

```bash
composer require --dev tomashojgr/dev-agents
```

After install, `Makefile.agents` is automatically included in your project's `Makefile`.

> **Note:** Composer 2.2+ requires explicit plugin trust. Add this to your project's `composer.json`:
> ```json
> "config": {
>     "allow-plugins": {
>         "tomashojgr/dev-agents": true
>     }
> }
> ```
> Or confirm interactively when Composer asks during `composer require`.

## Workflow

```bash
# 1. Generate task specification
make spec TASK="add DKIM validation to email sender"

# 2. Review .tasks/task-001-add-dkim-validation/TASK.md, then approve
make approve TASK=task-001-add-dkim-validation

# 3. Implement (launches Claude Code interactively)
make code TASK=task-001-add-dkim-validation

# 4. Stage changes, then commit
git add src/
make commit TASK=task-001-add-dkim-validation

# 5. Lint
make lint

# 6. Release
make release
```

## Agents

| Command | Binary | Description |
|---------|--------|-------------|
| `make spec TASK="..."` | `da-spec` | Generate `TASK.md` specification from a goal string |
| `make approve TASK=...` | `da-approve` | Approve a task spec (sets `status: approved`) |
| `make code TASK=...` | `da-code` | Launch Claude Code to implement an approved task |
| `make commit TASK=...` | `da-commit` | Generate Conventional Commits message from staged diff |
| `make lint` | `da-lint` | Run available PHP linters with AI error summary |
| `make release` | `da-release` | Bump semver, tag release with AI-generated changelog |

## Configuration

Create `.dev-agents.json` in your project root to customize behaviour:

```json
{
    "runner": "make bash cmd=\"{cmd}\"",
    "php": "php",
    "lint": {
        "phpstan": {
            "cmd": "vendor/bin/phpstan analyse --no-progress --configuration=phpstan.neon"
        },
        "phpcs": {
            "cmd": "vendor/bin/phpcs --standard=PSR12 src/"
        }
    },
    "spec": {
        "language": "cs",
        "default_scope": ["src/", "tests/"]
    }
}
```

| Key | Description | Default |
|-----|-------------|---------|
| `runner` | Shell template wrapping every command. Use `{cmd}` as placeholder. | direct execution |
| `php` | PHP binary. Wrapped by `runner` if set. | `php` |
| `ai` | AI backend: `claude`, `codex`, or `custom` | `claude` |
| `ai_commands.print` | Non-interactive command (only when `"ai": "custom"`) | — |
| `ai_commands.interactive` | Interactive command (only when `"ai": "custom"`) | — |
| `lint.*.cmd` | Override lint tool commands. If omitted, tools are auto-detected. | auto-detect |
| `spec.language` | Language for generated task specs (`en`, `cs`, …) | `en` |
| `spec.default_scope` | Paths always included in new task Scope sections | `[]` |

**Example – use Codex instead of Claude:**
```json
{
    "ai": "codex"
}
```

**Example – use a custom AI CLI:**
```json
{
    "ai": "custom",
    "ai_commands": {
        "print": "my-ai --output",
        "interactive": "my-ai"
    }
}
```

If no `lint` section is present, tools are auto-detected from `vendor/bin`. If a project config file (`phpstan.neon`, `.phpcs.xml`) exists it is used; otherwise the bundled default from `vendor/tomashojgr/dev-agents/config/` is used as fallback.

## Task files

Tasks are stored in `.tasks/<task-id>/TASK.md` in your project. Add `.tasks/` to `.gitignore` or commit them – your choice.
