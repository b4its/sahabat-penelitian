<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

class DocumentReviewService
{
    /**
     * Extract all tracked changes from HTML.
     */
    public function extractRevisions(string $html): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-revision-id] | //ins[not(ancestor::*[@data-revision-id])] | //del[not(ancestor::*[@data-revision-id])]');

        $revisions = [];
        $seenIds = [];

        foreach ($nodes as $node) {
            if (! ($node instanceof DOMElement)) {
                continue;
            }

            $tag = strtolower($node->tagName);
            $id = $node->getAttribute('data-revision-id') ?: ($node->getAttribute('id') ?: 'rev-'.Str::random(8));

            if (isset($seenIds[$id])) {
                continue;
            }
            $seenIds[$id] = true;

            $type = $node->getAttribute('data-revision-type') ?: ($tag === 'del' ? 'delete' : 'insert');
            $author = $node->getAttribute('data-author') ?: 'Author';
            $date = $node->getAttribute('data-date') ?: now()->toIso8601String();
            $text = $node->textContent;

            $revisions[] = [
                'id' => $id,
                'type' => $type,
                'author' => $author,
                'date' => $date,
                'text' => $text,
            ];
        }

        return $revisions;
    }

    /**
     * Accept a single revision.
     * Insertions are kept (unwrapped), deletions are removed.
     */
    public function acceptRevision(string $html, string $revisionId): string
    {
        return $this->processRevision($html, $revisionId, true);
    }

    /**
     * Reject a single revision.
     * Insertions are removed, deletions are restored (unwrapped).
     */
    public function rejectRevision(string $html, string $revisionId): string
    {
        return $this->processRevision($html, $revisionId, false);
    }

    /**
     * Accept all revisions in the document.
     */
    public function acceptAllRevisions(string $html): string
    {
        $revisions = $this->extractRevisions($html);
        $current = $html;

        foreach ($revisions as $rev) {
            $current = $this->acceptRevision($current, $rev['id']);
        }

        return $current;
    }

    /**
     * Reject all revisions in the document.
     */
    public function rejectAllRevisions(string $html): string
    {
        $revisions = $this->extractRevisions($html);
        $current = $html;

        foreach ($revisions as $rev) {
            $current = $this->rejectRevision($current, $rev['id']);
        }

        return $current;
    }

    /**
     * Compare two document HTMLs and produce a blackline semantic diff.
     */
    public function compareDocuments(string $originalHtml, string $modifiedHtml, string $author = 'Reviewer'): array
    {
        $origText = strip_tags($originalHtml);
        $modText = strip_tags($modifiedHtml);

        $origWords = preg_split('/(\s+)/u', $origText, -1, PREG_SPLIT_DELIM_CAPTURE);
        $modWords = preg_split('/(\s+)/u', $modText, -1, PREG_SPLIT_DELIM_CAPTURE);

        // Compute simple longest common subsequence / diff on word level
        $diffHtml = $this->diffWords($origWords, $modWords, $author);

        $additions = substr_count($diffHtml, 'data-revision-type="insert"');
        $deletions = substr_count($diffHtml, 'data-revision-type="delete"');

        return [
            'success' => true,
            'author' => $author,
            'comparedAt' => now()->toIso8601String(),
            'diffHtml' => $diffHtml,
            'summary' => [
                'insertions' => $additions,
                'deletions' => $deletions,
                'totalChanges' => $additions + $deletions,
            ],
        ];
    }

    /**
     * Extract anchored comments from document HTML.
     */
    public function extractComments(string $html): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-comment-id]');

        $comments = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $id = $node->getAttribute('data-comment-id');
                $text = $node->getAttribute('data-comment-text') ?: $node->textContent;
                $author = $node->getAttribute('data-comment-author') ?: 'Reviewer';
                $date = $node->getAttribute('data-comment-date') ?: now()->toIso8601String();
                $resolved = $node->getAttribute('data-comment-resolved') === 'true';

                $comments[] = [
                    'id' => $id,
                    'text' => $text,
                    'author' => $author,
                    'date' => $date,
                    'resolved' => $resolved,
                    'anchorText' => $node->textContent,
                ];
            }
        }

        return $comments;
    }

    /**
     * Process single revision accept or reject.
     */
    protected function processRevision(string $html, string $revisionId, bool $accept): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-revision-id="'.htmlspecialchars($revisionId, ENT_QUOTES, 'UTF-8').'"]');

        foreach ($nodes as $node) {
            if (! ($node instanceof DOMElement)) {
                continue;
            }

            $type = $node->getAttribute('data-revision-type') ?: (strtolower($node->tagName) === 'del' ? 'delete' : 'insert');

            if ($accept) {
                if ($type === 'insert') {
                    // Keep inserted content, remove revision wrapper
                    $this->unwrapNode($node);
                } else {
                    // Accept deletion: remove node and content completely
                    $node->parentNode->removeChild($node);
                }
            } else {
                if ($type === 'insert') {
                    // Reject insertion: remove node and content
                    $node->parentNode->removeChild($node);
                } else {
                    // Reject deletion: keep original content, unwrap revision tag
                    $this->unwrapNode($node);
                }
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body) {
            return $html;
        }

        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return trim($result);
    }

    protected function unwrapNode(DOMElement $node): void
    {
        $parent = $node->parentNode;
        while ($node->hasChildNodes()) {
            $child = $node->firstChild;
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['del', 'strike', 's'])) {
                while ($child->hasChildNodes()) {
                    $grandChild = $child->firstChild;
                    $parent->insertBefore($grandChild, $node);
                }
                $node->removeChild($child);
            } else {
                $parent->insertBefore($child, $node);
            }
        }
        $parent->removeChild($node);
    }

    protected function diffWords(array $orig, array $mod, string $author): string
    {
        $matrix = [];
        $lenOrig = count($orig);
        $lenMod = count($mod);

        for ($i = 0; $i <= $lenOrig; $i++) {
            $matrix[$i][0] = 0;
        }
        for ($j = 0; $j <= $lenMod; $j++) {
            $matrix[0][$j] = 0;
        }

        for ($i = 1; $i <= $lenOrig; $i++) {
            for ($j = 1; $j <= $lenMod; $j++) {
                if ($orig[$i - 1] === $mod[$j - 1]) {
                    $matrix[$i][$j] = $matrix[$i - 1][$j - 1] + 1;
                } else {
                    $matrix[$i][$j] = max($matrix[$i - 1][$j], $matrix[$i][$j - 1]);
                }
            }
        }

        // Backtrack to build diff tokens
        $i = $lenOrig;
        $j = $lenMod;
        $rawDiff = [];

        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $orig[$i - 1] === $mod[$j - 1]) {
                $rawDiff[] = ['type' => 'keep', 'text' => $orig[$i - 1]];
                $i--;
                $j--;
            } elseif ($j > 0 && ($i === 0 || $matrix[$i][$j - 1] >= $matrix[$i - 1][$j])) {
                $rawDiff[] = ['type' => 'insert', 'text' => $mod[$j - 1]];
                $j--;
            } elseif ($i > 0 && ($j === 0 || $matrix[$i][$j - 1] < $matrix[$i - 1][$j])) {
                $rawDiff[] = ['type' => 'delete', 'text' => $orig[$i - 1]];
                $i--;
            }
        }

        $rawDiff = array_reverse($rawDiff);

        // Group consecutive runs of the same operation type
        $grouped = [];
        foreach ($rawDiff as $item) {
            if (! empty($grouped) && end($grouped)['type'] === $item['type']) {
                $lastIdx = count($grouped) - 1;
                $grouped[$lastIdx]['text'] .= $item['text'];
            } else {
                $grouped[] = $item;
            }
        }

        $date = now()->toIso8601String();
        $diffHtml = '';

        foreach ($grouped as $group) {
            $escaped = htmlspecialchars($group['text'], ENT_QUOTES, 'UTF-8');
            if ($group['type'] === 'keep') {
                $diffHtml .= $escaped;
            } elseif ($group['type'] === 'insert') {
                $revId = 'rev-'.Str::random(8);
                $diffHtml .= "<span class=\"docx-revision insert\" data-revision-id=\"{$revId}\" data-revision-type=\"insert\" data-author=\"{$author}\" data-date=\"{$date}\">{$escaped}</span>";
            } elseif ($group['type'] === 'delete') {
                $revId = 'rev-'.Str::random(8);
                $diffHtml .= "<span class=\"docx-revision delete\" data-revision-id=\"{$revId}\" data-revision-type=\"delete\" data-author=\"{$author}\" data-date=\"{$date}\"><del>{$escaped}</del></span>";
            }
        }

        return $diffHtml;
    }
}
