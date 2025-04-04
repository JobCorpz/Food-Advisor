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
$cuisines = $pdo->query("SELECT * FROM cuisines")->fetchAll(PDO::FETCH_ASSOC);

// Handle review submission
if (isset($_POST['submit_review'])) {
    $restaurant_id = $_POST['restaurant_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, restaurant_id, rating, comment) VALUES (:user_id, :restaurant_id, :rating, :comment)");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':restaurant_id' => $restaurant_id,
        ':rating' => $rating,
        ':comment' => $comment
    ]);

    // Update average rating
    $stmt = $pdo->prepare("UPDATE restaurants SET average_rating = (SELECT AVG(rating) FROM reviews WHERE restaurant_id = :restaurant_id) WHERE id = :restaurant_id");
    $stmt->execute([':restaurant_id' => $restaurant_id]);
}

// Handle filters
$where = [];
$params = [];
if (!empty($_GET['cuisine'])) {
    $where[] = "cuisine_id = :cuisine";
    $params[':cuisine'] = $_GET['cuisine'];
}
if (!empty($_GET['location'])) {
    $where[] = "location LIKE :location";
    $params[':location'] = '%' . $_GET['location'] . '%';
}

$sql = "SELECT r.*, c.name AS cuisine_name FROM restaurants r JOIN cuisines c ON r.cuisine_id = c.id";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to display stars based on rating
function displayStars($rating) {
    $fullStars = floor($rating); // Number of full stars
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0; // Half star if >= 0.5
    $emptyStars = 5 - $fullStars - $halfStar; // Remaining empty stars

    $stars = str_repeat('★', $fullStars); // Full stars
    $stars .= $halfStar ? '½' : ''; // Half star (using text for simplicity)
    $stars .= str_repeat('☆', $emptyStars); // Empty stars
    return $stars . " (" . number_format($rating, 2) . ")";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .stars {
        display: inline-block;
        position: relative;
        font-size: 1.2em;
    }
        .stars::before {
        content: "☆☆☆☆☆";
        color: #ddd;
    }
    .stars::after {
        content: "★★★★★";
        color: #f1c40f;
        position: absolute;
        left: 0;
        top: 0;
        width: calc(20% * <?php echo $restaurant['average_rating']; ?>);
        overflow: hidden;
    }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        <a href="preference.php">Set Preferences</a> | <a href="logout.php">Logout</a>

        <!-- Filter Form -->
        <form method="get" class="filter-form">
            <select name="cuisine">
                <option value="">All Cuisines</option>
                <?php foreach ($cuisines as $cuisine): ?>
                    <option value="<?php echo $cuisine['id']; ?>" <?php echo isset($_GET['cuisine']) && $_GET['cuisine'] == $cuisine['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cuisine['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="location" placeholder="Enter location" value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>">
            <button type="submit">Filter</button>
        </form>

        <!-- Restaurants List -->
        <h2>Explore Restaurants</h2>
        <?php foreach ($restaurants as $restaurant): ?>
            <div class="restaurant">
                <h3><?php echo htmlspecialchars($restaurant['name']); ?> (<?php echo htmlspecialchars($restaurant['cuisine_name']); ?>)</h3>
                <p>Location: <?php echo htmlspecialchars($restaurant['location']); ?></p>
                <p>Average Rating: <span class="stars"><?php echo displayStars($restaurant['average_rating']); ?></span></p>

                <!-- Dishes -->
                <?php
                $dishes = $pdo->prepare("SELECT * FROM dishes WHERE restaurant_id = :restaurant_id");
                $dishes->execute([':restaurant_id' => $restaurant['id']]);
                $dish_list = $dishes->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <h4>Dishes</h4>
                <ul>
                    <?php foreach ($dish_list as $dish): ?>
                        <li><?php echo htmlspecialchars($dish['name']); ?> - $<?php echo number_format($dish['price'], 2); ?><br><?php echo htmlspecialchars($dish['description']); ?></li>
                    <?php endforeach; ?>
                </ul>

                <!-- Review Form -->
                <form method="post" class="review-form">
                    <input type="hidden" name="restaurant_id" value="<?php echo $restaurant['id']; ?>">
                    <label>Rating (1-5): 
                        <input type="number" name="rating" min="1" max="5" step="0.5" required>
                    </label>
                    <textarea name="comment" placeholder="Your review" required></textarea>
                    <button type="submit" name="submit_review">Submit Review</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>