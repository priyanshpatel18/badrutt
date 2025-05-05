<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage("login.php", "Please login to book a room", "error");
}

// Get room types
$sql = "SELECT * FROM room_types ORDER BY price_per_night";
$result = $conn->query($sql);
$room_types = [];
if ($result && $result->num_rows > 0) {
    while ($room_type = $result->fetch_assoc()) {
        $room_types[] = $room_type;
    }
}

// Default values
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d', strtotime('+1 day'));
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+2 days'));
$selected_room_type = isset($_GET['type']) ? intval($_GET['type']) : (count($room_types) > 0 ? $room_types[0]['id'] : 0);
$room_id = '';
$available_rooms = [];
$special_requests = '';
$nights = 1;
$total_price = 0;

// Process booking form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $check_in = clean_input($_POST["check_in"]);
    $check_out = clean_input($_POST["check_out"]);
    $room_id = intval($_POST["room_id"]);
    $special_requests = isset($_POST["special_requests"]) ? clean_input($_POST["special_requests"]) : '';
    $error = "";
    
    // Validate input
    if (empty($check_in) || empty($check_out) || empty($room_id)) {
        $error = "All required fields must be filled";
    } elseif (strtotime($check_in) < strtotime(date('Y-m-d'))) {
        $error = "Check-in date cannot be in the past";
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $error = "Check-out date must be after check-in date";
    } else {
        // Calculate number of nights and total price
        $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
        
        // Get room details and price
        $sql = "SELECT r.*, rt.price_per_night 
                FROM rooms r 
                JOIN room_types rt ON r.room_type_id = rt.id 
                WHERE r.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $room = $result->fetch_assoc();
            $total_price = $room["price_per_night"] * $nights;
            
            // Check if room is available for the selected dates
            $sql = "SELECT id FROM bookings 
                    WHERE room_id = ? 
                    AND ((check_in_date <= ? AND check_out_date > ?) 
                    OR (check_in_date < ? AND check_out_date >= ?))
                    AND status != 'cancelled'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $room_id, $check_out, $check_in, $check_out, $check_in);
            $stmt->execute();
            $conflict_result = $stmt->get_result();
            
            if ($conflict_result->num_rows > 0) {
                $error = "This room is not available for the selected dates";
            } else {
                // Create booking
                $user_id = $_SESSION["user_id"];
                $sql = "INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, special_requests, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'confirmed')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iissds", $user_id, $room_id, $check_in, $check_out, $total_price, $special_requests);
                
                if ($stmt->execute()) {
                    // Booking successful
                    redirectWithMessage("dashboard.php", "Booking successful! Your room has been reserved.");
                } else {
                    $error = "Error: " . $stmt->error;
                }
            }
        } else {
            $error = "Selected room not found";
        }
    }
} else {
    // Check if room type is selected and get available rooms
    if ($selected_room_type > 0) {
        // Find available rooms for this type
        $sql = "SELECT r.* FROM rooms r 
                WHERE r.room_type_id = ? 
                AND r.status = 'available' 
                AND r.id NOT IN (
                    SELECT b.room_id FROM bookings b 
                    WHERE ((b.check_in_date <= ? AND b.check_out_date > ?) 
                    OR (b.check_in_date < ? AND b.check_out_date >= ?))
                    AND b.status != 'cancelled'
                )";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $selected_room_type, $check_out, $check_in, $check_out, $check_in);
        $stmt->execute();
        $available_rooms_result = $stmt->get_result();
        
        while ($room = $available_rooms_result->fetch_assoc()) {
            $available_rooms[] = $room;
        }
        
        // Calculate total price based on default dates
        if (count($room_types) > 0) {
            foreach ($room_types as $room_type) {
                if ($room_type['id'] == $selected_room_type) {
                    $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
                    $total_price = $room_type['price_per_night'] * $nights;
                    break;
                }
            }
        }
    }
}

include 'header.php';
?>

<h1 class="page-title">Book a Room</h1>

