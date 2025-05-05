<?php 
require_once 'config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = clean_input($_POST["username"]);
    $password = $_POST["password"];
    $error = "";
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Check if user exists
        $sql = "SELECT id, username, password, full_name, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user["password"])) {
                // Password is correct, set session variables
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];
                
                // Redirect to dashboard
                redirectWithMessage("dashboard.php", "Login successful. Welcome back, " . $user["full_name"] . "!");
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
        
        $stmt->close();
    }
}

include 'header.php';
?>

<div class="auth-container">
    <div class="auth-form fade-in">
        <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
        
        <?php if (isset($error) && !empty($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($username) ? $username : ''; ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-block">Login</button>
        </form>
        
        <div class="form-footer">
            <p>Don't have an account? <a href="register.php">Register now</a></p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>