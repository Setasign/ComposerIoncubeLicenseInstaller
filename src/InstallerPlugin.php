<?php

namespace setasign\ComposerIoncubeLicenseInstaller;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\InstallationManager;
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

        if ($package->getType() === 'ioncube-license') {
            // if a license was installed - copy all license files to all related packages
            $this->installLicenseInAllRelatedPackages(
                $event->getComposer()->getInstallationManager(),
                $event->getLocalRepo(),
                $package
            );
        } else {
            // if a non license was installed - check whether this package requires a license and copy all required
            // licenses into the package
            $this->installAllLicenseInPackage(
                $event->getComposer()->getInstallationManager(),
                $event->getLocalRepo(),
                $package
            );
        }
    }

    protected function installLicenseInAllRelatedPackages(
        InstallationManager $installationManager,
        RepositoryInterface $localRepo,
        PackageInterface $licensePackage
    )
    {
        $packageExtra = $licensePackage->getExtra();
        if (!array_key_exists('licenseValidFor', $packageExtra)) {
            // missing licenseValidFor entry in license
            return;
        }
        $licenseValidFor = $packageExtra['licenseValidFor'];

        $targetPackages = [];
        foreach ($licenseValidFor as $validFor) {
            foreach ($localRepo->findPackages($validFor) as $package) {
                $targetPackages[] = $package;
            }
        }

        if (count($targetPackages) === 0) {
            // license isn't used
            return;
        }

        foreach ($targetPackages as $targetPackage) {
            $this->installLicenseInPackage($installationManager, $licensePackage, $targetPackage);
        }
    }

    protected function installAllLicenseInPackage(
        InstallationManager $installationManager,
        RepositoryInterface $localRepo,
        PackageInterface $targetPackage
    ) {
        $installingPackageName = $targetPackage->getName();

        $licensePackages = [];
        foreach ($this->lookForIoncubeLicenses($localRepo) as $licensePackage) {
            foreach ($licensePackage->getExtra()['licenseValidFor'] as $validFor) {
                if ($validFor === $installingPackageName) {
                    $licensePackages[] = $licensePackage;
                }
            }
        }

        if (count($licensePackages) === 0) {
            // package doesn't have a license
            return;
        }

        foreach ($licensePackages as $licensePackage) {
            $this->installLicenseInPackage($installationManager, $licensePackage, $targetPackage);
        }
    }

    protected function installLicenseInPackage(
        InstallationManager $installationManager,
        PackageInterface $licensePackage,
        PackageInterface $targetPackage
    ) {

        $sourceDirectory = $installationManager->getInstallPath($licensePackage);
        if ($sourceDirectory === null || !is_dir($sourceDirectory)) {
            return;
        }

        $targetDirectory = $installationManager->getInstallPath($targetPackage);
        if ($targetDirectory === null || !is_dir($targetDirectory)) {
            return;
        }

        foreach (new \DirectoryIterator($sourceDirectory) as $fileInfo) {
            /**
             * @var \SplFileInfo $fileInfo
             */
            if ($fileInfo->isDir() || strtolower($fileInfo->getExtension()) !== 'icl') {
                continue;
            }
            copy($fileInfo->getPathname(), $targetDirectory . '/' . $fileInfo->getFilename());
        }
    }

    /**
     * @param RepositoryInterface $repository
     * @return PackageInterface[]
     */
    protected function lookForIoncubeLicenses(RepositoryInterface $repository)
    {
        return array_filter($repository->getPackages(), function (PackageInterface $package) {
            return (
                $package->getType() === 'ioncube-license' && array_key_exists('licenseValidFor', $package->getExtra())
            );
        });
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}
