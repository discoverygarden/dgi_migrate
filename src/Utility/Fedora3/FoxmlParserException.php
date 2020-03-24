<?php

namespace Drupal\dgi_migrate\Utility\Fedora3;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class FoxmlParserException extends \Exception {

  use StringTranslationTrait;

  protected $parser;
  public function __construct($parser) {
    $this->parser = $parser;

    parent::__construct($this->generateMessage());
  }

  protected function generateMessage() {
    $code = xml_get_error_code();
    $string = xml_error_string($code);

    return $this->t('XML Parsing error; @message at line @line, column @col (byte offset @offset)', [
      '@message' => xml_error_string($code),
      '@line' => xml_get_current_line_number($this->parser),
      '@col' => xml_get_current_column_number($this->parser),
      '@offset' => xml_get_current_byte_index($this->parser),
    ]);
  }
}
