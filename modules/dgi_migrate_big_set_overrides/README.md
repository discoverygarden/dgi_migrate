# DGI Migrate Big Set Overrides

## Introduction

Big Set Overrides assists in extremely large migrations, which would typically see site slowdown and frequent failure. This module aims to reduce both of those. As such, it should only be enabled for the duration of a migration and is not recommended to be kept on otherwise.

## Table of Contents

* [Features](#features)
* [Installation](#installation)
* [Usage](#usage)
* [Troubleshooting/Issues](#troubleshootingissues)
* [Maintainers and Sponsors](#maintainers-and-sponsors)
* [Development/Contribution](#developmentcontribution)
* [License](#license)

## Features

Big Set Overrides is responsible for doing a few main things:

1. Disables the `repository_item_content_sync_helper_export` context so that `content_sync` doesn't export any nodes during the migration.
2. Disables the `repository_item_media_content_sync_helper_export` context so that `content_sync` doesn't export any media during the migration.
3. Disables the default Solr index, so items are not immediately indexed upon ingest.
4. Disables the `path_alias` title generation.

## Installation

Install as usual, see
[this](https://www.drupal.org/docs/extending-drupal/installing-modules) for
further information.

## Usage

Enable the module.

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
