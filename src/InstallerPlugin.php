<?php

namespace setasign\ComposerIoncubeLicenseInstaller;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\RepositoryInterface;

class InstallerPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $eventDispatcher = $composer->getEventDispatcher();
        $eventDispatcher->addListener(PackageEvents::POST_PACKAGE_INSTALL, [$this, 'onInstallOrUpdate']);
        $eventDispatcher->addListener(PackageEvents::POST_PACKAGE_UPDATE, [$this, 'onInstallOrUpdate']);
    }

    public function onInstallOrUpdate(PackageEvent $event)
    {
        $licensePackagesGroupedByMainPackage = [];
        foreach ($this->lookForIoncubeLicenses($event->getInstalledRepo()) as $package) {
            $mainPackage = $package->getExtra()['mainPackage'];
            $licensePackagesGroupedByMainPackage[$mainPackage][] = $package;
        }

        foreach ($event->getOperations() as $operation) {
            /**
             * @var OperationInterface $operation
             */
            if ($operation instanceof InstallOperation) {
                $package = $operation->getPackage();
            } elseif ($operation instanceof UpdateOperation) {
                $package = $operation->getTargetPackage();
            } else {
                continue;
            }
            $packageName = $package->getName();
            $targetDirectory = $package->getTargetDir();

            if (!array_key_exists($packageName, $licensePackagesGroupedByMainPackage)) {
                // package doesn't have a license
                continue;
            }

            foreach ($licensePackagesGroupedByMainPackage[$packageName] as $license) {
                /**
                 * @var PackageInterface $license
                 */
                $directory = $license->getTargetDir();
                foreach (glob($directory . DIRECTORY_SEPARATOR . '*.icl') as $licenseFile) {
                    copy($licenseFile, $targetDirectory);
                }
            }
        }
    }

    /**
     * @param RepositoryInterface $repository
     * @return PackageInterface[]
     */
    protected function lookForIoncubeLicenses(RepositoryInterface $repository)
    {
        return array_filter($repository->getPackages(), function (PackageInterface $package) {
            $packageExtra = $package->getExtra();
            return $package->getType() === 'ioncube-license' && array_key_exists('mainPackage', $packageExtra);
        });
    }
}
