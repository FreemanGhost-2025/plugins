<?php
/*
Plugin Name: Listing with Filter
Plugin URI: https://github.com/FreemanGhost-2025/plugins
Description: Plugin polyvalent pour lister plusieurs CPT (restaurant, street_food, coffee_shop...) via shortcode dynamique et filtres ACF.
Version: 2.2.1
Author: Freeman Ghost
Author URI: https://github.com/FreemanGhost-2025
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GitHub Plugin URI: https://github.com/FreemanGhost-2025/plugins
GitHub Branch: main
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RL_Listing_With_Filter {

    public static function init() {
        add_action( 'init',               [ __CLASS__, 'register_cpts'   ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
        // on change ici pour pointer vers rl_afficher_liste
        add_shortcode( 'liste_test_plugins', [ __CLASS__, 'rl_afficher_liste' ] );
    }

    public static function enqueue_styles() {
        wp_enqueue_style(
            'rl-listing-style',
            plugin_dir_url( __FILE__ ) . 'assets/style.css'
        );
    }

    public static function register_cpts() {
        $types = [
            'test' => [ 'Tests', 'Test', 'dashicons-store' ],
        ];
        foreach ( $types as $slug => $data ) {
            list( $plural, $singular, $icon ) = $data;
            register_post_type( $slug, [
                'labels'       => [
                    'name'          => $plural,
                    'singular_name' => $singular,
                    'menu_name'     => $plural,
                ],
                'public'        => true,
                'has_archive'   => true,
                'rewrite'       => [ 'slug' => $slug ],
                'show_in_rest'  => true,
                'supports'      => [ 'title', 'editor', 'thumbnail' ],
                'menu_position' => 5,
                'menu_icon'     => $icon,
                'taxonomies'    => [ 'category' ],
            ] );
            register_taxonomy_for_object_type( 'category', $slug );
        }
    }

    // on inclut le shortcode (le rendu est désormais dans rl_afficher_liste)
    public static function render_shortcode( $atts ) {
        return self::rl_afficher_liste( $atts );
    }

    // ici on définit la fonction renommée
    public static function rl_afficher_liste( $atts ) {
        // ... tout le code de filtrage et d'affichage que tu avais dans rl_afficher_liste_restaurants ...
        // par exemple :
        $atts = shortcode_atts( [ 'type' => 'test' ], $atts, 'liste_test_plugins' );
        $post_type = sanitize_key( $atts['type'] );
        // etc...
        // n’oublie pas le return ob_get_clean();
    }
}

// initialise tout
RL_Listing_With_Filter::init();

// on inclut la logique complète dans un fichier séparé si tu préfères
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcode.php';


// Ajout du filtre par catégorie
include_once plugin_dir_path(__FILE__) . 'includes/filters/category-filter.php';
