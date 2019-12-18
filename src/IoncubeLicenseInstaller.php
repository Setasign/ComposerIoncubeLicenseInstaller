<?php

namespace setasign\ComposerIoncubeLicenseInstaller;

use Composer\Composer;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

class IoncubeLicenseInstaller extends LibraryInstaller
{
    public function __construct(
        IOInterface $io,
        Composer $composer,
        Filesystem $filesystem = null,
        BinaryInstaller $binaryInstaller = null
    ) {
        parent::__construct($io, $composer, 'ioncube-license', $filesystem, $binaryInstaller);
    }

    public function getInstallPath(PackageInterface $package)
    {
        return dirname(parent::getInstallPath($package));
    }

    protected function getPackageBasePath(PackageInterface $package)
    {
        return dirname(parent::getPackageBasePath($package));
    }
}
