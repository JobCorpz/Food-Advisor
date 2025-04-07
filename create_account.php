<?php
session_start(); // Start session for feedback messages

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=foodie_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$error = '';
$success = '';

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $re_password = $_POST['re_password'];

    // Validate passwords match
    if ($password === $re_password) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hashed_password
            ]);
            $success = "Account created successfully! Redirecting to login...";
            // Redirect after 2 seconds
            header("Refresh: 2; url=login.php");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry error
                $error = "Email already exists. Please use a different email.";
            } else {
                $error = "An error occurred: " . $e->getMessage();
            }
        }
    } else {
        $error = "Passwords do not match. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Create Account</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Create Account</h2>

            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>

            <form method="post">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="re_password" placeholder="Re-enter Password" required>
                <button type="submit" name="submit">Create Account</button>
                <p class="toggle-text">Already have an account? <a href="login.php">Sign in</a></p>
            </form>
        </div>
    </div>
</body>

</html>