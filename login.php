<?php
session_start(); // Start session for user login

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=foodie_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$login_error = '';
$register_error = '';
$register_success = '';

// Handle login
if (isset($_POST['login_submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header("Location: preference.php");
        exit();
    } else {
        $login_error = "Invalid email or password.";
    }
}

// Handle registration
if (isset($_POST['register_submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $register_error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $register_error = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Invalid email format.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $register_error = "Email already registered.";
        } else {
            // Register new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
            $stmt->execute([':name' => $name, ':email' => $email, ':password' => $hashed_password]);
            $register_success = "Account created successfully! Please sign in.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Login / Register</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        <!-- Login Form -->
        <div class="form-container" id="login-form">
            <h2>Sign In</h2>
            <?php if ($login_error): ?>
                <p class="error"><?php echo $login_error; ?></p>
            <?php endif; ?>
            <?php if ($register_success): ?>
                <p class="success"><?php echo $register_success; ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login_submit">Sign In</button>
                <p class="toggle-text">Don't have an account? <a href="#" onclick="toggleForm('register')">Create one</a></p>
            </form>
        </div>

        <!-- Registration Form -->
        <div class="form-container" id="register-form" style="display: none;">
            <h2>Create Account</h2>
            <?php if ($register_error): ?>
                <p class="error"><?php echo $register_error; ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit" name="register_submit">Register</button>
                <p class="toggle-text">Already have an account? <a href="#" onclick="toggleForm('login')">Sign in</a></p>
            </form>
        </div>
    </div>

    <script>
        function toggleForm(form) {
            document.getElementById('login-form').style.display = form === 'login' ? 'block' : 'none';
            document.getElementById('register-form').style.display = form === 'register' ? 'block' : 'none';
        }
    </script>
</body>
</html>