<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use ZipArchive;

class DocxGenerator
{
    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const R_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const WP_NS = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';

    private const A_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    private const PIC_NS = 'http://schemas.openxmlformats.org/drawingml/2006/picture';

    protected array $relationships = [];

    protected array $mediaFiles = [];

    protected int $relCounter = 10;

    protected int $imageCounter = 1;

    protected bool $hasHeader = false;

    protected bool $hasFooter = false;

    /**
     * Generate native Open XML DOCX bytes from HTML and layout options.
     */
    public function generate(string $title, string $html, ?string $headerText = null, ?string $footerText = null, array $layoutOptions = []): string
    {
        $this->relationships = [];
        $this->mediaFiles = [];
        $this->relCounter = 10;
        $this->imageCounter = 1;
        $this->hasHeader = ! empty($headerText) && trim($headerText) !== '';
        $this->hasFooter = ! empty($footerText) && trim($footerText) !== '';

        // Standard relationship IDs
        $this->relationships[] = [
            'id' => 'rIdStyles',
            'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles',
            'target' => 'styles.xml',
        ];
        $this->relationships[] = [
            'id' => 'rIdNumbering',
            'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering',
            'target' => 'numbering.xml',
        ];
        $this->relationships[] = [
            'id' => 'rIdSettings',
            'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings',
            'target' => 'settings.xml',
        ];

        if ($this->hasHeader) {
            $this->relationships[] = [
                'id' => 'rIdHeader1',
                'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/header',
                'target' => 'header1.xml',
            ];
        }

        if ($this->hasFooter) {
            $this->relationships[] = [
                'id' => 'rIdFooter1',
                'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer',
                'target' => 'footer1.xml',
            ];
        }

        $documentXml = $this->buildDocumentXml($title, $html, $layoutOptions);
        $contentTypesXml = $this->buildContentTypesXml();
        $rootRelsXml = $this->buildRootRelsXml();
        $docRelsXml = $this->buildDocRelsXml();
        $stylesXml = $this->buildStylesXml();
        $numberingXml = $this->buildNumberingXml();
        $settingsXml = $this->buildSettingsXml();

        // Create temporary in-memory ZIP
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $contentTypesXml);
        $zip->addFromString('_rels/.rels', $rootRelsXml);
        $zip->addFromString('word/_rels/document.xml.rels', $docRelsXml);
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->addFromString('word/styles.xml', $stylesXml);
        $zip->addFromString('word/numbering.xml', $numberingXml);
        $zip->addFromString('word/settings.xml', $settingsXml);

        if ($this->hasHeader) {
            $zip->addFromString('word/header1.xml', $this->buildHeaderXml($headerText));
        }

        if ($this->hasFooter) {
            $zip->addFromString('word/footer1.xml', $this->buildFooterXml($footerText));
        }

        foreach ($this->mediaFiles as $mediaPath => $mediaBytes) {
            $zip->addFromString('word/'.$mediaPath, $mediaBytes);
        }

        $zip->close();

        $binary = file_get_contents($tempFile);
        @unlink($tempFile);

        return $binary;
    }

    protected function buildDocumentXml(string $title, string $html, array $layoutOptions): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        $bodyNode = $dom->getElementsByTagName('body')->item(0);
        $bodyContentXml = '';

        if ($bodyNode) {
            foreach ($bodyNode->childNodes as $child) {
                $bodyContentXml .= $this->convertHtmlNodeToWordXml($child);
            }
        }

        if (trim($bodyContentXml) === '') {
            $bodyContentXml = '<w:p><w:r><w:t></w:t></w:r></w:p>';
        }

        // Section Properties (Margins, Paper Size, Orientation, Headers/Footers)
        $sectPr = $this->buildSectPrXml($layoutOptions);

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
            xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
            xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
            xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
            xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
  <w:body>
    {$bodyContentXml}
    {$sectPr}
  </w:body>
