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

// Fetch restaurants
$sql = "SELECT r.*, c.name AS cuisine_name FROM restaurants r JOIN cuisines c ON r.cuisine_id = c.id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Foodie - Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-left">
            <h1>Foodie</h1>
            <img src="istockphoto-1295311342-612x612.jpg" alt="Foodie Logo" class="logo">
        </div>
        <div class="header-right">
            <a href=""><img src="user-member-avatar-face-profile-icon-vector-22965342.jpg" alt="Profile" class="profile-icon"></a>
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

    <!-- Body -->
    <main>
        <h2>Explore Restaurants</h2>
        <div class="restaurant-grid">
            <?php foreach ($restaurants as $restaurant): ?>
                <a href="restaurant.php?id=<?php echo $restaurant['id']; ?>" class="restaurant-card">
                    <img src="restaurant-photo.jpg" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="restaurant-photo">
                    <h3><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                    <p>Cuisine: <?php echo htmlspecialchars($restaurant['cuisine_name']); ?></p>
                    <p>Location: <?php echo htmlspecialchars($restaurant['location']); ?></p>
                    <p>Rating: <span class="stars"><?php echo displayStars($restaurant['average_rating']); ?></span></p>
                </a>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Foodie. All rights reserved.</p>
        <p>Follow us: 
            <a href="#">Facebook</a> | 
            <a href="#">Twitter</a> | 
            <a href="#">Instagram</a>
        </p>
    </footer>
</body>
</html>