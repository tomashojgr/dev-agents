<?php

declare(strict_types=1);

namespace DevAgents;

use Composer\IO\IOInterface;

class Installer
{
    private const INCLUDE_LINE = "include vendor/tomashojgr/dev-agents/Makefile.agents";
    private const MAKEFILE = 'Makefile';

    public static function run(IOInterface $io): void
    {
        // Check claude CLI is available
        exec('which claude 2>/dev/null', $out, $code);
        if ($code !== 0) {
            $io->writeError('<warning>dev-agents: claude CLI not found in PATH. Install it from https://claude.ai/code</warning>');
        }

        $makefile = getcwd() . '/' . self::MAKEFILE;

        // Create Makefile if it doesn't exist
        if (!file_exists($makefile)) {
            file_put_contents($makefile, self::INCLUDE_LINE . "\n");
            $io->write('<info>dev-agents: Created Makefile with dev-agents include</info>');
            return;
        }

        // Check if already included
        $contents = file_get_contents($makefile);
        if (str_contains($contents, 'Makefile.agents')) {
            $io->write('<info>dev-agents: Makefile already configured</info>');
            return;
        }

        // Prepend include line
        file_put_contents($makefile, self::INCLUDE_LINE . "\n\n" . $contents);
        $io->write('<info>dev-agents: Added dev-agents include to Makefile</info>');
    }
}
