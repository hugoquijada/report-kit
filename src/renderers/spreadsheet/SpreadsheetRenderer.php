<?php

namespace hqsoft\reportkit\renderers\spreadsheet;

use hqsoft\reportkit\document\CellContent;
use hqsoft\reportkit\document\CellImage;
use hqsoft\reportkit\document\Document;
use hqsoft\reportkit\Renderers\IRenderer;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SpreadsheetRenderer implements IRenderer {

  private int $currentRow = 1;

  public function render(Document $doc): string {

    $spreadsheet = new Spreadsheet();
    $ws = $spreadsheet->getActiveSheet();
    $maxCols = $doc->getMaxColumns();

    for ($i = 1; $i <= $maxCols; $i++) {
      $ws->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(4);
    }

    foreach ($doc->getHeaderRows() as $row) {
      $this->renderRow($ws, $row, $doc);
    }

    foreach ($doc->getRows() as $row) {
      $this->renderRow($ws, $row, $doc);
    }

    foreach ($doc->getFooterRows() as $row) {
      $this->renderRow($ws, $row, $doc);
    }

    $ws->getStyle("A1")->applyFromArray([
      'border' => [
        "bottom" => [
          'borderStyle' => Border::BORDER_THICK,
          'color' => ['argb' => "FF000000"],
        ]
      ]
    ]);

    $writer = new Xlsx($spreadsheet);
    ob_start();
    $writer->save('php://output');
    return ob_get_clean();
  }

  /**
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws 
   * @param Row $row
   * @param Document $doc
   */
  private function renderRow($ws, $row, Document $doc): void {
    $colIndex = 1;
    $maxRowRangeStart = null;
    $maxRowRangeEnd   = null;

    foreach ($row->getColumns() as $col) {
      $data = $col->toArray();
      $span = $data['span'];

      $start = Coordinate::stringFromColumnIndex($colIndex);
      $end = Coordinate::stringFromColumnIndex($colIndex + $span - 1);

      $cellRef = "{$start}{$this->currentRow}";
      $range = "{$start}{$this->currentRow}:{$end}{$this->currentRow}";

      if ($span > 1) {
        $ws->mergeCells($range);
      }

      $this->renderCellContents($data['contents'], $ws, $cellRef);

      $this->applyStyles($ws, $range, $data, $doc);

      $ws->getStyle($range)->getAlignment()->setWrapText(true);

      if ($maxRowRangeStart === null) {
        $maxRowRangeStart = $start;
      }
      $maxRowRangeEnd = $end;

      $colIndex += $span;
    }

    $fullRange = "{$maxRowRangeStart}{$this->currentRow}:{$maxRowRangeEnd}{$this->currentRow}";
    $ws->getStyle($fullRange)->getAlignment()->setWrapText(true);

    $this->currentRow++;
  }

  /**
   * @param array $contents
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws
   * @param string $cellRef
   */
  private function renderCellContents(array $contents, $ws, string $cellRef): void {
    $text = '';
    foreach ($contents as $item) {
      if (is_string($item)) {
        $trim = trim($item);
        if ($trim === '<br>' || $trim === '<br/>' || $trim === '<br />') {
          $text .= "\n";
          continue;
        }

        $text .= strip_tags($item) . " ";
        continue;
      }

      if ($item instanceof CellContent) {
        $text .= $item->text . " ";
        continue;
      }

      if ($item instanceof CellImage) {
        $drawing = new Drawing();
        $drawing->setPath($item->src);
        $drawing->setCoordinates($cellRef);

        if ($item->width) $drawing->setWidth($item->width);
        if ($item->height) $drawing->setWidth($item->height);

        $drawing->setWorksheet($ws);
      }
    }

    $ws->setCellValue($cellRef, trim($text));
    $ws->getStyle($cellRef)->getAlignment()->setWrapText(true);
  }

  /**
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws 
   * @param string $range
   * @param array $data
   * @param Document $doc
   */
  private function applyStyles($ws, string $range, array $data, Document $doc) {
    $finalStyle = [];

    // 1. Resolve Base Styles (from Column)
    if (!empty($data['style'])) {
      $stylesToApply = is_array($data['style']) ? $data['style'] : [$data['style']];
      foreach ($stylesToApply as $s) {
        if (is_string($s)) {
          $resolved = $doc->getStyle($s);
          if ($resolved) {
            $this->mergeStyleArray($finalStyle, $resolved->toArray());
          }
        } elseif ($s instanceof \hqsoft\reportkit\document\CellStyle) {
          $this->mergeStyleArray($finalStyle, $s->toArray());
        }
      }
    }

    // 2. Resolve Ad-hoc styles from Column properties (align)
    if (!empty($data['align'])) {
      $finalStyle['text-align'] = $data['align'];
    }

    // 3. Add format if specified
    if (!empty($data['format'])) {
      $finalStyle['format'] = $this->getExcelFormat($data['format']);
    }

    // 4. Resolve Content specific styles (background from CellContent)
    // Note: This overrides column style, as per original logic logic
    foreach ($data['contents'] as $item) {
      if ($item instanceof CellContent && $item->background) {
        $finalStyle['background-color'] = $item->background;
        break;
      }
    }

    // 5. Resolve Ad-hoc borders from Column
    if (!empty($data['borders'])) {
      foreach ($data['borders'] as [$side, $color, $thick]) {
        if (!isset($finalStyle['borders'])) $finalStyle['borders'] = [];
        $finalStyle['borders'][] = ['side' => $side, 'color' => $color, 'style' => $thick > 1 ? 'thick' : 'thin'];
      }
    }

    // Map to PhpSpreadsheet
    $phpSpreadsheetStyle = [];
    foreach ($finalStyle as $k => $v) {
      if ($k === 'borders') {
        foreach ($v as $borderDef) {
          $this->applyBorder($phpSpreadsheetStyle, $borderDef['side'], $borderDef['color'], $borderDef['style']);
        }
      } else {
        $this->mapCellStyle($phpSpreadsheetStyle, $k, $v);
      }
    }

    if ($phpSpreadsheetStyle) {
      $ws->getStyle($range)->applyFromArray($phpSpreadsheetStyle);
    }

    // Extraer columna inicial de un rango (ej "A1:B1" -> "A")
    // O si es celda simple "A1" -> "A"
    $startCell = explode(':', $range)[0]; // "A1"
    $colLetter = preg_replace('/[0-9]+/', '', $startCell);
    $rowNumber = (int)preg_replace('/[A-Z]+/', '', $startCell);

    $this->applyDimensions($ws, $colLetter, $rowNumber, $finalStyle);
  }

  private function mergeStyleArray(array &$target, array $source) {
    foreach ($source as $key => $value) {
      if ($key === 'borders' && isset($target['borders'])) {
        $target['borders'] = array_merge($target['borders'], $value);
      } else {
        $target[$key] = $value;
      }
    }
  }

  private function mapAlign(string $align): string {
    return match ($align) {
      'left' => Alignment::HORIZONTAL_LEFT,
      'right' => Alignment::HORIZONTAL_RIGHT,
      'center' => Alignment::HORIZONTAL_CENTER,
      default => Alignment::HORIZONTAL_LEFT
    };
  }

  private function mapValign(string $align): string {
    return match ($align) {
      'top' => Alignment::VERTICAL_TOP,
      'middle' => Alignment::VERTICAL_CENTER,
      'bottom' => Alignment::VERTICAL_BOTTOM,
      default => Alignment::VERTICAL_TOP
    };
  }

  private function getExcelFormat(string $format): string {
    return match ($format) {
      'number' => '#,##0.00',
      'currency' => '$#,##0.00',
      'percentage' => '0.00%',
      'date' => 'DD/MM/YYYY',
      'datetime' => 'DD/MM/YYYY HH:MM:SS',
      'time' => 'HH:MM:SS',
      'text' => '@',
      default => $format // Formato personalizado
    };
  }

  private function mapCellStyle(array &$style, string $key, mixed $value) {
    switch ($key) {

      case 'font-weight':
        if ($value === 'bold') $style['font']['bold'] = true;
        break;

      case 'font-style':
        if ($value === 'italic') $style['font']['italic'] = true;
        break;

      case 'font-size':
        $style['font']['size'] = (int)$value;
        break;

      case 'color':
        $style['font']['color'] = ['argb' => 'FF' . strtoupper(ltrim($value, '#'))];
        break;

      case 'background-color':
        $hex = strtoupper(ltrim($value, '#'));
        $style['fill'] = [
          'fillType' => Fill::FILL_SOLID,
          'startColor' => ['argb' => "FF{$hex}"]
        ];
        break;

      case 'text-align':
        $style['alignment']['horizontal'] = $this->mapAlign($value);
        break;

      case 'vertical-align':
        $style['alignment']['vertical'] = $this->mapValign($value);
        break;

      case 'wrap-text':
        $style['alignment']['wrapText'] = (bool)$value;
        break;

      case 'format':
        $style['numberFormat']['formatCode'] = $value;
        break;

      case 'text-decoration':
        if ($value === 'underline') {
          $style['font']['underline'] = \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE;
        } elseif ($value === 'line-through') {
          $style['font']['strikethrough'] = true;
        }
        break;

      case 'padding':
        // Aproximación: 1 unidad de indentación ~ 10px
        $px = (int)$value;
        if ($px > 0) {
          $style['alignment']['indent'] = min(15, (int)($px / 8));
        }
        break;
    }
  }

  private function applyDimensions(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws, string $colLetter, int $rowNumber, array $finalStyle) {
    // Aplicar ancho de columna si está definido
    if (isset($finalStyle['width'])) {
      $width = $finalStyle['width'];
      $px = (int)$width;
      // Aproximación: 1 char ~ 7px
      $chars = $px / 7;
      $ws->getColumnDimension($colLetter)->setWidth($chars);
    }

    // Aplicar alto de fila si está definido
    if (isset($finalStyle['height'])) {
      $height = $finalStyle['height'];
      $px = (int)$height;
      // Aproximación: 1 px ~ 0.75 pt
      $pt = $px * 0.75;
      $ws->getRowDimension($rowNumber)->setRowHeight($pt);
    }
  }

  private function applyBorder(array &$style, string $side, string $color, string $borderStyle) {
    $borderSide = match ($side) {
      'top' => 'top',
      'bottom' => 'bottom',
      'left' => 'left',
      'right' => 'right',
      'all' => 'allBorders',
    };

    $bs = match ($borderStyle) {
      'thick' => Border::BORDER_THICK,
      'thin' => Border::BORDER_THIN,
      'medium' => Border::BORDER_MEDIUM,
      'dashed' => Border::BORDER_DASHED,
      'double' => Border::BORDER_DOUBLE,
      default => Border::BORDER_THIN
    };

    $hex = strtoupper(ltrim($color, '#'));
    $style['borders'][$borderSide] = [
      'borderStyle' => $bs,
      'color' => ['argb' => "FF{$hex}"],
    ];
  }
}
