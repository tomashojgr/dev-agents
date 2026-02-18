<?php

declare(strict_types=1);

namespace DevAgents;

use Composer\IO\IOInterface;

class Installer
{
    private const INCLUDE_LINE = "include vendor/tomashojgr/dev-agents/Makefile.agents";
    private const MAKEFILE = 'Makefile';
    private const CONFIG_FILE = '.dev-agents.json';
    private const CONFIG_STUB = <<<'JSON'
{
    "ai": "claude",
    "runner": null,
    "php": "php",
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
JSON;

    public static function run(IOInterface $io): void
    {
        // Check claude CLI is available
        exec('which claude 2>/dev/null', $out, $code);
        if ($code !== 0) {
            $io->writeError('<warning>dev-agents: claude CLI not found in PATH. Install it from https://claude.ai/code</warning>');
        }

        self::ensureMakefile($io);
        self::ensureConfig($io);
    }

    private static function ensureMakefile(IOInterface $io): void
    {
        $makefile = getcwd() . '/' . self::MAKEFILE;

        if (!file_exists($makefile)) {
            file_put_contents($makefile, self::INCLUDE_LINE . "\n");
            $io->write('<info>dev-agents: Created Makefile with dev-agents include</info>');
            return;
        }

        $contents = file_get_contents($makefile);
        if (str_contains($contents, 'Makefile.agents')) {
            $io->write('<info>dev-agents: Makefile already configured</info>');
            return;
        }

        file_put_contents($makefile, self::INCLUDE_LINE . "\n\n" . $contents);
        $io->write('<info>dev-agents: Added dev-agents include to Makefile</info>');
    }

    private static function ensureConfig(IOInterface $io): void
    {
        $config = getcwd() . '/' . self::CONFIG_FILE;

        if (file_exists($config)) {
            return;
        }

        file_put_contents($config, self::CONFIG_STUB . "\n");
        $io->write('<info>dev-agents: Created .dev-agents.json — edit to customise AI backend, runner, lint tools, etc.</info>');
    }
}
