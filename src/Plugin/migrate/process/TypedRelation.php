<?php

namespace Drupal\dgi_migrate\Plugin\migrate\process;

use Drupal\dgi_migrate\Plugin\migrate\process\SubProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Map to a "typed_relation" field.
 *
 * Example:
 * @code
 * process:
 *   - plugin: dgi_migrate.typed_relation
 *     source: '@name_node'
 *     xpath: @_xpath_thing
 *     field_name: 'node.islandora_object.field_linked_agent'
 *     default_role: 'relators:asn'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dgi_migrate.typed_relation"
 * )
 */
class TypedRelation extends SubProcess implements ContainerFactoryPluginInterface {

  /**
   * An associative array mapping codes to textual representations.
   *
   * @var array
   */
  protected $mapping;

  /**
   * The key on the row containing a \DOMXPath instance.
   *
   * @var string
   */
  protected $xpathKey;

  /**
   * The default role to assign, if none is mapped.
   *
   * @var string
   */
  protected $defaultRole;

  /**
   * The key from the parent dealio to use as the target ID in the relation.
   *
   * @var string
   */
  protected $entityKey;

  /**
   * Prefix of role codes in the field.
   *
   * @var string
   */
  protected $prefix;

  /**
   * Regex pattern consuming the suffix of the role.
   *
   * @var string
   */
  protected $textSuffixPattern;

  /**
   * Flag for the ::multiple().
   *
   * @var bool
   */
  protected $multipleValues;

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
    $this->multipleValues = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['mapping'])) {
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
    }

    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($node_list, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!($node_list instanceof \DOMNodeList)) {
      throw new MigrateException('The passed value is not a DOMNodeList instance.');
    }

    $results = [];

    foreach ($node_list as $node) {
      $transformed = $this->doTransform($node, $migrate_executable, $row, $destination_property);
      if ($this->multiple()) {
        $results = array_merge($results, $transformed);
      }
      elseif ($transformed) {
        $results[] = $transformed;
      }
    }

    return $results;
  }

  /**
   * Helper; wrap around the transform proper.
   *
   * @see ::transform()
   */
  protected function doTransform($node, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!($node instanceof \DOMElement)) {
      throw new MigrateException('The passed value is not a DOMElement instance.');
    }

    $xpath = $row->get($this->xpathKey);
    if (!($xpath instanceof \DOMXPath)) {
      throw new MigrateException('The "xpath" key does not point at a DOMXPath instance. Row: ' . var_export($row, TRUE));
    }

    $typed_rels = [];

    // Lookup the entity..
    $entity = parent::transform($node, $migrate_executable, $row, $destination_property)[$this->entityKey];

    // Map/reduce the roles.
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

    $count = count($typed_rels);
    if ($count > 1) {
      $this->multipleValues = TRUE;
      return $typed_rels;
    }
    elseif ($count === 1) {
      $this->multipleValues = FALSE;
      return reset($typed_rels);
    }
    else {
      $this->multipleValues = FALSE;
      return NULL;
    }
  }

  /**
   * Actually map roleTerms to roles.
   *
   * @param \DOMElement $node
   *   A node of a MODS "name" element, from which to scrape roles.
   * @param \DOMXPath $xpath
   *   The xpath instance to facilitate accessing the child role/roleTerm
   *   elements.
   *
   * @return array
   *   An array of roles from $this->mapping present on the given name.
   */
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
        var_dump("\n$content");

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

        $matches = preg_grep("{$sep}^{$quoted}{$this->textSuffixPattern}\${$sep}i", $this->mapping);
        if ($matches) {
          $roles = array_merge($roles, array_keys($matches));
          continue 2;
        }
      }
    }
    var_dump($roles);

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multipleValues;
  }

}