</w:document>
XML;
    }

    protected function convertHtmlNodeToWordXml(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->textContent);
            if ($text === '') {
                return '';
            }

            return '<w:p><w:r><w:t>'.htmlspecialchars($text, ENT_XML1, 'UTF-8').'</w:t></w:r></w:p>';
        }

        if (! ($node instanceof DOMElement)) {
            return '';
        }

        $tag = strtolower($node->tagName);

        if (preg_match('/^h([1-6])$/', $tag, $m)) {
            $level = $m[1];
            $styleId = 'Heading'.$level;
            $runsXml = $this->convertInlineNodesToRuns($node);

            return "<w:p><w:pPr><w:pStyle w:val=\"{$styleId}\"/></w:pPr>{$runsXml}</w:p>";
        }

        if ($tag === 'p') {
            $alignXml = $this->getAlignXml($node);
            $runsXml = $this->convertInlineNodesToRuns($node);

            return "<w:p>{$alignXml}{$runsXml}</w:p>";
        }

        if ($tag === 'blockquote') {
            $runsXml = $this->convertInlineNodesToRuns($node);

            return "<w:p><w:pPr><w:pStyle w:val=\"Quote\"/></w:pPr>{$runsXml}</w:p>";
        }

        if ($tag === 'pre') {
            $codeText = htmlspecialchars($node->textContent, ENT_XML1, 'UTF-8');

            return "<w:p><w:pPr><w:pStyle w:val=\"Code\"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii=\"Courier New\" w:hAnsi=\"Courier New\"/></w:rPr><w:t xml:space=\"preserve\">{$codeText}</w:t></w:r></w:p>";
        }

        if ($tag === 'ul' || $tag === 'ol') {
            $numId = ($tag === 'ol') ? 1 : 2;
            $itemsXml = '';
            foreach ($node->childNodes as $li) {
                if ($li instanceof DOMElement && strtolower($li->tagName) === 'li') {
                    $runsXml = $this->convertInlineNodesToRuns($li);
                    $itemsXml .= "<w:p><w:pPr><w:numPr><w:ilvl w:val=\"0\"/><w:numId w:val=\"{$numId}\"/></w:numPr></w:pPr>{$runsXml}</w:p>";
                }
            }

            return $itemsXml;
        }

        if ($tag === 'table') {
            return $this->convertTableToWordXml($node);
        }

        if ($tag === 'figure' || $tag === 'img') {
            return $this->convertImageToWordXml($node);
        }

        if ($tag === 'hr') {
            return '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="auto"/></w:pBdr></w:pPr></w:p>';
        }

        if ($tag === 'div' || $tag === 'section') {
            $class = $node->getAttribute('class');
            if (str_contains($class, 'page-break')) {
                return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
            }
            if (str_contains($class, 'column-break')) {
                return '<w:p><w:r><w:br w:type="column"/></w:r></w:p>';
            }

            // Recurse children
            $res = '';
            foreach ($node->childNodes as $child) {
                $res .= $this->convertHtmlNodeToWordXml($child);
            }

            return $res;
        }

        return '';
    }

    protected function convertInlineNodesToRuns(DOMNode $node, array $formatting = []): string
    {
        $runsXml = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->textContent;
                if ($text !== '') {
                    $rPrXml = $this->buildRunProperties($formatting);
                    $escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');
                    $runsXml .= "<w:r>{$rPrXml}<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r>";
                }
            } elseif ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if ($tag === 'br') {
                    $runsXml .= '<w:r><w:br/></w:r>';

                    continue;
                }

                if ($tag === 'img') {
                    $runsXml .= $this->convertImageToInlineXml($child);

                    continue;
                }

                $childFormatting = $formatting;
                if ($tag === 'strong' || $tag === 'b') {
                    $childFormatting['bold'] = true;
                }
                if ($tag === 'em' || $tag === 'i') {
                    $childFormatting['italic'] = true;
                }
                if ($tag === 'u') {
                    $childFormatting['underline'] = true;
                }
                if ($tag === 's' || $tag === 'strike' || $tag === 'del') {
                    $childFormatting['strike'] = true;
                }
                if ($tag === 'sub') {
                    $childFormatting['sub'] = true;
                }
                if ($tag === 'sup') {
                    $childFormatting['sup'] = true;
                }
                if ($tag === 'code') {
                    $childFormatting['font'] = 'Courier New';
                }

                if ($tag === 'a') {
                    $href = $child->getAttribute('href');
                    $relId = 'rId'.(++$this->relCounter);
                    $this->relationships[] = [
                        'id' => $relId,
                        'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink',
                        'target' => $href,
                        'targetMode' => 'External',
                    ];
                    $innerRuns = $this->convertInlineNodesToRuns($child, array_merge($childFormatting, ['underline' => true, 'color' => '0000FF']));
                    $runsXml .= "<w:hyperlink r:id=\"{$relId}\">{$innerRuns}</w:hyperlink>";

                    continue;
                }

                $runsXml .= $this->convertInlineNodesToRuns($child, $childFormatting);
            }
        }

        return $runsXml;
    }

    protected function buildRunProperties(array $fmt): string
    {
        $props = '';
        if (! empty($fmt['bold'])) {
            $props .= '<w:b/>';
        }
        if (! empty($fmt['italic'])) {
            $props .= '<w:i/>';
        }
        if (! empty($fmt['underline'])) {
            $props .= '<w:u w:val="single"/>';
        }
        if (! empty($fmt['strike'])) {
            $props .= '<w:strike/>';
        }
        if (! empty($fmt['sub'])) {
            $props .= '<w:vertAlign w:val="subscript"/>';
        }
        if (! empty($fmt['sup'])) {
            $props .= '<w:vertAlign w:val="superscript"/>';
        }
        if (! empty($fmt['font'])) {
            $font = htmlspecialchars($fmt['font'], ENT_XML1, 'UTF-8');
            $props .= "<w:rFonts w:ascii=\"{$font}\" w:hAnsi=\"{$font}\"/>";
        }
        if (! empty($fmt['color'])) {
            $color = ltrim($fmt['color'], '#');
            $props .= "<w:color w:val=\"{$color}\"/>";
        }

        return $props !== '' ? "<w:rPr>{$props}</w:rPr>" : '';
    }

    protected function convertTableToWordXml(DOMElement $node): string
    {
        $rowsXml = '';
        $trList = $node->getElementsByTagName('tr');

        foreach ($trList as $tr) {
            $cellsXml = '';
            foreach ($tr->childNodes as $tc) {
                if ($tc instanceof DOMElement && in_array(strtolower($tc->tagName), ['td', 'th'])) {
                    $isHeader = strtolower($tc->tagName) === 'th';
                    $colSpan = (int) ($tc->getAttribute('colspan') ?: 1);

                    $tcPr = '<w:tcPr>';
                    if ($colSpan > 1) {
                        $tcPr .= "<w:gridSpan w:val=\"{$colSpan}\"/>";
                    }
                    if ($isHeader) {
                        $tcPr .= '<w:shd w:val="clear" w:color="auto" w:fill="F2F2F2"/>';
                    }
                    $tcPr .= '</w:tcPr>';

                    $runsXml = $this->convertInlineNodesToRuns($tc, $isHeader ? ['bold' => true] : []);
                    if ($runsXml === '') {
                        $runsXml = '<w:r><w:t></w:t></w:r>';
                    }
                    $cellsXml .= "<w:tc>{$tcPr}<w:p>{$runsXml}</w:p></w:tc>";
                }
            }
            $rowsXml .= "<w:tr>{$cellsXml}</w:tr>";
        }

        return <<<XML
<w:tbl>
  <w:tblPr>
    <w:tblStyle w:val="TableGrid"/>
    <w:tblW w:w="5000" w:type="pct"/>
    <w:tblBorders>
      <w:top w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
      <w:left w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
      <w:bottom w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
      <w:right w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
      <w:insideH w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
      <w:insideV w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/>
    </w:tblBorders>
  </w:tblPr>
  {$rowsXml}
</w:tbl>
XML;
    }

    protected function convertImageToWordXml(DOMElement $node): string
    {
        $img = null;
        $caption = '';

        if (strtolower($node->tagName) === 'img') {
            $img = $node;
        } else {
            $imgList = $node->getElementsByTagName('img');
            if ($imgList->length > 0) {
                $img = $imgList->item(0);
            }
            $caps = $node->getElementsByTagName('figcaption');
            if ($caps->length > 0) {
                $caption = trim($caps->item(0)->textContent);
            }
        }

        if (! $img) {
            return '';
        }

        $drawingXml = $this->convertImageToInlineXml($img);
        $result = "<w:p><w:pPr><w:jc w:val=\"center\"/></w:pPr>{$drawingXml}</w:p>";

        if ($caption !== '') {
            $capEscaped = htmlspecialchars($caption, ENT_XML1, 'UTF-8');
            $result .= "<w:p><w:pPr><w:pStyle w:val=\"Caption\"/><w:jc w:val=\"center\"/></w:pPr><w:r><w:rPr><w:i/></w:rPr><w:t>{$capEscaped}</w:t></w:r></w:p>";
        }

        return $result;
    }

    protected function convertImageToInlineXml(DOMElement $img): string
    {
        $src = $img->getAttribute('src');
        $alt = $img->getAttribute('alt') ?: 'Image';
        $altEscaped = htmlspecialchars($alt, ENT_XML1, 'UTF-8');

        $mediaBytes = null;
        $extension = 'png';

        if (str_starts_with($src, 'data:image/')) {
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $src, $m)) {
                $extension = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
                $mediaBytes = base64_decode($m[2]);
            }
        } elseif (filter_var($src, FILTER_VALIDATE_URL) === false && file_exists($src)) {
            $extension = pathinfo($src, PATHINFO_EXTENSION) ?: 'png';
            $mediaBytes = @file_get_contents($src);
        }

        // If no image bytes available, generate a 1x1 transparent png
        if ($mediaBytes === null || $mediaBytes === false) {
            $mediaBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
            $extension = 'png';
        }

        $mediaIndex = $this->imageCounter++;
        $mediaPath = "media/image{$mediaIndex}.{$extension}";
        $this->mediaFiles[$mediaPath] = $mediaBytes;

        $relId = 'rId'.(++$this->relCounter);
        $this->relationships[] = [
            'id' => $relId,
            'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image',
            'target' => $mediaPath,
        ];

        // Standard 400x300 pt in EMU (1 pt = 12700 EMU)
        $cx = 400 * 12700;
        $cy = 300 * 12700;

        return <<<XML
