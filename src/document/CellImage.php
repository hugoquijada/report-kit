<?php

namespace hqsoft\reportkit\document;

class CellImage {

  public function __construct(
    public string $src,
    public ?int $width = null,
    public ?int $height = null,
    public ?string $alt = null,
    public ?string $style = null
  ) {
  }
}
