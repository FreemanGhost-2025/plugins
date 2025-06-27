<?php
/**
 * Shortcode: [filtre_categorie_test]
 * Affiche un filtre par catégorie pour les CPT "test"
 */

function afficher_filtre_par_categorie_test() {
    $categories = get_categories([
        'taxonomy'   => 'category',
        'hide_empty' => false,
    ]);

    if (empty($categories)) {
        return '<p>Aucune catégorie trouvée.</p>';
    }

    ob_start();
    echo '<form method="get" action="">';
    echo '<select name="filtre_categorie" onchange="this.form.submit()">';
    echo '<option value="">-- Filtrer par catégorie --</option>';
    foreach ($categories as $cat) {
        $selected = selected($_GET['filtre_categorie'] ?? '', $cat->slug, false);
        echo "<option value='{$cat->slug}' {$selected}>{$cat->name}</option>";
    }
    echo '</select>';
    echo '</form>';

    return ob_get_clean();
}
add_shortcode('filtre_categorie_test', 'afficher_filtre_par_categorie_test');
