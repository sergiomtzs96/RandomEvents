<?php

$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'random_events_db';

// Crear la conexión, incluyendo la base de datos
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// 1. Consulta SQL para obtener los usuarios, detalles de pedido y ID del evento
$sql_select = "
    SELECT DISTINCT o.user_id, u.email, od.id AS order_detail_id, e.id AS event_id,
           e.location, e.event_name, e.event_date, e.event_time
    FROM orders o
    JOIN order_detail od ON o.id = od.order_id
    JOIN events e ON od.event_id = e.id
    JOIN users u ON o.user_id = u.id
    WHERE e.event_date < CURRENT_DATE
      AND od.review_email = FALSE
";

$result = $conn->query($sql_select);

if ($result === FALSE) {
    echo "Error en la consulta SELECT SQL: " . $conn->error . "\n";
} elseif ($result->num_rows > 0) {
    echo "Usuarios con compras de eventos pasados y sin valorar encontrados:\n";
    
    // Recorrer los resultados y enviar emails
    while ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        $user_email = $row['email'];
        $order_detail_id = $row['order_detail_id'];
        $event_id = $row['event_id'];
        $location = $row['location'];
        $event_name = $row['event_name'];
        $event_date = $row['event_date'];
        $event_time = $row['event_time'];

        // Construir el enlace dinámico a la encuesta
        $survey_link = "http://localhost/reserve-events/frontend/static/encuesta.php?id=" . $event_id;

        
        $email_subject = "¡Comparte tu experiencia: Valora tu evento pasado!";
        
        $template_path = '../../frontend/static/review_email.php';
        $body = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Rate Your Experience - {event_name} ?></title>
            <style>
                body {
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    border-radius: 8px;
                    border: 1px solid #ddd;
                }
                .header {
                    background-color: #4d194d; 
                    padding: 25px 30px;
                    color: #ffffff;
                    text-align: center;
                    font-size: 24px;
                    font-weight: bold;
                }
                .content {
                    padding: 30px;
                    color: #333333;
                    line-height: 1.6;
                    text-align: center;
                }
                .content h2 {
                    color: #4d194d;
                    font-size: 22px;
                    margin-bottom: 20px;
                }
                .event-info {
                    background-color: #f9f9f9;
                    border: 1px solid #eee;
                    border-radius: 6px;
                    padding: 20px;
                    margin-bottom: 25px;
                    text-align: left;
                    display: inline-block; 
                    width: fit-content; 
                    max-width: 90%; 
                }
                .event-info p {
                    margin: 5px 0;
                    font-size: 15px;
                    color: #555555;
                }
                .event-info strong {
                    color: #333333;
                }
                .button-container {
                    text-align: center;
                    margin-top: 30px;
                    margin-bottom: 20px;
                    color:white;
                }
                a.button {
                    display: inline-block;
                    background-color: #6a1a6a; 
                    color: #ffffff;
                    padding: 15px 30px;
                    border-radius: 5px;
                    font-size: 18px;
                    font-weight: bold;
                }
                a.button:hover {
                    background-color: #8c2a8c; 
                }
                .footer {
                    background-color: #eeeeee;
                    padding: 20px 30px;
                    color: #777777;
                    text-align: center;
                    font-size: 13px;
                    border-top: 1px solid #e0e0e0;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    Random Events
                </div>
                <div class="content">
                    <div class="logo">
                        </div>
                    <h2>We  love to hear your thoughts!</h2>
                    <p>To help us improve and bring you even better experiences, please take a moment to share your feedback by rating the event.</p>

                    <div class="event-info">
                        <p><strong>Event:</strong> {event_name}</p>
                        <p><strong>Date:</strong>  {event_date}</p>
                        <p><strong>Time:</strong> {event_time}</p>
                        <p><strong>Location:</strong> {location}</p>
                    </div>

                    <div class="button-container">
                        <a href="http://localhost/reserve-events/frontend/static/encuesta.php?id={event_id}" class="button">
                            Rate This Event
                        </a>
                    </div>
                    <p>Your opinion is invaluable to us!</p>
                </div>
                <div class="footer">
                    <p> Random Events. All rights reserved.</p>
                    <p>Follow us on social media!</p>
                    </div>
            </div>
        </body>
        </html>';

        // Reemplazar los marcadores de posición con los datos dinámicos
        $replacements = [
            '{location}' => htmlspecialchars($location),
            '{event_name}' => htmlspecialchars($event_name),
            '{event_id}' => htmlspecialchars($event_id),
            '{event_date}' => htmlspecialchars($event_date),
            '{event_time}' => htmlspecialchars($event_time),
        ];
        foreach ($replacements as $placeholder => $value) {
            $body = str_replace($placeholder, $value, $body);
        }
        // Cabeceras del email para indicar que es HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Tu Equipo de Eventos <randomeventsinfo@gmail.com>" . "\r\n"; 

        // Envía el email
        if (mail($user_email, $email_subject, $body, $headers)) { 
            echo "Email enviado a user_id: " . $user_id . " (" . $user_email . ")\n";

            // 2. Consulta SQL para actualizar el flag `review_email` a TRUE
            $sql_update = "UPDATE order_detail SET review_email = TRUE WHERE id = ?";
            
            $stmt = $conn->prepare($sql_update);
            
            if ($stmt === FALSE) {
                echo "Error al preparar la consulta UPDATE: " . $conn->error . "\n";
            } else {
                $stmt->bind_param("i", $order_detail_id);
                if ($stmt->execute()) {
                    echo "Flag 'review_email' actualizado para order_detail_id: " . $order_detail_id . "\n";
                } else {
                    echo "Error al actualizar 'review_email' para order_detail_id: " . $order_detail_id . ": " . $stmt->error . "\n";
                }
                $stmt->close();
            }
        } else {
            echo "Error al enviar email a user_id: " . $user_id . " (" . $user_email . ")\n";
        }
    }
} else {
    echo "No se encontraron usuarios con compras de eventos pasados pendientes de valoración.\n";
}

// Cerrar la conexión a la base de datos
$conn->close();
echo "\nConexión a la base de datos cerrada.\n";

?>