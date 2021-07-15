<?php

namespace Drupal\dgi_migrate_foxml_standard_mods\Plugin\migrate\process;

use Drupal\dgi_migrate\Plugin\migrate\process\SubProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Call a method on an object.
 *
 * Example:
 * @code
 * process:
 *   - plugin: dgi_migrate_foxml_standard_mods.typed_relation
 *     source: '@name_node'
 *     xpath: @_xpath_thing
 *     field_name: 'node.islandora_object.field_linked_agent'
 *     default_role: 'relators:asn'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate_foxml_standard_mods.typed_relation"
 * )
 */
class TypedRelation extends SubProcess implements ContainerFactoryPluginInterface {

  /**
   * An associative array mapping codes to textual representations.
   *
   * @var array
   */
  protected $mapping;

  protected $xpathKey;
  protected $defaultRole;
  protected $entityKey;
  protected $prefix;
  protected $textSuffixPattern;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!isset($this->configuration['xpath'])) {
      throw new \InvalidArgumentException('Missing the "xpath" argument.');
    }
    $this->xpathKey = $this->configuration['xpath'];
    $this->mapping = $this->configuration['mapping'] ?? [];
    $this->defaultRole = $this->configuration['default_role'] ?? NULL;
    $this->entityKey = $this->configuration['entity_key'] ?? 'target_id';
    $this->prefix = $this->configuration['role_prefix'] ?? 'relators:';
    $this->textSuffixPattern = $this->configuration['text_suffix_pattern'] ?? ' \\([a-z]{3}\\)';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_field_manager = $container->get('entity_field.manager');
    $field_type_plugin_manager = $container->get('plugin.manager.field.field_type');

    if (!isset($configuration['field_name'])) {
      throw new \InvalidArgumentException('Missing the "field_name" argument.');
    }

    list($entity_type, $bundle, $field_name) = explode('.', $configuration['field_name']);
    $field_def = $entity_field_manager->getFieldDefinitions($entity_type, $bundle)[$field_name];

    if ($field_def->getType() !== 'typed_relation') {
      throw new \InvalidArgumentException(strtr('Field of type :type passed; :required required.', [
        ':type' => $field_def->getType(),
        ':required' => 'typed_relation',
      ]));
    }

    $field_type = $field_type_plugin_manager->createInstance($field_def->getType(), [
      'field_definition' => $field_def,
      'name' => NULL,
      'parent' => NULL,
    ]);

    $configuration['mapping'] = $field_type->getRelTypes();

    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($node, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!($node instanceof \DOMElement)) {
      // TODO: Go boom.
    }

    $xpath = $row->get($this->xpathKey);
    if (!($xpath instanceof \DOMXPath)) {
      // TODO: Go boom.
    }

    $typed_rels = [];

    // Lookup the entity..
    $entity = parent::transform($node, $migrate_executable, $row, $destination_property)[$this->entityKey];

    // TODO: Map/reduce the roles
    $roles = $this->mapRoles($node, $xpath);

    $roles = array_unique($roles);

    if ($roles) {
      foreach ($roles as $role) {
        $typed_rels[] = [
          'rel_type' => $role,
          'target_id' => $entity,
        ];
      }
    }
    elseif (isset($this->defaultRole)) {
      $typed_rels[] = [
        'rel_type' => $this->defaultRole,
        'target_id' => $entity,
      ];
    }

    return $typed_rels;
  }

  public function mapRoles(\DOMElement $node, \DOMXPath $xpath) {
    $roles = [];

    foreach ($xpath->query('mods:role', $node) as $role_node) {
      foreach ($xpath->query('mods:roleTerm[@type="code"][normalize-space()]', $role_node) as $code) {
        $content = $xpath->evaluate('normalize-space(.)', $code);
        if (isset($this->mapping[$content])) {
          $roles[] = $content;
          continue 2;
        }
        elseif (isset($this->mapping["{$this->prefix}{$content}"])) {
          $roles[] = "{$this->prefix}{$content}";
          continue 2;
        }
      }
      foreach ($xpath->query('mods:roleTerm[@type="text" or not(@type)][normalize-space()]', $role_node) as $text) {
        $content = $xpath->evaluate('normalize-space(.)', $text);

        // XXX: Sometimes, there can be codes in "text"/untyped roleTerm.
        if (isset($this->mapping[$content])) {
          $roles[] = $content;
          continue 2;
        }
        elseif (isset($this->mapping["{$this->prefix}{$content}"])) {
          $roles[] = "{$this->prefix}{$content}";
          continue 2;
        }

        $sep = '/';
        $quoted = preg_quote($content, $sep);

        $matches = preg_grep("{$sep}{$quoted}{$this->textSuffixPattern}{$sep}i", $this->mapping);
        if ($matches) {
          $roles = array_merge($roles, array_keys($matches));
          continue 2;
        }
      }
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
