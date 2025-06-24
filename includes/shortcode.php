<?php
/**
 * Shortcode [liste_test_plugins type="test"]
 * Cette fonction remplace désormais rl_afficher_liste_restaurants.
 */
function rl_afficher_liste( $atts ) {
    // 1) Valeurs par défaut
    $atts      = shortcode_atts( [ 'type' => 'test' ], $atts, 'liste_test_plugins' );
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

    // 3) Formulaire
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

    // 4) meta_query
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

    // 5) requête
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
            ?>
            <div class="restaurant-card">
                <div class="restaurant-left">
                    <?php
                    $img = get_field( 'image_restaurant', $id );
                    if ( $img && is_array( $img ) ) {
                        printf(
                            '<img src="%s" class="restaurant-image" alt="%s"/>',
                            esc_url( $img['url'] ),
                            esc_attr( get_the_title() )
                        );
                    }
                    ?>
                    <div class="restaurant-info">
                        <h3><?php echo esc_html( get_the_title() ); ?></h3>
                        <?php if ( $post_type === 'test' ) :
                            $avis = get_field( 'avis', $id );
                            if ( $avis ) echo '<p>Avis : '. esc_html( $avis ) .'</p>';
                            // etc.
                        endif; ?>
                    </div>
                </div>
                <div class="restaurant-divider-vertical"></div>
                <div class="restaurant-right">
                    <?php
                    $link = get_field( 'lien_reservation', $id );
                    if ( $link ) {
                        printf(
                            '<a class="reserve-button" href="%s" target="_blank">Réserver</a>',
                            esc_url( $link )
                        );
                    }
                    ?>
                </div>
            </div>
            <?php
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
