<?php

declare(strict_types=1);

$pageTitle = 'Cookie Policy - My City Info';
$activePage = '';
$metaDescription = 'Read the My City Info Cookie Policy to learn about the cookies we use, why we use them, and how you can control them.';

ob_start();
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-4">

    <h1 class="h4 fw-bold mb-1">Cookie Policy</h1>
    <p class="text-muted small mb-4">Last updated: 30 March 2026</p>

    <p class="text-muted small mb-4" style="line-height:1.7;">
      This Cookie Policy explains what cookies are, how My City Info ("we", "us", "our") uses them on <strong>mycityinfo.com</strong>, and what choices you have. For information about how we handle your personal data more broadly, please read our <a href="/privacy-policy/" class="text-decoration-none fw-semibold">Privacy Policy</a>.
    </p>

    <!-- 1 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">What are cookies?</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      Cookies are small text files that are placed on your device (computer, phone, or tablet) when you visit a website. They are widely used to make websites work efficiently, to remember your preferences, and to provide information to website owners. Cookies cannot run programs or deliver viruses to your device.
    </p>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      Similar technologies such as web beacons, pixels, and local storage may also be used for some of the same purposes described below and are covered by this policy.
    </p>

    <!-- 2 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">Cookies we use</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">The table below describes the categories of cookies in use on this site:</p>

    <div class="table-responsive mb-4">
      <table class="table table-bordered table-sm align-middle bg-white" style="font-size:var(--mci-text-sm,0.8125rem);">
        <thead class="table-light">
          <tr>
            <th style="min-width:120px;">Type</th>
            <th>Purpose</th>
            <th style="min-width:160px;">Examples</th>
            <th style="min-width:110px;">Can opt out?</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><span class="badge text-bg-secondary">Essential</span></td>
            <td class="text-muted small">Required for the site to function. These enable session management, security (CSRF protection), and city preference storage. Without these, core features will not work.</td>
            <td class="text-muted small"><code>mci_session</code>, <code>mci_active_city</code>, CSRF tokens</td>
            <td class="text-muted small">No - disabling these will break the site.</td>
          </tr>
          <tr>
            <td><span class="badge text-bg-info text-dark">Analytics</span></td>
            <td class="text-muted small">Help us understand how visitors interact with the site - which pages are visited, how long users stay, and where they come from. Used to improve the Service.</td>
            <td class="text-muted small"><code>_ga</code>, <code>_gid</code>, <code>_gat</code> (Google Analytics)</td>
            <td class="text-muted small">Yes - see below.</td>
          </tr>
          <tr>
            <td><span class="badge text-bg-warning text-dark">Advertising</span></td>
            <td class="text-muted small">Used by Google AdSense to serve relevant advertisements. May use information about your browsing habits across websites to personalise ads shown to you.</td>
            <td class="text-muted small"><code>IDE</code>, <code>DSID</code>, <code>NID</code> (Google/DoubleClick)</td>
            <td class="text-muted small">Yes - see below.</td>
          </tr>
          <tr>
            <td><span class="badge text-bg-light border">Preferences</span></td>
            <td class="text-muted small">Remember user interface choices to improve your experience across visits, such as your preferred city or display settings.</td>
            <td class="text-muted small"><code>mci_active_city</code></td>
            <td class="text-muted small">Yes - clear via browser settings.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 3 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">Google Analytics</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      We use Google Analytics, a web analytics service provided by Google LLC. Google Analytics uses cookies to collect anonymised information about how users interact with our site - including pages viewed, session duration, device type, and approximate geographic location. This information is transmitted to and stored on Google's servers.
    </p>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      Google may use this data to evaluate your use of the site and compile reports for us. Google's use of the data is governed by the <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">Google Privacy Policy</a>.
    </p>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      To opt out of Google Analytics tracking across all websites, you can install the <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">Google Analytics Opt-out Browser Add-on</a>.
    </p>

    <!-- 4 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">Google AdSense</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      We use Google AdSense to display advertisements on our site. AdSense uses cookies and similar technologies to serve ads based on your prior visits to our site and other websites. Google's use of advertising cookies enables it and its partners to serve ads based on your visit to our site and/or other sites on the internet.
    </p>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      You can opt out of personalised advertising by visiting <a href="https://adssettings.google.com" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">Google Ad Settings</a>. You may also opt out via the <a href="https://www.networkadvertising.org/choices/" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">Network Advertising Initiative opt-out page</a>. Note that opting out means you will still see ads, but they will not be personalised to your interests.
    </p>

    <!-- 5 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">How to manage &amp; delete cookies</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      You can control and delete cookies through your browser settings. Please note that disabling essential cookies may affect the functionality of this site (for example, you may not be able to stay logged in or use location features).
    </p>
    <p class="text-muted small mb-2" style="line-height:1.7;">Instructions for managing cookies in popular browsers:</p>
    <ul class="text-muted small mb-3" style="line-height:1.7;">
      <li class="mb-1"><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">Google Chrome</a></li>
      <li class="mb-1"><a href="https://support.mozilla.org/en-US/kb/enhanced-tracking-protection-firefox-desktop" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">Mozilla Firefox</a></li>
      <li class="mb-1"><a href="https://support.apple.com/en-in/guide/safari/sfri11471/mac" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">Apple Safari</a></li>
      <li class="mb-1"><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">Microsoft Edge</a></li>
    </ul>
    <p class="text-muted small mb-4" style="line-height:1.7;">
      For more general information about cookies and how to manage them, visit <a href="https://www.allaboutcookies.org" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">allaboutcookies.org</a>.
    </p>

    <!-- 6 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">Updates to this policy</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      We may update this Cookie Policy from time to time to reflect changes in the cookies we use or for other operational, legal, or regulatory reasons. Please check this page periodically for updates. The "Last updated" date at the top of this page indicates when it was most recently revised.
    </p>

    <!-- 7 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">Contact us</h2>
    <div class="bg-light border rounded-3 p-3">
      <p class="text-muted small mb-1" style="line-height:1.7;">If you have any questions about our use of cookies, please contact us:</p>
      <p class="small mb-0"><strong>My City Info</strong><br>
      Email: <a href="mailto:hello@mycityinfo.com" class="text-decoration-none fw-semibold">hello@mycityinfo.com</a></p>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
