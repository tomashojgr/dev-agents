<?php

declare(strict_types=1);

namespace DevAgents;

use Composer\Script\Event;

class Installer
{
    private const INCLUDE_LINE = "include vendor/yourname/dev-agents/Makefile.agents";
    private const MAKEFILE = 'Makefile';

    public static function install(Event $event): void
    {
        $io = $event->getIO();
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
