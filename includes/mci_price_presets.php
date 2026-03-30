<?php

declare(strict_types=1);

/**
 * Shared price preset definitions for the products and services search pages.
 *
 * Each entry:
 *   'value'  — the <option> value / URL param  (string)
 *   'min'    — price_min query param           (string, '' = no lower bound)
 *   'max'    — price_max query param           (string, '' = no upper bound)
 *   'label'  — display label
 */
function mci_price_presets(): array
{
    return [
        ['value' => 'under500',   'min' => '',      'max' => '500',   'label' => 'Under ₹500'],
        ['value' => '500-2000',   'min' => '500',   'max' => '2000',  'label' => '₹500 – ₹2,000'],
        ['value' => '2000-10000', 'min' => '2000',  'max' => '10000', 'label' => '₹2,000 – ₹10,000'],
        ['value' => 'above10000', 'min' => '10000', 'max' => '',      'label' => 'Above ₹10,000'],
    ];
}

/**
 * Given the current price_min / price_max GET params, return the matching
 * preset value string (e.g. 'under500') or '' if no preset matches.
 */
function mci_price_preset_value(string $priceMin, string $priceMax): string
{
    foreach (mci_price_presets() as $p) {
        if ($p['min'] === $priceMin && $p['max'] === $priceMax) {
            return $p['value'];
        }
    }
    return '';
}

/**
 * Given the current price_min / price_max GET params, return the matching
 * preset label string (e.g. 'Under ₹500') or '' if no preset matches.
 */
function mci_price_preset_label(string $priceMin, string $priceMax): string
{
    foreach (mci_price_presets() as $p) {
        if ($p['min'] === $priceMin && $p['max'] === $priceMax) {
            return $p['label'];
        }
    }
    return '';
}

/**
 * Return a JSON-safe associative array mapping preset value → [min, max],
 * suitable for embedding in a JS var for the price select → hidden fields.
 */
function mci_price_presets_js_map(): string
{
    $map = ['""' => '["",""]'];
    foreach (mci_price_presets() as $p) {
        $map[] = '"' . $p['value'] . '":["' . $p['min'] . '","' . $p['max'] . '"]';
    }
    return '{' . implode(',', $map) . '}';
}
