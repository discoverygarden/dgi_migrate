# DGI Migrate Alter

## Introduction
A module to easily alter migrations using the `MigrationAlter` plugin.

## Features
The module adds the `MigrationAlter` plugin base, which allows for the easy alteration of migrations.

The `MigrationAlter` plugin base has four fields:
 - `id`
 - `label`
 - `description`
 - `migration_id`
   - The id of the migration you wish to alter

The path to place the alter plugins is as follows:
 - `module/Plugin/dgi_migrate_alter/spreadsheet`
 - `module/Plugin/dgi_migrate_alter/foxml`

There is no strict difference between the two, and are pathed as such strictly for organization.

The alter itself should be written in the `alter` function.

## Installation

Install as usual, see
[this](https://drupal.org/documentation/install/modules-themes/modules-8) for
further information.

## Troubleshooting/Issues

Having problems or solved a problem? Contact
[discoverygarden](http://support.discoverygarden.ca).

## Maintainers and Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development/Contribution

If you would like to contribute to this module, please check out github's helpful
[Contributing to projects](https://docs.github.com/en/get-started/quickstart/contributing-to-projects) documentation and Islandora community's [Documention for developers](https://islandora.github.io/documentation/contributing/CONTRIBUTING/#github-issues) to create an issue or pull request and/or
contact [discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
