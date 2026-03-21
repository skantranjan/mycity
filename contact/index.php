<?php

declare(strict_types=1);

$pageTitle = 'Contact Us - My City Info';
$activePage = 'contact';
$metaDescription = 'Get in touch with the My City Info team. We respond to all enquiries within 1–2 business days.';

$extraJS = <<<'HTML'
<script>
(function () {
  var form = document.getElementById('mciContactForm');
  var successBox = document.getElementById('mciContactSuccess');
  if (!form) return;
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var name = form.querySelector('[name="name"]').value.trim();
    var email = form.querySelector('[name="email"]').value.trim();
    var msg = form.querySelector('[name="message"]').value.trim();
    if (!name || !email || !msg) return;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      form.querySelector('[name="email"]').focus();
      return;
    }
    form.style.display = 'none';
    if (successBox) successBox.hidden = false;
  });
})();
</script>
HTML;

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm bg-white h-100">
      <div class="card-body p-4">
        <h1 class="h5 fw-bold mb-3">Get in touch</h1>
        <p class="text-muted small mb-4">
          Have a question, a listing request, or want to partner with us? We'd love to hear from you.
        </p>

        <div class="d-flex flex-column gap-3">
          <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
            <div style="font-size:var(--mci-text-xl);" aria-hidden="true">📍</div>
            <div>
              <div class="fw-semibold small mb-1">Office</div>
              <div class="text-muted small">Office 01, Ingenious Impulse Business Center,<br>Indrapuri Road 01, Ashiyana, Patna, Bihar</div>
            </div>
          </div>
          <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
            <div style="font-size:var(--mci-text-xl);" aria-hidden="true">📧</div>
            <div>
              <div class="fw-semibold small mb-1">Email</div>
              <div class="text-muted small">hello@mycityinfo.com</div>
            </div>
          </div>
          <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
            <div style="font-size:var(--mci-text-xl);" aria-hidden="true">⏰</div>
            <div>
              <div class="fw-semibold small mb-1">Response time</div>
              <div class="text-muted small">We typically reply within 1–2 business days.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <h2 class="h5 fw-bold mb-3">Send a message</h2>

        <div id="mciContactSuccess" class="text-center py-4" hidden>
          <div style="font-size:2.5rem;" aria-hidden="true" class="mb-2">✅</div>
          <div class="fw-semibold mb-1">Message received!</div>
          <div class="text-muted small">We've got your message and will be in touch soon.</div>
        </div>

        <form id="mciContactForm" novalidate>
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label" for="contactName">Name <span class="text-danger" aria-hidden="true">*</span></label>
              <input class="form-control" type="text" id="contactName" name="name" placeholder="Your name" required autocomplete="name" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="contactEmail">Email <span class="text-danger" aria-hidden="true">*</span></label>
              <input class="form-control" type="email" id="contactEmail" name="email" placeholder="name@example.com" required autocomplete="email" />
            </div>
            <div class="col-12">
              <label class="form-label" for="contactMessage">Message <span class="text-danger" aria-hidden="true">*</span></label>
              <textarea class="form-control" id="contactMessage" name="message" rows="6" placeholder="Write your message…" required></textarea>
            </div>
          </div>
          <button class="btn btn-dark mt-3" type="submit">
            <i class="bi bi-send me-1" aria-hidden="true"></i>Send message
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
