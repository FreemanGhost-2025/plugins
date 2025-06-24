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
            [ 'name'=>'avis',                 'placeholder'=>'Avis',                 'type'=>'text'   ],
            [ 'name'=>'type_de_restauration', 'placeholder'=>'Type de restauration', 'type'=>'text'   ],
            [ 'name'=>'budget_moyen',         'placeholder'=>'Budget moyen',        'type'=>'number' ],
            [ 'name'=>'populaire_pour', 'placeholder'=>'Populaire pour', 'type'=>'checkbox'],
        ]
    ];

    // 3) Affichage du formulaire de filtres
    echo '<form method="GET" class="restaurant-filter">';
    if ( isset( $filtres_config[ $post_type ] ) ) {
        foreach ( $filtres_config[ $post_type ] as $f ) {
            $name = $f['name'];
            $val  = $_GET[ $name ] ?? '';

if ( $f['type'] === 'checkbox' ) {
    // on récupère un ID de post 'test' pour charger la config ACF
    $dummy = get_posts([
        'post_type'      => $post_type,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);
    if ( ! empty( $dummy ) ) {
        $field = get_field_object( $name, $dummy[0] );
    } else {
        $field = get_field_object( $name );
    }

    if ( ! empty( $field['choices'] ) ) {
        echo '<div class="filter-field"><span class="filter-label">'
             . esc_html( $f['placeholder'] )
             . '</span>';
        $selected = (array) ( $_GET[ $name ] ?? [] );

        foreach ( $field['choices'] as $value => $label ) {
            $checked = in_array( $value, $selected, true ) ? ' checked' : '';
            // type="checkbox", name="populaire_pour[]" et value dynamiques
            printf(
                '<label><input type="checkbox" name="%1$s[]" value="%2$s"%3$s> %4$s</label>',
                esc_attr( $name ),   // => name="populaire_pour[]"
                esc_attr( $value ),  // valeur de la choice
                $checked,
                esc_html( $label )   // label de la choice
            );
        }
        echo '</div>';
    }
    continue;
} else {
                // Champ text ou number
                $attrs = '';
                if ( isset( $f['min'] ) ) $attrs .= ' min="'. intval( $f['min'] ) .'"';
                if ( isset( $f['max'] ) ) $attrs .= ' max="'. intval( $f['max'] ) .'"';
                printf(
                    '<div class="filter-field"><input type="%s" name="%s" placeholder="%s" value="%s"%s /></div>',
                    esc_attr( $f['type'] ),
                    esc_attr( $name ),
                    esc_attr( $f['placeholder'] ),
                    esc_attr( $val ),
                    $attrs
                );
            }
        }
    }
    echo '<div class="filter-field"><button type="submit">Filtrer</button></div>';
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
                $or[] = [
                    'key'     => 'populaire_pour',
                    'value'   => '"' . sanitize_text_field( $v ) . '"',
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
              // Bloc gauche
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
                  // Champs ACF “test”
                  $avis = get_field( 'avis', $id );
                  $type = get_field( 'type_de_restauration', $id );
                  $popu = get_field( 'populaire_pour', $id );
                  $description = get_field( 'description', $id );
                  $budg = get_field( 'budget_moyen', $id );


                  if ( $avis ) echo '<p>⭐ '. esc_html( $avis ) .'</p>';
                  if ( $type ) echo '<p><i class="fa-solid fa-utensils"></i> '. esc_html( $type ) .'</p>';
                   if ( $description )  echo '<p> '.   esc_html( $description ).' </p>';
                  if ( is_array( $popu ) && ! empty( $popu ) ) {
                      echo '<p><strong>Populaire pour :</strong> '. implode( ', ', array_map( 'esc_html', $popu ) ) .'</p>';
                  }
                  if ( $budg ) echo '<p>Budget : '. esc_html( $budg ) .' FCFA</p>';
                echo '</div>';
              echo '</div>';

              // Séparateur
              echo '<div class="restaurant-divider-vertical"></div>';

              // Bloc droite
              echo '<div class="restaurant-right">';
                $link = get_field( 'reservation', $id );
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
add_shortcode( 'liste_test_plugins', 'rl_afficher_liste' );
