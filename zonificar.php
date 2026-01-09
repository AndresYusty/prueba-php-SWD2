<?php
// Función para extraer coordenadas aproximadas de la dirección
function obtenerCoordenadas($direccion, $codigo_postal) {
    // Coordenadas base de Bogotá
    $lat_base = 4.6097;
    $lng_base = -74.0817;
    
    $calle = 0;
    $carrera = 0;
    
    // Buscar calle (CL, CALLE, DG)
    if (preg_match('/(?:CL|CALLE|DG)\s+(\d+)/i', $direccion, $match)) {
        $calle = intval($match[1]);
    } elseif (preg_match('/^(\d+)\s+\d+/', $direccion, $match)) {
        $calle = intval($match[1]);
    }
    
    // Buscar carrera (CR, CARRERA, CRRA)
    if (preg_match('/(?:CR|CARRERA|CRRA)\s+(\d+)/i', $direccion, $match)) {
        $carrera = intval($match[1]);
    } elseif (preg_match('/\d+\s+(\d+)/', $direccion, $match)) {
        $carrera = intval($match[1]);
    }
    
    // Si tenemos calle y carrera, calcular coordenadas
    if ($calle > 0 || $carrera > 0) {
        // En Bogotá: calles van de sur a norte, carreras de oriente a occidente
        $lat = $lat_base + (($calle / 200) * 0.1);
        $lng = $lng_base - (($carrera / 200) * 0.1);
    } else {
        // Si no hay números claros, usar código postal
        if ($codigo_postal != '0' && is_numeric($codigo_postal)) {
            $cp = intval($codigo_postal);
            // Los códigos postales de Bogotá dan pistas de ubicación
            $lat = $lat_base + (($cp % 1000) / 10000);
            $lng = $lng_base - (($cp % 100) / 1000);
        } else {
            // Distribución aleatoria en área central
            $lat = $lat_base + ((rand(0, 100) - 50) / 1000);
            $lng = $lng_base + ((rand(0, 100) - 50) / 1000);
        }
    }
    
    return ['lat' => $lat, 'lng' => $lng];
}

// Función para calcular distancia entre dos puntos
function calcularDistancia($p1, $p2) {
    $lat1 = $p1['lat'];
    $lng1 = $p1['lng'];
    $lat2 = $p2['lat'];
    $lng2 = $p2['lng'];
    
    $dLat = $lat2 - $lat1;
    $dLng = $lng2 - $lng1;
    
    return sqrt($dLat * $dLat + $dLng * $dLng);
}

// Agregar coordenadas a cada entrega
$entregas = require 'datos.php';
foreach ($entregas as &$entrega) {
    $coords = obtenerCoordenadas($entrega['direccion'], $entrega['codigo_postal']);
    $entrega['lat'] = $coords['lat'];
    $entrega['lng'] = $coords['lng'];
}

// Zonificar por código postal
$zonas = [];
foreach ($entregas as $entrega) {
    $cp = $entrega['codigo_postal'] == '0' ? 'SIN_CP' : $entrega['codigo_postal'];
    if (!isset($zonas[$cp])) {
        $zonas[$cp] = [];
    }
    $zonas[$cp][] = $entrega;
}

// Ordenar entregas dentro de cada zona (algoritmo simple: punto más cercano)
function ordenarRuta($puntos) {
    if (count($puntos) <= 1) return $puntos;
    
    $ruta = [];
    $restantes = $puntos;
    
    // Empezar desde el punto más al norte (mayor latitud)
    usort($restantes, function($a, $b) {
        return $b['lat'] <=> $a['lat'];
    });
    
    $actual = array_shift($restantes);
    $ruta[] = $actual;
    
    while (count($restantes) > 0) {
        $mas_cercano = null;
        $distancia_min = PHP_FLOAT_MAX;
        $indice = -1;
        
        foreach ($restantes as $i => $punto) {
            $dist = calcularDistancia($actual, $punto);
            if ($dist < $distancia_min) {
                $distancia_min = $dist;
                $mas_cercano = $punto;
                $indice = $i;
            }
        }
        
        if ($mas_cercano) {
            $ruta[] = $mas_cercano;
            array_splice($restantes, $indice, 1);
            $actual = $mas_cercano;
        }
    }
    
    return $ruta;
}

// Ordenar cada zona
$zonas_ordenadas = [];
foreach ($zonas as $cp => $puntos) {
    $zonas_ordenadas[$cp] = ordenarRuta($puntos);
}

// Combinar todas las zonas en orden final
$ruta_final = [];
foreach ($zonas_ordenadas as $cp => $puntos) {
    foreach ($puntos as $punto) {
        $punto['zona_cp'] = $cp;
        $ruta_final[] = $punto;
    }
}

return $ruta_final;
?>
