<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirectWithMessage("login.php", "Please login to access this page", "error");
}

// Ensure a valid booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithMessage("dashboard.php", "Invalid booking ID", "error");
}

$booking_id = $_GET['id'];

// Get the booking details to check if it belongs to the logged-in user
$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM bookings WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirectWithMessage("dashboard.php", "Booking not found or you do not have permission", "error");
}

$booking = $result->fetch_assoc();

if ($booking["status"] == "cancelled") {
    redirectWithMessage("dashboard.php", "This booking has already been cancelled", "error");
}

$current_date = date('Y-m-d');
if (strtotime($booking["check_in_date"]) <= strtotime($current_date)) {
    redirectWithMessage("dashboard.php", "Cannot cancel past bookings", "error");
}

$sql = "UPDATE bookings SET status = 'cancelled' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();

redirectWithMessage("dashboard.php", "Your booking has been successfully cancelled", "success");
?>
