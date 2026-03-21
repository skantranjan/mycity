<?php

declare(strict_types=1);

/**
 * Shared category icon map — single source of truth for the frontend.
 * Keys are category slugs (lowercase, hyphen-separated).
 * Values are unicode emoji characters.
 *
 * When the backend is fully wired, the API will return the icon field
 * directly from mci_categories.icon and this file becomes a fallback only.
 */
$mciCategoryIcons = [
    'real-estate'                    => '🏠',
    'furniture-store'                => '🛋️',
    'painter'                        => '🎨',
    'restaurant'                     => '🍽️',
    'health'                         => '⚕️',
    'automotive'                     => '🚗',
    'hotels'                         => '🏨',
    'gym'                            => '💪',
    'bakery'                         => '🥐',
    'electrician'                    => '⚡',
    'spa-wellness'                   => '🧖',
    'school-education'               => '🎓',
    'bank-finance'                   => '🏦',
    'travel-tourism'                 => '✈️',
    'pet-services'                   => '🐾',
    'pharmacy'                       => '💊',
    'park'                           => '🌳',
    'cafe'                           => '☕',
    'dentist'                        => '🦷',
    'spa'                            => '🧖',
    'airport'                        => '✈️',
    'amusement-park'                 => '🎡',
    'aquarium'                       => '🐠',
    'art-gallery'                    => '🖼️',
    'atm'                            => '💳',
    'bank'                           => '🏦',
    'bar'                            => '🍺',
    'beauty-salon'                   => '💇',
    'bicycle-store'                  => '🚲',
    'books-stationary-store'         => '📚',
    'bus-stations'                   => '🚌',
    'car-dealer'                     => '🚘',
    'car-rental'                     => '🚗',
    'car-repair'                     => '🔧',
    'car-wash'                       => '🚿',
    'cemetery'                       => '⛪',
    'church'                         => '⛪',
    'city-attraction'                => '🌆',
    'clothing-store'                 => '👕',
    'college'                        => '🎓',
    'convenience-store'              => '🏪',
    'courier-services'               => '📦',
    'departmental-store'             => '🏬',
    'doctor'                         => '🩺',
    'electronics-store'              => '📱',
    'fire-station'                   => '🚒',
    'florist'                        => '🌸',
    'funeral-home'                   => '🌹',
    'gift-shop'                      => '🎁',
    'government-office'              => '🏛️',
    'hardware-store'                 => '🔨',
    'hindu-temple'                   => '🛕',
    'home-appliances-products'       => '🏠',
    'hospital'                       => '🏥',
    'industrial-and-manufacturing-supplies' => '🏭',
    'insurance-agency'               => '🛡️',
    'jewelry-store'                  => '💎',
    'laundry'                        => '👕',
    'lawyer'                         => '⚖️',
    'library'                        => '📖',
    'liquor-store'                   => '🍷',
    'locksmith'                      => '🔑',
    'medical-store'                  => '💊',
    'monuments'                      => '🏛️',
    'mosque'                         => '🕌',
    'movie-theater'                  => '🎬',
    'museum'                         => '🏛️',
    'ngo-and-charitable-trusts'      => '🤝',
    'night-club'                     => '🎵',
    'pet-store'                      => '🐾',
    'petrol-pump'                    => '⛽',
    'physiotherapist'                => '🏃',
    'plumber'                        => '🔧',
    'police-station'                 => '🚔',
    'post-office'                    => '📮',
    'pre-schools-and-day-care'       => '🧒',
    'private-coaching-institutes'    => '📝',
    'resorts'                        => '🏖️',
    'school'                         => '🏫',
    'services'                       => '🛠️',
    'shoe-store'                     => '👟',
    'shopping'                       => '🛍️',
    'stadium'                        => '🏟️',
    'supermarket'                    => '🛒',
    'travel-agency'                  => '✈️',
    'university'                     => '🎓',
    'veterinary-care'                => '🐾',
];

/**
 * Render a category icon safely.
 * - If the icon starts with 'bi-' it is treated as a Bootstrap Icon class name.
 * - Otherwise it is treated as a unicode emoji / text.
 * - Returns empty string if $icon is empty.
 *
 * @param string $icon       The stored icon value (emoji or 'bi-xxx' class).
 * @param string $extraClass Additional CSS classes to append (e.g. 'fs-4').
 */
function mci_render_category_icon(string $icon, string $extraClass = ''): string
{
    $icon = trim($icon);
    if ($icon === '') {
        return '';
    }
    $classes = 'bi ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8')
        . ($extraClass !== '' ? ' ' . htmlspecialchars($extraClass, ENT_QUOTES, 'UTF-8') : '');
    if (str_starts_with($icon, 'bi-')) {
        return '<i class="' . $classes . '" aria-hidden="true"></i>';
    }
    // Emoji / plain text
    return '<span aria-hidden="true">' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '</span>';
}

/**
 * Get the icon for a category by its slug, falling back to a default.
 *
 * @param string $slug     The category slug.
 * @param string $default  Fallback icon when no mapping exists.
 */
function mci_category_icon(string $slug, string $default = '📁'): string
{
    global $mciCategoryIcons;
    return $mciCategoryIcons[strtolower(trim($slug))] ?? $default;
}
