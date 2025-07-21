<?php $isDarkMode = isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark'; ?>
<footer class="mt-auto py-4 <?php echo $isDarkMode ? 'bg-dark text-white' : 'bg-dark text-white'; ?>">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-3 mb-md-0">
        <h5 class="mb-3 text-primary"><i class="fas fa-cubes me-2"></i> EFFY'S CONCEPT</h5>
        <p>A comprehensive solution for tracking and managing your organization's assets.</p>
        <p><small>Designed & Developed with <i class="fas fa-heart text-danger"></i> by Hosea Ephraim</small></p>
      </div>
      <div class="col-md-4 mb-3 mb-md-0">
        <h5 class="mb-3 text-primary">Quick Links</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="/fams/" class="text-decoration-none text-light"><i class="fas fa-home me-2 text-primary"></i>Home</a></li>
          <li class="mb-2"><a href="/fams/about.php" class="text-decoration-none text-light"><i class="fas fa-info-circle me-2 text-primary"></i>About</a></li>
          <li class="mb-2"><a href="/fams/contact.php" class="text-decoration-none text-light"><i class="fas fa-envelope me-2 text-primary"></i>Contact</a></li>
          <li class="mb-2"><a href="/fams/terms.php" class="text-decoration-none text-light"><i class="fas fa-gavel me-2 text-primary"></i>Terms of Use</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h5 class="mb-3 text-primary">Contact Me</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="mailto:hoseaephraim50@gmail.com" class="text-decoration-none text-light"><i class="fas fa-envelope me-2 text-primary"></i> hoseaephraim50@gmail.com</a></li>
          <li class="mb-2"><a href="tel:+2348087815454" class="text-decoration-none text-light"><i class="fas fa-phone me-2 text-primary"></i> +234 808 781 5454</a></li>
          <li class="mb-2"><a href="t.me/Effy_boi" class="text-decoration-none text-light" target="_blank"><i class="fab fa-telegram me-2 text-primary"></i> t.me/Effy_boi</a></li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
      <p class="mb-3 mb-md-0">&copy; <?php echo date('Y'); ?> EFFY'S CONCEPT. All rights reserved.</p>
      <div class="social-links">
        <a href="https://www.facebook.com/profile.php?id=100007187109436" class="text-decoration-none text-light me-3" target="_blank"><i class="fab fa-facebook-f fa-lg"></i></a>
        <a href="https://github.com/Ephraimraxy" class="text-decoration-none text-light me-3" target="_blank"><i class="fab fa-github fa-lg"></i></a>
        <a href="t.me/Effy_boi" class="text-decoration-none text-light" target="_blank"><i class="fab fa-telegram fa-lg"></i></a>
      </div>
    </div>
  </div>
</footer>

<!-- Load local Bootstrap JS first -->
<script src="/fams/assets/bootstrap/bootstrap.bundle.min.js"></script>
<!-- Fallback to CDN if local copy is unavailable -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/fams/assets/js/main.js"></script>
</body>
</html>
