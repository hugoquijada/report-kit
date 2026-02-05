# Report Kit

Librería PHP para la generación fluida de reportes multipropósito (HTML, Excel, CSV, PDF). Diseñada para definir la estructura y el estilo del reporte una sola vez y renderizarlo en múltiples formatos.

## Instalación

```bash
composer require hqsoft/report-kit
```

## Uso Básico

La clase principal es `Document`, donde defines la estructura de tu reporte mediante filas (`Row`) y columnas (`Column`).

```php
use hqsoft\reportkit\document\Document;

// Crear un documento nuevo
$doc = new Document();

// Agregar un título (fila de ancho completo)
$doc->row(function($row) {
    $row->col(24)->text('Mi Reporte Mensual')->align('center'); // 24 columnas es el ancho total por defecto
});

// Agregar encabezados
$doc->header(function($row) {
    $row->col(8)->text('Producto', bold: true);
    $row->col(8)->text('Cantidad', bold: true);
    $row->col(8)->text('Precio', bold: true);
});

// Agregar datos
$doc->row(function($row) {
    $row->col(8)->text('Laptop');
    $row->col(8)->text('10');
    $row->col(8)->text('1500.00');
});
```

## Renderizado

Una vez definido el documento, puedes usar diferentes "Renderers" para generar la salida deseada.

### HTML
Genera una tabla HTML con estilos en línea, ideal para vistas previas o correos.

```php
use hqsoft\reportkit\renderers\html\HtmlRenderer;

$renderer = new HtmlRenderer();
echo $renderer->render($doc);
```

### Spreadsheet (Excel)
Genera un archivo `.xlsx` nativo. Requiere `phpoffice/phpspreadsheet`.

```php
use hqsoft\reportkit\renderers\spreadsheet\SpreadsheetRenderer;

$renderer = new SpreadsheetRenderer();
$content = $renderer->render($doc); // Retorna el contenido binario del archivo
file_put_contents('reporte.xlsx', $content);
```

### CSV
Exportación rápida a formato CSV.

```php
use hqsoft\reportkit\renderers\csv\CsvRenderer;

$renderer = new CsvRenderer();
echo $renderer->render($doc);
```

## Estilos (`CellStyle`)

Puedes aplicar estilos detallados a tus celdas usando la clase `CellStyle`.

```php
use hqsoft\reportkit\document\CellStyle;

$estiloResaltado = CellStyle::create()
    ->bold()
    ->background('#FFFF00')
    ->color('#FF0000')
    ->align('center')
    ->border('bottom', 'thick');

$doc->row(function($row) use ($estiloResaltado) {
    $row->col(12)->text('¡Importante!')->style($estiloResaltado);
});
```

### Capacidades de Estilo
- **Texto**: `bold()`, `italic()`, `decoration('underline')`, `decoration('line-through')`
- **Fuente**: `fontFamily('Arial')`, `fontSize(14)`
- **Color**: `color('#333')`, `background('#f0f0f0')`
- **Alineación**: `align('center')`, `valign('middle')`
- **Bordes**: `border('all', 'thin', '#000')`
- **Espaciado**: `padding(10)` (se convierte a indentación en Excel)
- **Dimensiones**: `width(200)` (ancho de columna), `height(50)` (alto de fila)

## Formatos de Datos

Puedes especificar el tipo de dato de una columna para asegurar que se formatee correctamente en Excel y HTML (ej. moneda, fechas).

```php
use hqsoft\reportkit\document\Column;

$doc->row(function($row) {
    // Moneda
    $row->col(6)->text('12500.50')->format(Column::FORMAT_CURRENCY);
    
    // Porcentaje
    $row->col(6)->text('0.15')->format(Column::FORMAT_PERCENTAGE);
    
    // Fecha (entrada YYYY-MM-DD -> salida DD/MM/YYYY)
    $row->col(6)->text('2026-02-03')->format(Column::FORMAT_DATE);
    
    // Número con separadores
    $row->col(6)->text('1000000')->format(Column::FORMAT_NUMBER);
});
```

## Estilos Nombrados

Para evitar repetir definiciones, puedes registrar estilos en el documento:

```php
$doc->addStyle('titulo', CellStyle::create()->fontSize(20)->bold());

// Usar por nombre
$doc->row(function($row) {
    $row->col(24)->text('Mi Título')->style('titulo');
});
```
