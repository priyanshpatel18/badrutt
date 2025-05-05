<?php include 'header.php'; ?>

<section class="hero">
    <div class="hero-content">
        <h1>Welcome to Badrutt's Palace</h1>
        <p>Experience luxury and comfort in our world-class accommodations</p>
        <div class="d-flex justify-between" style="max-width: 300px; margin: 0 auto;">
            <a href="booking.php" class="btn btn-primary">Book Now</a>
            <a href="#features" class="btn btn-secondary">Learn More</a>
        </div>
    </div>
</section>

<section class="features" id="features">
    <div class="container">
        <h2 class="section-title">Our Features</h2>
        <div class="features-container">
            <div class="feature fade-in">
                <i class="fas fa-bed"></i>
                <h3>Comfortable Rooms</h3>
                <p>Enjoy our spacious and elegantly designed rooms with premium amenities.</p>
            </div>
            <div class="feature fade-in">
                <i class="fas fa-utensils"></i>
                <h3>Fine Dining</h3>
                <p>Savor delicious cuisine prepared by our world-class chefs.</p>
            </div>
            <div class="feature fade-in">
                <i class="fas fa-swimming-pool"></i>
                <h3>Luxury Pool</h3>
                <p>Relax and unwind in our spectacular swimming pool with a stunning view.</p>
            </div>
            <div class="feature fade-in">
                <i class="fas fa-concierge-bell"></i>
                <h3>24/7 Service</h3>
                <p>Our dedicated staff is always ready to assist you with anything you need.</p>
            </div>
        </div>
    </div>
</section>

<section class="rooms-section">
    <div class="container">
        <h2 class="section-title">Our Rooms</h2>
        <div class="rooms-container">
            <?php
            $query = "SELECT rt.* FROM room_types rt ORDER BY rt.price_per_night";
            $result = $conn->query($query);
            
            if ($result->num_rows > 0) {
                while ($room_type = $result->fetch_assoc()) {
                    // Generate a placeholder image based on room type
                    $image_bg = '';
                    switch ($room_type['name']) {
                        case 'Standard':
                            $image_bg = 'background-color: #c1d3fe;';
                            $icon = 'fa-bed';
                            break;
                        case 'Deluxe':
                            $image_bg = 'background-color: #a5b4fc;';
                            $icon = 'fa-star';
                            break;
                        case 'Suite':
                            $image_bg = 'background-color: #818cf8;';
                            $icon = 'fa-gem';
                            break;
                        case 'Family':
                            $image_bg = 'background-color: #6366f1;';
                            $icon = 'fa-users';
                            break;
                        default:
                            $image_bg = 'background-color: #4f46e5;';
                            $icon = 'fa-hotel';
                    }
            ?>
                <div class="room-card fade-in">
                    <div class="room-image" style="<?php echo $image_bg; ?> display: flex; justify-content: center; align-items: center;">
                        <i class="fas <?php echo $icon; ?>" style="font-size: 5rem; color: white;"></i>
                    </div>
                    <div class="room-details">
                        <h3><?php echo $room_type['name']; ?> Room</h3>
                        <p><?php echo $room_type['description']; ?></p>
                        <p>Capacity: <strong><?php echo $room_type['capacity']; ?> People</strong></p>
                        <div class="room-price">$<?php echo number_format($room_type['price_per_night'], 2); ?> / night</div>
                        <a href="booking.php?type=<?php echo $room_type['id']; ?>" class="btn btn-block">Book Now</a>
                    </div>
                </div>
            <?php
                }
            } else {
                echo '<p class="text-center">No room types available at the moment.</p>';
            }
            ?>
        </div>
    </div>
</section>

<section class="testimonials">
    <div class="container">
        <h2 class="section-title">What Our Guests Say</h2>
        <div class="features-container">
            <div class="feature fade-in">
                <i class="fas fa-quote-left"></i>
                <p>"Amazing service and beautiful rooms. Will definitely come back!"</p>
                <h3>- John Smith</h3>
            </div>
            <div class="feature fade-in">
                <i class="fas fa-quote-left"></i>
                <p>"The staff was incredibly helpful and the amenities were top-notch."</p>
                <h3>- Sarah Johnson</h3>
            </div>
            <div class="feature fade-in">
                <i class="fas fa-quote-left"></i>
                <p>"Perfect location and outstanding comfort. A truly 5-star experience."</p>
                <h3>- Michael Davies</h3>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>