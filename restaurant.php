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
$stmt = $pdo->prepare("SELECT r.*, c.name AS cuisine_name FROM restaurants r JOIN cuisines c ON r.cuisine_id = c.id WHERE r.id = :id");
$stmt->execute([':id' => $restaurant_id]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
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
    <main>
        <h2><?php echo htmlspecialchars($restaurant['name']); ?></h2>
        <p>Cuisine: <?php echo htmlspecialchars($restaurant['cuisine_name']); ?></p>
        <p>Location: <?php echo htmlspecialchars($restaurant['location']); ?></p>
        <p>Rating: <span class="stars"><?php echo displayStars($restaurant['average_rating']); ?></span></p>
        <img src="restaurant-photo.jpg" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="restaurant-photo">
    </main>
    <footer>
        <p>&copy; 2025 Foodie. All rights reserved.</p>
        <p>Follow us: <a href="#">Facebook</a> | <a href="#">Twitter</a> | <a href="#">Instagram</a></p>
    </footer>
</body>
</html>