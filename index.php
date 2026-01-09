<?php
require_once 'zonificar.php';

$ruta = require 'zonificar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruta de Entregas</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .map-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        #map {
            height: 500px;
            width: 100%;
        }
        
        .tabla-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #2c3e50;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        tr:hover {
            background: #f0f0f0;
        }
        
        .num-orden {
            font-weight: bold;
            color: #3498db;
            text-align: center;
        }
        
        .zona-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .zona-cp {
            background: #e74c3c;
            color: white;
        }
        
        .zona-sin {
            background: #95a5a6;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ruta de Entregas - Orden Optimizado</h1>
        
        <div class="map-container">
            <div id="map"></div>
        </div>
        
        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Guía</th>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Código Postal</th>
                        <th>Zona</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ruta as $index => $entrega): ?>
                    <tr data-lat="<?= $entrega['lat'] ?>" data-lng="<?= $entrega['lng'] ?>" data-nombre="<?= htmlspecialchars($entrega['nombre']) ?>">
                        <td class="num-orden"><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($entrega['guia']) ?></td>
                        <td><?= htmlspecialchars($entrega['nombre']) ?></td>
                        <td><?= htmlspecialchars($entrega['direccion']) ?></td>
                        <td><?= $entrega['codigo_postal'] == '0' ? 'Sin CP' : $entrega['codigo_postal'] ?></td>
                        <td>
                            <span class="zona-badge <?= $entrega['zona_cp'] == 'SIN_CP' ? 'zona-sin' : 'zona-cp' ?>">
                                <?= $entrega['zona_cp'] == 'SIN_CP' ? 'Sin CP' : 'CP ' . $entrega['zona_cp'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Inicializar mapa centrado en Bogotá
        var map = L.map('map').setView([4.6097, -74.0817], 12);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Agregar marcadores
        var puntos = [];
        var filas = document.querySelectorAll('tbody tr');
        
        filas.forEach(function(fila, index) {
            var lat = parseFloat(fila.getAttribute('data-lat'));
            var lng = parseFloat(fila.getAttribute('data-lng'));
            var nombre = fila.getAttribute('data-nombre');
            
            var marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup('<b>#' + (index + 1) + '</b><br>' + nombre);
            
            puntos.push([lat, lng]);
            
            // Resaltar fila al hacer clic en marcador
            marker.on('click', function() {
                filas.forEach(f => f.style.background = '');
                fila.style.background = '#fff3cd';
            });
        });
        
        // Dibujar línea de ruta
        if (puntos.length > 1) {
            var polyline = L.polyline(puntos, {
                color: '#3498db',
                weight: 3,
                opacity: 0.7
            }).addTo(map);
            
            // Ajustar vista para mostrar todos los puntos
            var group = new L.featureGroup(puntos.map(p => L.marker(p)));
            map.fitBounds(group.getBounds().pad(0.1));
        }
    </script>
</body>
</html>
