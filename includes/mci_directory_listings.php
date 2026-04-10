<?php

declare(strict_types=1);

require_once __DIR__ . '/mci_paths.php';

$mciListingPlaceholderUrl = mci_listing_placeholder_url();

/**
 * Demo directory rows (shared by listings + nearby on business detail).
 * map_lat / map_lon are approximate pin points for distance search.
 * price_range values: free | moderate | pricey | ultra
 */
$mciDirectoryListings = [
    ['title' => 'Property 852', 'category' => 'Real Estate', 'location' => 'Hong Kong', 'slug' => 'property-852', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Office space', 'Leasing', 'Hong Kong', 'Waterfront', 'Commercial'], 'price_range' => 'ultra', 'map_lat' => 22.2623, 'map_lon' => 114.0126],
    ['title' => 'Locker Shop UK Ltd', 'category' => 'Furniture Store', 'location' => 'Chester, UK', 'slug' => 'locker-shop-uk', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Lockers', 'Shelving', 'Storage', 'Chester', 'B2B'], 'price_range' => 'moderate', 'map_lat' => 53.1934, 'map_lon' => -2.8931],
    ['title' => 'JXF Painting Service', 'category' => 'Painter', 'location' => 'Toronto, Ontario', 'slug' => 'jxf-painting', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Painting', 'Interior', 'Toronto', 'Condo', 'Low-VOC'], 'price_range' => 'moderate', 'map_lat' => 43.6532, 'map_lon' => -79.3832],
    ['title' => 'Hunter Hill Physiotherapy', 'category' => 'Health', 'location' => 'Hunters Hill NSW', 'slug' => 'hunter-hill-physio', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Physiotherapy', 'Sports injury', 'Sydney', 'Rehab', 'Health'], 'price_range' => 'pricey', 'map_lat' => -33.8368, 'map_lon' => 151.1473],
    ['title' => 'Famous Veg Restaurant In Bhopal | Naveen', 'category' => 'Restaurant', 'location' => 'Bhopal, MP', 'slug' => 'famous-veg-restaurant-bhopal', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Vegetarian', 'Thali', 'Indian food', 'Family friendly', 'Catering'], 'price_range' => 'free', 'map_lat' => 12.9698, 'map_lon' => 77.7499],
    ['title' => 'Chester Gym & Fitness', 'category' => 'Gym', 'location' => 'Chester, UK', 'slug' => 'chester-gym', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Gym', 'Fitness', 'Personal training', 'Chester'], 'price_range' => 'moderate', 'map_lat' => 53.1962, 'map_lon' => -2.8875],
    ['title' => 'Spark Electricals', 'category' => 'Electrician', 'location' => 'Bangalore', 'slug' => 'spark-electricals', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Electrician', 'Wiring', 'Bangalore', 'Emergency callout'], 'price_range' => 'free', 'map_lat' => 12.9745, 'map_lon' => 77.7462],
    ['title' => 'Sunrise Hotel Rooms', 'category' => 'Hotels', 'location' => 'Patna', 'slug' => 'sunrise-hotel-rooms', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Hotel', 'Budget stay', 'Patna', 'Rooms'], 'price_range' => 'free', 'map_lat' => 25.5941, 'map_lon' => 85.1376],
    ['title' => 'City Park Walks', 'category' => 'Park', 'location' => 'Guwahati', 'slug' => 'city-park-walks', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Park', 'Outdoors', 'Walking', 'Guwahati'], 'price_range' => 'free', 'map_lat' => 26.1445, 'map_lon' => 91.7362],
    ['title' => 'Cafe Aroma', 'category' => 'Cafe', 'location' => 'Delhi', 'slug' => 'cafe-aroma', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Coffee', 'Cafe', 'Delhi', 'Breakfast'], 'price_range' => 'free', 'map_lat' => 28.6139, 'map_lon' => 77.2090],
    ['title' => 'QuickCare Dentist', 'category' => 'Dentist', 'location' => 'Mumbai', 'slug' => 'quickcare-dentist', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Dentist', 'Dental care', 'Mumbai', 'Checkup'], 'price_range' => 'pricey', 'map_lat' => 19.0760, 'map_lon' => 72.8777],
    ['title' => 'Urban Spa House', 'category' => 'Spa', 'location' => 'Jaipur', 'slug' => 'urban-spa-house', 'image' => $mciListingPlaceholderUrl, 'tags' => ['Spa', 'Massage', 'Jaipur', 'Wellness'], 'price_range' => 'pricey', 'map_lat' => 26.9124, 'map_lon' => 75.7873],
];
