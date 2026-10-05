<?php

// ===== Workshop: categorías y capítulos =====
// Compartido por workshop.php (listado y filtros de la barra lateral) y por
// subir.php (el capítulo que elegís al subir un save).

// Convierte "Música" -> "musica" para usarlo como data-category
// (la tabla categorias no tiene columna slug).
function slugify($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $texto);
    return preg_replace('/[^a-z0-9]+/', '', $texto);
}

// Categorías que se dividen en capítulos y cuántos capítulos ofrecen base.
// El slug es el de la categoría con slugify($nombre): hoy sólo "Saves".
function capitulos_base($slug_categoria) {
    $por_categoria = ['saves' => 5]; // Ch.1 a Ch.5
    return $por_categoria[$slug_categoria] ?? 0;
}

function usa_capitulos($slug_categoria) {
    return capitulos_base($slug_categoria) > 0;
}

// Cómo se escribe un capítulo en la interfaz: 2 -> "Ch.2"
function etiqueta_capitulo($numero) {
    return "Ch." . (int)$numero;
}

// El capítulo vive en items.capitulo. Si venís de una base instalada antes de
// esa columna, el sitio sigue funcionando: no hay capítulos que elegir ni que
// filtrar. El resultado se consulta una sola vez por pedido.
function items_tienen_capitulo($pdo) {
    static $tiene = null;

    if ($tiene === null) {
        $tiene = (bool)$pdo->query("SHOW COLUMNS FROM items LIKE 'capitulo'")->fetchColumn();
    }

    return $tiene;
}

// Capítulos que se le ofrecen al usuario al subir: los configurados arriba más
// los que ya estén guardados, por si alguno quedó fuera del rango.
function capitulos_disponibles($pdo, $slug_categoria) {
    $base = capitulos_base($slug_categoria);
    $capitulos = $base > 0 ? range(1, $base) : [];

    if ($base > 0 && items_tienen_capitulo($pdo)) {
        $guardados = $pdo->query(
            "SELECT DISTINCT capitulo FROM items WHERE capitulo IS NOT NULL ORDER BY capitulo"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($guardados as $numero) {
            $numero = (int)$numero;
            if ($numero > 0 && !in_array($numero, $capitulos, true)) $capitulos[] = $numero;
        }

        sort($capitulos);
    }

    return $capitulos;
}
