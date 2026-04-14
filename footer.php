<footer class="footer mt-5" style="background-color:#1d3557; color:#f1faee; position: relative;">
  <div class="container py-3">
    <div class="row align-items-start">
      <!-- SuperMart Info -->
      <div class="col-md-4 col-sm-12 mb-3 mb-md-0">
        <h5>SuperMart</h5>
        <p>Your everyday supermarket. Fresh food, home essentials & more.</p>
      </div>

      <!-- Quick Links (centered column, left-aligned text) -->
      <div class="col-md-4 col-sm-12 mb-3 mb-md-0 mx-auto">
        <h5>Quick Links</h5>
        <ul class="list-unstyled mb-0">
          <li><a href="/OnlineSupermarketDB/index.php" class="text-decoration-none text-light">Home</a></li>
          <li><a href="/OnlineSupermarketDB/cart.php" class="text-decoration-none text-light">Your Cart</a></li>
          <li><a href="https://support.example.com" target="_blank" class="text-decoration-none text-light">Support</a></li>
        </ul>
      </div>

      <!-- Contact Info -->
      <div class="col-md-4 col-sm-12">
        <h5>Contact</h5>
        <p>Email: support@supermart.local</p>
        <p>Phone: +880 123 456 789</p>
      </div>
    </div>

    <hr style="border-color: rgba(255,255,255,0.1); margin: 0.5rem 0;">

    <div class="text-center small">&copy; <?= date('Y') ?> SuperMart</div>
  </div>
</footer>

<!-- Sticky Footer Script -->
<style>
  html, body {
    height: 100%;
  }
  body {
    display: flex;
    flex-direction: column;
  }
  /* Wrap content area */
  .content {
    flex: 1 0 auto; /* grow and shrink properly */
  }
  .footer a:hover {
    color: #a8dadc;
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
