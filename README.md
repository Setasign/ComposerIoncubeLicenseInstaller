# Composer Ioncube License Installer

[![Latest Stable Version](https://img.shields.io/packagist/v/setasign/composer-ioncube-license-installer.svg)](https://packagist.org/packages/setasign/composer-ioncube-license-installer)

The composer ioncube license installer is a plugin for [composer](https://getcomposer.org/) to automatically install [ioncube](https://www.ioncube.com/) licenses into a related composer package. 


## How does it work?
The license has to be a standalone composer package simular to this:
```composer
{ 
   "type":"ioncube-license",
   "name":"setasign/setapdf-core_ioncube_license",
   "license":"proprietary",
   "require":{ 
      "setasign/composer-ioncube-license-installer":"*"
   },
   "extra":{ 
      "licenseValidFor":[ 
         "setasign/setapdf-core_ioncube_php5",
         "setasign/setapdf-core_ioncube_php5.3",
         "setasign/setapdf-core_ioncube_php5.4",
         "setasign/setapdf-core_ioncube_php5.6",
         "setasign/setapdf-core_ioncube_php7.1"
      ]
   }
}
```
On license file(s) has to be on the root level of this package and must have the extension ".icl".

The plugin will listen to the eventDispatcher of composer and if one of the packages from "licenseValidFor" is installed or updated it will copy all ".icl" files into the package directory.



## License
Monolog is licensed under the MIT License - see the LICENSE file for details
