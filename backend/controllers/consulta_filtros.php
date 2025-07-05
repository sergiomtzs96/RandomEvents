<?php
include(__DIR__ . '/connection.php');
session_start();

// Activar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Obtener los valores del formulario
$city = isset($_POST['city']) ? trim($_POST['city']) : '';
$event_date = isset($_POST['event_date']) ? trim($_POST['event_date']) : '';
$price = isset($_POST['price']) ? (float)$_POST['price'] : '';
$style = isset($_POST['style']) ? trim($_POST['style']) : '';

// Log de los valores recibidos (solo para depuración)
error_log("Filtros recibidos - City: " . $city . ", Date: " . $event_date . ", Price: " . $price . ", Style: " . $style);

// Almacenar los filtros en la sesión
$_SESSION['city'] = $city;
$_SESSION['event_date'] = $event_date;
$_SESSION['price'] = $price;
$_SESSION['style'] = $style;

// Limpiar la sesión si todos los filtros están vacíos
if (empty($city) && empty($event_date) && empty($price) && empty($style)) {
    unset($_SESSION['city']);
    unset($_SESSION['event_date']);
    unset($_SESSION['price']);
    unset($_SESSION['style']);
}

// Cerrar la conexión
$conn->close();

// Redireccionar de vuelta al catálogo
header("Location: ../../frontend/static/catalog-events.php");
exit();
