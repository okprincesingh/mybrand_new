<?php

if (!function_exists('load_json_content')) {
    function load_json_content(string $relativePath, array $defaults = []): array
    {
        $fullPath = __DIR__ . '/../' . ltrim($relativePath, '/');
        if (!is_file($fullPath)) {
            return $defaults;
        }

        $raw = file_get_contents($fullPath);
        if ($raw === false || trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $decoded);
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize_rich_html')) {
    function normalize_wordpress_rich_html(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        // Remove Gutenberg comments and Visual Composer-like shortcodes.
        $html = preg_replace('/<!--\s*\/?wp:[\s\S]*?-->/', ' ', $html) ?? $html;
        $html = preg_replace('/\[(?:\/)?[a-zA-Z0-9_:-]+(?:\s+[^\]]*)?\]/', ' ', $html) ?? $html;

        // Clean common broken punctuation artifacts from imported WP dumps.
        $html = preg_replace('/(\d)\?{2,}(\d)/', '$1-$2', $html) ?? $html;
        $html = preg_replace('/\?{2,}/', '', $html) ?? $html;

        // Normalize whitespace without breaking HTML structure.
        $html = str_replace("\xC2\xA0", ' ', $html);
        $html = preg_replace('/[ \t]{2,}/', ' ', $html) ?? $html;

        return trim($html);
    }

    function sanitize_rich_html(string $html): string
    {
        $html = normalize_wordpress_rich_html($html);
        if ($html === '') {
            return '';
        }

        if (!class_exists('DOMDocument')) {
            $allowed = '<p><br><strong><b><em><i><u><s><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div><blockquote><table><thead><tbody><tr><th><td>';
            return strip_tags($html, $allowed);
        }

        $allowedTags = [
            'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
            'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'a', 'span', 'div', 'blockquote',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
        ];
        $allowedAttrs = ['href', 'target', 'rel', 'class', 'style', 'colspan', 'rowspan'];

        $wrapped = '<div>' . $html . '</div>';
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*');
        if ($nodes !== false) {
            $toRemove = [];
            foreach ($nodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }
                $tag = strtolower($node->tagName);
                if (!in_array($tag, $allowedTags, true) && $tag !== 'div') {
                    $toRemove[] = $node;
                    continue;
                }

                $removeAttrs = [];
                foreach (iterator_to_array($node->attributes ?? []) as $attr) {
                    $name = strtolower($attr->nodeName);
                    $value = trim((string) $attr->nodeValue);

                    if (str_starts_with($name, 'on')) {
                        $removeAttrs[] = $name;
                        continue;
                    }
                    if (!in_array($name, $allowedAttrs, true)) {
                        $removeAttrs[] = $name;
                        continue;
                    }
                    if ($name === 'href' && preg_match('/^\s*javascript:/i', $value)) {
                        $removeAttrs[] = $name;
                        continue;
                    }
                    if ($name === 'style' && stripos($value, 'expression(') !== false) {
                        $removeAttrs[] = $name;
                        continue;
                    }
                }
                foreach ($removeAttrs as $attrName) {
                    $node->removeAttribute($attrName);
                }
            }

            foreach ($toRemove as $node) {
                while ($node->firstChild) {
                    $node->parentNode?->insertBefore($node->firstChild, $node);
                }
                $node->parentNode?->removeChild($node);
            }
        }

        $root = $dom->getElementsByTagName('div')->item(0);
        if (!$root) {
            return '';
        }
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }
        return $out;
    }
}
