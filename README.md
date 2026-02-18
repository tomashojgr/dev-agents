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

`.dev-agents.json` is created automatically in your project root on install. All keys are optional — remove or leave any key at its default value.

```json
{
    "ai": "claude",
    "runner": null,
    "spec": {
        "language": "en",
        "default_scope": []
    },
    "lint": {
        "phpstan": {
            "cmd": null
        },
        "phpcs": {
            "cmd": null
        }
    }
}
```

| Key | Description | Values / example |
|-----|-------------|-----------------|
| `ai` | AI backend | `"claude"` (default), `"codex"`, or `"custom"` |
| `ai_commands` | Custom AI commands — only when `"ai": "custom"` | `{"print": "my-ai --output", "interactive": "my-ai"}` |
| `runner` | Shell wrapper for every command. `{cmd}` is replaced with the actual command. | `"make bash cmd=\"{cmd}\""` |
| `spec.language` | Language for generated task specs | `"en"`, `"cs"`, … |
| `spec.default_scope` | Paths always included in new task Scope sections | `["src/", "tests/"]` |
| `lint` | Override lint tool commands. If empty, tools are auto-detected from `vendor/bin`. | `{"phpstan": {"cmd": "vendor/bin/phpstan analyse"}}` |

Lint config files (`phpstan.neon`, `.phpcs.xml`) are created in your project root on install alongside `.dev-agents.json`. Edit them directly to customise lint rules.

If your PHP binary is not called `php` (e.g. `php84`), override `DA_PHP_PATH` in your project's `Makefile`:

```makefile
DA_PHP_PATH := php84
include vendor/tomashojgr/dev-agents/Makefile.agents
```

## Task files

Tasks are stored in `.tasks/<task-id>/TASK.md` in your project. Add `.tasks/` to `.gitignore` or commit them – your choice.
