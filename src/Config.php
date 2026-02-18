<?php

declare(strict_types=1);

namespace DevAgents;

class Config
{
    private const CONFIG_FILE = '.dev-agents.json';

    private array $data;

    public function __construct()
    {
        $path = getcwd() . '/' . self::CONFIG_FILE;
        $this->data = file_exists($path)
            ? (json_decode(file_get_contents($path), true) ?? [])
            : [];
    }

    /**
     * Build a shell command, optionally wrapping it in the configured runner.
     *
     * Runner template uses {cmd} as placeholder:
     *   "make bash cmd=\"{cmd}\""  →  make bash cmd="vendor/bin/phpstan analyse"
     */
    public function buildCmd(string $cmd): string
    {
        $runner = $this->data['runner'] ?? null;
        if ($runner === null) {
            return $cmd;
        }
        return str_replace('{cmd}', $cmd, $runner);
    }

    /**
     * Return configured lint tools, or null to use auto-detection.
     *
     * Each tool: ['name' => string, 'cmd' => string]
     */
    public function lintTools(): ?array
    {
        $lint = $this->data['lint'] ?? null;
        if ($lint === null) {
            return null;
        }

        $tools = [];
        foreach ($lint as $name => $def) {
            $cmd = $def['cmd'] ?? null;
            if ($cmd === null) {
                continue;
            }
            $tools[] = [
                'name' => $name,
                'cmd'  => $this->buildCmd($cmd),
            ];
        }

        return $tools ?: null;
    }

    /**
     * PHP binary – used for syntax fallback check.
     */
    public function phpBin(): string
    {
        $php = $this->data['php'] ?? 'php';
        return $this->buildCmd($php);
    }

    /**
     * Language for spec generation ('en' default).
     */
    public function specLanguage(): string
    {
        return $this->data['spec']['language'] ?? 'en';
    }

    /**
     * Default scope items pre-filled into new task specs.
     */
    public function specDefaultScope(): array
    {
        return $this->data['spec']['default_scope'] ?? [];
    }

    /**
     * Build a non-interactive AI call that prints response to stdout.
     * Default: claude --print "<prompt>"
     * Codex:   codex exec "<prompt>"
     */
    public function aiPrint(string $prompt): string
    {
        $bin = $this->data['ai']['print'] ?? 'claude --print';
        return $bin . ' ' . escapeshellarg($prompt);
    }

    /**
     * Build an interactive AI call (takes over the terminal).
     * Default: claude "<prompt>"
     * Codex:   codex "<prompt>"
     */
    public function aiInteractive(string $prompt): string
    {
        $bin = $this->data['ai']['interactive'] ?? 'claude';
        return $bin . ' ' . escapeshellarg($prompt);
    }
}