<w:r>
  <w:drawing>
    <wp:inline distT="0" distB="0" distL="0" distR="0">
      <wp:extent cx="{$cx}" cy="{$cy}"/>
      <wp:docPr id="{$mediaIndex}" name="Picture {$mediaIndex}" descr="{$altEscaped}"/>
      <a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
        <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
          <pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
            <pic:nvPicPr>
              <pic:cNvPr id="{$mediaIndex}" name="Image {$mediaIndex}"/>
              <pic:cNvPicPr/>
            </pic:nvPicPr>
            <pic:blipFill>
              <a:blip r:embed="{$relId}"/>
              <a:stretch><a:fillRect/></a:stretch>
            </pic:blipFill>
            <pic:spPr>
              <a:xfrm><a:off x="0" y="0"/><a:ext cx="{$cx}" cy="{$cy}"/></a:xfrm>
              <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
            </pic:spPr>
          </pic:pic>
        </a:graphicData>
      </a:graphic>
    </wp:inline>
  </w:drawing>
</w:r>
XML;
    }

    protected function getAlignXml(DOMElement $element): string
    {
        $style = $element->getAttribute('style');
        $class = $element->getAttribute('class');
        $align = 'left';

        if (preg_match('/text-align\s*:\s*(center|right|justify|left)/i', $style, $m)) {
            $align = strtolower($m[1]);
        } elseif (str_contains($class, 'text-center')) {
            $align = 'center';
        } elseif (str_contains($class, 'text-right')) {
            $align = 'right';
        } elseif (str_contains($class, 'text-justify')) {
            $align = 'both';
        }

        if ($align === 'center') {
            return '<w:pPr><w:jc w:val="center"/></w:pPr>';
        }
        if ($align === 'right') {
            return '<w:pPr><w:jc w:val="right"/></w:pPr>';
        }
        if ($align === 'both' || $align === 'justify') {
            return '<w:pPr><w:jc w:val="both"/></w:pPr>';
        }

        return '';
    }

    protected function buildSectPrXml(array $layoutOptions): string
    {
        $paperSize = strtolower($layoutOptions['paper_size'] ?? 'a4');
        $orientation = strtolower($layoutOptions['orientation'] ?? 'portrait');

        // Dimensions in dxa (1 mm = 56.7 dxa, 1 inch = 1440 dxa)
        // A4: 210mm x 297mm -> 11906 x 16838
        $sizes = [
            'a4' => ['w' => 11906, 'h' => 16838],
            'letter' => ['w' => 12240, 'h' => 15840],
            'legal' => ['w' => 12240, 'h' => 20160],
        ];

        $w = $sizes[$paperSize]['w'] ?? 11906;
        $h = $sizes[$paperSize]['h'] ?? 16838;

        if ($orientation === 'landscape') {
            $pgSz = "<w:pgSz w:w=\"{$h}\" w:h=\"{$w}\" w:orient=\"landscape\"/>";
        } else {
            $pgSz = "<w:pgSz w:w=\"{$w}\" w:h=\"{$h}\"/>";
        }

        // Standard 1 inch margins (1440 dxa)
        $top = 1440;
        $right = 1440;
        $bottom = 1440;
        $left = 1440;

        $marginsOpt = $layoutOptions['margins'] ?? null;
        if (! empty($marginsOpt) && is_array($marginsOpt)) {
            $top = isset($marginsOpt['top']) && is_numeric($marginsOpt['top']) ? (int) ($marginsOpt['top'] * 56.7) : 1440;
            $right = isset($marginsOpt['right']) && is_numeric($marginsOpt['right']) ? (int) ($marginsOpt['right'] * 56.7) : 1440;
            $bottom = isset($marginsOpt['bottom']) && is_numeric($marginsOpt['bottom']) ? (int) ($marginsOpt['bottom'] * 56.7) : 1440;
            $left = isset($marginsOpt['left']) && is_numeric($marginsOpt['left']) ? (int) ($marginsOpt['left'] * 56.7) : 1440;
        }

        $pgMar = "<w:pgMar w:top=\"{$top}\" w:right=\"{$right}\" w:bottom=\"{$bottom}\" w:left=\"{$left}\" w:header=\"720\" w:footer=\"720\" w:gutter=\"0\"/>";

        $headersXml = '';
        if ($this->hasHeader) {
            $headersXml .= '<w:headerReference w:type="default" r:id="rIdHeader1"/>';
        }
        if ($this->hasFooter) {
            $headersXml .= '<w:footerReference w:type="default" r:id="rIdFooter1"/>';
        }

        return <<<XML
<w:sectPr>
  {$headersXml}
  {$pgSz}
  {$pgMar}
  <w:cols w:space="720"/>
  <w:docGrid w:linePitch="360"/>
</w:sectPr>
XML;
    }

    protected function buildContentTypesXml(): string
    {
        $overrides = '';
        if ($this->hasHeader) {
            $overrides .= '<Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/>'."\n";
        }
        if ($this->hasFooter) {
            $overrides .= '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'."\n";
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Default Extension="png" ContentType="image/png"/>
  <Default Extension="jpeg" ContentType="image/jpeg"/>
  <Default Extension="jpg" ContentType="image/jpeg"/>
  <Default Extension="gif" ContentType="image/gif"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>
  <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
  {$overrides}
</Types>
XML;
    }

    protected function buildRootRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;
    }

    protected function buildDocRelsXml(): string
    {
        $relsXml = '';
        foreach ($this->relationships as $rel) {
            $id = $rel['id'];
            $type = $rel['type'];
            $target = $rel['target'];
            $targetMode = ! empty($rel['targetMode']) ? " TargetMode=\"{$rel['targetMode']}\"" : '';
            $relsXml .= "  <Relationship Id=\"{$id}\" Type=\"{$type}\" Target=\"{$target}\"{$targetMode}/>\n";
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
{$relsXml}</Relationships>
XML;
    }

    protected function buildStylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>
        <w:sz w:val="24"/>
        <w:lang w:val="id-ID"/>
      </w:rPr>
    </w:rPrDefault>
    <w:pPrDefault>
      <w:pPr>
        <w:spacing w:after="160" w:line="240" w:lineRule="auto"/>
      </w:pPr>
    </w:pPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/>
    <w:qFormat/>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/>
    <w:basedOn w:val="Normal"/>
    <w:next w:val="Normal"/>
    <w:qFormat/>
    <w:rPr>
      <w:b/>
      <w:sz w:val="40"/>
      <w:color w:val="1F497D"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/>
    <w:basedOn w:val="Normal"/>
    <w:next w:val="Normal"/>
    <w:qFormat/>
    <w:rPr>
      <w:b/>
      <w:sz w:val="30"/>
      <w:color w:val="595959"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading3">
    <w:name w:val="heading 3"/>
    <w:basedOn w:val="Normal"/>
    <w:next w:val="Normal"/>
    <w:qFormat/>
    <w:rPr>
      <w:b/>
      <w:sz w:val="26"/>
      <w:color w:val="595959"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Quote">
    <w:name w:val="Quote"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:ind w:left="720" w:right="720"/>
    </w:pPr>
    <w:rPr>
      <w:i/>
      <w:color w:val="4A5568"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Caption">
    <w:name w:val="caption"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:rPr>
      <w:i/>
      <w:sz w:val="18"/>
      <w:color w:val="718096"/>
    </w:rPr>
  </w:style>
</w:styles>
XML;
    }

    protected function buildNumberingXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:abstractNum w:abstractNumId="1">
    <w:lvl w:ilvl="0">
      <w:start w:val="1"/>
      <w:numFmt w:val="decimal"/>
      <w:lvlText w:val="%1."/>
      <w:lvlJc w:val="left"/>
      <w:pPr><w:ind w:left="720" w:hanging="360"/></w:pPr>
    </w:lvl>
  </w:abstractNum>
  <w:abstractNum w:abstractNumId="2">
    <w:lvl w:ilvl="0">
      <w:numFmt w:val="bullet"/>
      <w:lvlText w:val="&#8226;"/>
      <w:lvlJc w:val="left"/>
      <w:pPr><w:ind w:left="720" w:hanging="360"/></w:pPr>
    </w:lvl>
  </w:abstractNum>
  <w:num w:numId="1">
    <w:abstractNumId w:val="1"/>
  </w:num>
  <w:num w:numId="2">
    <w:abstractNumId w:val="2"/>
  </w:num>
