<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Exception;

/**
 * Generate exceptions for the parser.
 */
class FoxmlParserException extends Exception {

  use StringTranslationTrait;

  /**
   * Reference to the xml_parse parser.
   *
   * @var resource
   */
  protected $parser;

  /**
   * Constructor.
   */
  public function __construct($parser) {
    $this->parser = $parser;

    parent::__construct($this->generateMessage());
  }

  /**
   * Helper; generate the exception message.
   *
   * @return string
   *   The message to emit with the exception.
   */
  protected function generateMessage() {
    $code = xml_get_error_code($this->parser);

    return $this->t('XML Parsing error; @message (@code) at line @line, column @col (byte offset @offset)', [
      '@message' => xml_error_string($code),
      '@code' => $code,
      '@line' => xml_get_current_line_number($this->parser),
      '@col' => xml_get_current_column_number($this->parser),
      '@offset' => xml_get_current_byte_index($this->parser),
    ]);
  }

}
