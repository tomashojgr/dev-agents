<?php

declare(strict_types=1);

namespace DevAgents;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE  => 'onPackageUpdate',
        ];
    }

    public function onPackageInstall(PackageEvent $event): void
    {
        $package = $event->getOperation()->getPackage();
        if ($package->getName() === 'tomashojgr/dev-agents') {
            Installer::run($this->io);
        }
    }

    public function onPackageUpdate(PackageEvent $event): void
    {
        $package = $event->getOperation()->getTargetPackage();
        if ($package->getName() === 'tomashojgr/dev-agents') {
            Installer::run($this->io);
        }
    }
}
