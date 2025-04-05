<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=foodie_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$restaurant_id = $_GET['id'];

// Fetch restaurant details
$stmt = $pdo->prepare("
    SELECT r.*, c.name AS cuisine_name, AVG(re.rating) AS avg_rating
    FROM restaurants r
    JOIN cuisines c ON r.cuisine_id = c.id
    LEFT JOIN reviews re ON r.id = re.restaurant_id
    WHERE r.id = :id
    GROUP BY r.id, r.name, r.location, r.cuisine_id, r.photo, c.name
");
$stmt->execute([':id' => $restaurant_id]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant) {
    die("Restaurant not found.");
}

// Fetch dishes
$stmt = $pdo->prepare("SELECT * FROM dishes WHERE restaurant_id = :id");
$stmt->execute([':id' => $restaurant_id]);
$dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch reviews
$stmt = $pdo->prepare("
    SELECT re.rating, re.comment, re.created_at, u.name AS user_name
    FROM reviews re
    JOIN users u ON re.user_id = u.id
    WHERE re.restaurant_id = :id
    ORDER BY re.created_at DESC
");
$stmt->execute([':id' => $restaurant_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to display stars
function displayStars($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    return str_repeat('★', $fullStars) . ($halfStar ? '½' : '') . str_repeat('☆', $emptyStars);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurant['name']); ?> - Foodie</title>
    <link rel="stylesheet" href="restaurant.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-left">
            <h1>Foodie</h1>
            <img src="foodie-logo.png" alt="Foodie Logo" class="logo">
        </div>
        <div class="header-right">
            <img src="profile-icon.png" alt="Profile" class="profile-icon">
            <div class="dropdown">
                <button class="dropdown-btn">Menu ▼</button>
                <div class="dropdown-content">
                    <a href="dashboard.php">Home</a>
                    <a href="#about">About</a>
                    <a href="#contact">Contact</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main>
        <section class="restaurant-header">
            <img src="<?php echo htmlspecialchars($restaurant['photo']); ?>" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="restaurant-photo">
            <h2><?php echo htmlspecialchars($restaurant['name']); ?></h2>
            <p>Cuisine: <?php echo htmlspecialchars($restaurant['cuisine_name']); ?></p>
            <p>Location: <?php echo htmlspecialchars($restaurant['location']); ?></p>
            <p>Rating: <span class="stars"><?php echo displayStars($restaurant['avg_rating'] ?: 0); ?></span> (<?php echo number_format($restaurant['avg_rating'] ?: 0, 2); ?>/5)</p>
        </section>

        <section class="menu">
            <h3>Menu</h3>
            <ul>
                <?php foreach ($dishes as $dish): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($dish['name']); ?></strong> - $<?php echo number_format($dish['price'], 2); ?>
                        <p><?php echo htmlspecialchars($dish['description']); ?></p>
                        <p class="dietary"><?php echo $dish['dietary_restrictions'] ? 'Dietary: ' . htmlspecialchars($dish['dietary_restrictions']) : 'No specific dietary restrictions'; ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="reviews">
            <h3>Reviews</h3>
            <?php if (empty($reviews)): ?>
                <p>No reviews yet.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review">
                        <p><strong><?php echo htmlspecialchars($review['user_name']); ?></strong> - <span class="stars"><?php echo displayStars($review['rating']); ?></span></p>
                        <p><?php echo htmlspecialchars($review['comment']); ?></p>
                        <p class="date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>© 2025 Foodie. All rights reserved.</p>
        <p>Follow us: <a href="#">Facebook</a> | <a href="#">Twitter</a> | <a href="#">Instagram</a></p>
    </footer>
</body>
</html> 