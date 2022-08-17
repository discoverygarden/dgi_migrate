# DGI Migrate ImageMagick Cleanup

## Introduction

This module is presented as a temporary patch, meant to be enabled when running large migrations. ImageMagick's temporary files tend to stick around too long, to the extent that a drive can reach max capacity, most notably during migrations. As such, the module decreases the time that ImageMagick temporary files are stored in the system.

## Requirements

This module requires the following modules/libraries:

* [migrate](https://www.drupal.org/project/migrate)
* [imagemagick](https://www.drupal.org/project/imagemagick)

## Usage

Enable the module.

## Installation

Install as usual, see
[this](https://drupal.org/documentation/install/modules-themes/modules-8) for
further information.

## Troubleshooting/Issues

Having problems or solved a problem? Contact
[discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module create an issue, pull request
and or contact
[discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
