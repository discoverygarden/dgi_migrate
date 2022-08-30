# DGI Migrate Paragraphs

## Introduction

A migration plugin that facilitates the process of migrating data for paragraphs.

## Requirements

This module requires the following modules/libraries:

* [migrate](https://www.drupal.org/project/migrate)
* [paragraphs](https://www.drupal.org/project/paragraphs)

## Usage

When writing a migration, simply use the `dgi_paragraph_generate` plugin.

## Configuration

The plugin comes with some basic configuration:

 - `type`: The paragraph bundle with which to generate a paragraph.
 - `values`: A mapping of values to use to create the paragraph. Exact contents vary based upon the "process_values" flag.
 - `validate`: A boolean flag indicating whether the contents of the paragraph should be validated; defaults to `FALSE`.
 - `process_values`: A boolean flag indicating whether values should be mapped directly from the current row, or if we should kick of something of a subprocess flow, with nested process plugin configurations. Defaults to `FALSE`.
 - `propagate_skip`: A boolean indicating how a "MigrateSkipRowException" should be handled when processing a specific paragraph entity. `TRUE` to also skip import of the parent entity; otherwise, `FALSE` to skip only those sub-entities throwing the exception. Defaults to `TRUE`.
 - `parent_row_key`: A string representing a key under which to expose the the contents of the row to subprocessing with process_values. The contents of the row are split into two keys `source` and `dest`, containing respectively the source and (current) destination values of the parent row. Defaults to `parent_row`.
 - `parent_value_key`: A string representing a key under which to expose the value received by the `dgi_paragraph_generate` plugin itself, to make it available to subprocessing. Defaults to `parent_value`.

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
