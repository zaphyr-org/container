# Changelog

All notable changes to this project will be documented in this file,
in reverse chronological order by release.

## [v1.1.2](https://github.com/zaphyr-org/container/compare/1.1.1...1.1.2) [2025-02-09]

### New:
* Added generics to Container::get method and TagGenerator class

### Fixed:
* Removed PHP 8.4 deprecations
 
## [v1.1.1](https://github.com/zaphyr-org/container/compare/1.1.0...1.1.1) [2023-11-15]

### New:
* Added `.vscode/` to .gitignore file

### Changed:
* Improved unit tests and moved tests to "Unit" directory

### Removed:
* Removed phpstan-phpunit from composer require-dev

### Fixed:
* Removed .dist from phpunit.xml in .gitattributes export-ignore

## [v1.1.0](https://github.com/zaphyr-org/container/compare/1.0.0...1.1.0) [2023-08-17]

### New:
* Added `bindInstance` method to Container class

### Changed:
* Renamed `Zaphyr\Container\Contracts\BootableServiceProvider` to `Zaphyr\Container\Contracts\BootableServiceProviderInterface`

## v1.0.0 [2023-07-18]

### New:
* First stable release version
