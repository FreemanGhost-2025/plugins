<?php
/**
 * Shortcode [liste_test_plugins type="test"]
 */
function rl_afficher_liste( $atts ) {
    // 1) Valeurs par défaut
    $atts = shortcode_atts( [
        'type' => 'test',
    ], $atts, 'liste_test_plugins' );

    $post_type = sanitize_key( $atts['type'] );

    // 2) Configuration des filtres par CPT
    $filtres_config = [
        'test' => [
            [ 'name' => 'avis',                 'placeholder' => 'Avis',                 'type' => 'text'   ],
            [ 'name' => 'type_de_restauration',   'placeholder' => 'Type de restauration',   'type' => 'text'   ],
            [ 'name' => 'budget_moyen',         'placeholder' => 'Budget moyen',         'type' => 'number' ],
            [ 'name'     => 'populaire_pour','placeholder' => 'Populaire pour :','type'     => 'checkbox',
            'options'  => [
                'brunch'      => 'Brunch',
                'happy_hour'  => 'Happy Hour',
                'live_music'  => 'Live Music',
            ],
        ],

            
        ]
    ];

      // 3) Affichage du formulaire de filtres
    echo '<form method="GET" class="restaurant-filter">';
    if ( isset( $filtres_config[ $post_type ] ) ) {
        foreach ( $filtres_config[ $post_type ] as $f ) {
            $val   = esc_attr( $_GET[ $f['name'] ] ?? '' );
            $attrs = '';
            if ( isset( $f['min'] ) ) $attrs .= ' min="'. intval( $f['min'] ) .'"';
            if ( isset( $f['max'] ) ) $attrs .= ' max="'. intval( $f['max'] ) .'"';

            if ( $f['type'] === 'radio' && ! empty( $f['options'] ) ) {
                echo '<div class="radio-group">';
                echo '<span class="radio-label">'. esc_html( $f['placeholder'] ) .'</span>';
                foreach ( $f['options'] as $opt_val => $opt_label ) {
                    $checked = ( (array) ( $_GET[ $f['name'] ] ?? [] ) && in_array( $opt_val, $_GET[ $f['name'] ], true ) ) ? ' checked' : '';
                    printf(
                        '<label><input type="radio" name="%1$s" value="%2$s"%3$s> %4$s</label>',
                        esc_attr( $f['name'] ),
                        esc_attr( $opt_val ),
                        $checked,
                        esc_html( $opt_label )
                    );
                }
                echo '</div>';  // ← fermeture de .radio-group
            } // ← fermeture de if radio

        } // ← fermeture de foreach $filtres_config
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
        if ( ! empty( $_GET['type_de_restauration'] ) ) {
            $meta_query[] = [
                'key'     => 'type_de_restauration',
                'value'   => sanitize_text_field( $_GET['type_de_restauration'] ),
                'compare' => 'LIKE',
            ];
        }
        if ( ! empty( $_GET['populaire_pour'] ) && is_array( $_GET['populaire_pour'] ) ) {
        $or = [ 'relation' => 'OR' ];
        foreach ( $_GET['populaire_pour'] as $v ) {
            $v = sanitize_text_field( $v );
            $or[] = [
              'key'     => 'populaire_pour',
              'value'   => '"' . $v . '"',
              'compare' => 'LIKE',
            ];
        }
        $meta_query[] = $or;
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
                $img = get_field( 'images', $id );
                if ( $img && is_array( $img ) ) {
                    printf(
                        '<img src="%s" class="restaurant-image" alt="%s"/>',
                        esc_url( $img['url'] ),
                        esc_attr( get_the_title() )
                    );
                }
                echo '<div class="restaurant-info">';
                  printf( '<h3>%s</h3>', esc_html( get_the_title() ) );

                  // Affichage des champs ACF pour le CPT "test"
                  if ( $post_type === 'test' ) {
                    
                    $avis   = get_field( 'avis',                 $id );
                    $typeR  = get_field( 'type_de_restauration',   $id );
                    $populairePour = get_field('populaire_pour', $id);
                    $budg   = get_field( 'budget_moyen',         $id );
                    $description   = get_field( 'description',         $id );
                    $adres   = get_field( 'adresse',         $id );
                    $horai   = get_field( 'horaires',         $id );
                    

                    
                    if ( $avis )  echo '<p>⭐ '.     esc_html( $avis ).'</p>';
                    if ( $typeR ) echo '<p> '.     esc_html( $typeR ).'</p>';
                    if ( $description )  echo '<p> '.   esc_html( $description ).' </p>';
                    if ( is_array( $populairePour ) && ! empty( $populairePour ) ) {
                            echo '<p><strong>Populaire pour :</strong> '
                            . implode( ', ', array_map( 'esc_html', $populairePour ) )
                            . '</p>';
                        }
                    
                    if ( $adres )  echo '<p><i class="fa-solid fa-location-dot"></i> '.   esc_html( $adres ).' </p>';
                   
                    
                  }

                echo '</div>'; // .restaurant-info
              echo '</div>'; // .restaurant-left

              echo '<div class="restaurant-divider-vertical"></div>';

              echo '<div class="restaurant-right">';

              // Affiche le budget
                $budg = get_field('budget_moyen', $id);
                if ( $budg ) {
                    echo '<p class="restaurant-budget">Budget : '. esc_html( $budg ) .' FCFA</p>';
                }

                // Affiche les horaires
                $horai = get_field('horaires', $id);
                if ( $horai ) {
                    echo '<p class="restaurant-horaires">Horaires : '. esc_html( $horai ) .'</p>';
                }

                $link = get_field( 'reservation', $id );
                if ( $link ) {
                    printf(
                        
                        '<a class="reserve-button" href="%s" target="_blank">Réserver</a>',
                        esc_url( $link )
                    );
                }
              echo '</div>'; // .restaurant-right

            echo '</div>'; // .restaurant-card
        }
        echo '</div>'; // .liste-restaurants
        wp_reset_postdata();
    } else {
        printf(
            '<p>Aucun élément trouvé pour <strong>%s</strong>.</p>',
            esc_html( $post_type )
        );
    }

    return ob_get_clean();
}
add_shortcode( 'liste_test_plugins', 'rl_afficher_liste' );
