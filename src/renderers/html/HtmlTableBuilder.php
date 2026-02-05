<?php

namespace hqsoft\reportkit\renderers\html;

use hqsoft\reportkit\document\CellContent;
use hqsoft\reportkit\document\CellImage;
use hqsoft\reportkit\document\Document;
use hqsoft\reportkit\document\Row;

class HtmlTableBuilder {

  private ?float $colWidthMm = null;

  public function __construct(?float $colWidthMm = null) {
    $this->colWidthMm = $colWidthMm;
  }

  private function style(array $data): string {
    $styles = '';
    if (!empty($data['align'])) {
      $styles .= "text-align:{$data['align']};";
    }
    if (!empty($data['bold'])) {
      $styles .= "font-weight:bold;";
    }
    return $styles;
  }

  private function borderStyle(array $borders): string {
    $style = '';
    foreach ($borders as [$side, $color, $thick]) {
      if ($side === 'all') {
        $style .= "border:{$thick}px solid {$color};";
      } else {
        $style .= "border-{$side}: {$thick}px solid {$color};";
      }
    }
    return $style;
  }

  private function formatValue(string $value, ?string $format): string {
    if ($format === null) {
      return $value;
    }

    // Si no es numérico, retornar tal cual (excepto para fechas)
    $trimmed = trim($value);
    if (!is_numeric($trimmed) && !in_array($format, ['date', 'datetime', 'time'])) {
      return $value;
    }

    switch ($format) {
      case 'number':
        return number_format((float)$trimmed, 2, '.', ',');

      case 'currency':
        return '$' . number_format((float)$trimmed, 2, '.', ',');

      case 'percentage':
        return number_format((float)$trimmed, 2, '.', ',') . '%';

      case 'date':
        // Asumir timestamp o formato ISO
        $timestamp = is_numeric($trimmed) ? (int)$trimmed : strtotime($trimmed);
        return $timestamp ? date('d/m/Y', $timestamp) : $value;

      case 'datetime':
        $timestamp = is_numeric($trimmed) ? (int)$trimmed : strtotime($trimmed);
        return $timestamp ? date('d/m/Y H:i:s', $timestamp) : $value;

      case 'time':
        $timestamp = is_numeric($trimmed) ? (int)$trimmed : strtotime($trimmed);
        return $timestamp ? date('H:i:s', $timestamp) : $value;

      default:
        // Formato personalizado - intentar aplicar con number_format si es numérico
        if (is_numeric($trimmed)) {
          return number_format((float)$trimmed, 2, '.', ',');
        }
        return $value;
    }
  }

  public function buildHeader(array $headerRows = []): string {
    if (empty($headerRows)) {
      return '';
    }

    $html = '<thead>';
    /** @var Row[] $headerRows */
    foreach ($headerRows as $row) {
      $html .= $this->renderRow($row);
    }

    $html .= '</thead>';
    return $html;
  }

  public function buildFooter(array $footerRows): string {
    if (empty($footerRows)) {
      return '';
    }

    $html = '<tfoot>';

    foreach ($footerRows as $row) {
      $html .= $this->renderRow($row);
    }

    $html .= '</tfoot>';

    return $html;
  }

  public function buildBody(Document $doc): string {
    $html = '<tbody>';
    $html .= $this->buildCalibrationRow($doc->getMaxColumns(), $this->colWidthMm);
    foreach ($doc->getRows() as $row) {
      $html .= $this->renderRow($row);
    }

    $html .= '</tbody>';
    return $html;
  }

  public function buildColgroup(int $columns = 24): string {
    $html = '<colgroup>';
    $width = 100 / $columns;
    for ($i = 0; $i < $columns; $i++) {
      $html .= "<col style=\"width:{$width}%\">";
    }
    $html .= '</colgroup>';
    return $html;
  }

  private function renderCellContents(array $contents, ?string $format = null): string {
    $html = '';

    foreach ($contents as $item) {
      if (is_string($item)) {
        $html .= $item;
        continue;
      }

      if ($item instanceof CellContent) {
        $style = '';
        if ($item->bold) $style .= 'font-weight:bold;';
        if ($item->italic) $style .= 'font-style:italic;';
        if ($item->color) $style .= "color:{$item->color};";
        if ($item->size) $style .= "font-size:{$item->size}px;";
        if ($item->font) $style .= "font-family:'{$item->font}',sans-serif;";
        if ($item->background) $style .= "background-color:{$item->background};";
        if ($item->decoration) $style .= "text-decoration:{$item->decoration};vertical-align:top;";

        $text = $format ? $this->formatValue($item->text, $format) : $item->text;
        $html .= "<span style=\"{$style}\">" . htmlspecialchars($text) . "</span>";
        continue;
      }

      if ($item instanceof CellImage) {
        $style = '';

        $w = $item->width ? "width=\"{$item->width}\"" : "";
        $h = $item->height ? "height=\"{$item->height}\"" : "";

        $html .= "<img src=\"{$item->src}\" $w $h style=\"$style\">";
        continue;
      }
    }

    return $html;
  }

  private function renderRow(Row $row, bool $withWidth = false): string {
    $html = '<tr>';
    foreach ($row->getColumns() as $col) {
      $data = $col->toArray();
      $colspan = $data['span'];
      $rowspan = '';
      if (isset($data['rowspan'])) {
        $rowspan = "rowspan=\"{$data['rowspan']}\"";
      }
      $styles = $this->style($data);
      $borderStyle = $this->borderStyle($data['borders']);
      $format = $data['format'] ?? null;
      $cellHtml = $this->renderCellContents($data['contents'], $format);
      $width = '';
      if ($withWidth && $this->colWidthMm !== null) {
        $width = ($this->colWidthMm * $colspan) . 'mm;';
        $styles .= "width:{$width}";
      }
      $cellStyle = '';
      foreach ($data['style'] as $key => $value) {
        if (is_array($value)) continue;
        $cellStyle .= "{$key}:{$value};";
      }
      $html .= "<td colspan=\"{$colspan}\" {$rowspan} style=\"{$styles}{$borderStyle}{$cellStyle}\">{$cellHtml}</td>";
    }
    $html .= '</tr>';
    return $html;
  }

  public function getStyles(): string {
    $base = file_get_contents(__DIR__ . '/templates/styles.css');
    return $base;
  }

  private function buildCalibrationRow(int $columns = 24, ?int $colWidthMm = null): string {
    if ($colWidthMm === null) {
      return '';
    }
    $html = '<tr>';
    for ($i = 0; $i < $columns; $i++) {
      $w = $colWidthMm . 'mm';
      $html .= "<td style=\"width:{$w}; max-width:{$w}; padding:0; margin:0; border:none; font-size:0; line-height:0;\">&nbsp;</td>";
    }
    $html .= '</tr>';
    return $html;
  }
}
