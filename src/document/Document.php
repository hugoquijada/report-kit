<?php

namespace hqsoft\reportkit\document;

class Document {

  const TYPE_CSV = 'csv';
  const TYPE_HTML = 'html';
  const TYPE_PDF = 'pdf';
  const TYPE_SPREADSHEET = 'spreadsheet';

  private ?DocumentConfig $config;
  private array $headerRows = [];
  private array $footerRows = [];
  private array $rows = [];
  private $maxColumns = 24;
  private StyleSheet $styleSheet;

  /**
   * @param DocumentConfig|null $config
   */
  public function __construct(?DocumentConfig $config = null) {
    $this->config = $config ?? new DocumentConfig();
    $this->styleSheet = new StyleSheet();
    if ($this->config->maxColumns > 0) {
      $this->maxColumns = $this->config->maxColumns;
    }
  }

  public static function create(?DocumentConfig $config = null): self {
    return new self($config);
  }

  public function addStyle(string $name, CellStyle $style): self {
    $this->styleSheet->add($name, $style);
    return $this;
  }

  public function getStyle(string $name): ?CellStyle {
    return $this->styleSheet->get($name);
  }

  /**
   * @param callable(Row):void $callback
   * @return $this
   */
  public function header(callable $callback): self {
    $row = new Row($this->maxColumns);
    $callback($row);
    $this->headerRows[] = $row;
    return $this;
  }

  /**
   * @param callable(Row):void $callback
   * @return $this
   */
  public function footer(callable $callback): self {
    $row = new Row($this->maxColumns);
    $callback($row);
    $this->footerRows[] = $row;
    return $this;
  }

  public function getMaxColumns() {
    return $this->maxColumns;
  }

  /**
   * @param callable(Row):void $callback
   * @return $this
   */
  public function row(callable $callback): self {
    $row = new Row($this->maxColumns);
    $callback($row);
    $this->rows[] = $row;
    return $this;
  }

  /**
   * @return Row[]
   */
  public function getRows(): array {
    return $rows = $this->rows;
  }

  /** 
   * @return Row[]
   */
  public function getHeaderRows(): array {
    return $this->headerRows;
  }

  /** 
   * @return Row[]
   */
  public function getFooterRows(): array {
    return $this->footerRows;
  }
}
