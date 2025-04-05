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

// Fetch user preferences
$stmt = $pdo->prepare("SELECT dietary_restrictions FROM user_preferences WHERE user_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);
$user_dietary = $prefs && $prefs['dietary_restrictions'] ? explode(',', $prefs['dietary_restrictions']) : [];

// Fetch dishes with dynamic filtering
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';
$sql = "SELECT * FROM dishes WHERE restaurant_id = :id";
if (!empty($user_dietary) && !$show_all) {
    $conditions = array_map(function($pref) {
        return "FIND_IN_SET(:$pref, dietary_restrictions) > 0";
    }, array_keys($user_dietary));
    $sql .= " AND (" . implode(' OR ', $conditions) . " OR dietary_restrictions = '' OR dietary_restrictions IS NULL)";
}
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $restaurant_id, PDO::PARAM_INT);
if (!empty($user_dietary) && !$show_all) {
    foreach ($user_dietary as $key => $pref) {
        $stmt->bindValue(":$key", $pref);
    }
}
$stmt->execute();
$dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch reviews with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT re.rating, re.comment, re.created_at, u.name AS user_name
    FROM reviews re
    JOIN users u ON re.user_id = u.id
    WHERE re.restaurant_id = :id
    ORDER BY re.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':id', $restaurant_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total reviews for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE restaurant_id = :id");
$stmt->execute([':id' => $restaurant_id]);
$total_reviews = $stmt->fetchColumn();
$total_pages = ceil($total_reviews / $per_page);

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
    <title><?php echo htmlspecialchars($restaurant['name']); ?> - Foodie</title>
    <link rel="stylesheet" href="restaurant.css">
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
        <section class="restaurant-header">
            <img src="<?php echo htmlspecialchars($restaurant['photo']); ?>" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="restaurant-photo">
            <h2><?php echo htmlspecialchars($restaurant['name']); ?></h2>
            <p>Cuisine: <?php echo htmlspecialchars($restaurant['cuisine_name']); ?></p>
            <p>Location: <?php echo htmlspecialchars($restaurant['location']); ?></p>
            <p>Rating: <span class="stars"><?php echo displayStars($restaurant['avg_rating']); ?></span> (<?php echo number_format($restaurant['avg_rating'] ?: 0, 2); ?>/5)</p>
        </section>

        <section class="menu">
            <h3>Menu</h3>
            <?php if (!empty($user_dietary)): ?>
                <p>
                    <a href="?id=<?php echo $restaurant_id; ?>&show_all=<?php echo $show_all ? '0' : '1'; ?>&page=<?php echo $page; ?>">
                        <?php echo $show_all ? 'Filter by My Preferences' : 'Show All Dishes'; ?>
                    </a>
                </p>
            <?php endif; ?>
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
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?id=<?php echo $restaurant_id; ?>&page=<?php echo $i; ?>&show_all=<?php echo $show_all ? '1' : '0'; ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>© 2025 Foodie. All rights reserved.</p>
        <p>Follow us: <a href="#">Facebook</a> | <a href="#">Twitter</a> | <a href="#">Instagram</a></p>
    </footer>
</body>
</html>