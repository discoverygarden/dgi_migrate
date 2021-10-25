<?php

namespace Drupal\Tests\dgi_migrate_foxml_standard_mods\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\migrate\Kernel\MigrateTestBase as UpstreamMigrateTestBase;

abstract class MigrateTestBase extends UpstreamMigrateTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'dgi_migrate',
    'dgi_migrate_paragraphs',
    'dgi_migrate_edtf_validator',
    'dgi_migrate_foxml_standard_mods',
    'migrate_plus',
    'node',
    'media',
    'file',
    'taxonomy',
    'text',
    'user',
    'image',
    'migrate_directory',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem() {
    parent::setUpFilesystem();

    $settings = [];

    $settings['file_private_path'] = $this->siteDirectory . '/private';
    mkdir($settings['file_private_path'], 0775);

    new Settings(Settings::getAll() + $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    #// XXX: We want to skip UpstreamMigrateTestBase's implementation, as we
    #// don't need to deal with a "source" database.
    #KernelTestBase::setUp();
    parent::setUp();

    $types = [
      'file',
      'media',
      'node',
      'taxonomy_vocabulary',
      'taxonomy_term',
      'user',
    ];
    foreach ($types as $type) {
      $this->installEntitySchema($type);
    }
  }

}
