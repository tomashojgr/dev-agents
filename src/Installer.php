<?php

declare(strict_types=1);

namespace DevAgents;

use Composer\IO\IOInterface;

class Installer
{
    private const INCLUDE_LINE = "DA_PHP_PATH ?= php\ninclude vendor/tomashojgr/dev-agents/Makefile.agents";
    private const MAKEFILE = 'Makefile';
    private const CONFIG_FILE = '.dev-agents.json';
    private const CONFIG_TEMPLATE = __DIR__ . '/../config/.dev-agents.json';

    private const LINT_CONFIGS = [
        'phpstan.neon' => ['template' => __DIR__ . '/../config/phpstan.neon',  'bin' => 'phpstan'],
        '.phpcs.xml'   => ['template' => __DIR__ . '/../config/.phpcs.xml',    'bin' => 'phpcs'],
    ];

    public static function run(IOInterface $io): void
    {
        // Check claude CLI is available
        exec('which claude 2>/dev/null', $out, $code);
        if ($code !== 0) {
            $io->writeError('<warning>dev-agents: claude CLI not found in PATH. Install it from https://claude.ai/code</warning>');
        }

        self::ensureMakefile($io);
        self::ensureConfig($io);
        self::ensureLintConfigs($io);
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

        file_put_contents($config, file_get_contents(self::CONFIG_TEMPLATE));
        $io->write('<info>dev-agents: Created .dev-agents.json — edit to customise AI backend, runner, lint tools, etc.</info>');
    }

    private static function ensureLintConfigs(IOInterface $io): void
    {
        $cwd = getcwd();
        foreach (self::LINT_CONFIGS as $filename => $def) {
            $target = $cwd . '/' . $filename;
            if (file_exists($target)) {
                continue;
            }
            if (!file_exists($cwd . '/vendor/bin/' . $def['bin'])) {
                continue;
            }
            file_put_contents($target, file_get_contents($def['template']));
            $io->write("<info>dev-agents: Created {$filename} — edit to customise lint rules.</info>");
        }
    }
}
