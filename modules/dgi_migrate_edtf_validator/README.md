# DGI Migrate EDTF Validator

## Introduction

A migration plugin that validates the dates in the EDTF format.

## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Configuration](#configuration)
* [Usage](#usage)
* [Troubleshooting/Issues](#troubleshootingissues)
* [Maintainers and Sponsors](#maintainers-and-sponsors)
* [Development/Contribution](#developmentcontribution)
* [License](#license)

## Requirements

This module requires the following modules/libraries:

* [migrate](https://www.drupal.org/project/migrate)
* [controlled_access_terms](https://github.com/Islandora/controlled_access_terms)

## Installation

Install as usual, see
[this](https://www.drupal.org/docs/extending-drupal/installing-modules) for
further information.

## Configuration

The plugin comes with some basic configuration:

- `ignore_empty` (optional): Boolean to ignore empty values, defaults to `TRUE`.
- `intervals` (optional): Boolean of whether this field is supporting intervals or not, defaults to `TRUE`.
- `sets` (optional): Boolean of whether this field is supporting sets or not, defaults to `TRUE`.
- `strict` (optional): Boolean of whether this field is supporting calendar dates or not, defaults to `FALSE`.

## Usage

When writing a migration, simply use the `dgi_migrate_edtf_validator` plugin.

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
