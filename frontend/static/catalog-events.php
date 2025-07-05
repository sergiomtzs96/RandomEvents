<?php
include __DIR__ . '/../../backend/controllers/connection.php';
include(__DIR__ . '/../../backend/controllers/filters.php');

session_start();

// Limpiar todos los filtros si se ha solicitado
if (isset($_GET['clear_filters']) || isset($_POST['clear_filters'])) {
    unset($_SESSION['city']);
    unset($_SESSION['event_date']);
    unset($_SESSION['price']);
    unset($_SESSION['style']);
    // Redireccionar para limpiar la URL
    if (isset($_GET['clear_filters'])) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Almacena los filtros en la sesión cuando el formulario se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validación de datos
    $_SESSION['city'] = isset($_POST['city']) ? trim($_POST['city']) : '';
    $_SESSION['event_date'] = isset($_POST['event_date']) ? trim($_POST['event_date']) : '';

    // Asegurar que el valor del precio se procesa correctamente
    if (isset($_POST['price']) && $_POST['price'] !== '') {
        $price_value = trim($_POST['price']);
        $_SESSION['price'] = is_numeric($price_value) ? (float)$price_value : '';
    } else {
        $_SESSION['price'] = '';
    }

    $_SESSION['style'] = isset($_POST['style']) ? trim($_POST['style']) : '';
}

// Capturar los filtros desde la sesión
$city = $_SESSION['city'] ?? '';
$event_date = $_SESSION['event_date'] ?? '';
$price = $_SESSION['price'] ?? '';
$style = $_SESSION['style'] ?? '';

//Construir la consulta SQL con los filtros
$sql = "SELECT * FROM events WHERE 1";
$params = [];
$types = '';

if (!empty($city)) {
    $sql .= " AND LOWER(city) = LOWER(?)";
    $params[] = $city;
    $types .= 's';
}

if (!empty($event_date)) {
    $sql .= " AND event_date = ?";
    $params[] = $event_date;
    $types .= 's';
}

if (!empty($price) && is_numeric($price)) {
    // Cast price to float for proper comparison
    $price_float = (float)$price;
    $sql .= " AND price <= ?";
    $params[] = $price_float;
    $types .= 'd';
}

if (!empty($style)) {
    $sql .= " AND LOWER(style) = LOWER(?)";
    $params[] = $style;
    $types .= 's';
}

// Preparar la consulta SQL
$stmt = $conn->prepare($sql);

// Verificar si la preparación fue exitosa
if ($stmt === false) {
    die('Error en la preparación de la consulta: ' . $conn->error);
}

