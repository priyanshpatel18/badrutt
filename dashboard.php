<?php
require_once 'config.php';

// Check login
if (!isLoggedIn()) {
    redirectWithMessage("login.php", "Please login to access the dashboard", "error");
}

$user_id = $_SESSION["user_id"];
$current_date = date('Y-m-d');

// Fetch bookings
$stmt = $conn->prepare("
    SELECT b.*, r.room_number, rt.name AS room_type, rt.price_per_night 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN room_types rt ON r.room_type_id = rt.id 
    WHERE b.user_id = ? 
    ORDER BY b.check_in_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();

// Fetch stats
function getCount($conn, $sql, $params, $types) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$upcoming_count = getCount($conn, 
    "SELECT COUNT(*) AS count FROM bookings WHERE user_id = ? AND check_in_date > ? AND status != 'cancelled'", 
    [$user_id, $current_date], "is"
)['count'];

$active_count = getCount($conn, 
    "SELECT COUNT(*) AS count FROM bookings WHERE user_id = ? AND check_in_date <= ? AND check_out_date >= ? AND status = 'confirmed'", 
    [$user_id, $current_date, $current_date], "iss"
)['count'];

$past_count = getCount($conn, 
    "SELECT COUNT(*) AS count FROM bookings WHERE user_id = ? AND check_out_date < ?", 
    [$user_id, $current_date], "is"
)['count'];

include 'header.php';
?>

<h1 class="page-title">My Dashboard</h1>

<div class="stats-container">
    <?php
    $stats = [
        ['icon' => 'calendar-check', 'count' => $upcoming_count, 'label' => 'Upcoming Bookings'],
        ['icon' => 'bed', 'count' => $active_count, 'label' => 'Active Stays'],
        ['icon' => 'history', 'count' => $past_count, 'label' => 'Past Stays'],
        ['icon' => 'user', 'count' => htmlspecialchars($_SESSION["full_name"]), 'label' => 'Welcome Back!'],
    ];

    foreach ($stats as $stat) {
        echo "<div class='stat-card'>
                <i class='fas fa-{$stat['icon']}'></i>
                <h3>{$stat['count']}</h3>
                <p>{$stat['label']}</p>
              </div>";
    }
    ?>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3><i class="fas fa-plus-circle"></i> Quick Actions</h3>
        <p>What would you like to do today?</p>
        <a href="booking.php" class="btn btn-block">Book a Room</a>
        <a href="#my-bookings" class="btn btn-block" style="background-color: #ff9800;">View My Bookings</a>
        <?php if (isAdmin()): ?>
            <a href="admin.php" class="btn btn-block" style="background-color: #f44336;">Admin Panel</a>
        <?php endif; ?>
    </div>

    <div class="dashboard-card">
        <h3><i class="fas fa-concierge-bell"></i> Hotel Services</h3>
        <ul style="list-style: none; padding-left: 0;">
            <?php
            $services = [
                ['icon' => 'utensils', 'name' => 'Room Service'],
                ['icon' => 'spa', 'name' => 'Spa & Wellness'],
                ['icon' => 'dumbbell', 'name' => 'Fitness Center'],
                ['icon' => 'swimmer', 'name' => 'Swimming Pool'],
            ];
            foreach ($services as $service) {
                echo "<li class='mb-2'><i class='fas fa-{$service['icon']}' style='width: 20px;'></i> {$service['name']}</li>";
            }
            ?>
        </ul>
    </div>
</div>

<div id="my-bookings" class="mb-4">
    <h2 class="mb-3">My Bookings</h2>
    <?php if ($bookings->num_rows > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($booking["room_type"]); ?> Room<br>
                                <small>Room <?php echo htmlspecialchars($booking["room_number"]); ?></small>
                            </td>
                            <td><?php echo date("M d, Y", strtotime($booking["check_in_date"])); ?></td>
                            <td><?php echo date("M d, Y", strtotime($booking["check_out_date"])); ?></td>
                            <td>$<?php echo number_format($booking["total_price"], 2); ?></td>
                            <td>
                                <?php
                                $status = strtolower($booking['STATUS']);
                                $status_labels = [
                                    'confirmed' => 'badge-success',
                                    'pending' => 'badge-warning',
                                    'cancelled' => 'badge-danger',
                                    'completed' => 'badge-primary'
                                ];
                                $class = $status_labels[$status] ?? 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $class; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (($booking["STATUS"] == 'pending' || $booking["STATUS"] == 'confirmed') &&
                                        strtotime($booking["check_in_date"]) > time()): ?>
                                    <a href="cancel_booking.php?id=<?php echo $booking["id"]; ?>" 
                                    class="btn btn-small btn-danger"
                                    onclick="return confirm('Are you sure you want to cancel this booking?');">
                                    Cancel
                                    </a>
                                <?php else: ?>
                                    <span style="color: gray;">No Action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No bookings found.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
