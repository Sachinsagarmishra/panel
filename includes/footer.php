</main>
</div>
<script>
    // Common App Scripts
    document.addEventListener('DOMContentLoaded', function () {
        // Fade-in animation
        const elements = document.querySelectorAll('.fade-in');
        elements.forEach((el, index) => {
            setTimeout(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Auto-hide alerts
        const alerts = document.querySelectorAll('.alert, .alert-success, .alert-error');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    });
</script>
</body>

</html>