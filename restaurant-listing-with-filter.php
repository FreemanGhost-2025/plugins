<?php
/*
Plugin Name: Restaurant Listing with Filter
Plugin URI: https://github.com/FreemanGhost-2025/restaurant-listing
Description: Plugin polyvalent pour lister plusieurs CPT (restaurant, street_food, coffee_shop...) via shortcode dynamique et filtres ACF.
Version: 1.1.0
Author: Freeman Ghost
Author URI: https://github.com/FreemanGhost-2025
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GitHub Plugin URI: https://github.com/FreemanGhost-2025/restaurant-listing
GitHub Branch: main
*/

// 1. Enqueue styles
function rl_enqueue_styles() {
    wp_enqueue_style('restaurant-listing-style-filter', plugin_dir_url(__FILE__) . 'assets/style.css');
}
add_action('wp_enqueue_scripts', 'rl_enqueue_styles');

// 2. Register all CPTs dynamically
function rl_register_all_cpts() {
    $types = [
        'restaurant'   => ['Restaurants','Restaurant','dashicons-store'],
        'street_food'  => ['Street Food','Street Food','dashicons-carrot'],
        'coffee_shop'  => ['Coffee Shops','Café','dashicons-coffee'],
    ];
    foreach ($types as $slug => $data) {
        list($plural, $singular, $icon) = $data;
        register_post_type($slug, [
            'labels'       => [
                'name'          => $plural,
                'singular_name' => $singular,
                'menu_name'     => $plural,
            ],
            'public'        => true,
            'has_archive'   => true,
            'rewrite'       => ['slug' => $slug],
            'show_in_rest'  => true,
            'supports'      => ['title', 'editor', 'thumbnail'],
            'menu_position' => 5,
            'menu_icon'     => $icon,
            'taxonomies'    => ['category'],
        ]);
        register_taxonomy_for_object_type('category', $slug);
    }
}
add_action('init', 'rl_register_all_cpts');

// 3. Include shortcode logic
require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
