<?php
/*
Plugin Name: Listing with Filter
Plugin URI: https://github.com/FreemanGhost-2025/plugins
Description: Plugin polyvalent pour lister plusieurs CPT (restaurant, street_food, coffee_shop...) via shortcode dynamique et filtres ACF.
Version: 1.2.0
Author: Freeman Ghost
Author URI: https://github.com/FreemanGhost-2025
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GitHub Plugin URI: https://github.com/FreemanGhost-2025/plugins
GitHub Branch: main
*/

// 1. Enqueue styles
unction rl_register_all_cpts() {
    $types = [
        'test'  => ['Tests','Test','dashicons-store'],
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
            'supports'      => ['title','editor','thumbnail'],
            'menu_position' => 5,
            'menu_icon'     => $icon,
            'taxonomies'    => ['category'],   // ← on active les catégories
        ]);
        register_taxonomy_for_object_type('category', $slug);
    }
}
add_action('init','rl_register_all_cpts');

// 3. Include shortcode logic
require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
