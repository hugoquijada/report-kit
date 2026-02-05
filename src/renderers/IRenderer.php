<?php

namespace hqsoft\reportkit\Renderers;

use hqsoft\reportkit\document\Document;

interface IRenderer {

  public function render(Document $doc): string;

}