# DGI Migrate DSpace

## Introduction

A module which provides the migrations for importing [AIP](https://wiki.lyrasis.org/display/DSDOC6x/DSpace+AIP+Format) 5.5 ZIPs, using a METS/MODS mapping.

It also provides a single command, `dgi_migrate_dspace:list`, which outputs a CSV formatted list of each node ID and URL, and its respective handle URL.

## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
* [Troubleshooting/Issues](#troubleshootingissues)
* [Maintainers and Sponsors](#maintainers-and-sponsors)
* [Development/Contribution](#developmentcontribution)
* [License](#license)

## Requirements

This module requires the following modules/libraries:

* [migrate](https://www.drupal.org/project/migrate)
* [dgi_migrate](https://github.com/discoverygarden/dgi_migrate)

## Installation

Install as usual, see
[this](https://www.drupal.org/docs/extending-drupal/installing-modules) for
further information.

## Usage

The migration can be run using the `dspace_to_dgis` migration group.

For running the command, use `dgi_migrate_dspace:list`, and provide the optional `--uri` flag for your respective environment.

## Troubleshooting/Issues

Having problems or solved a problem? Contact
[discoverygarden](http://support.discoverygarden.ca).

## Maintainers and Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

Sponsors:

* [FLVC](https://www.flvc.org)

## Development/Contribution

If you would like to contribute to this module, please check out github's helpful
[Contributing to projects](https://docs.github.com/en/get-started/quickstart/contributing-to-projects) documentation and Islandora community's [Documention for developers](https://islandora.github.io/documentation/contributing/CONTRIBUTING/#github-issues) to create an issue or pull request and/or
contact [discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
