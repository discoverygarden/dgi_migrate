# DGI Migrate

## Introduction

A module to facilitate I7 to I8 migration.

## Requirements

This module requires the following modules/libraries:

* [migrate](https://www.drupal.org/project/migrate)
* [migrate_plus](https://www.drupal.org/project/migrate_plus)
* [migrate_directory](https://www.drupal.org/project/migrate_directory)
* [islandora](https://github.com/Islandora/islandora/tree/8.x-1.x)
* [islandora_drush_utils](https://github.com/discoverygarden/islandora_drush_utils)
* [foxml](https://github.com/discoverygarden/foxml)

## Included modules

DGI Migrate has a suite of submodules to assist in the migration process.

* [devel](https://github.com/discoverygarden/dgi_migrate/tree/2.x/modules/devel/README.md)
* [dgi_migrate_big_set_overrides](https://github.com/discoverygarden/dgi_migrate/tree/2.x/modules/dgi_migrate_big_set_overrides/README.md)
* [dgi_migrate_dspace](https://github.com/discoverygarden/dgi_migrate/tree/2.x/modules/dgi_migrate_dspace/README.md)
* [dgi_migrate_edtf_validator](https://github.com/discoverygarden/dgi_migrate/tree/2.x/modules/dgi_migrate_edtf_validator/README.md)
* [dgi_migrate_foxml_standard_mods](https://github.com/discoverygarden/dgi_migrate/tree/2.x/modules/dgi_migrate_foxml_standard_mods/README.md)
* [dgi_migrate_imagemagick_cleanup](https://github.com/discoverygarden/dgi_migrate/tree/2.x/modules/dgi_migrate_imagemagick_cleanup/README.md)
* [dgi_migrate_paragraphs](https://github.com/discoverygarden/dgi_migrate/tree/2.x/modules/dgi_migrate_paragraphs/README.md)
* [dgi_migrate_regenerate_pathauto_aliases](https://github.com/discoverygarden/dgi_migrate/blob/2.x/modules/dgi_migrate_regenerate_pathauto_aliases/README.md)

## Usage

An improved migration import command `migrate:batch-import` is included.
An example FOXML migration that can be used as a starting point is provided.
It illustrates the usage of migrate plugins that have been created to
facilitate FOXML and large data processesing.
* `dgi_migrate.process.xml.xpath` is limited to xpath 1.0.

## Installation

Install as usual, see
[this](https://drupal.org/documentation/install/modules-themes/modules-8) for
further information.

## Troubleshooting/Issues

Having problems or solved a problem? Contact
[discoverygarden](http://support.discoverygarden.ca).

### Known Issues:
* `php://filter` use can lead to large memory usage
    * we should probably look at rolling another stream wrapper to wrap up our
usage of OpenSSL to Base64 decode
* There are some expensive assertions made in the code,
particularly regarding binary datastream content with digests. Assertions should
typically be disabled in production environments, so these shouldn't have any
impact on execution there; however, in development environments, could
potentially lead to issues, especially with larger datastreams, exacerbated by
the `php://filter` usage to Base64-decode the contents
    * hesitant to remove the assertions without having any other mechanism to
    * could instead roll some unit tests?

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module create an issue, pull request
and or contact
[discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
