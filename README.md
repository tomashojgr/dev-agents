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
# 1. Discuss the task interactively with the AI, say "ok, zapiš to" to save the spec.
#    The AI displays the spec in the conversation and asks if you want to implement now.
#    You can continue refining — the discussion stays open until you confirm.
#    If you confirm, implementation starts automatically.
make da-spec TASK="add DKIM validation to email sender"

# 2. AI creates a branch, implements autonomously, runs lint, pushes and opens a PR
#    (starts automatically after step 1 if confirmed, or run manually with task number/name)
make da-code TASK=1

# 3. Review the PR, leave comments. When ready, let the AI address them:
make da-review TASK=1

# 4. Repeat step 3 until satisfied, then merge the PR

# 5. After all PRs are merged — tag and publish a release
make da-release
```

## Agents

| Command | Description |
|---------|-------------|
| `make da-spec TASK="..."` | Interactive discussion with AI, generates and displays `TASK.md` in-conversation, then offers to start implementation (context preserved throughout) |
| `make da-code TASK=...` | Creates a task branch, implements autonomously, runs lint, pushes and opens a PR |
| `make da-review TASK=...` | Reads PR comments and addresses them autonomously, then pushes |
| `make da-release` | Bumps semver, tags release with AI-generated changelog, pushes |

## Manual tools

These agents are useful outside the main workflow — for manual development, ad-hoc fixes, or CI integration:

| Command | Description |
|---------|-------------|
| `make da-spec-continue TASK=...` | Reopen spec discussion for an existing task (context from spec, not original conversation) |
| `make da-spec-approve TASK=...` | Approve a manually written task spec (sets status to `waiting-for-coding`) |
| `make da-lint-fix` | Run linters, auto-fix style issues via phpcbf, then AI fixes remaining issues |
| `make da-lint` | Run available PHP linters (check only, no fixes) |
| `make da-commit TASK=...` | Generate a Conventional Commits message from staged diff — useful when committing manual changes |

> **Note:** `da-code` runs lint automatically before opening the PR, so `da-lint` / `da-lint-fix` are typically only needed for manual code changes.

## Configuration

AI commands are configured via Makefile variables. Override them in your `Makefile` **above** the `BEGIN dev-agents` block:

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
| `DA_AI_PRINT` | `claude --print` | Non-interactive AI call — returns output to stdout |
| `DA_AI_RUN` | `claude` | Interactive AI session — used by `da-spec` |
| `DA_AI_AUTO` | `claude --dangerously-skip-permissions` | Autonomous AI session without permission prompts — used by `da-code` and `da-review` |

Lint config files (`phpstan.neon`, `.phpcs.xml`) are created in your project root on install if the respective tools are present in `vendor/bin`. Edit them to customise lint rules.

## Task files

Tasks are stored in `.tasks/<task-id>/`. Add `.tasks/` to `.gitignore` or commit them — your choice.

```
.tasks/
  task-001-add-dkim-validation/
    TASK.md      ← spec (pure markdown, no metadata)
    task.json    ← lifecycle metadata (status, id, created)
```

All agents accept a flexible `TASK` argument — use the task number, a substring of the name, or the full ID:

```bash
make da-code TASK=1           # by number
make da-code TASK=dkim        # by substring
make da-code TASK=task-001-add-dkim-validation  # full ID
```

### Task lifecycle

```
spec-in-progress → waiting-for-spec-approval → waiting-for-coding
  → coding-in-progress → waiting-for-pr-review
  → review-in-progress → waiting-for-pr-review → ...
  → task-completed
```

Status is tracked in `task.json` (separate from the spec). Each agent updates it automatically — you can always see where a task stands at a glance.