// Vincular los parámetros a la consulta
if (!empty($types) && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result_events = $stmt->get_result();

// Extraer todas las ciudades de la base de datos para el filtro
$all_cities_query = "SELECT DISTINCT city FROM events ORDER BY city";
$all_cities_result = $conn->query($all_cities_query);
$available_cities = [];
if ($all_cities_result && $all_cities_result->num_rows > 0) {
    while ($row = $all_cities_result->fetch_assoc()) {
        $available_cities[] = $row['city'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>catalog-events</title>
    <!-- Importar Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons Bootstrap-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Cargar Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!--CSS-->
    <link rel="stylesheet" href="../assets/style/style-catalog-events.css">

</head>

<body>
    <!-- Encabezado -->
    <?php include 'header.php'; ?>

    <main class="vh-100">


        </header>
        <main class="mb-5">
            <div class="container mt-5">

                <!--Filtros-->
                <div class="container">

                    <div class="row">
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>  
                            <a href="add_your_event.php" class="create-event-btn">Create Event</a>
                        <?php endif; ?>
                        <div class="col" id="all-filters">
                            <div class="container mt-4 c-e-container-filters">
                                <h5 class="w-100 mb-3 text-center">Search filters</h5>
                                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>"
                                    class="c-e-form-filters">
                                    
                                    
                                    <!--Filtro ciudad-->
                                    <select name="city" id="city" class="c-e-location">
                                        <option value="" selected> City </option>
                                        <?php
                                        // Debugging
                                        $debug_cities = [];

                                        // Si no hay ciudades disponibles, usar las de la base de datos
                                        if (empty($available_cities)) {
                                            echo "<!-- No cities found in database -->";
                                        } else {
                                            foreach ($available_cities as $city_value) {
                                                $debug_cities[] = $city_value;
                                                $selected = ($_SESSION['city'] ?? '') === $city_value ? 'selected' : '';
                                                echo "<option value=\"$city_value\" $selected>$city_value</option>";
                                            }

                                            // Echo debug info as HTML comment
                                            echo "<!-- Available cities: " . implode(", ", $debug_cities) . " -->";
                                        }
                                        ?>
                                    </select>

                                    <!--Filtro fecha-->
                                    <input type="date" class="c-e-input-date" id="date" name="event_date"
                                        value="<?php echo $_SESSION['event_date'] ?? ''; ?>">

                                    <!--Filtro precio-->
                                    <div class="position-relative">
                                        <button type="button" class="c-e-btn" id="toggleFilterBtn" name="btnprice"
                                            onclick="
                                                var priceFilter = document.getElementById('priceFilter');
                                                var priceRange = document.getElementById('priceRange');
                                                var hiddenPriceInput = document.getElementById('hiddenPriceInput');
                                                
                                                if (priceFilter.style.display === 'none' || priceFilter.style.display === '') {
                                                    priceFilter.style.display = 'block';
                                                    this.style.backgroundColor = '#6f42c1';
                                                     
                                                    // Sincronizar valores
                                                    if (priceRange.value && priceRange.value !== '0') {
                                                        hiddenPriceInput.value = priceRange.value;
                                                    }
                                                } else {
                                                    priceFilter.style.display = 'none';
                                                }
                                            ">
                                            <?php echo !empty($_SESSION['price']) ? $_SESSION['price'] . ' €' : 'Price'; ?>
                                        </button>

                                        <div class="c-e-price-container" id="priceFilter" style="display:none;">
                                            <input type="range" id="priceRange" class="c-e-range-price" min="0"
                                                max="1000" step="5" value="<?php echo !empty($_SESSION['price']) ? $_SESSION['price'] : 50; ?>">
                                            <div id="priceValueDisplay" class="mt-2 text-center fw-bold" style="color: white;">
                                                <?php echo !empty($_SESSION['price']) ? $_SESSION['price'] . ' €' : '50 €'; ?>
                                            </div>
                                        </div>
                                        <!-- Añadir campo oculto para asegurar que siempre se envía el valor del precio -->
                                        <input type="hidden" id="hiddenPriceInput" name="price" value="<?php echo !empty($_SESSION['price']) ? $_SESSION['price'] : ''; ?>">
                                    </div>

                                    <!--Filtro estilo-->
                                    <select name="style" id="style" class="c-e-style">
                                        <option value="" selected> Style </option>
                                        <?php
                                        // Debugging
                                        $debug_styles = [];
                                        $style_count = $result_style->num_rows;

                                        // Si no hay estilos, reiniciar el cursor
                                        if ($style_count == 0) {
                                            echo "<!-- No styles found in database -->";
                                        } else {
                                            // Reset the pointer to the beginning
                                            $result_style->data_seek(0);

                                            while ($row = $result_style->fetch_assoc()) {
                                                $style_value = htmlspecialchars($row['style']);
                                                $debug_styles[] = $style_value;
                                                $selected = ($_SESSION['style'] ?? '') === $style_value ? 'selected' : '';
                                                echo "<option value=\"$style_value\" $selected>$style_value</option>";
                                            }

                                            // Echo debug info as HTML comment
                                            echo "<!-- Available styles: " . implode(", ", $debug_styles) . " -->";
                                        }
                                        ?>
                                    </select>

                                    <!--Botón aplicar filtros-->
                                    <button type="submit" class="c-e-btn-aceptar-filtros" id="aceptar-filtros"
                                        name="aceptar-filtros">Filter</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Catalog Section-->
                <div class="container mt-5">

                    <!--Título de sección-->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="fw-bold text-uppercase">Catalog</h2>
                        <div>
                            <a href="?clear_filters=1" class="btn btn-sm btn-outline-secondary me-2">Reset Filter</a>
                        </div>
                    </div>

                    <?php if ($result_events->num_rows > 0): ?>
                        <div class="row g-4">
                            <?php while ($event = $result_events->fetch_assoc()): ?>
                                <?php
                                    $sql_reviews = "
                                        SELECT r.rating, r.comment, r.review_date, u.name
                                        FROM reviews r
                                        JOIN users u ON r.user_id = u.id
                                        WHERE r.event_id = ?
                                        ORDER BY r.review_date DESC
                                    ";

                                    $stmt_reviews = $conn->prepare($sql_reviews);
                                    $stmt_reviews->bind_param("i", $event["id"]);
                                    $stmt_reviews->execute();
                                    $result_reviews = $stmt_reviews->get_result();

                                    $total_ratings = 0;
                                    $num_reviews = 0;

                                    $reviews_data = []; 

                                    if ($result_reviews->num_rows > 0) {
                                        while ($row = $result_reviews->fetch_assoc()) {
                                            $reviews_data[] = $row; 
                                            $total_ratings += $row['rating'];
                                            $num_reviews++;
                                        }
                                    }

                                    $average_rating = 0;
                                    if ($num_reviews > 0) {
                                        $average_rating = $total_ratings / $num_reviews;
                                    }
                                ?>
                                <div class="col-md-4 col-sm-6 mb-4">
                                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                                <div class="d-flex justify-content-end gap-3 fs-5">
                                                    <a href="../../backend/controllers/edit_event.php?id=<?= $event['id'] ?>"><i
                                                            class="fa-solid fa-pen-to-square"></i></a>
                                                    <a class="text-danger"
                                                        href="../../backend/controllers/delete_event.php?id=<?= $event['id'] ?>"><i
                                                            class="fa-solid fa-trash"></i></a>
                                                </div>
                                            <?php endif; ?>
                                    <div class="c-e-card shadow-sm">
                                        <img src="<?= htmlspecialchars($event['image_url']) ?>" alt="evento"
                                            class="card-img-top c-e-img-catalog">
                                        <div class="c-e-card-body text-center">
                                            <h6 class="fw-bold"><?= htmlspecialchars($event['event_name']) ?></h6>
                                            <p class="text-muted"><?= htmlspecialchars($event['event_date']) ?></p>
                                            <p class="text-muted"><?= htmlspecialchars($event['city']) ?> -
                                                <?= htmlspecialchars($event['style']) ?></p>
                                            <div>
                                                <?php if ($num_reviews > 0): ?>
                                                    <span class="star-rating">
                                                        <?php
                                                        for ($i = 0; $i < floor($average_rating); $i++) {
                                                            echo '<i class="fa-solid fa-star"></i>';
                                                        }
                                                        if ($average_rating - floor($average_rating) >= 0.5) {
                                                            echo '<i class="fa-solid fa-star-half-stroke"></i>';
                                                        }
                                                        for ($i = 0; $i < (5 - ceil($average_rating)); $i++) {
                                                            echo '<i class="fa-regular fa-star"></i>'; 
                                                        }
                                                        ?>
                                                    </span>
                                                    *<?php echo number_format($average_rating, 1); ?>* (<?php echo $num_reviews; ?> reviews)
                                                <?php else: ?>
                                                    <span>Be the first to rate this event.</span>
                                                <?php endif; ?>
                                            </div>
                                            <button class="c-e-btn-card btn btn-primary w-100 custom"><a
                                                    href="pagina-evento.php?id=<?= $event['id'] ?>">Book Now</a></button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p>No se han encontrado eventos con los filtros seleccionados</p>
                    <?php endif; ?>
                </div>
            </div>


        </main>
        <!-- Footer -->
        <?php include "../static/footer.php" ?>

        </footer>
        <!--Script Bootstrap-->

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const toggleBtn = document.getElementById("toggleFilterBtn");
                const priceFilter = document.getElementById("priceFilter");
                const priceRange = document.getElementById("priceRange");
                const priceValueDisplay = document.getElementById("priceValueDisplay");
                const filterForm = document.querySelector(".c-e-form-filters");
                const filterButton = document.getElementById("aceptar-filtros");
                const hiddenPriceInput = document.getElementById("hiddenPriceInput");

                // Comprobar si ya hay un precio seleccionado
                const currentPrice = "<?php echo !empty($_SESSION['price']) ? $_SESSION['price'] : ''; ?>";
                if (currentPrice) {
                    // Si hay un precio seleccionado, mostrar el valor en el botón
                    toggleBtn.style.backgroundColor = "#6f42c1";
                    toggleBtn.textContent = currentPrice + " €";
                    priceRange.value = currentPrice;
                    hiddenPriceInput.value = currentPrice;
                    priceValueDisplay.textContent = currentPrice + " €";
                }

                // Actualizar valor al mover el slider
                priceRange.addEventListener("input", function() {
                    const price = this.value;
                    priceValueDisplay.textContent = price + " €";
                    toggleBtn.textContent = price + " €";
                    hiddenPriceInput.value = price;

                    // Asegurarse de que el valor se aplica inmediatamente
                    if (price && price !== "0") {
                        toggleBtn.style.backgroundColor = "#6f42c1";
                    } else {
                        toggleBtn.style.backgroundColor = "#4D194D";
                        toggleBtn.textContent = "Price";
                        hiddenPriceInput.value = "";
                    }
                });

                // Asegurar que el precio se guarda al cambiar el valor del slider
                priceRange.addEventListener("change", function() {
                    const price = this.value;
                    if (price && price !== "0") {
                        toggleBtn.textContent = price + " €";
                        toggleBtn.style.backgroundColor = "#6f42c1";
                        hiddenPriceInput.value = price;
                    } else {
                        toggleBtn.textContent = "Price";
                        toggleBtn.style.backgroundColor = "#4D194D";
                        hiddenPriceInput.value = "";
                    }
                });

                // Asegurar que el formulario incluye el precio cuando se envía
                filterForm.addEventListener("submit", function(e) {
                    // No detener el envío, solo asegurarse de que el precio se envía correctamente
                    if (priceRange.value && priceRange.value !== "0") {
                        hiddenPriceInput.value = priceRange.value;
                    }
                });

                // Asegurar que el formulario incluye el precio cuando se hace clic en el botón de filtrar
                filterButton.addEventListener("click", function() {
                    if (priceRange.value && priceRange.value !== "0") {
                        hiddenPriceInput.value = priceRange.value;
                    }
                });

                // Marcar los selectores que tengan un valor seleccionado
                const citySelect = document.getElementById('city');
                const styleSelect = document.getElementById('style');
                const dateInput = document.getElementById('date');

                if (citySelect.value) {
                    citySelect.style.backgroundColor = "#6f42c1";
                }

                if (styleSelect.value) {
                    styleSelect.style.backgroundColor = "#6f42c1";
                }

                if (dateInput.value) {
                    dateInput.style.backgroundColor = "#6f42c1";
                }

                // Cambiar color cuando se selecciona un valor
                citySelect.addEventListener('change', function() {
                    this.style.backgroundColor = this.value ? "#6f42c1" : "#4D194D";
                });

                styleSelect.addEventListener('change', function() {
                    this.style.backgroundColor = this.value ? "#6f42c1" : "#4D194D";
                });

                dateInput.addEventListener('change', function() {
                    this.style.backgroundColor = this.value ? "#6f42c1" : "#4D194D";
                });
            });
        </script>
</body>

</html>