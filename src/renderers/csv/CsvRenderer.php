<?php

namespace hqsoft\reportkit\renderers\csv;

use hqsoft\reportkit\document\CellContent;
use hqsoft\reportkit\document\Document;
use hqsoft\reportkit\Renderers\IRenderer;

class CsvRenderer implements IRenderer {

  public function render(Document $doc): string {
    $content = "";
    foreach ($doc->getRows() as $row) {
      $cols = [];
      foreach ($row->getColumns() as $col) {
        $data = $col->toArray();
        $text = $this->extractCellText($data['contents']);
        $format = $data['format'] ?? null;
        $formatted = $this->formatValue($text, $format);
        $cols[] = $this->escapeCsv($formatted);
      }
      $content .= implode(",", $cols) . "\n";
    }

    return $content;
  }

  private function extractCellText(array $contents): string {
    $text = '';
    foreach ($contents as $item) {
      if (is_string($item)) {
        $trim = trim($item);
        if (in_array($trim, ['<br>', '<br/>', '<br />'])) {
          $text .= " ";
          continue;
        }
        $text .= strip_tags($item) . " ";
        continue;
      }

      if ($item instanceof CellContent) {
        $text .= $item->text . " ";
        continue;
      }
    }
    return trim($text);
  }

  private function formatValue(string $value, ?string $format): string {
    if ($format === null || $value === '') {
      return $value;
    }

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
        $timestamp = is_numeric($trimmed) ? (int)$trimmed : strtotime($trimmed);
        return $timestamp ? date('d/m/Y', $timestamp) : $value;

      case 'datetime':
        $timestamp = is_numeric($trimmed) ? (int)$trimmed : strtotime($trimmed);
        return $timestamp ? date('d/m/Y H:i:s', $timestamp) : $value;

      case 'time':
        $timestamp = is_numeric($trimmed) ? (int)$trimmed : strtotime($trimmed);
        return $timestamp ? date('H:i:s', $timestamp) : $value;

      default:
        if (is_numeric($trimmed)) {
          return number_format((float)$trimmed, 2, '.', ',');
        }
        return $value;
    }
  }

  private function escapeCsv(string $value): string {
    // Si contiene coma, comillas o salto de línea, envolver en comillas
    if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
      return '"' . str_replace('"', '""', $value) . '"';
    }
    return $value;
  }
}
