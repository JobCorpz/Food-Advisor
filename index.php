<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class = "parent">
    <div class = "Logo">
        <h1>Foodie</h1>
        <img src="logo/foodie-logo-black.png" alt="logo" style="min-height: 150px;">
        <h2>Your personalized online Food Advisor</h2>
    </div>
    <div class = "Auth">
        <h1>Start your Journey</h1>
        <form action= "create_account.php" method="get">
            <button type= "submit">Create account</button>
        </form>
        <p>Already have an account?</p>
        <form action="login.php" method="get">
            <button type= "submit">Sign in </button>
        </form>
    </div>
    </div>
</body>
</html>