<?php
// Script para verificar y reparar datos de eventos
include_once(__DIR__ . '/../config/database.php');

// Determinar si es una solicitud web o consola
$isWeb = !empty($_SERVER['HTTP_HOST']);
$output = [];

function addOutput($message)
{
    global $isWeb, $output;
    if ($isWeb) {
        $output[] = htmlspecialchars($message);
    } else {
        echo $message . "\n";
    }
}

addOutput("Verificando datos de la base de datos...");

// Contar eventos
$result = $conn->query("SELECT COUNT(*) as count FROM events");
$events_count = $result->fetch_assoc()['count'];
addOutput("Total de eventos: $events_count");

// Contar ciudades
$result = $conn->query("SELECT COUNT(DISTINCT city) as count FROM events WHERE city IS NOT NULL AND city != ''");
$cities_count = $result->fetch_assoc()['count'];
addOutput("Total de ciudades: $cities_count");

// Contar estilos
$result = $conn->query("SELECT COUNT(DISTINCT style) as count FROM events WHERE style IS NOT NULL AND style != ''");
$styles_count = $result->fetch_assoc()['count'];
addOutput("Total de estilos: $styles_count");

// Verificar si hay ciudades y estilos
if ($events_count > 0 && ($cities_count == 0 || $styles_count == 0)) {
    addOutput("Se encontraron eventos sin ciudades o estilos. Actualizando...");

    // Actualizar ciudades si es necesario
    if ($cities_count == 0) {
        $conn->query("UPDATE events SET city = 'Madrid' WHERE id % 3 = 0");
        $conn->query("UPDATE events SET city = 'Barcelona' WHERE id % 3 = 1");
        $conn->query("UPDATE events SET city = 'Valencia' WHERE id % 3 = 2");
        addOutput("Ciudades actualizadas.");
    }

    // Actualizar estilos si es necesario
    if ($styles_count == 0) {
        $conn->query("UPDATE events SET style = 'Rock' WHERE id % 4 = 0");
        $conn->query("UPDATE events SET style = 'Pop' WHERE id % 4 = 1");
        $conn->query("UPDATE events SET style = 'Jazz' WHERE id % 4 = 2");
        $conn->query("UPDATE events SET style = 'Electrónica' WHERE id % 4 = 3");
        addOutput("Estilos actualizados.");
    }

    // Verificar cambios
    $result = $conn->query("SELECT COUNT(DISTINCT city) as count FROM events WHERE city IS NOT NULL AND city != ''");
    $cities_count = $result->fetch_assoc()['count'];
    addOutput("Total de ciudades después de actualizar: $cities_count");

    $result = $conn->query("SELECT COUNT(DISTINCT style) as count FROM events WHERE style IS NOT NULL AND style != ''");
    $styles_count = $result->fetch_assoc()['count'];
    addOutput("Total de estilos después de actualizar: $styles_count");
}

// Si no hay eventos, insertar algunos por defecto
if ($events_count == 0) {
    addOutput("No se encontraron eventos. Insertando eventos por defecto...");

    $sql_insert_events = "
    INSERT INTO events (name, email, event_name, description, event_date, event_time, image_url, city, location, style, price, number_of_tickets)
    VALUES
    ('Juan Pérez', 'juan@example.com', 'Concierto de Rock', 'Una noche inolvidable con las mejores bandas locales.', '2025-06-15', '20:00:00', 'https://dg9aaz8jl1ktt.cloudfront.net/uploaded_files/000/373/898/verkami_96a2aafefc1bb088f028e38424540650.jpg?1667978137', 'Madrid', 'Auditorio Nacional', 'Rock', 45.00, 200),
    ('María López', 'maria@example.com', 'Feria de Tecnología', 'Exposición de los últimos avances tecnológicos.', '2025-07-10', '10:00:00', 'https://www.shutterstock.com/image-vector/vector-poster-banner-rock-festival-600nw-1211649412.jpg', 'Barcelona', 'Centro de Convenciones', 'Rap', 25.00, 500),
    ('Carlos Gómez', 'carlos@example.com', 'Festival de Cine', 'Proyección de cortometrajes internacionales.', '2025-08-05', '18:00:00', 'https://i.pinimg.com/236x/a2/b0/c9/a2b0c97516575fa5363dc0e5a6f08f57.jpg', 'Valencia', 'Cine Independiente', 'Pop', 30.00, 150),
    ('Ana Torres', 'ana@example.com', 'Conferencia de Marketing', 'Aprende de los expertos en marketing digital.', '2025-05-20', '09:00:00', 'https://previews.123rf.com/images/paseven/paseven2011/paseven201100047/158662131-afiche-para-un-concierto-de-m%C3%BAsica-en-vivo-con-una-guitarra-abstracta-brillante-y-letras-sobre-un.jpg', 'Valencia', 'Hotel Gran Vista', 'Flamenco', 60.00, 100),
    ('Luis Fernández', 'luis@example.com', 'Carrera 5K', 'Carrera familiar en el parque central.', '2025-09-12', '07:30:00', 'https://www.ritmo.es/Portals/0/EasyDNNnews/1275/RP201212_FOTO3.jpg', 'Madrid', 'Parque Central', 'Heavy Metal', 15.00, 300);
    ";

    if ($conn->query($sql_insert_events) === TRUE) {
        addOutput("Eventos insertados correctamente.");

        // Contar eventos después de insertar
        $result = $conn->query("SELECT COUNT(*) as count FROM events");
        $events_count = $result->fetch_assoc()['count'];
        addOutput("Total de eventos después de insertar: $events_count");
    } else {
        addOutput("Error al insertar eventos: " . $conn->error);
    }
}

addOutput("Verificación completada.");

// Mostrar ciudades y estilos disponibles
$cities = [];
$result = $conn->query("SELECT DISTINCT city FROM events WHERE city IS NOT NULL AND city != '' ORDER BY city");
while ($row = $result->fetch_assoc()) {
    $cities[] = $row['city'];
}
addOutput("Ciudades disponibles: " . implode(", ", $cities));

$styles = [];
$result = $conn->query("SELECT DISTINCT style FROM events WHERE style IS NOT NULL AND style != '' ORDER BY style");
while ($row = $result->fetch_assoc()) {
    $styles[] = $row['style'];
}
addOutput("Estilos disponibles: " . implode(", ", $styles));

// Si es acceso web directo, mostrar resultados
if ($isWeb && basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verificación de Datos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <h1>Verificación de Datos</h1>
            <div class="alert alert-info">';

    foreach ($output as $line) {
        echo '<p>' . $line . '</p>';
    }

    echo '</div>
            <a href="../../frontend/static/catalog-events.php" class="btn btn-primary">Volver al Catálogo</a>
        </div>
    </body>
    </html>';
}

$conn->close();
