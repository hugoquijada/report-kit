<?php

namespace hqsoft\reportkit\renderers\pdf;

use hqsoft\reportkit\document\Document;
use hqsoft\reportkit\renderers\html\HtmlTableBuilder;
use hqsoft\reportkit\Renderers\IRenderer;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PdfRenderer implements IRenderer {

  private HtmlTableBuilder $builder;
  private array $config;
  private string $template;

  public function __construct(array $config = [], ?string $template = null) {
    $this->config = array_merge([
      'format' => 'A4',
      'margin_top' => 10,
      'margin_bottom' => 10,
      'margin_left' => 10,
      'margin_right' => 10,
    ], $config);

    // plantilla mínima por defecto
    $this->template = $template ?? __DIR__ . '/templates/base.php';
  }

  public function render(Document $doc): string {
    $mpdf = new Mpdf($this->config);
    $mpdf->shrink_tables_to_fit = 0;
    $usableWidth = $mpdf->w - $mpdf->lMargin - $mpdf->rMargin;
    $colWidthMm = $usableWidth / $doc->getMaxColumns();
    $this->builder = new HtmlTableBuilder(colWidthMm: $colWidthMm);

    $styles = $this->builder->getStyles();
    $colgroup = $this->buildPdfColgroup($mpdf, $doc->getMaxColumns());
    $header = $this->builder->buildHeader($doc->getHeaderRows());
    $body = $this->builder->buildBody($doc);
    $footer = $this->builder->buildFooter($doc->getFooterRows());

    $vars = compact('styles', 'colgroup', 'header', 'body', 'footer');
    $html = $this->renderTemplate($this->template, $vars);

    if ($header) {
      $mpdf->SetHTMLHeader($this->renderTemplate(__DIR__ . '/templates/header.php', compact('colgroup', 'header')));
    }
    if ($footer) {
      $mpdf->SetHTMLFooter($this->renderTemplate(__DIR__ . '/templates/footer.php', compact('colgroup', 'footer')));
    }

    $html = $this->fixImagePaths($html);
    $mpdf->WriteHTML($html);
    return $mpdf->Output('', Destination::STRING_RETURN);
  }

  private function renderTemplate(string $file, array $vars): string {
    extract($vars, EXTR_SKIP);
    ob_start();
    include $file;
    return ob_get_clean();
  }

  private function fixImagePaths(string $html): string {
    return preg_replace_callback('/<img[^>]+src="([^"]+)"/', function ($m) {
      $src = $m[1];

      if (str_starts_with($src, 'http')) {
        return $m[0]; // dejar como está
      }

      // convertir a ruta absoluta
      $abs = realpath($src);
      if ($abs) {
        return str_replace($src, $abs, $m[0]);
      }

      return $m[0];
    }, $html);
  }

  private function buildPdfColgroup(Mpdf $mpdf, int $cols = 24): string {
    $pageWidth = $mpdf->w - $mpdf->lMargin - $mpdf->rMargin;
    $colWidth = $pageWidth / $cols;

    $html = "<colgroup>";
    for ($i = 0; $i < $cols; $i++) {
      $html .= "<col style=\"width:{$colWidth}mm;\"></col>";
    }
    $html .= "</colgroup>";

    return $html;
  }

}
