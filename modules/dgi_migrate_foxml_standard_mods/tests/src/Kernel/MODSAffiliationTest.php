<?php

namespace Drupal\Tests\dgi_migrate_foxml_standard_mods\Kernel;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * @group dgi_migrate_foxml_standard_mods
 */
class MODSAffiliationTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // TODO: Lob our fixtures into 'private://exports', after the VFS business
    // is setup.
    #$exports = 'private://exports';
    $exports = "{$this->siteDirectory}/private/exports";
    mkdir($exports);
    $fixture = 'islandora:37058.xml';
    copy(__DIR__ . "/../../fixtures/affiliation/{$fixture}", "{$exports}/{$fixture}");

    $this->toProtect = [
      "{$exports}/{$fixture}",
      $exports,
    ];

    foreach ($this->toProtect as $protec) {
      $this->assertEqual(TRUE, chmod($protec, $this->getReadonlyMode($protec)), "chmod'd thing");
    }

    $this->setTestLogger();
  }

  protected function getReadonlyMode($name) {
    if (is_dir($name)) {
      return 0555;
    }
    elseif (is_file($name)) {
      return 0444;
    }
    else {
      throw new \InvalidArgumentException('What have you passed me?');
    }
  }

  public function testAffiliations() {
    foreach ($this->toProtect as $to_protect) {
      $this->assertEqual(FALSE, is_writable($to_protect));
    }

    $this->executeMigrations([
      'dgis_foxml_files',
      'dgis_stub_nodes',
      'dgis_stub_terms_person',
      'dgis_stub_terms_corporate_body',
      'dgis_stub_terms_affiliate',
      'dgis_stub_terms_generic',
      'dgis_nodes',
    ]);

    $alpha_tid = 
  }

  protected function executeMigration($migration) {
    parent::executeMigration($migration);

    #print_r([
    #  'id' => $this->migration->id(),
    #  'count' => count($this->migration->getSourcePlugin()),
    #  'processed' => $this->migration->allRowsProcessed(),
    #  'messages' => iterator_to_array($this->migration->getIdMap()->getMessages()),
    #]);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMigration(MigrationInterface $migration) {
    parent::prepareMigration($migration);

    switch ($migration->id()) {
      case 'dgis_nodes':
        // XXX: To avoid requiring the full field config.
        $to_add_mapping = [
          ['field_linked_agent', 1],
          ['field_organizations', 1],
        ];
        $process = $migration->getProcess();

        foreach ($to_add_mapping as $to_add) {
          $config =& NestedArray::getValue($process, $to_add);
          $config += [
            'mapping' => [$this->randomMachineName() => $this->randomMachineName()],
          ];
        }
        unset($config);

        $migration->setProcess($process);
        break;

    }
  }

}
