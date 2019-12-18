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
        $operation = $event->getOperation();
        /**
         * @var OperationInterface $operation
         */
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            return;
        }
        $packageName = $package->getName();

        $licensePackages = [];
        foreach ($this->lookForIoncubeLicenses($event->getInstalledRepo()) as $licensePackage) {
            $licenseValidFor = $licensePackage->getExtra()['licenseValidFor'];
            foreach ($licenseValidFor as $validFor) {
                if ($validFor !== $packageName) {
                    continue;
                }
                $licensePackages[] = $licensePackage;
            }
        }

        if (count($licensePackages) === 0) {
            // package doesn't have a license
            return;
        }

        $installationManager = $event->getComposer()->getInstallationManager();
        $targetDirectory = $installationManager->getInstallPath($package);
        if (!is_dir($targetDirectory)) {
            return;
        }

        foreach ($licensePackages as $licensePackage) {
            /**
             * @var PackageInterface $licensePackage
             */
            $directory = $installationManager->getInstallPath($licensePackage);
            if ($directory === null || !is_dir($directory)) {
                continue;
            }

            foreach (new \DirectoryIterator($directory) as $fileInfo) {
                /**
                 * @var \SplFileInfo $fileInfo
                 */
                if ($fileInfo->isDir() || strtolower($fileInfo->getExtension()) !== 'icl') {
                    continue;
                }
                copy($fileInfo->getPathname(), $targetDirectory . '/' . $fileInfo->getFilename());
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
            return $package->getType() === 'ioncube-license' && array_key_exists('licenseValidFor', $packageExtra);
        });
    }
}
