#!/bin/bash

# Commit Agent

# Usage: ./commit.sh "task name"

# Analyzes staged diff and creates properly formatted conventional commits

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../../shared/config.sh"

TASK_NAME="${1:-}"

# Check if inside git repo

if ! git rev-parse --git-dir > /dev/null 2>&1; then
echo "❌ Not a git repository"
exit 1
fi

# Get staged diff

DIFF=$(git diff --staged)

if [ -z "$DIFF" ]; then
echo "❌ No staged changes. Use 'git add' first."
exit 1
fi

# Get list of changed files

CHANGED_FILES=$(git diff --staged --name-only)

echo "📋 Staged files:"
echo "$CHANGED_FILES"
echo ""

# Build prompt

PROMPT="You are a git commit message expert. Analyze the following git diff and generate commit message(s) following the Conventional Commits standard.

Task context: ${TASK_NAME:-unknown}

Conventional Commits format:
<type>(<scope>): <description>

Types: feat, fix, chore, docs, refactor, test, style, perf, ci

- scope is optional but recommended
- description in imperative mood, lowercase, no period at end
- max 72 characters for the first line

Rules:

1. If the diff contains multiple logical changes, generate MULTIPLE commit messages (one per line, prefixed with COMMIT:)
1. If it's one logical change, generate ONE commit message (prefixed with COMMIT:)
1. Only output COMMIT: lines, nothing else

Changed files:
$CHANGED_FILES

Diff:
$DIFF"

# Call Claude API

RESPONSE=$(curl -s "$CLAUDE_API_URL" \
-H "Content-Type: application/json" \
-H "x-api-key: $ANTHROPIC_API_KEY" \
-H "anthropic-version: 2023-06-01" \
-d "{
\"model\": \"$CLAUDE_MODEL\",
\"max_tokens\": 1024,
\"messages\": [{
\"role\": \"user\",
\"content\": $(echo "$PROMPT" | jq -Rs .)
}]
}")

# Extract text from response

COMMIT_MESSAGES=$(echo "$RESPONSE" | jq -r '.content[0].text' 2>/dev/null)

if [ -z "$COMMIT_MESSAGES" ] || [ "$COMMIT_MESSAGES" = "null" ]; then
echo "❌ Failed to get response from Claude API"
echo "$RESPONSE"
exit 1
fi

# Parse COMMIT: lines

COMMITS=$(echo "$COMMIT_MESSAGES" | grep "^COMMIT:" | sed 's/^COMMIT: //')

if [ -z "$COMMITS" ]; then
echo "❌ No commit messages generated"
echo "$COMMIT_MESSAGES"
exit 1
fi

# Validate format with regex

PATTERN="^(feat|fix|chore|docs|refactor|test|style|perf|ci)(\(.+\))?: .{3,}"

while IFS= read -r msg; do
if ! echo "$msg" | grep -qE "$PATTERN"; then
echo "❌ Invalid commit message format: $msg"
exit 1
fi
done <<< "$COMMITS"

# Count commits

COMMIT_COUNT=$(echo "$COMMITS" | wc -l | tr -d ' ')

echo "💬 Generated $COMMIT_COUNT commit(s):"
echo "$COMMITS"
echo ""

# Ask for confirmation

read -p "Proceed with these commits? [Y/n] " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Nn]$ ]]; then
echo "❌ Aborted"
exit 1
fi

# If multiple commits, we need to split the staged changes

if [ "$COMMIT_COUNT" -gt 1 ]; then
echo "⚠️  Multiple commits detected. Committing all staged changes with first message,"
echo "   then you should manually split remaining commits."
echo ""
fi

# Create commits

FIRST=true
while IFS= read -r msg; do
if [ "$FIRST" = true ]; then
git commit -m "$msg"
FIRST=false
else
echo "📝 Next commit message (stage relevant files manually, then run):"
echo "   git commit -m \"$msg\""
fi
done <<< "$COMMITS"

echo ""
echo "✅ Done"
