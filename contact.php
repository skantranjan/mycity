<?php
$pageTitle = 'Contact Us - My City Info';
$activePage = 'contact';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm bg-white h-100">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Contact</div>
        <div class="text-muted small mb-3">
          Send us a message and we will get back to you (UI placeholder).
        </div>
        <div class="bg-light border rounded-3 p-3">
          <div class="text-muted small">Office</div>
          <div class="fw-semibold small">Office 01, Ingenious Impulse Business Center, Indrapuri Road 01, Ashiyana, Patna, Bihar</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <h1 class="h4 fw-bold mb-3">Send a message</h1>
        <form action="#" method="post">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" type="text" name="name" placeholder="Your name" required />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" placeholder="name@example.com" required />
            </div>
            <div class="col-12">
              <label class="form-label">Message</label>
              <textarea class="form-control" name="message" rows="6" placeholder="Write your message" required></textarea>
            </div>
          </div>
          <button class="btn btn-dark mt-3" type="submit">Submit</button>
          <div class="text-muted small mt-2">Backend handling will be added later.</div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>

