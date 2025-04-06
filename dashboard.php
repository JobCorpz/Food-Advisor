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

// Fetch cuisines for filter
$cuisines = $pdo->query("SELECT * FROM cuisines ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle cuisine filter
$where = [];
$params = [];
if (!empty($_GET['cuisines'])) {
    $where[] = "r.cuisine_id IN (" . implode(',', array_fill(0, count($_GET['cuisines']), '?')) . ")";
    $params = array_merge($params, $_GET['cuisines']);
}

$sql = "
    SELECT r.*, c.name AS cuisine_name, AVG(re.rating) AS avg_rating, COUNT(re.id) AS review_count
    FROM restaurants r
    JOIN cuisines c ON r.cuisine_id = c.id
    LEFT JOIN reviews re ON r.id = re.restaurant_id
";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " GROUP BY r.id, r.name, r.location, r.cuisine_id, r.photo
          ORDER BY avg_rating DESC
          LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to display stars
function displayStars($rating) {
    $rating = $rating ?: 0;
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
            <a href="dashboard.php"><img src="istockphoto-1295311342-612x612.jpg" alt="Foodie Logo" class="logo"></a>
        </div>
        <div class="header-right">
            <a href="preference.php"><img src="user-member-avatar-face-profile-icon-vector-22965342.jpg" alt="Profile" class="profile-icon"></a>
            <div class="dropdown">
                <button class="dropdown-btn">Menu ▼</button>
                <div class="dropdown-content">
                    <a href="dashboard.php">Home</a>
                    <a href="preference.php">Preference</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Body -->
    <main>
        <h2>Top Rated Restaurants</h2>
        <form method="get" class="filter-form">
            <div class="cuisine-filter">
                <button type="button" class="filter-toggle">Filter by Cuisine ▼</button>
                <div class="filter-options">
                    <?php foreach ($cuisines as $cuisine): ?>
                        <label>
                            <input type="checkbox" name="cuisines[]" value="<?php echo $cuisine['id']; ?>" <?php echo in_array($cuisine['id'], $_GET['cuisines'] ?? []) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($cuisine['name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit">Apply</button>
        </form>
        <div class="restaurant-grid">
            <?php foreach ($restaurants as $restaurant): ?>
                <a href="restaurant.php?id=<?php echo $restaurant['id']; ?>" class="restaurant-card">
                    <img src="<?php echo htmlspecialchars($restaurant['photo']); ?>" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="restaurant-photo">
                    <h3><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                    <p>Cuisine: <?php echo htmlspecialchars($restaurant['cuisine_name']); ?></p>
                    <p>Location: <?php echo htmlspecialchars($restaurant['location']); ?></p>
                    <p>Rating: <span class="stars"><?php echo displayStars($restaurant['avg_rating']); ?></span> (<?php echo number_format($restaurant['avg_rating'] ?: 0, 2); ?>/5)</p>
                    <p>Reviews: <?php echo $restaurant['review_count']; ?></p>
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