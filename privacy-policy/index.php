<?php

declare(strict_types=1);

$pageTitle = 'Privacy Policy - My City Info';
$activePage = '';
$metaDescription = 'Read the My City Info Privacy Policy to understand how we collect, use, and protect your personal information in accordance with Indian law.';

ob_start();
?>

<div class="row g-4">

  <!-- Sticky section nav (desktop) -->
  <div class="col-12 col-lg-3 d-none d-lg-block">
    <div class="card border-0 shadow-sm bg-white" style="position:sticky;top:1.5rem;">
      <div class="card-body p-3">
        <div class="fw-semibold small mb-3" style="color:var(--mci-color-primary-deep);">On this page</div>
        <nav class="d-flex flex-column gap-1">
          <?php
          $sections = [
            'who-we-are'      => 'Who we are',
            'what-we-collect' => 'Information we collect',
            'how-we-use'      => 'How we use it',
            'cookies'         => 'Cookies &amp; tracking',
            'google-services' => 'Google services',
            'sharing'         => 'Sharing &amp; disclosure',
            'affiliates'      => 'Affiliate links',
            'retention'       => 'Data retention',
            'security'        => 'Security',
            'children'        => 'Children',
            'your-rights'     => 'Your rights',
            'changes'         => 'Policy changes',
            'contact'         => 'Contact us',
          ];
          foreach ($sections as $id => $label): ?>
          <a href="#<?= $id ?>" class="text-muted small text-decoration-none py-1 px-2 rounded-2"
             style="transition:background .15s;" onmouseover="this.style.background='var(--mci-color-primary-soft)'" onmouseout="this.style.background=''"><?= $label ?></a>
          <?php endforeach; ?>
        </nav>
      </div>
    </div>
  </div>

  <!-- Main content -->
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">

        <h1 class="h4 fw-bold mb-1">Privacy Policy</h1>
        <p class="text-muted small mb-4">Effective date: 1 April 2024 &nbsp;&middot;&nbsp; Last updated: 30 March 2026</p>

        <p class="text-muted small mb-4" style="line-height:1.7;">
          My City Info ("we", "us", or "our") operates the website <strong>mycityinfo.com</strong> (the "Service"). This Privacy Policy explains what information we collect, how we use it, and the choices you have. It is written in accordance with the <strong>Information Technology Act, 2000</strong> and the <strong>Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011</strong> of India.
        </p>
        <p class="text-muted small mb-4" style="line-height:1.7;">
          By using the Service, you agree to the collection and use of information as described in this policy. If you do not agree, please do not use our Service.
        </p>

        <!-- 1 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="who-we-are">1. Who we are</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          My City Info is a local business directory platform based in India. We are currently operated as an informal venture (not a registered legal entity). For all privacy-related matters, you may contact us at <a href="mailto:hello@mycityinfo.com" class="text-decoration-none fw-semibold">hello@mycityinfo.com</a>.
        </p>

        <!-- 2 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="what-we-collect">2. Information we collect</h2>
        <p class="text-muted small mb-2 fw-semibold" style="line-height:1.7;">a) Information you provide directly</p>
        <ul class="text-muted small mb-3" style="line-height:1.7;">
          <li class="mb-1"><strong>Contact &amp; lead forms</strong> - when you send an enquiry to a listed business, we collect your name, phone number, and email address.</li>
          <li class="mb-1"><strong>Business submissions</strong> - when you submit or claim a listing, we collect the business details and your contact information.</li>
          <li class="mb-1"><strong>Subscriber accounts</strong> - when you register as a subscriber (business owner), we collect your name and email address.</li>
          <li class="mb-1"><strong>Reviews &amp; ratings</strong> - content you submit, such as star ratings and review text.</li>
        </ul>
        <p class="text-muted small mb-2 fw-semibold" style="line-height:1.7;">b) Information collected automatically</p>
        <ul class="text-muted small mb-3" style="line-height:1.7;">
          <li class="mb-1"><strong>Log data</strong> - your IP address, browser type, pages visited, time and date of visit, and referring URL.</li>
          <li class="mb-1"><strong>Cookies &amp; similar technologies</strong> - session identifiers, city preferences, and analytics cookies. See our <a href="/cookies/" class="text-decoration-none fw-semibold">Cookie Policy</a> for full details.</li>
          <li class="mb-1"><strong>Device information</strong> - device type, operating system, and screen size (collected via Google Analytics).</li>
        </ul>
        <p class="text-muted small mb-3" style="line-height:1.7;"><span class="fw-semibold">c) User-submitted listing content</span> - business names, addresses, phone numbers, descriptions, photos, and other content submitted by users or business owners become part of the public directory.</p>

        <!-- 3 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="how-we-use">3. How we use your information</h2>
        <ul class="text-muted small mb-3" style="line-height:1.7;">
          <li class="mb-1">Operate and maintain the directory and deliver enquiries to the relevant business.</li>
          <li class="mb-1">Moderate and verify submitted listings before publication.</li>
          <li class="mb-1">Respond to your messages and support requests.</li>
          <li class="mb-1">Understand how users interact with the site (Google Analytics).</li>
          <li class="mb-1">Display contextually relevant advertisements (Google AdSense).</li>
          <li class="mb-1">Show maps and location data for listed businesses (Google Maps).</li>
          <li class="mb-1">Detect and prevent spam, fraud, and abuse.</li>
          <li class="mb-1">Comply with applicable legal obligations.</li>
        </ul>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          We do <strong>not</strong> sell, rent, or trade your personal information to third parties for their marketing purposes.
        </p>

        <!-- 4 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="cookies">4. Cookies &amp; tracking</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          We use cookies to maintain your session, remember your preferred city, and collect analytics data. For a full description of the cookies we use and how to opt out, please read our <a href="/cookies/" class="text-decoration-none fw-semibold">Cookie Policy</a>.
        </p>

        <!-- 5 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="google-services">5. Google services</h2>
        <p class="text-muted small mb-2" style="line-height:1.7;">We use the following Google services, each of which may collect data subject to Google's own privacy policy:</p>
        <ul class="text-muted small mb-3" style="line-height:1.7;">
          <li class="mb-1"><strong>Google Analytics</strong> - tracks page views, sessions, and user interactions to help us improve the Service. You can opt out using the <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">Google Analytics Opt-out Browser Add-on</a>.</li>
          <li class="mb-1"><strong>Google Maps</strong> - used to display the location of listed businesses. Google Maps operates under Google's own terms and privacy policy.</li>
          <li class="mb-1"><strong>Google AdSense</strong> - used to display advertisements. AdSense may use cookies to serve personalised ads based on your past visits to this and other websites. You can manage ad personalisation at <a href="https://adssettings.google.com" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">adssettings.google.com</a>.</li>
        </ul>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          Google's Privacy Policy is available at <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold">policies.google.com/privacy</a>.
        </p>

        <!-- 6 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="sharing">6. Sharing &amp; disclosure</h2>
        <p class="text-muted small mb-2" style="line-height:1.7;">We may share your information in the following limited circumstances:</p>
        <ul class="text-muted small mb-3" style="line-height:1.7;">
          <li class="mb-1"><strong>With the business you contact</strong> - when you submit an enquiry or lead form, your name, email, and message are shared with the business owner so they can respond to you.</li>
          <li class="mb-1"><strong>Service providers</strong> - trusted third-party vendors who help us operate the Service (e.g. hosting, analytics). They are obligated to keep your information secure and use it only for the purposes we specify.</li>
          <li class="mb-1"><strong>Legal compliance</strong> - if required by law, court order, or government authority, or to protect the rights and safety of My City Info, our users, or the public.</li>
          <li class="mb-1"><strong>Business transfer</strong> - in the event of a merger, acquisition, or sale of assets, your information may be transferred as part of that transaction with reasonable notice to you.</li>
        </ul>

        <!-- 7 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="affiliates">7. Affiliate &amp; promotional links</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          Some pages or listings on My City Info may contain affiliate links or sponsored content. If you click an affiliate link and make a purchase or take an action, we may receive a small commission or referral fee at no additional cost to you. Affiliate relationships do not influence the accuracy of our listing content or our editorial decisions. For full details, see our <a href="/disclaimer/" class="text-decoration-none fw-semibold">Disclaimer</a>.
        </p>

        <!-- 8 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="retention">8. Data retention</h2>
        <ul class="text-muted small mb-3" style="line-height:1.7;">
          <li class="mb-1"><strong>Session data</strong> - retained for the duration of your browser session; destroyed on logout or session expiry.</li>
          <li class="mb-1"><strong>Enquiry &amp; lead data</strong> (name, phone, email, message) - retained for as long as the associated business listing is active, or until you request deletion.</li>
          <li class="mb-1"><strong>Subscriber account data</strong> - retained for as long as your account is active. On deletion, personal data is removed within 30 days.</li>
          <li class="mb-1"><strong>Analytics data</strong> - retained by Google Analytics per their own retention settings (typically 14 months by default).</li>
          <li class="mb-1"><strong>Error logs</strong> - retained for up to 90 days for debugging and security purposes, then automatically purged.</li>
        </ul>

        <!-- 9 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="security">9. Security</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          We implement reasonable technical and organisational measures to protect your personal information against unauthorised access, loss, misuse, or alteration - including HTTPS encryption in transit, CSRF token protection on all forms, and access-controlled database systems. However, no method of internet transmission or storage is 100% secure. We cannot guarantee absolute security and encourage you to contact us immediately if you suspect any unauthorised access.
        </p>

        <!-- 10 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="children">10. Children</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          My City Info is a general-audience platform and is not directed at children under the age of 13. We do not knowingly collect personal information from children. If you believe a child has provided us with personal information, please contact us and we will delete it promptly.
        </p>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          Note: the directory may include listings from businesses that operate in industries intended for adults (such as liquor retailers or adult entertainment venues). Such listings carry individual age-advisory notices and are not directed at minors.
        </p>

        <!-- 11 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="your-rights">11. Your rights</h2>
        <p class="text-muted small mb-2" style="line-height:1.7;">Under applicable Indian law, you have the right to:</p>
        <ul class="text-muted small mb-3" style="line-height:1.7;">
          <li class="mb-1">Access the personal information we hold about you.</li>
          <li class="mb-1">Correct inaccurate or incomplete personal information.</li>
          <li class="mb-1">Request deletion of your personal data (subject to legal and operational requirements).</li>
          <li class="mb-1">Withdraw consent for processing where consent is the legal basis.</li>
        </ul>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          To exercise any of these rights, email us at <a href="mailto:hello@mycityinfo.com" class="text-decoration-none fw-semibold">hello@mycityinfo.com</a>. We will respond within 30 days.
        </p>

        <!-- 12 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="changes">12. Changes to this policy</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          We may update this Privacy Policy from time to time. When we do, we will revise the "Last updated" date at the top of this page. For material changes, we will make reasonable efforts to notify registered subscribers. Your continued use of the Service after changes are posted constitutes your acceptance of the updated policy.
        </p>

        <!-- 13 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="contact">13. Contact us</h2>
        <div class="bg-light border rounded-3 p-3">
          <p class="text-muted small mb-1" style="line-height:1.7;">If you have any questions, concerns, or requests regarding this Privacy Policy or how we handle your data, please contact us:</p>
          <p class="small mb-0"><strong>My City Info</strong><br>
          Email: <a href="mailto:hello@mycityinfo.com" class="text-decoration-none fw-semibold">hello@mycityinfo.com</a></p>
        </div>

      </div>
    </div>
  </div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
