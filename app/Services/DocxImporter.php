<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class DocxImporter
{
    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const R_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const A_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    private const PIC_NS = 'http://schemas.openxmlformats.org/drawingml/2006/picture';

    private const V_NS = 'urn:schemas-microsoft-com:vml';

    private string $extractedHeader = '';

    private string $extractedFooter = '';

    private bool $differentFirstPage = false;

    public function getExtractedHeader(): string
    {
        return $this->extractedHeader;
    }

    public function getExtractedFooter(): string
    {
        return $this->extractedFooter;
    }

    public function getDifferentFirstPage(): bool
    {
        return $this->differentFirstPage;
    }

    /**
     * Parse DOCX with extracted headers and footers metadata.
     */
    public function parseWithMetadata(UploadedFile|string $file): array
    {
        $html = $this->parse($file);

        return [
            'html' => $html,
            'header_text' => $this->extractedHeader,
            'footer_text' => $this->extractedFooter,
            'different_first_page' => $this->differentFirstPage,
        ];
    }

    /**
     * Parse DOCX file into high-fidelity semantic HTML with full formatting preservation.
     */
    public function parse(UploadedFile|string $file): string
    {
        $this->extractedHeader = '';
        $this->extractedFooter = '';
        $this->differentFirstPage = false;

        $filePath = ($file instanceof UploadedFile) ? $file->getRealPath() : (is_string($file) && file_exists($file) ? $file : null);
        $raw = $filePath && file_exists($filePath) ? @file_get_contents($filePath) : (is_string($file) ? $file : '');

        if (! $filePath || ! class_exists('\ZipArchive')) {
            return $this->fallbackRawContent($raw);
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return $this->fallbackRawContent($raw);
        }

        $docXml = $zip->getFromName('word/document.xml');
        if ($docXml === false) {
            $zip->close();

            return $this->fallbackRawContent($raw);
        }

        // 1. Parse Relationships (hyperlinks and embedded images)
        $rels = $this->parseRelationships($zip);

        // 2. Extract "Different First Page" setting from Word section properties
        $this->differentFirstPage = (bool) preg_match('/<w:titlePg\s*\/?>/i', $docXml);

        // 3. Extract Running Headers and Footers from Word archive
        $this->parseHeadersAndFooters($zip, $rels, $docXml);

        // 4. Parse Numbering formats (bullet vs ordered lists)
        $numberingMap = $this->parseNumbering($zip);

        // 5. Parse Styles (Heading levels, Title, Subtitle, etc.)
        $stylesMap = $this->parseStyles($zip);

        // 4. Parse Main Document XML
        $dom = new DOMDocument;
        @$dom->loadXML($docXml, LIBXML_NOENT | LIBXML_NOWARNING | LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);
        $xpath->registerNamespace('r', self::R_NS);
        $xpath->registerNamespace('a', self::A_NS);
        $xpath->registerNamespace('pic', self::PIC_NS);
        $xpath->registerNamespace('v', self::V_NS);

        $body = $xpath->query('/w:document/w:body')->item(0);
        if (! $body) {
            $zip->close();

            return $this->fallbackRawContent($raw);
        }

        $blocks = [];
        $currentListItems = [];
        $currentListType = null; // 'ul' or 'ol'
        $currentListLevel = 0;

        $flushList = function () use (&$blocks, &$currentListItems, &$currentListType) {
            if (! empty($currentListItems)) {
                $tag = ($currentListType === 'ol') ? 'ol' : 'ul';
                $class = ($tag === 'ol') ? 'list-decimal pl-6 my-3 space-y-1' : 'list-disc pl-6 my-3 space-y-1';
                $itemsHtml = implode("\n", array_map(fn ($item) => "<li>{$item}</li>", $currentListItems));
                $blocks[] = "<{$tag} class=\"{$class}\">\n{$itemsHtml}\n</{$tag}>";
                $currentListItems = [];
                $currentListType = null;
            }
        };

        foreach ($body->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if ($node->nodeName === 'w:p') {
                $pResult = $this->parseParagraph($node, $xpath, $zip, $rels, $numberingMap, $stylesMap);

                if (! empty($pResult['page_break_before'])) {
                    $flushList();
                    $blocks[] = $this->renderPageBreak();
                }

                if (! empty($pResult['is_list'])) {
                    $listType = $pResult['list_type']; // 'ul' or 'ol'
                    if ($currentListType !== null && $currentListType !== $listType) {
                        $flushList();
                    }
                    $currentListType = $listType;
                    $currentListLevel = $pResult['list_level'];
                    $currentListItems[] = $pResult['html_content'];
                } else {
                    $flushList();
                    if (! empty($pResult['html'])) {
                        $blocks[] = $pResult['html'];
                    }
                }

                if (! empty($pResult['page_break_after'])) {
                    $flushList();
                    $blocks[] = $this->renderPageBreak();
                }
            } elseif ($node->nodeName === 'w:tbl') {
                $flushList();
                $tblHtml = $this->parseTable($node, $xpath, $zip, $rels, $numberingMap, $stylesMap);
                if (! empty($tblHtml)) {
                    $blocks[] = $tblHtml;
                }
            } elseif ($node->nodeName === 'w:sdt') {
                // Structured Document Tag - extract contents
                $sdtContent = $xpath->query('.//w:sdtContent', $node)->item(0);
                if ($sdtContent) {
                    foreach ($sdtContent->childNodes as $sdtChild) {
                        if ($sdtChild->nodeType !== XML_ELEMENT_NODE) {
                            continue;
                        }
                        if ($sdtChild->nodeName === 'w:p') {
                            $pResult = $this->parseParagraph($sdtChild, $xpath, $zip, $rels, $numberingMap, $stylesMap);
                            $flushList();
                            if (! empty($pResult['html'])) {
                                $blocks[] = $pResult['html'];
                            }
                        } elseif ($sdtChild->nodeName === 'w:tbl') {
                            $flushList();
                            $tblHtml = $this->parseTable($sdtChild, $xpath, $zip, $rels, $numberingMap, $stylesMap);
                            if (! empty($tblHtml)) {
                                $blocks[] = $tblHtml;
                            }
                        }
                    }
                }
            }
        }

        $flushList();
        $zip->close();

        if (empty($blocks)) {
            return $this->fallbackRawContent($raw);
        }

        return implode("\n", $blocks);
    }

    /**
     * Parse running headers and footers from Word archive.
     */
    private function parseHeadersAndFooters(ZipArchive $zip, array $rels = [], string $docXml = ''): void
    {
        $extractCleanText = function (string $xml): string {
            $dom = new DOMDocument;
            @$dom->loadXML($xml, LIBXML_NOENT | LIBXML_NOWARNING | LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', self::W_NS);
            $texts = [];
            foreach ($xpath->query('//w:t') as $tNode) {
                $texts[] = trim($tNode->nodeValue);
            }
            $joined = trim(implode(' ', array_filter($texts)));
            $cleaned = preg_replace('/\bPAGE\b|\bNUMPAGES\b|\bMERGEFORMAT\b|[\\\\*]/i', '', $joined);
            $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned ?? ''));

            return (! empty($cleaned) && ! preg_match('/^\d+$/', $cleaned)) ? $cleaned : '';
        };

        // 1. Check referenced default headers and footers from document.xml
        if (! empty($docXml) && ! empty($rels)) {
            if (preg_match('/<w:headerReference[^>]*w:type=["\']default["\'][^>]*r:id=["\']([^"\']+)["\']/i', $docXml, $m) ||
                preg_match('/<w:headerReference[^>]*r:id=["\']([^"\']+)["\'][^>]*w:type=["\']default["\']/i', $docXml, $m) ||
                preg_match('/<w:headerReference[^>]*r:id=["\']([^"\']+)["\']/i', $docXml, $m)) {
                $rId = $m[1];
                if (isset($rels[$rId]['target'])) {
                    $target = str_starts_with($rels[$rId]['target'], 'word/') ? $rels[$rId]['target'] : 'word/'.$rels[$rId]['target'];
                    $xml = $zip->getFromName($target);
                    if ($xml !== false) {
                        $txt = $extractCleanText($xml);
                        if (! empty($txt)) {
                            $this->extractedHeader = $txt;
                        }
                    }
                }
            }

            if (preg_match('/<w:footerReference[^>]*w:type=["\']default["\'][^>]*r:id=["\']([^"\']+)["\']/i', $docXml, $m) ||
                preg_match('/<w:footerReference[^>]*r:id=["\']([^"\']+)["\'][^>]*w:type=["\']default["\']/i', $docXml, $m) ||
                preg_match('/<w:footerReference[^>]*r:id=["\']([^"\']+)["\']/i', $docXml, $m)) {
                $rId = $m[1];
                if (isset($rels[$rId]['target'])) {
                    $target = str_starts_with($rels[$rId]['target'], 'word/') ? $rels[$rId]['target'] : 'word/'.$rels[$rId]['target'];
                    $xml = $zip->getFromName($target);
                    if ($xml !== false) {
                        $txt = $extractCleanText($xml);
                        if (! empty($txt)) {
                            $this->extractedFooter = $txt;
                        }
                    }
                }
            }
        }

        // 2. Check relationship entries matching header or footer types
        if ((empty($this->extractedHeader) || empty($this->extractedFooter)) && ! empty($rels)) {
            foreach ($rels as $rel) {
                if (empty($this->extractedHeader) && isset($rel['type'], $rel['target']) && str_contains($rel['type'], '/header')) {
                    $target = str_starts_with($rel['target'], 'word/') ? $rel['target'] : 'word/'.$rel['target'];
                    $xml = $zip->getFromName($target);
                    if ($xml !== false) {
                        $txt = $extractCleanText($xml);
                        if (! empty($txt)) {
                            $this->extractedHeader = $txt;
                        }
                    }
                }
                if (empty($this->extractedFooter) && isset($rel['type'], $rel['target']) && str_contains($rel['type'], '/footer')) {
                    $target = str_starts_with($rel['target'], 'word/') ? $rel['target'] : 'word/'.$rel['target'];
                    $xml = $zip->getFromName($target);
                    if ($xml !== false) {
                        $txt = $extractCleanText($xml);
                        if (! empty($txt)) {
                            $this->extractedFooter = $txt;
                        }
                    }
                }
            }
        }

        // 3. Scan zip files if not found
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/^word\/header\d*\.xml$/i', $name) && empty($this->extractedHeader)) {
                $xml = $zip->getFromIndex($i);
                if ($xml !== false) {
                    $txt = $extractCleanText($xml);
                    if (! empty($txt)) {
                        $this->extractedHeader = $txt;
                    }
                }
            }
            if (preg_match('/^word\/footer\d*\.xml$/i', $name) && empty($this->extractedFooter)) {
                $xml = $zip->getFromIndex($i);
                if ($xml !== false) {
                    $txt = $extractCleanText($xml);
                    if (! empty($txt)) {
                        $this->extractedFooter = $txt;
                    }
                }
            }
        }
    }

    /**
     * Parse word/_rels/document.xml.rels into [id => [type, target]].
     */
    private function parseRelationships(ZipArchive $zip): array
    {
        $rels = [];
        $xml = $zip->getFromName('word/_rels/document.xml.rels');
        if ($xml !== false) {
            $dom = new DOMDocument;
            @$dom->loadXML($xml, LIBXML_NOENT | LIBXML_NOWARNING | LIBXML_NOERROR);
            foreach ($dom->getElementsByTagName('Relationship') as $rel) {
                $id = $rel->getAttribute('Id');
                $type = $rel->getAttribute('Type');
                $target = $rel->getAttribute('Target');
                $rels[$id] = [
                    'type' => $type,
                    'target' => $target,
                ];
            }
        }

        return $rels;
    }

    /**
     * Parse word/numbering.xml into [numId => [ilvl => format]].
     */
    private function parseNumbering(ZipArchive $zip): array
    {
        $map = [];
        $xml = $zip->getFromName('word/numbering.xml');
        if ($xml === false) {
            return $map;
        }

        $dom = new DOMDocument;
        @$dom->loadXML($xml, LIBXML_NOENT | LIBXML_NOWARNING | LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);

        $abstractNums = [];
        foreach ($xpath->query('//w:abstractNum') as $ab) {
            $abId = $ab->getAttribute('w:abstractNumId');
            $abstractNums[$abId] = [];
            foreach ($xpath->query('.//w:lvl', $ab) as $lvl) {
                $ilvl = $lvl->getAttribute('w:ilvl');
                $fmtNode = $xpath->query('.//w:numFmt/@w:val', $lvl);
                $fmt = ($fmtNode->length > 0) ? strtolower($fmtNode->item(0)->nodeValue) : 'bullet';
                $abstractNums[$abId][$ilvl] = $fmt;
            }
        }

        foreach ($xpath->query('//w:num') as $num) {
            $numId = $num->getAttribute('w:numId');
            $abRef = $xpath->query('.//w:abstractNumId/@w:val', $num);
            if ($abRef->length > 0) {
                $refId = $abRef->item(0)->nodeValue;
                $map[$numId] = $abstractNums[$refId] ?? [];
            }
        }

        return $map;
    }

    /**
     * Parse word/styles.xml into style map.
     */
    private function parseStyles(ZipArchive $zip): array
    {
        $map = [];
        $xml = $zip->getFromName('word/styles.xml');
        if ($xml === false) {
            return $map;
        }

        $dom = new DOMDocument;
        @$dom->loadXML($xml, LIBXML_NOENT | LIBXML_NOWARNING | LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);

        foreach ($xpath->query('//w:style[@w:type="paragraph"]') as $style) {
            $styleId = $style->getAttribute('w:styleId');
            $nameNode = $xpath->query('.//w:name/@w:val', $style);
            $name = ($nameNode->length > 0) ? strtolower($nameNode->item(0)->nodeValue) : strtolower($styleId);

            $headingLevel = null;
            if (preg_match('/heading\s*(\d)/i', $name, $m) || preg_match('/judul\s*(\d)/i', $name, $m)) {
                $headingLevel = (int) $m[1];
            } elseif ($name === 'title' || $styleId === 'Title' || $name === 'judul') {
                $headingLevel = 1;
            } elseif ($name === 'subtitle' || $styleId === 'Subtitle' || $name === 'subjudul') {
                $headingLevel = 2;
            }

            $map[$styleId] = [
                'name' => $name,
                'heading_level' => $headingLevel,
            ];
        }

        return $map;
    }

    /**
     * Parse a single paragraph <w:p>.
     */
    private function parseParagraph(DOMElement $p, DOMXPath $xpath, ZipArchive $zip, array $rels, array $numberingMap, array $stylesMap): array
    {
        $pageBreakBefore = false;
        $pageBreakAfter = false;
        $headingLevel = null;
        $align = null; // 'center', 'right', 'justify', 'left'
        $isList = false;
        $listType = 'ul'; // 'ul' or 'ol'
        $listLevel = 0;
        $pStyle = '';

        // Check paragraph properties <w:pPr>
        $pPr = $xpath->query('./w:pPr', $p)->item(0);
        if ($pPr) {
            // Check page break before
            if ($xpath->query('./w:pageBreakBefore', $pPr)->length > 0) {
                $pageBreakBefore = true;
            }

            // Check style
            $styleNode = $xpath->query('./w:pStyle/@w:val', $pPr)->item(0);
            if ($styleNode) {
                $pStyle = $styleNode->nodeValue;
                $styleMeta = $stylesMap[$pStyle] ?? null;
                if ($styleMeta && $styleMeta['heading_level'] !== null) {
                    $headingLevel = $styleMeta['heading_level'];
                } elseif (preg_match('/^heading\s*(\d)/i', $pStyle, $hm) || preg_match('/^judul\s*(\d)/i', $pStyle, $hm)) {
                    $headingLevel = (int) $hm[1];
                } elseif (stripos($pStyle, 'title') !== false || stripos($pStyle, 'judul') !== false) {
                    $headingLevel = 1;
                } elseif (stripos($pStyle, 'subtitle') !== false || stripos($pStyle, 'subjudul') !== false) {
                    $headingLevel = 2;
                }
            }

            // Check alignment <w:jc>
            $jcNode = $xpath->query('./w:jc/@w:val', $pPr)->item(0);
            if ($jcNode) {
                $jcVal = strtolower($jcNode->nodeValue);
                if ($jcVal === 'center') {
                    $align = 'center';
                } elseif ($jcVal === 'right' || $jcVal === 'end') {
                    $align = 'right';
                } elseif ($jcVal === 'both' || $jcVal === 'justify') {
                    $align = 'justify';
                } elseif ($jcVal === 'left' || $jcVal === 'start') {
                    $align = 'left';
                }
            }

            // Check list properties <w:numPr>
            $numPr = $xpath->query('./w:numPr', $pPr)->item(0);
            if ($numPr) {
                $numIdNode = $xpath->query('./w:numId/@w:val', $numPr)->item(0);
                $ilvlNode = $xpath->query('./w:ilvl/@w:val', $numPr)->item(0);
                $numId = $numIdNode ? $numIdNode->nodeValue : '1';
                $listLevel = $ilvlNode ? (int) $ilvlNode->nodeValue : 0;
                $fmt = $numberingMap[$numId][$listLevel] ?? 'bullet';
                $isList = true;
                $listType = ($fmt === 'bullet') ? 'ul' : 'ol';
            }
        }

        // Parse runs and inline children
        $inlines = [];
        foreach ($p->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if ($child->nodeName === 'w:r') {
                $runResult = $this->parseRun($child, $xpath, $zip, $rels);
                if ($runResult['is_page_break']) {
                    $pageBreakAfter = true;
                }
                if ($runResult['html'] !== '') {
                    $inlines[] = $runResult['html'];
                }
            } elseif ($child->nodeName === 'w:hyperlink') {
                $rId = $child->getAttributeNS(self::R_NS, 'id') ?: $child->getAttribute('r:id');
                $targetUrl = $rels[$rId]['target'] ?? '#';
                $linkContent = [];
                foreach ($child->childNodes as $subR) {
                    if ($subR->nodeType === XML_ELEMENT_NODE && $subR->nodeName === 'w:r') {
                        $subRes = $this->parseRun($subR, $xpath, $zip, $rels);
                        if ($subRes['html'] !== '') {
                            $linkContent[] = $subRes['html'];
                        }
                    }
                }
                $linkText = implode('', $linkContent);
                if ($linkText !== '') {
                    $inlines[] = '<a href="'.htmlspecialchars($targetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')."\" target=\"_blank\" class=\"text-indigo-600 dark:text-indigo-400 underline\">{$linkText}</a>";
                }
            }
        }

        $rawText = trim(strip_tags(implode('', $inlines)));
        $innerHtml = trim(implode('', $inlines));

        // Detect Table of Contents styles
        $isTocStyle = stripos($pStyle, 'toc') !== false || stripos($pStyle, 'daftar isi') !== false;

        // Compose CSS classes and styles
        $classes = [];
        $styleAttr = [];

        if ($align === 'center') {
            $classes[] = 'text-center';
            $styleAttr[] = 'text-align: center;';
        } elseif ($align === 'right') {
            $classes[] = 'text-right';
            $styleAttr[] = 'text-align: right;';
        } elseif ($align === 'justify') {
            $classes[] = 'text-justify';
            $styleAttr[] = 'text-align: justify;';
        } elseif ($align === 'left') {
            $classes[] = 'text-left';
            $styleAttr[] = 'text-align: left;';
        }

        $classStr = ! empty($classes) ? ' class="'.implode(' ', $classes).'"' : '';
        $styleStr = ! empty($styleAttr) ? ' style="'.implode(' ', $styleAttr).'"' : '';

        // If paragraph is empty and no images, return empty
        if ($innerHtml === '' && ! str_contains($innerHtml, '<img')) {
            return [
                'html' => ($pageBreakBefore || $pageBreakAfter) ? '' : '<p><br /></p>',
                'html_content' => '',
                'page_break_before' => $pageBreakBefore,
                'page_break_after' => $pageBreakAfter,
                'is_list' => false,
                'list_type' => 'ul',
                'list_level' => 0,
            ];
        }

        // If list item
        if ($isList) {
            return [
                'html' => '',
                'html_content' => $innerHtml,
                'page_break_before' => $pageBreakBefore,
                'page_break_after' => $pageBreakAfter,
                'is_list' => true,
                'list_type' => $listType,
                'list_level' => $listLevel,
            ];
        }

        // If heading
        if ($headingLevel !== null && $headingLevel >= 1 && $headingLevel <= 6) {
            $hTag = "h{$headingLevel}";
            $hClass = ($headingLevel === 1)
                ? 'heading-chapter font-bold text-xl my-6 '.($align === 'center' ? 'text-center uppercase' : '')
                : (($headingLevel === 2) ? 'heading-section font-bold text-lg my-4' : 'heading-subsection font-semibold text-base my-3');

            $fullClass = trim("{$hClass} ".implode(' ', $classes));
            $hHtml = "<{$hTag} class=\"{$fullClass}\"{$styleStr}>{$innerHtml}</{$hTag}>";

            return [
                'html' => $hHtml,
                'html_content' => $innerHtml,
                'page_break_before' => $pageBreakBefore,
                'page_break_after' => $pageBreakAfter,
                'is_list' => false,
                'list_type' => 'ul',
                'list_level' => 0,
            ];
        }

        // If TOC entry
        if ($isTocStyle) {
            $tocHtml = "<p class=\"toc-entry py-0.5 text-sm font-serif text-gray-800 dark:text-gray-200{$classStr}\"{$styleStr}>{$innerHtml}</p>";

            return [
                'html' => $tocHtml,
                'html_content' => $innerHtml,
                'page_break_before' => $pageBreakBefore,
                'page_break_after' => $pageBreakAfter,
                'is_list' => false,
                'list_type' => 'ul',
                'list_level' => 0,
            ];
        }

        // Standard paragraph with preserved alignment and typography
        $pHtml = "<p{$classStr}{$styleStr}>{$innerHtml}</p>";

        return [
            'html' => $pHtml,
            'html_content' => $innerHtml,
            'page_break_before' => $pageBreakBefore,
            'page_break_after' => $pageBreakAfter,
            'is_list' => false,
            'list_type' => 'ul',
            'list_level' => 0,
        ];
    }

    /**
     * Parse a single run <w:r>.
     */
    private function parseRun(DOMElement $r, DOMXPath $xpath, ZipArchive $zip, array $rels): array
    {
        $isPageBreak = false;
        $htmlParts = [];

        // Formatting flags
        $isBold = false;
        $isItalic = false;
        $isUnderline = false;
        $isStrike = false;
        $vertAlign = null; // 'superscript' or 'subscript'
        $color = null;
        $highlight = null;
        $fontSize = null;
        $fontFamily = null;

        $rPr = $xpath->query('./w:rPr', $r)->item(0);
        if ($rPr) {
            // Bold
            if ($xpath->query('./w:b[not(@w:val="0" or @w:val="false")]', $rPr)->length > 0) {
                $isBold = true;
            }
            // Italic
            if ($xpath->query('./w:i[not(@w:val="0" or @w:val="false")]', $rPr)->length > 0) {
                $isItalic = true;
            }
            // Underline (any u tag except none)
            if ($xpath->query('./w:u[not(@w:val="none")]', $rPr)->length > 0) {
                $isUnderline = true;
            }
            // Strike
            if ($xpath->query('./w:strike[not(@w:val="0" or @w:val="false")] | ./w:dstrike', $rPr)->length > 0) {
                $isStrike = true;
            }
            // Vertical align (superscript / subscript)
            $vaNode = $xpath->query('./w:vertAlign/@w:val', $rPr)->item(0);
            if ($vaNode) {
                $vertAlign = strtolower($vaNode->nodeValue);
            }
            // Color
            $colorNode = $xpath->query('./w:color/@w:val', $rPr)->item(0);
            if ($colorNode && strtolower($colorNode->nodeValue) !== 'auto') {
                $color = '#'.ltrim($colorNode->nodeValue, '#');
            }
            // Highlight
            $hlNode = $xpath->query('./w:highlight/@w:val', $rPr)->item(0);
            if ($hlNode && strtolower($hlNode->nodeValue) !== 'none') {
                $highlight = strtolower($hlNode->nodeValue);
            }
            // Font size (half-points)
            $szNode = $xpath->query('./w:sz/@w:val', $rPr)->item(0);
            if ($szNode) {
                $pt = ((int) $szNode->nodeValue) / 2;
                if ($pt >= 8 && $pt <= 72) {
                    $fontSize = "{$pt}pt";
                }
            }
            // Font family
            $fontNode = $xpath->query('./w:rFonts/@w:ascii | ./w:rFonts/@w:hAnsi', $rPr)->item(0);
            if ($fontNode) {
                $fontFamily = $fontNode->nodeValue;
            }
        }

        // Child elements in <w:r>
        foreach ($r->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if ($child->nodeName === 'w:t') {
                $text = htmlspecialchars($child->nodeValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $htmlParts[] = $text;
            } elseif ($child->nodeName === 'w:br') {
                $brType = $child->getAttributeNS(self::W_NS, 'type') ?: $child->getAttribute('w:type');
                if ($brType === 'page') {
                    $isPageBreak = true;
                } else {
                    $htmlParts[] = '<br />';
                }
            } elseif ($child->nodeName === 'w:tab') {
                $htmlParts[] = '&emsp;';
            } elseif ($child->nodeName === 'w:drawing' || $child->nodeName === 'w:pict') {
                $imgHtml = $this->parseImage($child, $xpath, $zip, $rels);
                if (! empty($imgHtml)) {
                    $htmlParts[] = $imgHtml;
                }
            }
        }

        $content = implode('', $htmlParts);
        if ($content === '') {
            return ['html' => '', 'is_page_break' => $isPageBreak];
        }

        // Wrap with inline tags
        if ($isUnderline) {
            $content = "<u>{$content}</u>";
        }
        if ($isStrike) {
            $content = "<s>{$content}</s>";
        }
        if ($isItalic) {
            $content = "<em>{$content}</em>";
        }
        if ($isBold) {
            $content = "<strong>{$content}</strong>";
        }
        if ($vertAlign === 'superscript') {
            $content = "<sup>{$content}</sup>";
        } elseif ($vertAlign === 'subscript') {
            $content = "<sub>{$content}</sub>";
        }

        // Wrap with span for color, highlight, font-size, font-family
        $styles = [];
        if ($color) {
            $styles[] = "color: {$color};";
        }
        if ($highlight) {
            $styles[] = "background-color: {$highlight};";
        }
        if ($fontSize) {
            $styles[] = "font-size: {$fontSize};";
        }
        if ($fontFamily) {
            $styles[] = "font-family: '{$fontFamily}', serif;";
        }

        if (! empty($styles)) {
            $styleStr = implode(' ', $styles);
            $content = "<span style=\"{$styleStr}\">{$content}</span>";
        }

        return [
            'html' => $content,
            'is_page_break' => $isPageBreak,
        ];
    }

    /**
     * Parse table element <w:tbl>.
     */
    private function parseTable(DOMElement $tbl, DOMXPath $xpath, ZipArchive $zip, array $rels, array $numberingMap, array $stylesMap): string
    {
        $rows = $xpath->query('./w:tr', $tbl);
        if ($rows->length === 0) {
            return '';
        }

        // Table alignment
        $tblAlign = '';
        $tblJc = $xpath->query('./w:tblPr/w:jc/@w:val', $tbl)->item(0);
        if ($tblJc) {
            $alignVal = strtolower($tblJc->nodeValue);
            if ($alignVal === 'center') {
                $tblAlign = ' mx-auto';
            } elseif ($alignVal === 'right') {
                $tblAlign = ' ml-auto';
            }
        }

        $headRows = [];
        $bodyRows = [];

        foreach ($rows as $tr) {
            $isHeader = $xpath->query('./w:trPr/w:tblHeader', $tr)->length > 0;
            $cells = $xpath->query('./w:tc', $tr);
            $cellsHtml = [];

            foreach ($cells as $tc) {
                $cellTag = $isHeader ? 'th' : 'td';
                $attrs = [];
                $classes = ['border', 'border-gray-300', 'dark:border-gray-700', 'p-2'];
                $styles = [];

                if ($isHeader) {
                    $classes[] = 'bg-gray-100';
                    $classes[] = 'dark:bg-gray-800';
                    $classes[] = 'font-semibold';
                    $classes[] = 'text-center';
                }

                $tcPr = $xpath->query('./w:tcPr', $tc)->item(0);
                if ($tcPr) {
                    // Colspan
                    $gridSpan = $xpath->query('./w:gridSpan/@w:val', $tcPr)->item(0);
                    if ($gridSpan) {
                        $attrs[] = 'colspan="'.((int) $gridSpan->nodeValue).'"';
                    }

                    // Background color (shd)
                    $shd = $xpath->query('./w:shd/@w:fill', $tcPr)->item(0);
                    if ($shd) {
                        $fill = strtolower($shd->nodeValue);
                        if ($fill !== 'auto' && $fill !== 'none' && $fill !== 'ffffff') {
                            $styles[] = "background-color: #{$fill};";
                        }
                    }

                    // Vertical alignment
                    $vAlign = $xpath->query('./w:vAlign/@w:val', $tcPr)->item(0);
                    if ($vAlign) {
                        $styles[] = 'vertical-align: '.strtolower($vAlign->nodeValue).';';
                    }
                }

                // Parse cell contents (<w:p>)
                $cellBlocks = [];
                $tcParas = $xpath->query('./w:p', $tc);
                foreach ($tcParas as $tcp) {
                    $pRes = $this->parseParagraph($tcp, $xpath, $zip, $rels, $numberingMap, $stylesMap);
                    if (! empty($pRes['html'])) {
                        $cellBlocks[] = $pRes['html'];
                    }
                }

                $cellContent = ! empty($cellBlocks) ? implode('', $cellBlocks) : '&nbsp;';
                $attrStr = ! empty($attrs) ? ' '.implode(' ', $attrs) : '';
                $classStr = ' class="'.implode(' ', $classes).'"';
                $styleStr = ! empty($styles) ? ' style="'.implode(' ', $styles).'"' : '';

                $cellsHtml[] = "<{$cellTag}{$attrStr}{$classStr}{$styleStr}>{$cellContent}</{$cellTag}>";
            }

            $rowHtml = "<tr>\n".implode("\n", $cellsHtml)."\n</tr>";
            if ($isHeader) {
                $headRows[] = $rowHtml;
            } else {
                $bodyRows[] = $rowHtml;
            }
        }

        $tableHtml = "<table class=\"w-full border-collapse my-4 text-sm border border-gray-300 dark:border-gray-700{$tblAlign}\">\n";
        if (! empty($headRows)) {
            $tableHtml .= "<thead>\n".implode("\n", $headRows)."\n</thead>\n";
        }
        if (! empty($bodyRows)) {
            $tableHtml .= "<tbody>\n".implode("\n", $bodyRows)."\n</tbody>\n";
        }
        $tableHtml .= '</table>';

        return $tableHtml;
    }

    /**
     * Parse drawing/image element into base64 data URI image tag.
     */
    private function parseImage(DOMElement $container, DOMXPath $xpath, ZipArchive $zip, array $rels): string
    {
        $rId = null;

        // OpenXML DrawingML
        $blip = $xpath->query('.//a:blip/@r:embed', $container)->item(0);
        if ($blip) {
            $rId = $blip->nodeValue;
        }

        // VML fallback
        if (! $rId) {
            $vmlImg = $xpath->query('.//v:imagedata/@r:id', $container)->item(0);
            if ($vmlImg) {
                $rId = $vmlImg->nodeValue;
            }
        }

        if (! $rId || ! isset($rels[$rId])) {
            return '';
        }

        $target = $rels[$rId]['target'];
        // Path in zip is usually 'word/' . target unless absolute
        $zipPath = str_starts_with($target, 'word/') ? $target : 'word/'.ltrim($target, '/');
        $imgBytes = $zip->getFromName($zipPath);
        if ($imgBytes === false) {
            return '';
        }

        $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        $base64 = base64_encode($imgBytes);

        return "<img src=\"data:{$mime};base64,{$base64}\" class=\"max-w-full h-auto my-2 inline-block rounded shadow-xs\" alt=\"Dokumen Gambar\" />";
    }

    /**
     * Render an authentic visual page break element.
     */
    private function renderPageBreak(): string
    {
        return '<div class="page-break my-8 border-b-2 border-dashed border-gray-400 dark:border-gray-600 text-center py-2 select-none" data-page-break="true"><span class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full text-xs text-gray-500 font-mono">Pemisah Halaman</span></div>';
    }

    /**
     * Fallback for simulated or plain raw content.
     */
    private function fallbackRawContent(string $raw): string
    {
        if (! empty($raw) && (str_contains($raw, '<p>') || str_contains($raw, '<div') || str_contains($raw, 'BAB '))) {
            return $raw;
        }

        return '<p>Dokumen Microsoft Word berhasil diimpor.</p>';
    }
}
