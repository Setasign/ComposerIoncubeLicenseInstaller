# Composer Ioncube License Installer

[![Latest Stable Version](https://img.shields.io/packagist/v/setasign/composer-ioncube-license-installer.svg)](https://packagist.org/packages/setasign/composer-ioncube-license-installer)

The composer ioncube license installer is a plugin for [composer](https://getcomposer.org/) to automatically install [ioncube](https://www.ioncube.com/) licenses into a related composer package. 


## How does it work?
The license has to be a standalone composer package similar to this:
```composer
{ 
   "type":"ioncube-license",
   "name":"your-vendor/your-encoded-package-license",
   "license":"proprietary",
   "require":{ 
      "setasign/composer-ioncube-license-installer":"*"
   },
   "extra":{ 
      "licenseValidFor":[ 
         "your-vendor/your-encoded-package"
      ]
   }
}
```
All license files have to be on the root level of this package and must have the extension ".icl".

The plugin will listen to the EventDispatcher of composer and if one of the packages from "licenseValidFor" is installed or updated it will copy all ".icl" files into the package directory.



## License
ComposerIoncubeLicenseInstaller is licensed under the MIT License - see the LICENSE file for details
