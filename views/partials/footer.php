<?php
// Shared footer.
?>
<footer class="site-footer mt-0">
  <div class="container px-3 px-sm-4 py-5 py-md-5">
    <div class="row g-4 align-items-start">
      <div class="col-12 col-md-4">
        <a href="/" class="d-inline-block mb-2" title="My City Info">
          <img
            src="https://www.mycityinfo.com/wp-content/uploads/2017/04/my-city-info-logo-t-3.png"
            alt="My City Info"
            class="site-logo site-logo--footer"
            loading="lazy"
            decoding="async"
          />
        </a>
        <div class="text-muted small mci-footer__tagline">
          Explore local business, services and places of your city.
        </div>
      </div>

      <div class="col-6 col-md-2">
        <div class="mci-footer__title">Company</div>
        <ul class="mci-footer__links" aria-label="Company links">
          <li><a class="mci-footer__link" href="/about/">About</a></li>
          <li><a class="mci-footer__link" href="/contact/">Contact</a></li>
        </ul>
      </div>

      <div class="col-6 col-md-3">
        <div class="mci-footer__title">Legal</div>
        <ul class="mci-footer__links" aria-label="Legal links">
          <li><a class="mci-footer__link" href="/privacy-policy/">Privacy Policy</a></li>
          <li><a class="mci-footer__link" href="/terms-of-use/">Terms of Use</a></li>
          <li><a class="mci-footer__link" href="/disclaimer/">Disclaimer</a></li>
          <li><a class="mci-footer__link" href="/cookies/">Cookies</a></li>
        </ul>
      </div>

      <div class="col-12 col-md-3">
        <div class="mci-footer__title">Need help?</div>
        <div class="text-muted small mci-footer__help">
          Have a question or need assistance? We are here to help.
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <a href="/contact/" class="btn btn-sm btn-outline-dark mci-footer__cta">
            <i class="bi bi-envelope-fill me-2" aria-hidden="true"></i>Contact us
          </a>
        </div>
      </div>
    </div>

    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mt-5 pt-4 border-top mci-footer__bottom">
      <div class="text-muted small">
        <?php $mciStartYear = 2020; $mciCurrentYear = (int) date('Y'); ?>
        Copyright &copy; <?= $mciStartYear ?><?= $mciCurrentYear > $mciStartYear ? ' - ' . $mciCurrentYear : '' ?> MyCityInfo.
      </div>
      <div class="text-muted small d-flex align-items-center gap-3">
        <span class="d-inline-flex align-items-center gap-2">
          <i class="bi bi-shield-check" aria-hidden="true"></i>Community-first
        </span>
        <span class="d-inline-flex align-items-center gap-2">
          <i class="bi bi-lightning-charge" aria-hidden="true"></i>Fast listings
        </span>
      </div>
    </div>
  </div>
</footer>

