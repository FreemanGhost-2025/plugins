<?php
/*
Plugin Name: Listing with Filter
Plugin URI: https://github.com/FreemanGhost-2025/plugins
Description: Plugin polyvalent pour lister plusieurs CPT (restaurant, street_food, coffee_shop...) via shortcode dynamique et filtres ACF.
Version: 1.2.1
Author: Freeman Ghost
Author URI: https://github.com/FreemanGhost-2025
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GitHub Plugin URI: https://github.com/FreemanGhost-2025/plugins
GitHub Branch: main
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sécurité : empêche l'accès direct
}

class RL_Listing_With_Filter {

    /** Initialise hooks et shortcode */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_cpts' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
        add_shortcode( 'liste_test_plugins', [ __CLASS__, 'render_shortcode' ] );
    }

    /** Enqueue CSS du plugin */
    public static function enqueue_styles() {
        wp_enqueue_style(
            'rl-listing-style',
            plugin_dir_url( __FILE__ ) . 'assets/style.css'
        );
    }

    /** Enregistrement des CPT */
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

    /**
     * Callback du shortcode [liste_test_plugins type="test"]
     */
    public static function render_shortcode( $atts ) {
        // 1) Valeurs par défaut
        $atts = shortcode_atts([
            'type' => 'test',
        ], $atts, 'liste_test_plugins');
        $post_type = sanitize_key( $atts['type'] );

        // 2) Configuration des filtres
        $filtres_config = [
            'test' => [
                [ 'name'=>'avis',                 'placeholder'=>'Avis',                 'type'=>'text'   ],
                [ 'name'=>'type_de_restaurant',   'placeholder'=>'Type de restaurant',   'type'=>'text'   ],
                [ 'name'=>'services_disponibles', 'placeholder'=>'Services disponibles', 'type'=>'text'   ],
                [ 'name'=>'budget_moyen',         'placeholder'=>'Budget moyen',         'type'=>'number' ],
            ],
        ];

        // 3) Affichage du formulaire de filtres
        echo '<form method="GET" class="restaurant-filter">';
        if ( isset( $filtres_config[ $post_type ] ) ) {
            foreach ( $filtres_config[ $post_type ] as $f ) {
                $val   = esc_attr( $_GET[ $f['name'] ] ?? '' );
                $attrs = '';
                if ( isset( $f['min'] ) ) $attrs .= ' min="'. intval( $f['min'] ) .'"';
                if ( isset( $f['max'] ) ) $attrs .= ' max="'. intval( $f['max'] ) .'"';
                printf(
                    '<input type="%s" name="%s" placeholder="%s" value="%s"%s/>',
                    esc_attr( $f['type'] ),
                    esc_attr( $f['name'] ),
                    esc_attr( $f['placeholder'] ),
                    $val,
                    $attrs
                );
            }
        }
        echo '<button type="submit">Filtrer</button>';
        echo '</form>';

        // 4) Construction du meta_query
        $meta_query = [ 'relation' => 'AND' ];
        if ( $post_type === 'test' ) {
            if ( ! empty( $_GET['avis'] ) ) {
                $meta_query[] = [
                    'key'     => 'avis',
                    'value'   => sanitize_text_field( $_GET['avis'] ),
                    'compare' => 'LIKE',
                ];
            }
            if ( ! empty( $_GET['type_de_restaurant'] ) ) {
                $meta_query[] = [
                    'key'     => 'type_de_restaurant',
                    'value'   => sanitize_text_field( $_GET['type_de_restaurant'] ),
                    'compare' => 'LIKE',
                ];
            }
            if ( ! empty( $_GET['services_disponibles'] ) ) {
                $meta_query[] = [
                    'key'     => 'services_disponibles',
                    'value'   => sanitize_text_field( $_GET['services_disponibles'] ),
                    'compare' => 'LIKE',
                ];
            }
            if ( ! empty( $_GET['budget_moyen'] ) ) {
                $meta_query[] = [
                    'key'     => 'budget_moyen',
                    'value'   => intval( $_GET['budget_moyen'] ),
                    'type'    => 'NUMERIC',
                    'compare' => '<=',
                ];
            }
        }

        // 5) Exécution de la requête
        $args = [
            'post_type'      => $post_type,
            'posts_per_page' => -1,
        ];
        if ( count( $meta_query ) > 1 ) {
            $args['meta_query'] = $meta_query;
        }

        $q = new WP_Query( $args );
        ob_start();
        if ( $q->have_posts() ) {
            echo '<div class="liste-restaurants">';
            while ( $q->have_posts() ) {
                $q->the_post();
                $id = get_the_ID();

                echo '<div class="restaurant-card">';
                echo '<div class="restaurant-left">';
                    $img = get_field( 'image_restaurant', $id );
                    if ( $img && is_array( $img ) ) {
                        printf(
                            '<img src="%s" class="restaurant-image" alt="%s"/>',
                            esc_url( $img['url'] ),
                            esc_attr( get_the_title() )
                        );
                    }
                    echo '<div class="restaurant-info">';
                        printf( '<h3>%s</h3>', esc_html( get_the_title() ) );
                        if ( $post_type === 'test' ) {
                            $avis = get_field( 'avis', $id );
                            $typeR = get_field( 'type_de_restaurant', $id );
                            $serv = get_field( 'services_disponibles', $id );
                            $budg = get_field( 'budget_moyen', $id );
                            if ( $avis ) echo '<p>Avis : '. esc_html( $avis ) .'</p>';
                            if ( $typeR ) echo '<p>Type : '. esc_html( $typeR ) .'</p>';
                            if ( $serv ) echo '<p>Services : '. esc_html( $serv ) .'</p>';
                            if ( $budg ) echo '<p>Budget : '. esc_html( $budg ) .' FCFA</p>';
                        }
                    echo '</div>';
                echo '</div>';

                echo '<div class="restaurant-divider-vertical"></div>';

                echo '<div class="restaurant-right">';
                    $link = get_field( 'lien_reservation', $id );
                    if ( $link ) {
                        printf(
                            '<a class="reserve-button" href="%s" target="_blank">Réserver</a>',
                            esc_url( $link )
                        );
                    }
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            printf(
                '<p>Aucun élément trouvé pour <strong>%s</strong>.</p>',
                esc_html( $post_type )
            );
        }

        return ob_get_clean();
    }
}

// On initialise la classe
RL_Listing_With_Filter::init();