<?php
include_once(__DIR__ . '/connection.php');

// Comprobar conexión a base de datos
if (!$conn || $conn->connect_error) {
    die("Error de conexión a la base de datos: " . ($conn ? $conn->connect_error : "No hay conexión"));
}

// Debugging
error_log("Ejecutando consultas de filtros");

// Consulta para verificar si hay eventos
$check_events = $conn->query("SELECT COUNT(*) as count FROM events");
if (!$check_events) {
    die("Error al verificar eventos: " . $conn->error);
}
$events_count = $check_events->fetch_assoc()['count'];
error_log("Total de eventos: $events_count");

// Si no hay eventos, insertar algunos por defecto
if ($events_count == 0) {
    error_log("No hay eventos, insertando datos de ejemplo");

    $sql_insert_events = "
    INSERT INTO events (name, email, event_name, description, event_date, event_time, image_url, city, location, style, price, number_of_tickets)
    VALUES
    ('Juan Pérez', 'juan@example.com', 'Concierto de Rock', 'Una noche inolvidable con las mejores bandas locales.', '2025-06-15', '20:00:00', 'https://dg9aaz8jl1ktt.cloudfront.net/uploaded_files/000/373/898/verkami_96a2aafefc1bb088f028e38424540650.jpg?1667978137', 'Madrid', 'Auditorio Nacional', 'Rock', 45.00, 200),
    ('María López', 'maria@example.com', 'Feria de Tecnología', 'Exposición de los últimos avances tecnológicos.', '2025-07-10', '10:00:00', 'https://www.shutterstock.com/image-vector/vector-poster-banner-rock-festival-600nw-1211649412.jpg', 'Barcelona', 'Centro de Convenciones', 'Rap', 25.00, 500),
    ('Carlos Gómez', 'carlos@example.com', 'Festival de Cine', 'Proyección de cortometrajes internacionales.', '2025-08-05', '18:00:00', 'https://i.pinimg.com/236x/a2/b0/c9/a2b0c97516575fa5363dc0e5a6f08f57.jpg', 'Valencia', 'Cine Independiente', 'Pop', 30.00, 150),
    ('Ana Torres', 'ana@example.com', 'Conferencia de Marketing', 'Aprende de los expertos en marketing digital.', '2025-05-20', '09:00:00', 'https://previews.123rf.com/images/paseven/paseven2011/paseven201100047/158662131-afiche-para-un-concierto-de-m%C3%BAsica-en-vivo-con-una-guitarra-abstracta-brillante-y-letras-sobre-un.jpg', 'Valencia', 'Hotel Gran Vista', 'Flamenco', 60.00, 100),
    ('Luis Fernández', 'luis@example.com', 'Carrera 5K', 'Carrera familiar en el parque central.', '2025-09-12', '07:30:00', 'https://www.ritmo.es/Portals/0/EasyDNNnews/1275/RP201212_FOTO3.jpg', 'Madrid', 'Parque Central', 'Heavy Metal', 15.00, 300);
    ";

    if (!$conn->query($sql_insert_events)) {
        error_log("Error al insertar eventos: " . $conn->error);
    } else {
        error_log("Eventos insertados correctamente");
    }
}

// Consultas para obtener datos únicos
$query_city = "SELECT DISTINCT city FROM events WHERE city IS NOT NULL AND city != '' ORDER BY city ASC";
$query_style = "SELECT DISTINCT style FROM events WHERE style IS NOT NULL AND style != '' ORDER BY style ASC";

// Ejecutar consultas
$result_city = $conn->query($query_city);
$result_style = $conn->query($query_style);

// Verificar errores
if ($result_city === false) {
    die("Error en la consulta de ciudades: " . $conn->error);
}

if ($result_style === false) {
    die("Error en la consulta de estilos: " . $conn->error);
}

// Debug: verificar que hay resultados
$city_count = $result_city->num_rows;
$style_count = $result_style->num_rows;
error_log("Ciudades encontradas: $city_count, Estilos encontrados: $style_count");

// Verificar ciudades disponibles
$cities = array();
if ($city_count > 0) {
    $result_city_temp = $conn->query($query_city);
    while ($row = $result_city_temp->fetch_assoc()) {
        $cities[] = $row['city'];
    }
    error_log("Ciudades disponibles: " . implode(", ", $cities));
} else {
    error_log("No hay ciudades disponibles");
}

// Si no hay datos, insertar algunos valores por defecto
if ($city_count == 0 || $style_count == 0) {
    error_log("Faltan ciudades o estilos, actualizando eventos...");

    // Actualizar ciudades si es necesario
    if ($city_count == 0) {
        $conn->query("UPDATE events SET city = 'Madrid' WHERE id % 3 = 0");
        $conn->query("UPDATE events SET city = 'Barcelona' WHERE id % 3 = 1");
        $conn->query("UPDATE events SET city = 'Valencia' WHERE id % 3 = 2");
        error_log("Ciudades actualizadas");
    }

    // Actualizar estilos si es necesario
    if ($style_count == 0) {
        $conn->query("UPDATE events SET style = 'Rock' WHERE id % 4 = 0");
        $conn->query("UPDATE events SET style = 'Pop' WHERE id % 4 = 1");
        $conn->query("UPDATE events SET style = 'Jazz' WHERE id % 4 = 2");
        $conn->query("UPDATE events SET style = 'Electrónica' WHERE id % 4 = 3");
        error_log("Estilos actualizados");
    }

    // Recargar resultados
    $result_city = $conn->query($query_city);
    $result_style = $conn->query($query_style);

    $city_count = $result_city->num_rows;
    $style_count = $result_style->num_rows;
    error_log("Después de actualizar - Ciudades: $city_count, Estilos: $style_count");
}
