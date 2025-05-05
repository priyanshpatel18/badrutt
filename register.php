<?php
require_once 'config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = clean_input($_POST["username"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $email = clean_input($_POST["email"]);
    $full_name = clean_input($_POST["full_name"]);
    $phone = isset($_POST["phone"]) ? clean_input($_POST["phone"]) : "";
    $error = "";
    
    // Validate input
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($full_name)) {
        $error = "All required fields must be filled";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if username or email already exists
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $sql = "INSERT INTO users (username, password, email, full_name, phone) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $hashed_password, $email, $full_name, $phone);
            
            if ($stmt->execute()) {
                // Registration successful, redirect to login
                redirectWithMessage("login.php", "Registration successful! Please login with your new account.");
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
        
        $stmt->close();
    }
}

include 'header.php';
?>

<div class="auth-container">
    <div class="auth-form fade-in">
        <h2><i class="fas fa-user-plus"></i> Register</h2>
        
        <?php if (isset($error) && !empty($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($full_name) ? $full_name : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($username) ? $username : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <small>Password must be at least 6 characters long</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($phone) ? $phone : ''; ?>">
            </div>
            
            <button type="submit" class="btn btn-block">Register</button>
        </form>
        
        <div class="form-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>