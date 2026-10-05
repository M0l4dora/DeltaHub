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

?>