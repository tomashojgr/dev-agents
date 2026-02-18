# dev-agents

Development agents for automating commit messages, linting and releases.

## Agents

- **commit** – Analyzes staged diff, generates Conventional Commits messages via Claude API
- **lint** – Code linting before commit *(coming soon)*
- **release** – Semantic versioning, CHANGELOG, git tag *(coming soon)*

## Installation

```bash
composer require yourname/dev-agents
```

## Configuration

Set your Anthropic API key:

```bash
export ANTHROPIC_API_KEY="sk-ant-..."
```

Add to your `.bashrc` or `.zshrc` for persistence.

## Usage

### Commit agent

```bash
# Stage your changes first
git add .

# Run commit agent with task context
./vendor/bin/commit.sh "add DKIM validation"
```

The agent will:
1. Analyze staged diff
2. Generate properly formatted commit message(s)
3. Ask for confirmation
4. Commit

## Requirements

- bash
- curl
- jq
- git
- Anthropic API key
