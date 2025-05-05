</div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <a href="index.php" class="logo">
                        <i class="fas fa-hotel"></i> Badrutt's Palace
                    </a>
                    <p>Experience luxury and comfort</p>
                </div>
                <ul class="footer-links">
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Badrutt's Palace. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const hamburger = document.getElementById('hamburger');
        const navLinks = document.getElementById('nav-links');
        
        if (hamburger) {
            hamburger.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }

        // Animation for elements with fade-in class
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach(element => {
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = 1;
                            observer.unobserve(entry.target);
                        }
                    });
                });
                observer.observe(element);
            });
        });
    </script>
</body>
</html>