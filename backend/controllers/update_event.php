<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../frontend/static/home.php');
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = $_POST['event_id'] ?? null;

    if (!$eventId) {
        header('Location: ../../frontend/static/home.php');
        exit;
    }

    // Validar y sanitizar campos
    $data = [
        'name' => htmlspecialchars($_POST['name']),
        'email' => htmlspecialchars($_POST['email']),
        'event_name' => htmlspecialchars($_POST['eventName']),
        'description' => htmlspecialchars($_POST['description']),
        'event_date' => htmlspecialchars($_POST['date']),
        'event_time' => htmlspecialchars($_POST['time']),
        'image_url' => htmlspecialchars($_POST['image']),
        'city' => htmlspecialchars($_POST['city']),
        'location' => htmlspecialchars($_POST['location']),
        'style' => htmlspecialchars($_POST['musicalStyle']),
        'price' => (float)$_POST['price'],
        'number_of_tickets' => (int)$_POST['number_tickets']
    ];

    // Actualizar en la base de datos
    $sql = "UPDATE events SET 
            name = ?,
            email = ?,
            event_name = ?,
            description = ?,
            event_date = ?,
            event_time = ?,
            image_url = ?,
            city = ?,
            location = ?,
            style = ?,
            price = ?,
            number_of_tickets = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param(
        'ssssssssssidi',
        $data['name'],
        $data['email'],
        $data['event_name'],
        $data['description'],
        $data['event_date'],
        $data['event_time'],
        $data['image_url'],
        $data['city'],
        $data['location'],
        $data['style'],
        $data['price'],
        $data['number_of_tickets'],
        $eventId
    );

    if ($stmt->execute()) {
        header('Location: ../../frontend/static/home.php');
    } else {
        $_SESSION['error'] = 'Error updating event: ' . $stmt->error;
        header('Location: ../../frontend/static/add_your_event.php?edit=' . $eventId);
    }
    exit;
}
