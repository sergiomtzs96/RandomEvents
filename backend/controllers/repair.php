<?php
// Ejecutar verificación y reparación de datos
include_once(__DIR__ . '/check_data.php');

// Redireccionar a la página del catálogo
header("Location: ../../frontend/static/catalog-events.php");
exit();
