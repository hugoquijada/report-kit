<?php

namespace hqsoft\reportkit\document;

class Column {

  public const FORMAT_NUMBER = 'number';
  public const FORMAT_CURRENCY = 'currency';
  public const FORMAT_PERCENTAGE = 'percentage';
  public const FORMAT_DATE = 'date';
  public const FORMAT_DATETIME = 'datetime';
  public const FORMAT_TIME = 'time';
  public const FORMAT_TEXT = 'text';

  private int $span;
  private ?int $rowspan = null;
  private array $contents = [];
  private ?string $align = null;
  private array $borders = [];
  private ?CellStyle $style = null;
  private ?string $format = null;

  public function __construct(int $span, ?int $rowspan = null) {
    $this->span = $span;
    $this->rowspan = $rowspan;
  }

  public function align(string $align): self {
    if (!in_array($align, ['left', 'center', 'right'])) {
      throw new \InvalidArgumentException("Alineación inválida: {$align}");
    }
    $this->align = $align;
    return $this;
  }

  public function style(CellStyle $style): self {
    if ($this->style === null) {
      $this->style = $style;
    } else {
      $this->style->merge($style);
    }
    return $this;
  }

  public function format(string $format): self {
    $this->format = $format;
    return $this;
  }

  public function text(string $text, bool $bold = false, bool $italic = false, ?string $color = null, ?int $size = null, ?string $font = null, ?string $background = null, ?string $decoration = null): self {
    $this->contents[] = new CellContent($text, $bold, $italic, $color, $size, $font, $background, $decoration);
    return $this;
  }

  public function html(string $html): self {
    $this->contents[] = $html;
    return $this;
  }

  public function image(string $src, ?int $width = null, ?int $height = null, ?string $alt = null, ?string $style = null): self {
    $this->contents[] = new CellImage($src, $width, $height, $alt, $style);
    return $this;
  }

  public function break(): self {
    $this->contents[] = '<br>';
    return $this;
  }

  public function border(string $side = 'all', string $color = '#ccc', int $thick = 1): self {
    if (!in_array($side, ['all', 'top', 'bottom', 'left', 'right'])) {
      throw new \InvalidArgumentException("Lado de borde inválido: {$side}");
    }
    $this->borders[] = [$side, $color, $thick];
    return $this;
  }

  public function getContents(): array {
    return $this->contents;
  }

  public function getFormat(): ?string {
    return $this->format;
  }

  public function toArray(): array {
    return [
      'span' => $this->span,
      'rowspan' => $this->rowspan,
      'align' => $this->align,
      'contents' => $this->contents,
      'borders' => $this->borders,
      'style' => $this->style?->toArray() ?? [],
      'format' => $this->format,
    ];
  }
}
