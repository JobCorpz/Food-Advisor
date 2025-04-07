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

$user_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) ? (int)$_GET['tab'] : 1;
$message = '';
$error = '';

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch current preferences
$stmt = $pdo->prepare("SELECT dietary_restrictions FROM user_preferences WHERE user_id = :id");
$stmt->execute([':id' => $user_id]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);
$current_prefs = $prefs ? explode(',', $prefs['dietary_restrictions']) : [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    if ($tab == 1) { // Preferences
        if (isset($_POST['reset'])) {
            $stmt = $pdo->prepare("DELETE FROM user_preferences WHERE user_id = :id");
            $stmt->execute([':id' => $user_id]);
            $message = "Preferences reset successfully!";
            $current_prefs = [];
        } else {
            $dietary = isset($_POST['dietary']) ? implode(',', $_POST['dietary']) : '';
            $stmt = $pdo->prepare("REPLACE INTO user_preferences (user_id, dietary_restrictions) VALUES (:id, :dietary)");
            $stmt->execute([':id' => $user_id, ':dietary' => $dietary]);
            $message = "Preferences updated successfully!";
            $current_prefs = explode(',', $dietary);
        }
    } elseif ($tab == 2) { // Profile
        $new_name = trim($_POST['name']);
        $new_email = trim($_POST['email']);
        $new_password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        if (!empty($new_name) && $new_name !== $user['name']) {
            $stmt = $pdo->prepare("UPDATE users SET name = :name WHERE id = :id");
            $stmt->execute([':name' => $new_name, ':id' => $user_id]);
            $user['name'] = $new_name;
            $message = "Name updated successfully!";
        }
        if (!empty($new_email) && $new_email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
            $stmt->execute([':email' => $new_email, ':id' => $user_id]);
            if ($stmt->fetchColumn() == 0) {
                $stmt = $pdo->prepare("UPDATE users SET email = :email WHERE id = :id");
                $stmt->execute([':email' => $new_email, ':id' => $user_id]);
                $user['email'] = $new_email;
                $message .= " Email updated successfully!";
            } else {
                $error = "Email is already in use.";
            }
        }
        if (!empty($new_password)) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute([':password' => $hashed_password, ':id' => $user_id]);
                $message .= " Password updated successfully!";
            } else {
                $error = "Passwords do not match.";
            }
        }
    } elseif ($tab == 3) { // Account Settings
        if (isset($_POST['delete_account']) && $_POST['delete_account'] === 'DELETE') {
            $stmt = $pdo->prepare("DELETE FROM user_preferences WHERE user_id = :id");
            $stmt->execute([':id' => $user_id]);
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_id = :id");
            $stmt->execute([':id' => $user_id]);
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            session_destroy();
            header("Location: login.php?deleted=true");
            exit();
        } else {
            $error = "Please type 'DELETE' to confirm account deletion.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Settings</title>
    <link rel="stylesheet" href="preference.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-left">
            <h1>Foodie</h1>
            <a href="dashboard.php"><img src="logo/foodie-logo-white.png" alt="Foodie Logo" class="logo"></a>
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

    <div class="container">
        <aside class="sidebar">
            <h3>Settings</h3>
            <nav>
                <a href="?tab=1" class="<?php echo $tab == 1 ? 'active' : ''; ?>"><i class="fas fa-utensils"></i> Preferences</a>
                <a href="?tab=2" class="<?php echo $tab == 2 ? 'active' : ''; ?>"><i class="fas fa-user"></i> Profile</a>
                <a href="?tab=3" class="<?php echo $tab == 3 ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Account</a>
            </nav>
        </aside>

        <main class="content">
            <?php if ($message): ?>
                <p class="message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if ($tab == 1): ?>
                <h2>Dietary Preferences</h2>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="checkbox-group">
                        <label class="tooltip"><input type="checkbox" name="dietary[]" value="vegetarian" <?php echo in_array('vegetarian', $current_prefs) ? 'checked' : ''; ?>> Vegetarian
                            <span class="tooltiptext">No meat or fish.</span></label>
                        <label class="tooltip"><input type="checkbox" name="dietary[]" value="vegan" <?php echo in_array('vegan', $current_prefs) ? 'checked' : ''; ?>> Vegan
                            <span class="tooltiptext">No animal products.</span></label>
                        <label class="tooltip"><input type="checkbox" name="dietary[]" value="gluten-free" <?php echo in_array('gluten-free', $current_prefs) ? 'checked' : ''; ?>> Gluten-Free
                            <span class="tooltiptext">No wheat or gluten.</span></label>
                        <label class="tooltip"><input type="checkbox" name="dietary[]" value="nut-free" <?php echo in_array('nut-free', $current_prefs) ? 'checked' : ''; ?>> Nut-Free
                            <span class="tooltiptext">No nuts or nut products.</span></label>
                        <label class="tooltip"><input type="checkbox" name="dietary[]" value="dairy-free" <?php echo in_array('dairy-free', $current_prefs) ? 'checked' : ''; ?>> Dairy-Free
                            <span class="tooltiptext">No milk or dairy.</span></label>
                        <label class="tooltip"><input type="checkbox" name="dietary[]" value="low-carb" <?php echo in_array('low-carb', $current_prefs) ? 'checked' : ''; ?>> Low-Carb
                            <span class="tooltiptext">Low carbohydrate intake.</span></label>
                    </div>
                    <div class="button-group">
                        <button type="submit">Save Preferences</button>
                        <button type="submit" name="reset" value="1" class="reset-btn">Reset Preferences</button>
                    </div>
                </form>
            <?php elseif ($tab == 2): ?>
                <h2>Profile</h2>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" placeholder="Enter new name">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Enter new email">
                    <label for="password">New Password:</label>
                    <input type="password" id="password" name="password" placeholder="Enter new password">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                    <button type="submit">Update Profile</button>
                </form>
            <?php elseif ($tab == 3): ?>
                <h2>Account Settings</h2>
                <form method="post" class="delete-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <p class="warning">Warning: This action cannot be undone.</p>
                    <label for="delete_account">Type 'DELETE' to confirm account deletion:</label>
                    <input type="text" id="delete_account" name="delete_account" placeholder="DELETE" required>
                    <button type="submit" class="delete-btn">Delete Account</button>
                </form>
            <?php endif; ?>
        </main>
    </div>

    <footer>
        <p>© 2025 Foodie. All rights reserved.</p>
        <p>Follow us: <a href="#">Facebook</a> | <a href="#">Twitter</a> | <a href="#">Instagram</a></p>
    </footer>
</body>
</html>