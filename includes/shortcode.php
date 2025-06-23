<?php
/**
 * Shortcode [liste_restaurants type="restaurant"]
 */
function rl_afficher_liste($atts) {
    $atts = shortcode_atts([
        'type' => 'restaurant',
    ], $atts, 'liste_restaurants');
    $post_type = sanitize_key($atts['type']);

    // Configuration des filtres par CPT
    $filtres_config = [
        'restaurant' => [
            ['name'=>'type_de_cuisine','placeholder'=>'Type de cuisine','type'=>'text'],
            ['name'=>'montant_a_prevoir','placeholder'=>'Prix max','type'=>'number'],
            ['name'=>'nombre_etoiles','placeholder'=>'Étoiles','type'=>'number','min'=>1,'max'=>5],
        ],
        'street_food' => [
            ['name'=>'montant_a_prevoir','placeholder'=>'Prix max','type'=>'number'],
            ['name'=>'distance_max','placeholder'=>'Distance (km)','type'=>'number'],
        ],
        'coffee_shop' => [
            ['name'=>'ville','placeholder'=>'Ville','type'=>'text'],
            ['name'=>'ambiance','placeholder'=>'Ambiance','type'=>'text'],
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
    $meta_query = ['relation'=>'AND'];
    if ($post_type==='restaurant') {
        if (!empty($_GET['type_de_cuisine'])) {
            $meta_query[] = ['key'=>'type_de_cuisine','value'=>sanitize_text_field($_GET['type_de_cuisine']),'compare'=>'LIKE'];
        }
        if (!empty($_GET['montant_a_prevoir'])) {
            $meta_query[] = ['key'=>'montant_a_prevoir','value'=>intval($_GET['montant_a_prevoir']),'type'=>'NUMERIC','compare'=>'<='];
        }
        if (!empty($_GET['nombre_etoiles'])) {
            $meta_query[] = ['key'=>'nombre_etoiles','value'=>intval($_GET['nombre_etoiles']),'type'=>'NUMERIC','compare'=>'='];
        }
    } elseif ($post_type==='street_food') {
        if (!empty($_GET['montant_a_prevoir'])) {
            $meta_query[] = ['key'=>'montant_a_prevoir','value'=>intval($_GET['montant_a_prevoir']),'type'=>'NUMERIC','compare'=>'<='];
        }
        if (!empty($_GET['distance_max'])) {
            $meta_query[] = ['key'=>'distance_max','value'=>intval($_GET['distance_max']),'type'=>'NUMERIC','compare'=>'<='];
        }
    } elseif ($post_type==='coffee_shop') {
        if (!empty($_GET['ville'])) {
            $meta_query[] = ['key'=>'ville','value'=>sanitize_text_field($_GET['ville']),'compare'=>'LIKE'];
        }
        if (!empty($_GET['ambiance'])) {
            $meta_query[] = ['key'=>'ambiance','value'=>sanitize_text_field($_GET['ambiance']),'compare'=>'LIKE'];
        }
    }

    $args = ['post_type'=>$post_type,'posts_per_page'=>-1];
    if (count($meta_query)>1) {
        $args['meta_query'] = $meta_query;
    }

    $q = new WP_Query($args);
    ob_start();
    if ($q->have_posts()) {
        echo '<div class="liste-restaurants">';
        while ($q->have_posts()) {
            $q->the_post();
            $id = get_the_ID();
            $image = get_field('image_restaurant',$id);
            $title = get_the_title();
            echo '<div class="restaurant-card">';
                echo '<div class="restaurant-left">';
                    if($image && is_array($image)){
                        echo '<img src="'.esc_url($image['url']).'" class="restaurant-image"/>';
                    }
                    echo '<div class="restaurant-info">';
                        echo '<h3>'.esc_html($title).'</h3>';
                        if($post_type==='restaurant'){
                            $cuisine = get_field('type_de_cuisine',$id);
                            if($cuisine) echo '<p>'.esc_html($cuisine).'</p>';
                        }
                    echo '</div>';
                echo '</div>';
                echo '<div class="restaurant-divider-vertical"></div>';
                echo '<div class="restaurant-right">';
                    $prix = get_field('montant_a_prevoir',$id);
                    if($prix) echo '<p class="restaurant-price">'.esc_html($prix).' FCFA</p>';
                    $lien = get_field('lien_reservation',$id);
                    if($lien) echo '<a class="reserve-button" href="'.esc_url($lien).'" target="_blank">Réserver</a>';
                echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>Aucun élément trouvé pour <strong>'.esc_html($post_type).'</strong>.</p>';
    }
    return ob_get_clean();
}
add_shortcode('liste_restaurants','rl_afficher_liste');
