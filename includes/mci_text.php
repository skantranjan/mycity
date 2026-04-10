<?php

declare(strict_types=1);

/**
 * Convert stored HTML / rich text to plain paragraphs for safe public display.
 * Strips tags, decodes entities, collapses whitespace; splits on block boundaries.
 *
 * @return list<string>
 */
function mci_plain_paragraphs_from_html(string $html): array
{
    $html = trim($html);
    if ($html === '') {
        return [];
    }

    $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
    $html = preg_replace('/<\/(p|div|h[1-6]|li|tr|section|article)\s*>/i', "\n\n", $html) ?? $html;

    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/[\x{00A0}\x{2007}\x{202F}]/u", ' ', $text) ?? $text;
    $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
    $text = preg_replace('/[ ]*\n[ ]*/u', "\n", $text) ?? $text;
    $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
    $text = trim($text);
    if ($text === '') {
        return [];
    }

    $parts = preg_split('/\n\s*\n/u', $text);
    if ($parts === false) {
        return [trim(str_replace("\n", ' ', $text))];
    }

    $out = [];
    foreach ($parts as $p) {
        $p = trim(preg_replace('/\s+/u', ' ', str_replace("\n", ' ', $p)) ?? '');
        if ($p !== '') {
            $out[] = $p;
        }
    }

    return $out;
}

/** First paragraph only (e.g. FAQ questions). */
function mci_plain_text_one_line(string $html): string
{
    $paras = mci_plain_paragraphs_from_html($html);
    if ($paras === []) {
        return '';
    }
    return $paras[0];
}

/** All paragraphs joined with spaces (e.g. tagline subtitle). */
function mci_plain_text_flat(string $html): string
{
    $paras = mci_plain_paragraphs_from_html($html);
    return $paras === [] ? '' : implode(' ', $paras);
}
