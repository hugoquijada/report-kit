<?php

namespace hqsoft\reportkit\renderers\html;

use hqsoft\reportkit\document\Document;
use hqsoft\reportkit\renderers\IRenderer;

class HtmlRenderer implements IRenderer {

  private HtmlTableBuilder $builder;

  public function __construct() {
    $this->builder = new HtmlTableBuilder();
  }

  public function render(Document $doc): string {

    $tableHeader = $this->builder->buildHeader($doc->getHeaderRows());
    $tableBody = $this->builder->buildBody($doc);
    $tableFooter = $this->builder->buildFooter($doc->getFooterRows());
    
    $styles = $this->builder->getStyles();

    $template = __DIR__ . '/templates/base.php';

    ob_start();
    include $template;
    $html = ob_get_clean();

    return $html;
  }
}
