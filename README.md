# dev-agents

Composer package for PHP 8.4 projects providing development workflow automation via specialized CLI agents built on [Claude Code](https://claude.ai/code).

## Requirements

- PHP 8.4+
- [`claude` CLI](https://claude.ai/code) installed and authenticated

## Installation

```bash
composer require tomashojgr/dev-agents
```

After install, `Makefile.agents` is automatically included in your project's `Makefile`.

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

## Task files

Tasks are stored in `.tasks/<task-id>/TASK.md` in your project. Add `.tasks/` to `.gitignore` or commit them – your choice.
