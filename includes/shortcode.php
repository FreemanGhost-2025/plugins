<?php
/**
 * Shortcode [liste_test_plugins type="test" term="maquis"]
 */
function rl_afficher_liste( $atts ) {
    // 1) Valeurs par défaut
    $atts = shortcode_atts( [
        'type' => 'test',
        'term' => '',
    ], $atts, 'liste_test_plugins' );

    $post_type   = sanitize_key( $atts['type'] );
    $term        = sanitize_key( $atts['term'] );
    $current_url = strtok( $_SERVER['REQUEST_URI'], '?' );

    // 2) Config des filtres, par CPT ET par term (slug de category)
    $filtres_config = [
        'test' => [
            'maquis' => [
                [ 'name'=>'avis',           'placeholder'=>'Avis',           'type'=>'text'   ],
                [ 'name'=>'budget_moyen',   'placeholder'=>'Budget moyen',   'type'=>'number' ],
                [ 'name'=>'populaire_pour', 'placeholder'=>'Populaire pour', 'type'=>'checkbox' ],
            ],
            'street-food' => [
                [ 'name'=>'type_de_restauration','placeholder'=>'Type de restauration','type'=>'text'   ],
                [ 'name'=>'populaire_pour',      'placeholder'=>'Populaire pour',      'type'=>'checkbox' ],
            ],
            'hopitaux' => [
                [ 'name'=>'avis',         'placeholder'=>'Avis',         'type'=>'text'   ],
                [ 'name'=>'specialite_hopitaux',   'placeholder'=>'Spécialité',   'type'=>'checkbox' ],
            ],
            'pharmacies' => [
                [ 'name'=>'avis',           'placeholder'=>'Avis',                 'type'=>'text'     ],
                [ 'name'=>'services_pharmacies',       'placeholder'=>'Services disponibles',  'type'=>'checkbox' ],
                
            ],
            'plages-et-piscines' => [
                [ 'name'=>'entrees', 'placeholder'=>'Gratuit ou payant', 'type'=>'checkbox' ],
            ],
            'restaurants' => [
                [ 'name'=>'avis', 'placeholder'=>'Avis', 'type'=>'text' ],
                [ 'name'=>'type_de_cuisine', 'placeholder'=>'Avis', 'type'=>'text' ],
                [ 'name'=>'budget_moyen',   'placeholder'=>'Budget moyen',   'type'=>'number' ],
            ],
            'default' => [
                [ 'name'=>'avis', 'placeholder'=>'Avis', 'type'=>'text' ],
            ],
        ],
    ];

    // 3) Choix des filtres à afficher selon le CPT et la catégorie
    $to_show = [];
    if ( isset( $filtres_config[ $post_type ] ) ) {
        if ( $term && isset( $filtres_config[ $post_type ][ $term ] ) ) {
            $to_show = $filtres_config[ $post_type ][ $term ];
        } else {
            $to_show = $filtres_config[ $post_type ]['default'];
        }
    }

   // 4) Affichage du formulaire
              // 4) Affichage du formulaire

                echo '<form method="GET" action="' . esc_url( $current_url ) . '" class="restaurant-filter">';

                // juste avant ta boucle foreach( $to_show as $f )
$dummy = get_posts([
  'post_type'      => $post_type,
  'posts_per_page' => 1,
  'fields'         => 'ids',
]);
$post_id_for_choices = ! empty($dummy) ? $dummy[0] : null;

echo '<!-- DEBUG: term='.esc_html($term).' to_show_names='
   . implode(',', wp_list_pluck($to_show,'name'))
   .' -->';


                foreach ( $to_show as $f ) {
                    $name = $f['name'];
                    $val  = $_GET[ $name ] ?? '';

                    if ( $f['type'] === 'checkbox' ) {
                            // récupère l'objet ACF en passant l'ID
                            $field = get_field_object( $f['name'], $post_id_for_choices );
                            if ( ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
                                echo '<div class="filter-field filter-field--checkboxes">';
                                echo '<span class="filter-label">'. esc_html( $f['placeholder'] ) .'</span>';
                                $selected = (array) ( $_GET[ $f['name'] ] ?? [] );
                                foreach ( $field['choices'] as $value => $label ) {
                                    $checked = in_array( $value, $selected, true ) ? ' checked' : '';
                                    printf(
                                        '<label class="filter-checkbox"><input type="checkbox" name="%1$s[]" value="%2$s"%3$s> %4$s</label>',
                                        esc_attr( $f['name'] ),
                                        esc_attr( $value ),
                                        $checked,
                                        esc_html( $label )
                                    );
                                }
                                echo '</div>';
                            }
                            continue;
                        }


                    // --- remettre ici l'affichage des champs text / number ---
                    $attrs = '';
                    if ( isset( $f['min'] ) ) $attrs .= ' min="' . intval( $f['min'] ) . '"';
                    if ( isset( $f['max'] ) ) $attrs .= ' max="' . intval( $f['max'] ) . '"';

                    printf(
                        '<div class="filter-field"><input type="%1$s" name="%2$s" placeholder="%3$s" value="%4$s"%5$s/></div>',
                        esc_attr( $f['type'] ),      // type="text" ou "number"
                        esc_attr( $name ),           // name="budget_moyen" ou "type_de_restauration"
                        esc_attr( $f['placeholder'] ),
                        esc_attr( $val ),
                        $attrs
                    );
                }

                echo '<div class="filter-field filter-field--actions">';
                echo '<button type="submit" class="btn-filter">Filtrer</button>';
                echo '<a href="' . esc_url( $current_url ) . '" class="btn-clear-filters">Effacer</a>';
                echo '</div>';

                echo '</form>';



       

    // 5) Construction de la meta_query
    $meta_query = [ 'relation' => 'AND' ];
    foreach ( $to_show as $f ) {
        $name  = $f['name'];
        $value = $_GET[ $name ] ?? null;

        if ( $value === null || $value === '' ) {
            continue;
        }
        switch ( $f['type'] ) {
            case 'text':
                $meta_query[] = [
                    'key'     => $name,
                    'value'   => sanitize_text_field( $value ),
                    'compare' => 'LIKE',
                ];
                break;
            case 'number':
                $meta_query[] = [
                    'key'     => $name,
                    'value'   => intval( $value ),
                    'type'    => 'NUMERIC',
                    'compare' => '<=',
                ];
                break;
            case 'checkbox':
                if ( is_array( $value ) ) {
                    $or = [ 'relation' => 'OR' ];
                    foreach ( $value as $v ) {
                        $or[] = [
                            'key'     => $name,
                            'value'   => '"' . sanitize_text_field( $v ) . '"',
                            'compare' => 'LIKE',
                        ];
                    }
                    $meta_query[] = $or;
                }
                break;
        }
    }

    // 6) Assemblage de la requête WP_Query
    $args = [
        'post_type'      => $post_type,
        'posts_per_page' => -1,
    ];
    if ( count( $meta_query ) > 1 ) {
        $args['meta_query'] = $meta_query;
    }
    if ( $term ) {
        $args['tax_query'] = [[
            'taxonomy' => 'category',
            'field'    => 'slug',
            'terms'    => $term,
        ]];
    }

    $q = new WP_Query( $args );

    // 7) Affichage des résultats
    ob_start();
    if ( $q->have_posts() ) {
        echo '<div class="liste-restaurants">';
        while ( $q->have_posts() ) {
            $q->the_post();
            $id    = get_the_ID();
            $img   = get_field( 'images', $id );
            $avis  = get_field( 'avis', $id );
            $type  = get_field( 'type_de_restauration', $id );
            $desc  = get_field( 'description', $id );
            $popu  = get_field( 'populaire_pour', $id );
            $spe   = get_field( 'specialite_hopitaux', $id );
            $ser   = get_field( 'services_pharmacies', $id );
            $budg  = get_field( 'budget_moyen', $id );
            $link  = get_field( 'reservation', $id );
            $itin  = get_field( 'lien_reservation', $id );
            $type  = get_field('type_de_cuisine', $post_id);
            $telephone      = get_field('numero_de_telephone',    $post_id);
            $adresse        = get_field('adresse',             $post_id);

            echo '<div class="restaurant-card">';
              echo '<div class="restaurant-left">';
                if ( $img && is_array( $img ) ) {
                    printf(
                        '<img src="%s" class="restaurant-image" alt="%s"/>',
                        esc_url( $img['url'] ),
                        esc_attr( get_the_title() )
                    );
                }
                echo '<div class="restaurant-info">';
                  printf( '<h3 class="restaurant-title">%s</h3>', esc_html( get_the_title() ) );
                  echo '<div class="note">';
                    if ( $avis ) echo '<p class="restaurant-etoiles">⭐ '. esc_html( $avis ) .'</p>';
                    if ( $type ) echo '<p class="restaurant-type"><i class="fa-solid fa-utensils"></i> '. esc_html( $type ) .'</p>';
                    if ($adresse)  echo '<p class="restaurant-adresse"><i class="fa-solid fa-location-dot"></i> ' . esc_html($adresse). '</p>';
                  echo '</div>';

                  if ( $desc ) echo '<p class="restaurant-description">'. esc_html( $desc ) .'</p>';

                  // Téléphone + étoiles
                    echo '<div class="restaurant-contact-note">';
                        if ($telephone) echo '<span class="restaurant-telephone"><i class="fa-solid fa-phone-volume"></i> ' . esc_html($telephone)     . '</span>';
                    echo '</div>';

                  // Populaire pour
                  if ( ! empty( $popu ) && is_array( $popu )   && in_array( $term, ['maquis','street-food'], true ) ) {
                    echo '<p class="restaurant-populaire"><strong>Populaire pour :</strong> '
                       . implode(', ', array_map('esc_html', $popu))
                       . '</p>';
                  }

                  // Spécialité (hopitaux)
                  if ( ! empty( $spe ) && is_array( $spe ) && $term === 'hopitaux' ) {
                    echo '<p class="hopitaux-specialite"><strong>Spécialité :</strong> '
                       . implode(', ', array_map('esc_html', $spe))
                       . '</p>';
                  }

                  // Services (pharmacies)
                  if ( ! empty( $ser ) && is_array( $ser ) && $term === 'pharmacies' ) {
                    echo '<p class="restaurant-services"><strong>Services :</strong> '
                       . implode(', ', array_map('esc_html', $ser))
                       . '</p>';
                  }

                echo '</div>';
              echo '</div>';

              echo '<div class="restaurant-divider-vertical"></div>';
              echo '<div class="restaurant-right">';
                if ( $budg ) {
                  echo '<span class="price-label">À prévoir</span>';
                  echo '<p class="restaurant-price">'. esc_html( $budg ) .' FCFA</p>';
                }
                if ( $link ) {
                    if ( is_array( $link ) ) {
                        $url    = $link['url']    ?? '';
                        $text   = $link['title']  ?: '';
                        $target = $link['target'] ?: '_self';
                        printf(
                            '<a class="reserve-button" href="%s" target="%s">%s</a>',
                            esc_url(  $url   ),
                            esc_attr( $target ),
                            esc_html( $text  )
                        );
                    } else {
                        printf(
                            '<span class="reserve-button">%s</span>',
                            esc_html( $link )
                        );
                    }
                }
                if ( $itin ) {
                    echo '<a class="reserve-button" href="'. esc_url( $itin ).'" target="_blank">Itinéraire</a>';
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
