<?php

// Ayudas compartidas del banner del perfil.
//
// Vive en config/ porque lo necesitan las tres páginas que tocan el banner:
// cambiar_banner.php (valida lo que llega por POST), cuenta.php (pinta la
// vista previa) y perfil.php (lo aplica como estilo en el perfil público).


// Normaliza la posición del banner al formato "X% Y%".
//
// La posición la elige el usuario en la ventana emergente de recorte y va
// directa a un `object-position`, así que se valida en vez de escaparse:
// se acepta un par de porcentajes en el rango 0-100 y cualquier otra cosa
// devuelve "" para que el que llame use el centro.

function banner_posicion_de($valor) {
    if (!is_string($valor)) {
        return "";
    }

    $valor = trim($valor);

    // "50% 30%", con decimales opcionales y coma o punto como separador. El
    // espacio entre los dos porcentajes es opcional: "0%0%" también es un
    // object-position válido. Lo que sobra se rechaza, porque los extremos del
    // patrón son anclados y "50% 30% 70%" no entra.
    if (!preg_match('/^(\d{1,3}(?:[.,]\d+)?)%\s*(\d{1,3}(?:[.,]\d+)?)%$/', $valor, $coincide)) {
        return "";
    }

    $x = (float) str_replace(",", ".", $coincide[1]);
    $y = (float) str_replace(",", ".", $coincide[2]);

    if ($x > 100 || $y > 100) {
        return "";
    }

    // Redondeado a un decimal: alcanza para cualquier encuadre y evita guardar
    // cadenas con 14 decimales.
    return round($x, 1) . "% " . round($y, 1) . "%";
}


// La posición a usar, con el centro como valor por defecto.
function banner_posicion_o_centro($valor) {
    $posicion = banner_posicion_de($valor);

    return $posicion === "" ? "50% 50%" : $posicion;
}


// Normaliza el zoom del banner: un multiplicador entre 1 (la imagen apenas
// tapa el marco, como un cover) y 4.
//
// El zoom va aparte de la posición porque un `object-position` no agranda la
// imagen: sólo elige qué pedazo se ve de una imagen que ya está arrancada con
// cover. El zoom se aplica encima como `transform: scale()`, anclado al mismo
// punto que se usa de object-position, así que los dos juntos reconstruyen el
// encuadre de la ventana de recorte (ver .pf-banner-img en style.css).
//
// Se recorta en vez de rechazarse porque un valor fuera de rango casi siempre
// es un error de dedo, no un intento de nada: lo que importa es que el CSS
// reciba un número dentro del rango que el navegador aguanta. Lo que no es un
// número devuelve "" para que el que llame use el zoom de siempre.

function banner_zoom_de($valor) {
    if (is_string($valor)) {
        $valor = trim($valor);
    }

    if (!is_numeric($valor)) {
        return "";
    }

    $zoom = (float) $valor;

    // Los mismos topes que pone script.js en la ventana de recorte, para que un
    // valor disparado a mano no pueda mandarle un scale() gigante al CSS.
    if ($zoom < 1) {
        $zoom = 1;
    }

    if ($zoom > 4) {
        $zoom = 4;
    }

    // Sin ceros de más: "1" en vez de "1.00", que es lo mismo para el CSS y se
    // lee mejor en la base.
    return rtrim(rtrim(number_format($zoom, 2, ".", ""), "0"), ".");
}


// El zoom a usar, con 1 (sin zoom) como valor por defecto.
function banner_zoom_o_uno($valor) {
    $zoom = banner_zoom_de($valor);

    return $zoom === "" ? "1" : $zoom;
}

?>