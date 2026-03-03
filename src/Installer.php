<?php

declare(strict_types=1);

namespace DevAgents;

use Composer\IO\IOInterface;

class Installer
{
    private const MAKEFILE_BLOCK_BEGIN = '# BEGIN dev-agents — do not edit this block manually';
    private const MAKEFILE_BLOCK_END   = '# END dev-agents';
    private const MAKEFILE_BLOCK =
        self::MAKEFILE_BLOCK_BEGIN . "\n" .
        "# Set to your AI CLI tool (e.g. 'codex exec' / 'codex' for OpenAI Codex):\n" .
        "DA_AI_PRINT ?= claude --print\n" .
        "DA_AI_RUN   ?= claude\n" .
        "DA_AI_AUTO  ?= claude --dangerously-skip-permissions\n" .
        "include vendor/tomashojgr/dev-agents/Makefile.agents\n" .
        self::MAKEFILE_BLOCK_END;
    private const MAKEFILE = 'Makefile';

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
        self::ensureLintConfigs($io);
        self::ensureGitignore($io);
    }

    private static function ensureMakefile(IOInterface $io): void
    {
        $makefile = getcwd() . '/' . self::MAKEFILE;

        if (!file_exists($makefile)) {
            file_put_contents($makefile, self::MAKEFILE_BLOCK . "\n");
            $io->write('<info>dev-agents: Created Makefile with dev-agents include</info>');
            return;
        }

        $contents = file_get_contents($makefile);

        if (str_contains($contents, self::MAKEFILE_BLOCK_BEGIN)) {
            $io->write('<info>dev-agents: Makefile already configured</info>');
            return;
        }

        file_put_contents($makefile, rtrim($contents) . "\n\n" . self::MAKEFILE_BLOCK . "\n");
        $io->write('<info>dev-agents: Added dev-agents include to Makefile</info>');
    }

    public static function runUninstall(IOInterface $io): void
    {
        $makefile = getcwd() . '/' . self::MAKEFILE;

        if (!file_exists($makefile)) {
            return;
        }

        $contents = file_get_contents($makefile);

        if (!str_contains($contents, self::MAKEFILE_BLOCK_BEGIN)) {
            return;
        }

        $pattern = '/\n?' . preg_quote(self::MAKEFILE_BLOCK_BEGIN, '/') . '.*?' . preg_quote(self::MAKEFILE_BLOCK_END, '/') . '\n?/s';
        $cleaned = preg_replace($pattern, '', $contents);
        file_put_contents($makefile, $cleaned);
        $io->write('<info>dev-agents: Removed dev-agents block from Makefile</info>');
    }

    private static function ensureGitignore(IOInterface $io): void
    {
        $gitignore = getcwd() . '/.gitignore';
        $entry = '.dev-agents-*';

        if (file_exists($gitignore)) {
            $contents = file_get_contents($gitignore);
            if (str_contains($contents, $entry)) {
                return;
            }
            file_put_contents($gitignore, $contents . "\n" . $entry . "\n");
        } else {
            file_put_contents($gitignore, $entry . "\n");
        }

        $io->write('<info>dev-agents: Added .dev-agents-* to .gitignore</info>');
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