<?php if (isset($error) && !empty($error)): ?>
    <div class="alert alert-error">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="booking-container">
    <div class="booking-details">
        <h2 class="mb-3">Booking Details</h2>
        
        <form id="booking-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="check-in">Check-in Date *</label>
                <input type="date" class="form-control" id="check-in" name="check_in" value="<?php echo $check_in; ?>" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="check-out">Check-out Date *</label>
                <input type="date" class="form-control" id="check-out" name="check_out" value="<?php echo $check_out; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Room Type *</label>
                <div class="room-type-select">
                    <?php foreach ($room_types as $room_type): ?>
                        <div class="room-type-option <?php echo $room_type['id'] == $selected_room_type ? 'selected' : ''; ?>" data-type-id="<?php echo $room_type['id']; ?>">
                            <h3><?php echo $room_type['name']; ?> Room</h3>
                            <p><?php echo $room_type['description']; ?></p>
                            <p>Max Capacity: <?php echo $room_type['capacity']; ?> People</p>
                            <div class="room-price">$<?php echo number_format($room_type['price_per_night'], 2); ?> / night</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="room-type-input" name="room_type_id" value="<?php echo $selected_room_type; ?>">
            </div>
            
            <div class="form-group">
                <label for="room-id">Available Rooms *</label>
                <select class="form-control" id="room-id" name="room_id" required>
                    <?php if (count($available_rooms) > 0): ?>
                        <?php foreach ($available_rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" <?php echo $room_id == $room['id'] ? 'selected' : ''; ?>>
                                Room <?php echo $room['room_number']; ?> (<?php echo $room['description']; ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">No available rooms for selected type/dates</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="special-requests">Special Requests</label>
                <textarea class="form-control" id="special-requests" name="special_requests" rows="3"><?php echo $special_requests; ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-block" <?php echo count($available_rooms) > 0 ? '' : 'disabled'; ?>>Complete Booking</button>
        </form>
    </div>
    
    <div class="booking-summary">
        <h2 class="mb-3">Booking Summary</h2>
        
        <div class="summary-item">
            <span>Check-in:</span>
            <span id="summary-check-in"><?php echo date("M d, Y", strtotime($check_in)); ?></span>
        </div>
        
        <div class="summary-item">
            <span>Check-out:</span>
            <span id="summary-check-out"><?php echo date("M d, Y", strtotime($check_out)); ?></span>
        </div>
        
        <div class="summary-item">
            <span>Nights:</span>
            <span id="summary-nights"><?php echo $nights; ?></span>
        </div>
        
        <div class="divider"></div>
        
        <div class="summary-item">
            <span>Room Type:</span>
            <span id="summary-room-type">
                <?php 
                $room_type_name = "";
                foreach ($room_types as $room_type) {
                    if ($room_type['id'] == $selected_room_type) {
                        $room_type_name = $room_type['name'];
                        break;
                    }
                }
                echo $room_type_name;
                ?>
            </span>
        </div>
        
        <div class="summary-item">
            <span>Price per Night:</span>
            <span id="summary-price-per-night">
                $<?php 
                $price_per_night = 0;
                foreach ($room_types as $room_type) {
                    if ($room_type['id'] == $selected_room_type) {
                        $price_per_night = $room_type['price_per_night'];
                        echo number_format($price_per_night, 2);
                        break;
                    }
                }
                ?>
            </span>
        </div>
        
        <div class="divider"></div>
        
        <div class="summary-item summary-total">
            <span>Total Price:</span>
            <span id="summary-total-price">$<?php echo number_format($total_price, 2); ?></span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update room type selection
    const roomTypeOptions = document.querySelectorAll('.room-type-option');
    const roomTypeInput = document.getElementById('room-type-input');
    const checkInInput = document.getElementById('check-in');
    const checkOutInput = document.getElementById('check-out');
    
    // Summary elements
    const summaryCheckIn = document.getElementById('summary-check-in');
    const summaryCheckOut = document.getElementById('summary-check-out');
    const summaryNights = document.getElementById('summary-nights');
    const summaryRoomType = document.getElementById('summary-room-type');
    const summaryPricePerNight = document.getElementById('summary-price-per-night');
    const summaryTotalPrice = document.getElementById('summary-total-price');
    
    // Room type price mapping
    const roomTypePrices = {};
    <?php foreach ($room_types as $room_type): ?>
    roomTypePrices[<?php echo $room_type['id']; ?>] = <?php echo $room_type['price_per_night']; ?>;
    <?php endforeach; ?>
    
    // Room type name mapping
    const roomTypeNames = {};
    <?php foreach ($room_types as $room_type): ?>
    roomTypeNames[<?php echo $room_type['id']; ?>] = "<?php echo $room_type['name']; ?>";
    <?php endforeach; ?>
    
    // Handle room type selection
    roomTypeOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            roomTypeOptions.forEach(opt => opt.classList.remove('selected'));
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Update hidden input value
            const typeId = this.getAttribute('data-type-id');
            roomTypeInput.value = typeId;
            
            // Update booking summary
            updateSummary();
            
            // Reload page with selected room type
            window.location.href = `${window.location.pathname}?type=${typeId}&check_in=${checkInInput.value}&check_out=${checkOutInput.value}`;
        });
    });
    
    // Handle date changes
    checkInInput.addEventListener('change', function() {
        // Ensure check-out date is after check-in date
        const checkInDate = new Date(this.value);
        const checkOutDate = new Date(checkOutInput.value);
        
        if (checkOutDate <= checkInDate) {
            // Set check-out date to the day after check-in
            const nextDay = new Date(checkInDate);
            nextDay.setDate(nextDay.getDate() + 1);
            checkOutInput.value = nextDay.toISOString().split('T')[0];
        }
        
        updateSummary();
        reloadWithParams();
    });
    
    checkOutInput.addEventListener('change', function() {
        // Ensure check-out date is after check-in date
        const checkInDate = new Date(checkInInput.value);
        const checkOutDate = new Date(this.value);
        
        if (checkOutDate <= checkInDate) {
            // Set check-in date to the day before check-out
            const prevDay = new Date(checkOutDate);
            prevDay.setDate(prevDay.getDate() - 1);
            checkInInput.value = prevDay.toISOString().split('T')[0];
        }
        
        updateSummary();
        reloadWithParams();
    });
    
    // Update booking summary based on selected options
    function updateSummary() {
        const checkInDate = new Date(checkInInput.value);
        const checkOutDate = new Date(checkOutInput.value);
        const nights = Math.round((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
        const roomTypeId = parseInt(roomTypeInput.value);
        const pricePerNight = roomTypePrices[roomTypeId] || 0;
        const totalPrice = pricePerNight * nights;
        
        // Update summary display
        summaryCheckIn.textContent = formatDate(checkInDate);
        summaryCheckOut.textContent = formatDate(checkOutDate);
        summaryNights.textContent = nights;
        summaryRoomType.textContent = roomTypeNames[roomTypeId] || '';
        summaryPricePerNight.textContent = '$' + pricePerNight.toFixed(2);
        summaryTotalPrice.textContent = '$' + totalPrice.toFixed(2);
    }
    
    // Format date for display
    function formatDate(date) {
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
    
    // Reload page with updated parameters
    function reloadWithParams() {
        const typeId = roomTypeInput.value;
        window.location.href = `${window.location.pathname}?type=${typeId}&check_in=${checkInInput.value}&check_out=${checkOutInput.value}`;
    }
    
    // Initialize summary
    updateSummary();
});
</script>

<?php include 'footer.php'; ?>