</w:numbering>
XML;
    }

    protected function buildSettingsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:defaultTabStop w:val="720"/>
  <w:characterSpacingControl w:val="doNotCompress"/>
  <w:compat>
    <w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/>
  </w:compat>
</w:settings>
XML;
    }

    protected function buildHeaderXml(string $headerText): string
    {
        $escaped = htmlspecialchars($headerText, ENT_XML1, 'UTF-8');

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:p>
    <w:pPr>
      <w:pStyle w:val="Header"/>
      <w:jc w:val="right"/>
    </w:pPr>
    <w:r>
      <w:rPr><w:sz w:val="18"/><w:color w:val="718096"/></w:rPr>
      <w:t>{$escaped}</w:t>
    </w:r>
  </w:p>
</w:hdr>
XML;
    }

    protected function buildFooterXml(string $footerText): string
    {
        $escaped = htmlspecialchars($footerText, ENT_XML1, 'UTF-8');

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:p>
    <w:pPr>
      <w:pStyle w:val="Footer"/>
    </w:pPr>
    <w:r>
      <w:rPr><w:sz w:val="18"/><w:color w:val="718096"/></w:rPr>
      <w:t xml:space="preserve">{$escaped}  |  Halaman </w:t>
    </w:r>
    <w:fldSimple w:instr="PAGE"/>
    <w:r>
      <w:rPr><w:sz w:val="18"/><w:color w:val="718096"/></w:rPr>
      <w:t xml:space="preserve"> dari </w:t>
    </w:r>
    <w:fldSimple w:instr="NUMPAGES"/>
  </w:p>
</w:ftr>
XML;
    }
}
