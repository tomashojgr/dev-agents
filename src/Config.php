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
                'cmd'  => $cmd,
            ];
        }

        return $tools ?: null;
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

    private const AI_PRESETS = [
        'claude' => [
            'print'       => 'claude --print',
            'interactive' => 'claude',
        ],
        'codex' => [
            'print'       => 'codex exec',
            'interactive' => 'codex',
        ],
    ];

    /**
     * Resolve AI commands from config.
     * "ai": "claude"  → preset
     * "ai": "codex"   → preset
     * "ai": "custom"  → uses "ai_commands": { "print": "...", "interactive": "..." }
     */
    private function aiCommands(): array
    {
        $ai = $this->data['ai'] ?? 'claude';

        if (isset(self::AI_PRESETS[$ai])) {
            return self::AI_PRESETS[$ai];
        }

        // custom
        return [
            'print'       => $this->data['ai_commands']['print'] ?? 'claude --print',
            'interactive' => $this->data['ai_commands']['interactive'] ?? 'claude',
        ];
    }

    /**
     * Build a non-interactive AI call that prints response to stdout.
     */
    public function aiPrint(string $prompt): string
    {
        return $this->aiCommands()['print'] . ' ' . escapeshellarg($prompt);
    }

    /**
     * Build an interactive AI call (takes over the terminal).
     */
    public function aiInteractive(string $prompt): string
    {
        return $this->aiCommands()['interactive'] . ' ' . escapeshellarg($prompt);
    }

}
