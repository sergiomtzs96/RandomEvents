<?php
session_start(); 

// 1. Manejo de la redirección al login si no está autenticado
if (!isset($_SESSION['user_id'])) {
    $redirect_after_login = $_SERVER['REQUEST_URI'];
    header("Location: http://localhost/reserve-events/frontend/static/login.php?redirect=" . urlencode($redirect_after_login));
    exit();
}

$user_id = $_SESSION['user_id'];

include '../../backend/config/database.php'; 

// 2. Obtener el ID del evento de la URL
if (isset($_GET['id'])) {
    $event_id = $_GET['id'];
} else {
    // Si no hay ID de evento, redirigir o mostrar un error más amigable
    die("Error: No se ha especificado un ID de evento para la encuesta.");
}

//Obtención de datos del evento
$sql = "SELECT * FROM events WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

//Comprobación de si el usuario ya ha valorado el evento
$sqlRev = "SELECT * FROM reviews WHERE event_id = ? AND user_id = ?";
$stmtRev = $conn->prepare($sqlRev);
$stmtRev->bind_param("ii", $event_id, $user_id);
$stmtRev->execute();
$resultRev = $stmtRev->get_result();
$review = $resultRev->fetch_assoc();

//Verificar si el usuario ha comprado entrada para el evento
$sqlOrder = "SELECT
    o.id AS order_id,
    o.user_id,
    od.event_id
FROM
    orders o
JOIN
    order_detail od ON o.id = od.order_id
WHERE
    o.user_id = ?
    AND od.event_id = ?";
$stmtOrder = $conn->prepare($sqlOrder);
$stmtOrder->bind_param("ii", $user_id, $event_id);
$stmtOrder->execute();
$resultOrder = $stmtOrder->get_result();
$order = $resultOrder->fetch_assoc();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review</title>
    <!-- CSS  -->
    <link rel="stylesheet" href="../assets/style/encuesta.css">
    <!-- Importar Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="font-family_encuesta d-flex flex-column min-vh-100">
    <!-- Encabezado -->
    <?php
    include "../static/header.php";
    ?>
    <main class="container mt-4 flex-grow-1">
        <!-- Tarjeta del evento -->
        <div class="card horizontal-card mb-4 shadow">
            <div class="row g-0">
                <div class="col-md-4">
                    <img src="<?php echo $event['image_url'] ? htmlspecialchars($event['image_url']) : 'default_image.jpg'; ?>" 
                        class="img-fluid h-100 object-fit-cover" 
                        alt="Imagen del evento">
                </div>
                <div class="col-md-8">
                    <div class="card-body p-4">
                        <h5 class="card-title"><?php echo htmlspecialchars($event['event_name']); ?></h5>
                        <p class="card-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                        <p class="card-text"><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($event['event_date'])); ?></p>
                        <p class="card-text"><strong>Time:</strong> <?php echo date('H:i', strtotime($event['event_time'])); ?>h</p>
                        <p class="card-text"><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                        <p class="card-text"><strong>City:</strong> <?php echo htmlspecialchars($event['city']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php $event_date_time = strtotime($event['event_date'] . ' ' . $event['event_time']);?>
        <?php if (!empty($review)): ?> <!-- Evento ya valorado -->
            <div class="card rating-card mt-5 mb-5 shadow">
                <div class="card-header text-white text-center fs-5 bg-success">
                    You have already rated this event!
                </div>
                <div class="card-body text-center">
                    <p class="mb-3 fs-4">Your rating:</p>
                    <div class="display-rating-stars mb-4">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $review['rating']) {
                                echo '<i class="fas fa-star"></i>'; // Estrella rellena
                            } else {
                                echo '<i class="far fa-star empty"></i>'; // Estrella vacía
                            }
                        }
                        ?>
                    </div>
                    <p class="mb-3 fs-5">Your comment:</p>
                    <p class="form-control-plaintext border p-3 rounded bg-light">
                        <?php echo !empty($review['comment']) ? nl2br(htmlspecialchars($review['comment'])) : 'No comment provided.'; ?>
                    </p>
                    <div class="d-grid mt-4">
                        <a href="historialPedidos.php" class="back-button btn btn-primary mb-3" style="background-color: #4d194d; color: white">Go back to My Orders</a>
                    </div>
                </div>
            </div>
        <?php elseif (empty($order)): ?> <!-- No se ha comprado entrada -->
            <p class="alert alert-danger text-center py-4 fs-5">
                You have not attended this event.
            </p>
        <?php elseif ($event_date_time > time()): ?> <!-- El evento aún no ha tenido lugar -->
            <p class="alert alert-warning text-center py-4 fs-5">
                Event not yet passed.
            </p>
        <?php else: ?>
        
        <div class="card rating-card mt-5 shadow">
            <div class="card-header text-white text-center fs-5">
                How would you rate this event?
            </div>

            <div class="card-body">
                <!-- Formulario -->
                <form method="POST" action="../../backend/controllers/guardar_valoracion.php">
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">

                    <!-- Estrellas de calificación -->
                    <div class="rating-stars mb-4 text-center">
                        <input type="radio" name="rating" value="5" id="star5" required><label for="star5">&#9733;</label>
                        <input type="radio" name="rating" value="4" id="star4"><label for="star4">&#9733;</label>
                        <input type="radio" name="rating" value="3" id="star3"><label for="star3">&#9733;</label>
                        <input type="radio" name="rating" value="2" id="star2"><label for="star2">&#9733;</label>
                        <input type="radio" name="rating" value="1" id="star1"><label for="star1">&#9733;</label>
                    </div>

                    <!-- Comentario -->
                    <div class="form-floating mb-3">
                        <textarea class="form-control" name="comment" placeholder="Share your opinion" id="comment" style="height: 100px"></textarea>
                        <label for="comment">Comment (optional)</label>
                    </div>

                    <!-- Botón -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-submit_review">Submit review</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </main>
    <!-- Footer -->
    <?php
    include '../static/footer.php';
    ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
