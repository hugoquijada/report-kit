<?php

namespace hqsoft\reportkit\document;

class Row {

  /** @var Column[] */
  private array $cols = [];
  private int $used = 0;
  private int $maxColumns;

  public function __construct($maxColumns = 24) {
    $this->maxColumns = $maxColumns;
  }

  public function col(int $span = 1, ?int $rowspan = null): Column {
    if ($span < 1) {
      throw new \InvalidArgumentException("El ancho debe ser al menos 1 columna.");
    }

    if ($this->used + $span > $this->maxColumns) {
      throw new \Exception("Las columnas exceden el total permitido ({$this->maxColumns}).");
    }

    $col = new Column($span, $rowspan);
    $this->cols[] = $col;
    $this->used += $span;

    return $col;
  }

  public function colText(int $span, string $text, ?string $align = null, bool $bold = false): self {
    $this->col($span)->text($text, $align, $bold);
    return $this;
  }

  /**
   * @return Column[]
   */
  public function getColumns(): array {
    return $this->cols;
  }
}
