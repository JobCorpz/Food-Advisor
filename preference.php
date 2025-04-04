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

$success = '';
$error = '';
if (isset($_POST['submit'])) {
    $dietary_restrictions = implode(',', (array)$_POST['dietary_restrictions']);
    $favorite_cuisines = implode(',', (array)$_POST['favorite_cuisines']);

    $stmt = $pdo->prepare("SELECT id FROM user_preferences WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE user_preferences SET dietary_restrictions = :diet, favorite_cuisines = :cuisines WHERE user_id = :user_id");
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, dietary_restrictions, favorite_cuisines) VALUES (:user_id, :diet, :cuisines)");
    }

    try {
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':diet' => $dietary_restrictions,
            ':cuisines' => $favorite_cuisines
        ]);
        $success = "Preferences saved successfully!";
    } catch (PDOException $e) {
        $error = "Error saving preferences: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT dietary_restrictions, favorite_cuisines FROM user_preferences WHERE user_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);
$current_dietary = $prefs ? explode(',', $prefs['dietary_restrictions']) : [];
$current_cuisines = $prefs ? explode(',', $prefs['favorite_cuisines']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Preferences</title>
    <link rel="stylesheet" href="preference.css">
</head>
<body>
    <div class="preferences">
        <h1>Your Preferences, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="post">
            <fieldset>
                <legend>Dietary Restrictions</legend>
                <label><input type="checkbox" name="dietary_restrictions[]" value="vegetarian" <?php echo in_array('vegetarian', $current_dietary) ? 'checked' : ''; ?>> Vegetarian</label>
                <label><input type="checkbox" name="dietary_restrictions[]" value="vegan" <?php echo in_array('vegan', $current_dietary) ? 'checked' : ''; ?>> Vegan</label>
                <label><input type="checkbox" name="dietary_restrictions[]" value="gluten-free" <?php echo in_array('gluten-free', $current_dietary) ? 'checked' : ''; ?>> Gluten-Free</label>
                <label><input type="checkbox" name="dietary_restrictions[]" value="nut-free" <?php echo in_array('nut-free', $current_dietary) ? 'checked' : ''; ?>> Nut-Free</label>
            </fieldset>
            <fieldset>
                <legend>Favorite Cuisines</legend>
                <label><input type="checkbox" name="favorite_cuisines[]" value="Italian" <?php echo in_array('Italian', $current_cuisines) ? 'checked' : ''; ?>> Italian</label>
                <label><input type="checkbox" name="favorite_cuisines[]" value="Mexican" <?php echo in_array('Mexican', $current_cuisines) ? 'checked' : ''; ?>> Mexican</label>
                <label><input type="checkbox" name="favorite_cuisines[]" value="Chinese" <?php echo in_array('Chinese', $current_cuisines) ? 'checked' : ''; ?>> Chinese</label>
                <label><input type="checkbox" name="favorite_cuisines[]" value="Indian" <?php echo in_array('Indian', $current_cuisines) ? 'checked' : ''; ?>> Indian</label>
            </fieldset>
            <button type="submit" name="submit">Save Preferences</button>
        </form>
        <a href="dashboard.php">Go to Dashboard</a> <!-- Link to dashboard -->
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>