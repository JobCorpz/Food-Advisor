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
    $dietary_restrictions = implode(',', (array)($_POST['dietary_restrictions']?? []));
    $restrictions = implode(',', (array)($_POST['restrictions'] ?? []));
    $favorite_cuisines = implode(',', (array)($_POST['favorite_cuisines']?? []));
    
    $stmt = $pdo->prepare("SELECT id FROM user_preferences WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE user_preferences SET dietary_restrictions = :diet, favorite_cuisines = :cuisines, restrictions = :restrictions WHERE user_id = :user_id");
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, dietary_restrictions, favorite_cuisines, restrictions) VALUES (:user_id, :diet, :cuisines, :restrictions)");
    }
    

    try {
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':diet' => $dietary_restrictions,
            ':cuisines' => $favorite_cuisines,
            ':restrictions' => $restrictions
        ]);
        
        $success = "Preferences saved successfully!";
    } catch (PDOException $e) {
        $error = "Error saving preferences: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT dietary_restrictions, favorite_cuisines, restrictions FROM user_preferences WHERE user_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);
$current_dietary = $prefs ? explode(',', $prefs['dietary_restrictions']) : [];
$current_cuisines = $prefs ? explode(',', $prefs['favorite_cuisines']) : [];
$current_restrictions = $prefs ? explode(',', $prefs['restrictions']) : [];

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
    <h2>Set your dietary restrictions and preferences</h2>

    <?php if ($success): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="dietary-restrictions">
            <fieldset>
                <legend>Dietary Restrictions</legend>
                <label><input type="checkbox" name="dietary_restrictions[]" value="Vegetarian" <?php echo in_array('Vegetarian', $current_dietary) ? 'checked' : ''; ?>> Vegetarian</label>
                <label><input type="checkbox" name="dietary_restrictions[]" value="Pescatarian" <?php echo in_array('Pescatarian', $current_dietary) ? 'checked' : ''; ?>> Pescatarian</label>
                <label><input type="checkbox" name="dietary_restrictions[]" value="Non-vegetarian" <?php echo in_array('Non-vegetarian', $current_dietary) ? 'checked' : ''; ?>> Non-vegetarian</label>
            </fieldset>
        </div>

        <div class="favorite-cuisines">
            <fieldset>
                <legend>Favorite Cuisines</legend>
                <label><input type="checkbox" name="favorite_cuisines[]" value="Italian" <?php echo in_array('Italian', $current_cuisines) ? 'checked' : ''; ?>> Italian</label>
                <label><input type="checkbox" name="favorite_cuisines[]" value="Mexican" <?php echo in_array('Mexican', $current_cuisines) ? 'checked' : ''; ?>> Mexican</label>
                <label><input type="checkbox" name="favorite_cuisines[]" value="Chinese" <?php echo in_array('Chinese', $current_cuisines) ? 'checked' : ''; ?>> Chinese</label>
                <label><input type="checkbox" name="favorite_cuisines[]" value="Indian" <?php echo in_array('Indian', $current_cuisines) ? 'checked' : ''; ?>> Indian</label>
            </fieldset>
        </div>

        <div class="restrictions">
            <fieldset>
                <legend>Restrictions</legend>
                <label><input type="checkbox" name="restrictions[]" value="Sugar-free" <?php echo in_array('sugar-free', $current_restrictions) ? 'checked' : ''; ?>> Sugar-Free</label>
                <label><input type="checkbox" name="restrictions[]" value="Dairy-free" <?php echo in_array('dairy-free', $current_restrictions) ? 'checked' : ''; ?>> Dairy-Free</label>
                <label><input type="checkbox" name="restrictions[]" value="Nut-free" <?php echo in_array('nut-free', $current_restrictions) ? 'checked' : ''; ?>> Nut-Free</label>
                <label><input type="checkbox" name="restrictions[]" value="Gluten-free" <?php echo in_array('Gluten-free', $current_restrictions) ? 'checked' : ''; ?>> Gluten-free</label>
            </fieldset>
        </div>

        <button type="submit" name="submit">Save Preferences</button>
    </form>
</div>

</body>
</html>