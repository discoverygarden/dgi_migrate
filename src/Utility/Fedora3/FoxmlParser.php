<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

use Drupal\dgi_migrate\Utility\Fedora3\Element\DigitalObject;

class FoxmlParser extends AbstractParser {
  const READ_SIZE = 4096;
  protected $parser;
  protected $target;
  protected $file = NULL;
  protected $chunk = NULL;
  protected $foxmlParser;

  const MAP = [
    DigitalObject::TAG => DigitalObject::class,
  ];

  public function __construct() {
    $this->parser = xml_parser_create_ns();
    $this->foxmlParser = $this;

    xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, FALSE);
    xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, TRUE);
    xml_set_object($this->parser, $this);
    xml_set_element_handler($this->parser, 'tagOpen', 'tagClose');
    xml_set_character_data_handler($this->parser, 'characters');
  }

  public function __destruct() {
    xml_parser_free($this->parser);
  }

  public function parse($target) {
    $this->target = $target;

    $this->file = fopen($target, 'rb');
    try {
      while (!feof($this->file)) {
        $this->chunk = fread($this->file, static::READ_SIZE);
        $result = xml_parse($this->parser, $this->chunk, feof($this->file));
        // Error code "0" means incomplete parse, so we just need to feed it
        // some more.
        if ($result && xml_get_error_code($this->parser) !== 0) {
          throw new FoxmlParserException($this->parser);
        }
      }
      return $this->output;
    }
    finally {
      fclose($this->file);
      $this->file = NULL;
      $this->chunk = NULL;
      $this->target = NULL;
    }
  }
  public function getTarget() {
    return $this->target;
  }
  public function getOffset() {
    // XXX: Apparently, there may be differences in what
    // xml_get_current_byte_index() returns, based on what parser is used
    // (libxml2 vs expat); for example, "start element" having placed the offset
    // _after_ the started element for libxml2, while expat does not... somewhat
    // anecdotal, but something of which to be wary:
    // @see https://www.php.net/manual/en/function.xml-get-current-byte-index.php#56953
    //return ftell($this->file) - strlen($this->chunk) + xml_get_current_byte_index($this->parser);
    // TODO: Verify... _does_ this provide the total offset?
    return xml_get_current_byte_index($this->parser);
  }

  protected function pop() {
    $this->output = parent::pop();
    return $this->output;
  }

}
