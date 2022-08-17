# DGI Migrate EDTF Validator

## Introduction

A migration plugin that validates the dates in the EDTF format.

## Requirements

This module requires the following modules/libraries:

* [migrate](https://www.drupal.org/project/migrate)
* [controlled_access_terms](https://github.com/Islandora/controlled_access_terms)

## Usage

When writing a migration, simply use the `dgi_migrate_edtf_validator` plugin.

## Configuration

The plugin comes with some basic configuration:

 - `ignore_empty` (optional): Boolean to ignore empty values, defaults to `TRUE`.
 - `intervals` (optional): Boolean of whether this field is supporting intervals or not, defaults to `TRUE`.
 - `sets` (optional): Boolean of whether this field is supporting sets or not, defaults to `TRUE`.
 - `strict` (optional): Boolean of whether this field is supporting calendar dates or not, defaults to `FALSE`.

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
