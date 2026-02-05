<?php

namespace hqsoft\reportkit\document;

class DocumentConfig {

  public function __construct(
    public int $maxColumns = 24
  ) { }
  
}