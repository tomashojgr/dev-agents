# dev-agents

Composer package providing development workflow automation via AI-powered CLI agents. Agents are bash scripts — no PHP required on the host machine.

## Requirements

- [`claude` CLI](https://claude.ai/code) installed and authenticated on the host machine (or another configured AI CLI — see [Configuration](#configuration))
- `gh` CLI for PR workflows (`da-code`, `da-review`)
- `git`

## Installation

```bash
composer require --dev tomashojgr/dev-agents
```

After install, a block is automatically appended to your project's `Makefile`:

```makefile
# BEGIN dev-agents — do not edit this block manually
# Set to your AI CLI tool (e.g. 'codex exec' / 'codex' for OpenAI Codex):
DA_AI_PRINT ?= claude --print
DA_AI_RUN   ?= claude
DA_AI_AUTO  ?= claude --dangerously-skip-permissions
include vendor/tomashojgr/dev-agents/Makefile.agents
# END dev-agents
```

> **Note:** Composer 2.2+ requires explicit plugin trust. Add this to your project's `composer.json`:
> ```json
> "config": {
>     "allow-plugins": {
>         "tomashojgr/dev-agents": true
>     }
> }
> ```

## Workflow

```bash
# 1. Discuss the task interactively with the AI, then say "ok, zapiš to" to generate spec
make da-spec TASK="add DKIM validation to email sender"

# 2. Review .tasks/task-001-add-dkim-validation/TASK.md, then approve
make da-approve TASK=task-001-add-dkim-validation

# 3. AI creates a branch, implements autonomously, pushes and opens a PR
make da-code TASK=task-001-add-dkim-validation

# 4. Review the PR, leave comments. When ready, let the AI address them:
make da-review TASK=task-001-add-dkim-validation

# 5. Repeat step 4 until satisfied, then merge the PR

# 6. Lint and fix — runs linters, auto-fixes style issues, then AI fixes the rest
make da-lint-fix

# 6b. Lint check only
make da-lint

# 7. Stage changes, then commit
git add src/
make da-commit TASK=task-001-add-dkim-validation

# 8. Release
make da-release
```

## Agents

| Command | Description |
|---------|-------------|
| `make da-spec TASK="..."` | Interactive discussion with AI to clarify requirements, then generates `TASK.md` |
| `make da-approve TASK=...` | Review and approve a task spec (sets `status: approved`) |
| `make da-code TASK=...` | Creates a task branch, implements autonomously, pushes and opens a PR |
| `make da-review TASK=...` | Reads PR comments and addresses them autonomously, then pushes |
| `make da-commit TASK=...` | Generate Conventional Commits message from staged diff |
| `make da-lint` | Run available PHP linters |
| `make da-lint-fix` | Run linters, auto-fix style issues via phpcbf, then AI fixes the rest |
| `make da-release` | Bump semver, tag release with AI-generated changelog |

## Configuration

AI commands and tool behaviour are configured via Makefile variables. Override them in your `Makefile` above the `BEGIN dev-agents` block:

```makefile
# Use OpenAI Codex instead of Claude
DA_AI_PRINT = codex exec
DA_AI_RUN   = codex
DA_AI_AUTO  = codex exec

# BEGIN dev-agents — do not edit this block manually
...
```

| Variable | Default | Description |
|----------|---------|-------------|
| `DA_AI_PRINT` | `claude --print` | Non-interactive AI call (returns output to stdout) |
| `DA_AI_RUN` | `claude` | Interactive AI session (used by `da-spec`) |
| `DA_AI_AUTO` | `claude --dangerously-skip-permissions` | Autonomous AI session without permission prompts (used by `da-code`, `da-review`) |

Lint config files (`phpstan.neon`, `.phpcs.xml`) are created in your project root on install if the respective tools are present in `vendor/bin`. Edit them directly to customise lint rules.

## Task files

Tasks are stored in `.tasks/<task-id>/TASK.md`. Add `.tasks/` to `.gitignore` or commit them — your choice.

```
.tasks/
  task-001-add-dkim-validation/
    TASK.md
```

### TASK.md lifecycle

```
draft → approved → done
```

`da-spec` creates tasks as `draft`. `da-approve` sets `approved`. `da-code` marks them `done` after implementation.
