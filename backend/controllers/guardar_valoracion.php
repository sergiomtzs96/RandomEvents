<?php
session_start();
include '../config/database.php';

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = trim($_POST['comment'] ?? '');

// Si el usuario es admin, añade prefix a su comentario
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $comment = "[Comentario de dios] " . $comment;
}

// Validación básica
if (!$event_id || !$rating) {
    die("Missing event ID or rating.");
}

// Verifica si el usuario ya valoró este evento
$check_sql = "SELECT * FROM reviews WHERE user_id = ? AND event_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    die("Ya has enviado una valoración para este evento.");
}

// Inserta la valoración
$sql = "INSERT INTO reviews (user_id, event_id, rating, comment) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiis", $user_id, $event_id, $rating, $comment);

if ($stmt->execute()) {
    header("Location:../../frontend/static/agradecimiento.php");
    exit();
} else {
    echo "Error al guardar la valoración.";
}
