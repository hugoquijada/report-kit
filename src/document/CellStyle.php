<?php

namespace hqsoft\reportkit\document;

class CellStyle {

  private array $styles = [];

  public static function create(): self {
    return new self();
  }

  public static function title(): self {
    return self::create()
      ->bold()
      ->fontSize(16)
      ->padding(6);
  }

  public static function subtle(): self {
    return self::create()
      ->color('#555')
      ->fontSize(12);
  }

  public static function box(): self {
    return self::create()
      ->padding(8);
  }

  public static function header(): self {
    return self::create()
      ->bold()
      ->background('#f5f5f5')
      ->padding(6);
  }

  public function align(string $value): self {
    $this->styles['text-align'] = $value;
    return $this;
  }

  public function valign(string $value): self {
    $this->styles['vertical-align'] = $value;
    return $this;
  }

  public function padding(string|int $value, $unit = 'px'): self {
    $this->styles['padding'] = is_numeric($value) ? "{$value}{$unit}" : $value;
    return $this;
  }

  public function bold(bool $isBold = true): self {
    $this->styles['font-weight'] = $isBold ? 'bold' : 'normal';
    return $this;
  }

  public function italic(bool $isItalic = true): self {
    $this->styles['font-style'] = $isItalic ? 'italic' : 'normal';
    return $this;
  }

  public function fontSize(int $px, $unit = 'px'): self {
    $this->styles['font-size'] = "{$px}{$unit}";
    return $this;
  }

  public function color(string $hex): self {
    $this->styles['color'] = $hex;
    return $this;
  }

  public function background(string $hex): self {
    $this->styles['background-color'] = $hex;
    return $this;
  }

  public function decoration(string $value): self {
    $this->styles['text-decoration'] = $value;
    return $this;
  }

  public function height(string|int $value, string $unit = 'px'): self {
    $this->styles['height'] = is_numeric($value) ? "{$value}{$unit}" : $value;
    return $this;
  }

  public function width(string|int $value, string $unit = 'px'): self {
    $this->styles['width'] = is_numeric($value) ? "{$value}{$unit}" : $value;
    return $this;
  }

  public function border(string $side = 'all', string $style = 'thin', string $color = '#000000'): self {
    if (!isset($this->styles['borders'])) {
      $this->styles['borders'] = [];
    }
    $this->styles['borders'][] = ['side' => $side, 'style' => $style, 'color' => $color];
    return $this;
  }

  public function format(string $formatCode): self {
    $this->styles['format'] = $formatCode;
    return $this;
  }

  public function wrapText(bool $wrap = true): self {
    $this->styles['wrap-text'] = $wrap;
    return $this;
  }

  public function toArray(): array {
    return $this->styles;
  }

  public function merge(CellStyle $other): self {
    $otherStyles = $other->toArray();

    if (isset($this->styles['borders']) && isset($otherStyles['borders'])) {
      $otherStyles['borders'] = array_merge($this->styles['borders'], $otherStyles['borders']);
    }

    $this->styles = array_merge($this->styles, $otherStyles);
    return $this;
  }
}
