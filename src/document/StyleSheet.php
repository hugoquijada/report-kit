<?php

namespace hqsoft\reportkit\document;

class StyleSheet {

  private array $styles = [];

  public function add(string $name, CellStyle $style): self {
    $this->styles[$name] = $style;
    return $this;
  }

  public function get(string $name): ?CellStyle {
    return $this->styles[$name] ?? null;
  }

  public function has(string $name): bool {
    return isset($this->styles[$name]);
  }
}
