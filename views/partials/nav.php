<?php

/**
 * Détection du chemin courant et gestion de l'état "active" pour la navigation.
 */

$currentPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

/**
 * Fonction utilitaire pour appliquer la classe "active" sur un lien.
 *
 * Exemple d'utilisation :
 * <a href="<?= url('/explore') ?>" class="<?= $active('/explore') ?>">Explorer</a>
 *
 * - Active si le chemin correspond ou commence par le lien fourni.
 * - Gère aussi la racine ("/").
 */
$active = function (string $href) use ($currentPath): string {
    if ($href === '/') {
        return $currentPath === '/' ? 'active' : '';
    }

    return str_starts_with($currentPath, rtrim($href, '/')) ? 'active' : '';
};
