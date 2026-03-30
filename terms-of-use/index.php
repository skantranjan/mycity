<?php

declare(strict_types=1);

$pageTitle = 'Terms of Use - My City Info';
$activePage = '';
$metaDescription = 'Read the My City Info Terms of Use - the rules and guidelines that govern your use of our local business directory platform.';

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
            'acceptance'      => 'Acceptance of terms',
            'the-service'     => 'The service',
            'user-content'    => 'User-submitted content',
            'adult-content'   => '18+ content notice',
            'prohibited'      => 'Prohibited conduct',
            'accounts'        => 'Accounts',
            'ip'              => 'Intellectual property',
            'third-party'     => 'Third-party services',
            'advertising'     => 'Advertising',
            'warranties'      => 'Disclaimer of warranties',
            'liability'       => 'Limitation of liability',
            'governing-law'   => 'Governing law',
            'changes'         => 'Changes to terms',
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

        <h1 class="h4 fw-bold mb-1">Terms of Use</h1>
        <p class="text-muted small mb-4">Effective date: 1 April 2024 &nbsp;&middot;&nbsp; Last updated: 30 March 2026</p>

        <p class="text-muted small mb-4" style="line-height:1.7;">
          Please read these Terms of Use ("Terms") carefully before using My City Info ("we", "us", "our") at <strong>mycityinfo.com</strong> (the "Service"). By accessing or using the Service, you agree to be bound by these Terms and our <a href="/privacy-policy/" class="text-decoration-none fw-semibold">Privacy Policy</a>. If you do not agree, you must not use the Service.
        </p>

        <!-- 1 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="acceptance">1. Acceptance of terms</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          These Terms constitute a legally binding agreement between you and My City Info. By browsing the site, submitting a listing, creating an account, or using any feature of the Service, you confirm that you have read, understood, and agree to these Terms. If you are using the Service on behalf of an organisation, you represent that you have authority to bind that organisation to these Terms.
        </p>

        <!-- 2 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="the-service">2. The service</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          My City Info is an online local business directory that allows users to discover, submit, and review local businesses and their products and services across Indian cities. We provide this platform as a discovery tool only. We are not a party to any transaction, arrangement, or relationship between you and any listed business. We do not endorse, recommend, or guarantee any business, product, or service listed on the platform.
        </p>

        <!-- 3 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="user-content">3. User-submitted content</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          Users and business owners may submit listings, reviews, ratings, photos, and other content to the Service ("User Content"). By submitting User Content, you:
        </p>
        <ul class="text-muted small mb-3" style="line-height:1.7;">
          <li class="mb-1">Confirm that you own the content or have the right to submit it.</li>
          <li class="mb-1">Grant My City Info a non-exclusive, worldwide, royalty-free licence to display, reproduce, and distribute the content as part of the Service.</li>
          <li class="mb-1">Confirm that the content is accurate, not misleading, and does not violate any law or third-party rights.</li>
        </ul>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          We reserve the right to moderate, edit, or remove any User Content at our sole discretion, without notice, if we believe it violates these Terms or is otherwise objectionable. Anonymous submissions may require additional moderation before going live.
        </p>

        <!-- 4 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="adult-content">4. 18+ content notice</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          My City Info is a general-audience directory. However, the platform may include listings from businesses that operate in industries intended for adults - such as liquor retailers, tobacco shops, or adult entertainment venues. Such listings are individually marked with an age-advisory notice. By accessing any listing marked as age-restricted, you confirm that you are of the legal age to view such content in your jurisdiction. My City Info is not responsible for the nature, legality, or conduct of any listed business.
        </p>

        <!-- 5 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="prohibited">5. Prohibited conduct</h2>
        <p class="text-muted small mb-2" style="line-height:1.7;">You agree not to use the Service to:</p>
        <ul class="text-muted small mb-3" style="line-height:1.7;">
          <li class="mb-1">Submit false, misleading, defamatory, or fraudulent listing information.</li>
          <li class="mb-1">Impersonate any person, business, or entity.</li>
          <li class="mb-1">Submit spam, duplicate listings, or listings that serve no genuine directory purpose.</li>
          <li class="mb-1">Scrape, crawl, or harvest content from the Service without our written permission.</li>
          <li class="mb-1">Use automated tools or bots to access or interact with the Service.</li>
          <li class="mb-1">Interfere with or disrupt the integrity or performance of the Service or its servers.</li>
          <li class="mb-1">Upload or transmit viruses, malware, or any harmful code.</li>
          <li class="mb-1">Post content that is illegal, obscene, hateful, or violates any applicable law.</li>
          <li class="mb-1">Use the Service in any way that violates the Information Technology Act, 2000, or any other applicable Indian law.</li>
        </ul>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          We reserve the right to remove content, suspend accounts, and take legal action against users who violate these prohibitions.
        </p>

        <!-- 6 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="accounts">6. Accounts &amp; subscribers</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          Business owners may register as subscribers to claim and manage their listings. You are responsible for maintaining the confidentiality of your login credentials and for all activity that occurs under your account. You must notify us immediately at <a href="mailto:hello@mycityinfo.com" class="text-decoration-none fw-semibold">hello@mycityinfo.com</a> if you suspect unauthorised access to your account.
        </p>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          We reserve the right to suspend or terminate any account that violates these Terms, engages in fraudulent activity, or is inactive for an extended period, without notice.
        </p>

        <!-- 7 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="ip">7. Intellectual property</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          The My City Info name, logo, design, and all platform content created by us (excluding User Content) are our intellectual property and may not be used, copied, or reproduced without our prior written consent. The look and feel of the Service, including its layout, graphics, and navigation structure, is protected by copyright.
        </p>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          User Content remains the property of its original owner. By submitting User Content, you grant us the licence described in Section 3 above, but you retain all other ownership rights.
        </p>

        <!-- 8 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="third-party">8. Third-party links &amp; services</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          The Service integrates or links to third-party services including Google Analytics, Google Maps, Google AdSense, and general affiliate or promotional links. These services are governed by their own terms and privacy policies. My City Info is not responsible for the content, practices, or privacy policies of any third-party website or service. Links to external sites do not constitute an endorsement.
        </p>

        <!-- 9 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="advertising">9. Advertising</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          We display advertisements through Google AdSense and may include affiliate or promotional links within the Service. Advertisements are clearly separated from organic listing content. We may earn revenue when users click on ads or affiliate links. Advertising relationships do not influence which listings appear in the directory or how they are ranked.
        </p>

        <!-- 10 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="warranties">10. Disclaimer of warranties</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          The Service is provided on an "as is" and "as available" basis, without warranties of any kind, either express or implied. We do not warrant that the Service will be uninterrupted, error-free, or free of viruses or other harmful components. We make no warranty regarding the accuracy, completeness, or reliability of any listing, review, or other content on the Service.
        </p>

        <!-- 11 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="liability">11. Limitation of liability</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          To the maximum extent permitted by applicable Indian law, My City Info and its operators shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising out of or in connection with your use of - or inability to use - the Service, including but not limited to loss of data, business, goodwill, or profits, even if we have been advised of the possibility of such damages.
        </p>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          Our total liability to you for any claim arising from these Terms or your use of the Service shall not exceed the amount you paid us (if any) in the 12 months prior to the claim.
        </p>

        <!-- 12 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="governing-law">12. Governing law &amp; disputes</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          These Terms are governed by and construed in accordance with the laws of India. Any disputes arising from or in connection with these Terms shall be subject to the exclusive jurisdiction of the courts of India. We encourage you to contact us first at <a href="mailto:hello@mycityinfo.com" class="text-decoration-none fw-semibold">hello@mycityinfo.com</a> to resolve any dispute informally before pursuing legal action.
        </p>

        <!-- 13 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="changes">13. Changes to these terms</h2>
        <p class="text-muted small mb-3" style="line-height:1.7;">
          We may revise these Terms from time to time. When we do, we will update the "Last updated" date at the top of this page. Continued use of the Service after changes are posted constitutes your acceptance of the revised Terms. We encourage you to review this page periodically.
        </p>

        <!-- 14 -->
        <h2 class="h6 fw-semibold mt-4 mb-2" id="contact">14. Contact us</h2>
        <div class="bg-light border rounded-3 p-3">
          <p class="text-muted small mb-1" style="line-height:1.7;">If you have any questions about these Terms, please contact us:</p>
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
