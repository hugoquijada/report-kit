<?php

namespace hqsoft\reportkit\document;

class CellContent {

  public function __construct(
    public string $text,
    public bool $bold = false,
    public bool $italic = false,
    public ?string $color = null,
    public ?int $size = null,
    public ?string $font = null,
    public ?string $background = null,
    public ?string $decoration = null
  ) { }

}
