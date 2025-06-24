<?php
/**
 * Shortcode [liste_restaurants type="restaurant"]
 */
function rl_afficher_liste($atts) {
    $atts = shortcode_atts([
        'type' => 'test',
    ], $atts, 'liste_test_plugins');
    $post_type = sanitize_key($atts['type']);

    // Configuration des filtres par CPT
    $filtres_config = [
        'test' => [
            ['name'=>'avis','placeholder'=>'Avis','type'=>'text'],
            ['name'=>'type_de_restaurant','placeholder'=>'Type de restaurant','type'=>'text'],
            ['name'=>'services_disponibles','placeholder'=>'Services disponibles','type'=>'text'],
            ['name'=>'budget_moyen','placeholder'=>'Budget moyen','type'=>'number'],
        ],
       
    ];

    // Formulaire de filtres
    echo '<form method="GET" class="restaurant-filter">';
    if (isset($filtres_config[$post_type])) {
        foreach ($filtres_config[$post_type] as $f) {
            $val = esc_attr($_GET[$f['name']] ?? '');
            $attrs = '';
            if (isset($f['min'])) $attrs .= ' min="'.intval($f['min']).'"';
            if (isset($f['max'])) $attrs .= ' max="'.intval($f['max']).'"';
            echo '<input type="'.$f['type'].'" name="'.$f['name'].'" placeholder="'.esc_attr($f['placeholder']).'" value="'.$val.'"'.$attrs.'/>';
        }
    }
    echo '<button type="submit">Filtrer</button>';
    echo '</form>';

 // Construction du meta_query
$meta_query = ['relation' => 'AND'];

if ( $post_type === 'test' ) {
    // Avis (texte, recherche partielle)
    if ( ! empty( $_GET['avis'] ) ) {
        $meta_query[] = [
            'key'     => 'avis',
            'value'   => sanitize_text_field( $_GET['avis'] ),
            'compare' => 'LIKE',
        ];
    }
    // Type de restaurant (texte, recherche partielle)
    if ( ! empty( $_GET['type_de_restaurant'] ) ) {
        $meta_query[] = [
            'key'     => 'type_de_restaurant',
            'value'   => sanitize_text_field( $_GET['type_de_restaurant'] ),
            'compare' => 'LIKE',
        ];
    }
    // Services disponibles (texte, recherche partielle)
    if ( ! empty( $_GET['services_disponibles'] ) ) {
        $meta_query[] = [
            'key'     => 'services_disponibles',
            'value'   => sanitize_text_field( $_GET['services_disponibles'] ),
            'compare' => 'LIKE',
        ];
    }
    // Budget moyen (nombre, on suppose que c'est un maximum)
    if ( ! empty( $_GET['budget_moyen'] ) ) {
        $meta_query[] = [
            'key'     => 'budget_moyen',
            'value'   => intval( $_GET['budget_moyen'] ),
            'type'    => 'NUMERIC',
            'compare' => '<=',
        ];
    }
}

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

        // — Bloc Gauche (image + infos communes) —
        echo '<div class="restaurant-left">';
          $image = get_field('image_restaurant', $id);
          if ( $image && is_array($image) ) {
              echo '<img src="'.esc_url($image['url']).'" class="restaurant-image"/>';
          }
          echo '<div class="restaurant-info">';
            // Titre générique (nom du post)
            echo '<h3>'.esc_html( get_the_title() ).'</h3>';

            // Champs selon le CPT
           
            elseif ( $post_type === 'test' ) {
                $avis       = get_field('avis',                  $id);
                $typeRest   = get_field('type_de_restaurant',    $id);
                $services   = get_field('services_disponibles',  $id);
                $budget     = get_field('budget_moyen',          $id);

                if ( $avis )      echo '<p>Avis : '.   esc_html($avis).     '</p>';
                if ( $typeRest )  echo '<p>Type : '.   esc_html($typeRest). '</p>';
                if ( $services )  echo '<p>Services : '.esc_html($services).'</p>';
                if ( $budget )    echo '<p>Budget : '. esc_html($budget).' FCFA</p>';
            }

          echo '</div>'; // .restaurant-info
        echo '</div>';   // .restaurant-left

        // — Divider —
        echo '<div class="restaurant-divider-vertical"></div>';

        // — Bloc Droite (réservation / actions) —
        echo '<div class="restaurant-right">';
          $lien = get_field('lien_reservation', $id);
          if ( $lien ) {
            echo '<a class="reserve-button" href="'.esc_url($lien).'" target="_blank">Réserver</a>';
          }
        echo '</div>';   // .restaurant-right

        echo '</div>';   // .restaurant-card
    }
    echo '</div>';    // .liste-restaurants
    wp_reset_postdata();
} else {
    echo '<p>Aucun élément trouvé pour <strong>'.esc_html($post_type).'</strong>.</p>';
}

return ob_get_clean();
}
add_shortcode('liste_test_plugins', 'rl_afficher_liste');
