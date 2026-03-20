<?php
/** @var array<int, string> $categories */
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$times = [];
for ($m = 0; $m < 24 * 60; $m += 30) {
    $h = intdiv($m, 60);
    $mm = $m % 60;
    $times[] = sprintf('%02d:%02d', $h, $mm);
}
?>

<div class="mci-submit-wrap" id="mciSubmitWrap">
  <div class="row g-4 g-lg-5 align-items-start">
    <div class="col-12">

      <div class="mci-submit-hero mb-4">
        <p class="mci-submit-hero__kicker">Subscriber</p>
        <h1 class="mci-submit-hero__title">List your business</h1>
        <p class="mci-submit-hero__lead">Seven guided steps — save anytime; preview before you publish.</p>
      </div>

      <div class="mci-steps-nav mci-steps-nav--7 mb-4" aria-label="Form progress">
        <?php
        $stepDefs = [
            ['icon' => 'bi-building', 'label' => 'Business'],
            ['icon' => 'bi-stars', 'label' => 'Services'],
            ['icon' => 'bi-geo-alt', 'label' => 'Location'],
            ['icon' => 'bi-clock', 'label' => 'Hours'],
            ['icon' => 'bi-images', 'label' => 'Photos'],
            ['icon' => 'bi-question-circle', 'label' => 'FAQs'],
            ['icon' => 'bi-send', 'label' => 'Publish'],
        ];
        foreach ($stepDefs as $i => $s):
            $n = $i + 1;
            ?>
          <button type="button" class="mci-step-btn <?= $n === 1 ? 'is-active' : '' ?>" data-step="<?= $n ?>"
            aria-current="<?= $n === 1 ? 'step' : 'false' ?>"
            aria-label="Step <?= $n ?>: <?= htmlspecialchars($s['label']) ?>">
            <span class="mci-step-btn__dot">
              <i class="bi <?= htmlspecialchars($s['icon']) ?>"></i>
              <span class="mci-step-btn__check"><i class="bi bi-check-lg"></i></span>
            </span>
            <span class="mci-step-btn__label"><?= htmlspecialchars($s['label']) ?></span>
          </button>
            <?php if ($n < count($stepDefs)): ?>
            <span class="mci-step-connector" aria-hidden="true"></span>
            <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <div class="mci-progress-bar mb-4" role="progressbar" aria-valuenow="14" aria-valuemin="0" aria-valuemax="100">
        <div class="mci-progress-bar__fill" id="stepProgressFill" style="width:14%"></div>
      </div>

      <form action="#" method="post" enctype="multipart/form-data" id="mciSubmitForm" novalidate>
        <input type="hidden" name="form_origin" value="ui_subscriber_listing" />
        <input type="hidden" name="posting_type" value="registered" />

        <!-- STEP 1 — Business + tags -->
        <div class="mci-step is-active" data-step="1">
          <div class="mci-step-header">
            <div class="mci-step-header__icon" aria-hidden="true"><i class="bi bi-building"></i></div>
            <div>
              <div class="mci-step-header__title">Business details</div>
              <div class="mci-step-header__desc">Name, category, story — and tags so locals can find you.</div>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label mci-field-label" for="listing_title">Business name <span class="text-danger">*</span></label>
              <input class="form-control form-control-lg" id="listing_title" type="text" name="listing_title" placeholder="e.g. City Auto Care" required autocomplete="organization" />
            </div>
            <div class="col-12">
              <label class="form-label mci-field-label" for="tagline">Tagline</label>
              <input class="form-control" id="tagline" type="text" name="tagline" placeholder="One punchy line customers will remember" />
            </div>
            <div class="col-12 col-sm-7">
              <label class="form-label mci-field-label" for="category">Category <span class="text-danger">*</span></label>
              <select class="form-select" id="category" name="category" required>
                <option value="">Choose a category…</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label mci-field-label" for="description">Description <span class="text-danger">*</span></label>
              <textarea class="form-control" id="description" name="description" rows="4" maxlength="1200" placeholder="Tell customers what makes your business worth visiting…" required></textarea>
              <div class="d-flex justify-content-end mt-1"><span class="form-text" id="descCount">0 / 1200</span></div>
            </div>
            <div class="col-12">
              <label class="form-label mci-field-label" for="tags">Tags</label>
              <input class="form-control" id="tags" type="text" name="tags" placeholder="vegan, delivery, 24/7, parking, family-friendly…" />
              <div class="form-text">Comma-separated — helps search and filters on your listing.</div>
            </div>
          </div>
        </div>

        <!-- STEP 2 — Products & services + pricing -->
        <div class="mci-step" data-step="2">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--2" aria-hidden="true"><i class="bi bi-stars"></i></div>
            <div>
              <div class="mci-step-header__title">Products &amp; services</div>
              <div class="mci-step-header__desc">Highlight what you sell or do, and optional price expectations.</div>
            </div>
          </div>
          <div class="mci-submit-label mb-1">Services &amp; products <span class="fw-normal text-muted text-lowercase">(optional)</span></div>
          <p class="text-muted small mb-3">Add each offer with a short description.</p>
          <div id="serviceItems" class="mb-2">
            <div class="mci-faq-item" data-svc-index="0">
              <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
              <div class="flex-grow-1">
                <input class="form-control form-control-sm mb-2" type="text" name="service_name[]" placeholder="Name, e.g. Interior Painting" aria-label="Service name" />
                <textarea class="form-control form-control-sm" name="service_desc[]" rows="2" placeholder="Short description — what's included, turnaround…" aria-label="Service description"></textarea>
              </div>
              <button type="button" class="btn btn-sm removeSvcBtn mci-faq-item__remove" aria-label="Remove"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
          <button type="button" id="addServiceBtn" class="btn btn-sm btn-outline-dark mb-4">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add service
          </button>
          <template id="serviceItemTemplate">
            <div class="mci-faq-item" data-svc-index="__INDEX__">
              <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
              <div class="flex-grow-1">
                <input class="form-control form-control-sm mb-2" type="text" name="service_name[]" placeholder="Name, e.g. Interior Painting" aria-label="Service name" />
                <textarea class="form-control form-control-sm" name="service_desc[]" rows="2" placeholder="Short description…" aria-label="Service description"></textarea>
              </div>
              <button type="button" class="btn btn-sm removeSvcBtn mci-faq-item__remove" aria-label="Remove"><i class="bi bi-x-lg"></i></button>
            </div>
          </template>

          <div class="mci-submit-label">Pricing <span class="fw-normal text-muted text-lowercase">(optional)</span></div>
          <div class="row g-3">
            <div class="col-12 col-md-4">
              <label class="form-label mci-field-label" for="price_range">Price range</label>
              <select class="form-select" id="price_range" name="price_range">
                <option value="">Not specified</option>
                <option value="free">$ Inexpensive</option>
                <option value="moderate">$$ Moderate</option>
                <option value="pricey">$$$ Pricey</option>
                <option value="ultra">$$$$ Ultra high</option>
              </select>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label mci-field-label" for="price_from">Min price</label>
              <input class="form-control" id="price_from" type="text" name="price_from" placeholder="e.g. 10" />
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label mci-field-label" for="price_to">Max price</label>
              <input class="form-control" id="price_to" type="text" name="price_to" placeholder="e.g. 200" />
            </div>
          </div>
        </div>

        <!-- STEP 3 — Location & contact -->
        <div class="mci-step" data-step="3">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--3" aria-hidden="true"><i class="bi bi-geo-alt"></i></div>
            <div>
              <div class="mci-step-header__title">Location &amp; contact</div>
              <div class="mci-step-header__desc">Address, channels to reach you, and social profiles.</div>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label mci-field-label" for="full_address">Full address</label>
              <input class="form-control" id="full_address" type="text" name="full_address" placeholder="Street, area, postal code" />
            </div>
            <div class="col-12 col-md-5">
              <label class="form-label mci-field-label" for="city">City <span class="text-danger">*</span></label>
              <input class="form-control" id="city" type="text" name="city" placeholder="City name" required />
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label mci-field-label" for="latitude">Latitude</label>
              <input class="form-control" id="latitude" type="text" name="latitude" placeholder="e.g. 28.6139" />
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label mci-field-label" for="longitude">Longitude</label>
              <input class="form-control" id="longitude" type="text" name="longitude" placeholder="e.g. 77.2090" />
            </div>
            <div class="col-12">
              <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#mapModal">
                <i class="bi bi-pin-map me-1" aria-hidden="true"></i>Set map pin manually
              </button>
            </div>
            <div class="col-12"><hr class="mci-inner-divider" /></div>
            <div class="col-12 col-md-4">
              <label class="form-label mci-field-label" for="phone">Phone</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                <input class="form-control" id="phone" type="tel" name="phone" placeholder="+1 555 000 0000" />
              </div>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label mci-field-label" for="whatsapp">WhatsApp</label>
              <div class="input-group">
                <span class="input-group-text text-success"><i class="bi bi-whatsapp" aria-hidden="true"></i></span>
                <input class="form-control" id="whatsapp" type="tel" name="whatsapp" placeholder="+1 555 000 0000" />
              </div>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label mci-field-label" for="email_contact">Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                <input class="form-control" id="email_contact" type="email" name="email_contact" placeholder="hello@yourbusiness.com" />
              </div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label mci-field-label" for="website">Website</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                <input class="form-control" id="website" type="url" name="website" placeholder="https://…" />
              </div>
            </div>
            <div class="col-12"><hr class="mci-inner-divider" /></div>
            <div class="col-12">
              <div class="mci-submit-label mb-1">Social media <span class="fw-normal text-muted text-lowercase">(optional)</span></div>
              <p class="text-muted small mb-0">Pages or @handles — all optional.</p>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_facebook">Facebook</label>
              <div class="input-group">
                <span class="input-group-text mci-social-icon--fb"><i class="bi bi-facebook" aria-hidden="true"></i></span>
                <input class="form-control" id="social_facebook" type="text" name="social_facebook" placeholder="facebook.com/yourpage" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_instagram">Instagram</label>
              <div class="input-group">
                <span class="input-group-text mci-social-icon--ig"><i class="bi bi-instagram" aria-hidden="true"></i></span>
                <input class="form-control" id="social_instagram" type="text" name="social_instagram" placeholder="@yourhandle" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_x">X (Twitter)</label>
              <div class="input-group">
                <span class="input-group-text mci-social-icon--x"><i class="bi bi-twitter-x" aria-hidden="true"></i></span>
                <input class="form-control" id="social_x" type="text" name="social_x" placeholder="@yourhandle" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_youtube">YouTube</label>
              <div class="input-group">
                <span class="input-group-text mci-social-icon--yt"><i class="bi bi-youtube" aria-hidden="true"></i></span>
                <input class="form-control" id="social_youtube" type="text" name="social_youtube" placeholder="youtube.com/@channel" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_linkedin">LinkedIn</label>
              <div class="input-group">
                <span class="input-group-text mci-social-icon--li"><i class="bi bi-linkedin" aria-hidden="true"></i></span>
                <input class="form-control" id="social_linkedin" type="text" name="social_linkedin" placeholder="linkedin.com/company/…" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_tiktok">TikTok</label>
              <div class="input-group">
                <span class="input-group-text mci-social-icon--tt"><i class="bi bi-tiktok" aria-hidden="true"></i></span>
                <input class="form-control" id="social_tiktok" type="text" name="social_tiktok" placeholder="@yourhandle" />
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 4 — Hours only -->
        <div class="mci-step" data-step="4">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--4" aria-hidden="true"><i class="bi bi-clock"></i></div>
            <div>
              <div class="mci-step-header__title">Business hours</div>
              <div class="mci-step-header__desc">When can customers visit or call?</div>
            </div>
          </div>
          <div class="mci-submit-label">Weekly schedule</div>
          <div class="mci-hours-compact mb-2">
            <?php foreach ($days as $day):
                $key = strtolower($day);
                ?>
            <div class="mci-hours-row">
              <div class="mci-hours-row__day">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" name="hours[open][<?= $key ?>]" id="day_<?= $key ?>" value="1" />
                  <label class="form-check-label fw-semibold" for="day_<?= $key ?>"><?= htmlspecialchars($day) ?></label>
                </div>
              </div>
              <div class="mci-hours-row__slots">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <select class="form-select form-select-sm mci-hours-select" name="hours[slot1_start][<?= $key ?>]"><option value="">Open</option><?php foreach ($times as $t): ?><option><?= $t ?></option><?php endforeach; ?></select>
                  <span class="text-muted small">–</span>
                  <select class="form-select form-select-sm mci-hours-select" name="hours[slot1_end][<?= $key ?>]"><option value="">Close</option><?php foreach ($times as $t): ?><option><?= $t ?></option><?php endforeach; ?></select>
                  <span class="text-muted small d-none d-sm-inline">2nd:</span>
                  <select class="form-select form-select-sm mci-hours-select" name="hours[slot2_start][<?= $key ?>]"><option value="">—</option><?php foreach ($times as $t): ?><option><?= $t ?></option><?php endforeach; ?></select>
                  <span class="text-muted small">–</span>
                  <select class="form-select form-select-sm mci-hours-select" name="hours[slot2_end][<?= $key ?>]"><option value="">—</option><?php foreach ($times as $t): ?><option><?= $t ?></option><?php endforeach; ?></select>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- STEP 5 — Photos -->
        <div class="mci-step" data-step="5">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--5" aria-hidden="true"><i class="bi bi-images"></i></div>
            <div>
              <div class="mci-step-header__title">Photos &amp; media</div>
              <div class="mci-step-header__desc">Logo, profile, banner, gallery, and an optional video link.</div>
            </div>
          </div>
          <div class="mci-submit-label">Logo, profile &amp; banner</div>
          <p class="text-muted small mb-3">Crop each image to fit. All optional.</p>
          <div class="mci-img-upload-grid">
            <div class="mci-img-upload-tile" data-type="logo" data-aspect="1">
              <div class="mci-img-upload-tile__preview">
                <div class="mci-img-upload-tile__placeholder"><i class="bi bi-shop" aria-hidden="true"></i><span>No logo</span></div>
              </div>
              <div class="mci-img-upload-tile__footer">
                <div class="mci-img-upload-tile__info"><strong>Logo</strong><span>1:1</span></div>
                <button type="button" class="btn btn-sm btn-outline-dark mci-img-upload-btn"><i class="bi bi-upload me-1"></i>Upload</button>
              </div>
              <input type="file" class="d-none mci-img-file-input" accept="image/*" />
              <input type="hidden" name="img_logo" />
            </div>
            <div class="mci-img-upload-tile mci-img-upload-tile--circle" data-type="profile" data-aspect="1">
              <div class="mci-img-upload-tile__preview">
                <div class="mci-img-upload-tile__placeholder"><i class="bi bi-person-circle" aria-hidden="true"></i><span>No photo</span></div>
              </div>
              <div class="mci-img-upload-tile__footer">
                <div class="mci-img-upload-tile__info"><strong>Profile</strong><span>1:1</span></div>
                <button type="button" class="btn btn-sm btn-outline-dark mci-img-upload-btn"><i class="bi bi-upload me-1"></i>Upload</button>
              </div>
              <input type="file" class="d-none mci-img-file-input" accept="image/*" />
              <input type="hidden" name="img_profile" />
            </div>
          </div>
          <div class="mci-img-upload-tile mci-img-upload-tile--banner mt-3" data-type="banner" data-aspect="3.2">
            <div class="mci-img-upload-tile__preview mci-img-upload-tile__preview--banner">
              <div class="mci-img-upload-tile__placeholder"><i class="bi bi-panorama" aria-hidden="true"></i><span>No banner</span></div>
            </div>
            <div class="mci-img-upload-tile__footer">
              <div class="mci-img-upload-tile__info"><strong>Banner</strong><span>16:5</span></div>
              <button type="button" class="btn btn-sm btn-outline-dark mci-img-upload-btn"><i class="bi bi-upload me-1"></i>Upload</button>
            </div>
            <input type="file" class="d-none mci-img-file-input" accept="image/*" />
            <input type="hidden" name="img_banner" />
          </div>
          <hr class="mci-inner-divider mt-4 mb-3" />
          <div class="mci-submit-label">Gallery photos</div>
          <div id="dropFiles" class="mci-drop-zone" role="button" tabindex="0" aria-label="Upload gallery images">
            <div class="mci-drop-zone__inner">
              <div class="mci-drop-zone__icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></div>
              <div class="mci-drop-zone__title">Drop photos here</div>
              <div class="mci-drop-zone__sub">or click to browse</div>
              <button type="button" class="btn btn-sm btn-dark mt-2 mci-drop-zone__btn">Choose files</button>
              <input id="fileInputImages" class="d-none" type="file" name="images[]" accept="image/*" multiple />
            </div>
          </div>
          <div class="mci-photo-preview d-flex flex-wrap gap-2 mt-3" id="imagePreview"></div>
          <div class="row g-3 mt-1 mb-2">
            <div class="col-12 col-md-6">
              <label class="form-label mci-field-label" for="video_url">Video URL</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-play-circle" aria-hidden="true"></i></span>
                <input class="form-control" id="video_url" type="url" name="video_url" placeholder="YouTube or Vimeo link" />
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 6 — FAQs -->
        <div class="mci-step" data-step="6">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--6" aria-hidden="true"><i class="bi bi-question-circle"></i></div>
            <div>
              <div class="mci-step-header__title">FAQs</div>
              <div class="mci-step-header__desc">Answer common questions before customers ask.</div>
            </div>
          </div>
          <p class="text-muted small mb-3">Optional — add as many as you need.</p>
          <div id="faqItems" class="mb-2">
            <div class="faq-item mci-faq-item" data-faq-index="0">
              <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
              <div class="flex-grow-1">
                <input class="form-control form-control-sm mb-2" type="text" name="faq_question[]" placeholder="Question…" aria-label="FAQ question" />
                <textarea class="form-control form-control-sm" name="faq_answer[]" rows="2" placeholder="Your answer…" aria-label="FAQ answer"></textarea>
              </div>
              <button type="button" class="btn btn-sm removeFaqBtn mci-faq-item__remove" aria-label="Remove FAQ"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
          <button type="button" id="addFaqBtn" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add FAQ
          </button>
          <template id="faqItemTemplate">
            <div class="faq-item mci-faq-item" data-faq-index="__INDEX__">
              <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
              <div class="flex-grow-1">
                <input class="form-control form-control-sm mb-2" type="text" name="faq_question[]" placeholder="Question…" aria-label="FAQ question" />
                <textarea class="form-control form-control-sm" name="faq_answer[]" rows="2" placeholder="Your answer…" aria-label="FAQ answer"></textarea>
              </div>
              <button type="button" class="btn btn-sm removeFaqBtn mci-faq-item__remove" aria-label="Remove FAQ"><i class="bi bi-x-lg"></i></button>
            </div>
          </template>
        </div>

        <!-- STEP 7 — Review & publish -->
        <div class="mci-step" data-step="7">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--7" aria-hidden="true"><i class="bi bi-send"></i></div>
            <div>
              <div class="mci-step-header__title">Review &amp; publish</div>
              <div class="mci-step-header__desc">You’re signed in — submit when everything looks right.</div>
            </div>
          </div>
          <div class="alert alert-light border mb-4 py-3">
            <div class="fw-semibold small mb-1">
              <i class="bi bi-person-check-fill me-1 text-primary" aria-hidden="true"></i>
              Preview &amp; publish
            </div>
            <p class="text-muted small mb-0">
              You’re already logged in. Confirm the preview below and submit your business for listing review.
            </p>
          </div>

          <div class="mci-submit-preview mb-4">
            <div class="mci-preview-label">
              <i class="bi bi-eye me-1" aria-hidden="true"></i>Preview
            </div>

            <div class="mci-preview-photo" id="step7PreviewPhoto">
              <div class="mci-preview-photo__placeholder" id="previewPhotoPlaceholder">
                <i class="bi bi-image" aria-hidden="true"></i><span>Photos appear here</span>
              </div>
              <img id="previewPhotoImg" src="" alt="" class="d-none" />
            </div>

            <div class="mci-preview-body">
              <div class="mci-preview-category" id="previewCategory">Category</div>
              <div class="mci-preview-name" id="previewName">Your business name</div>
              <div class="mci-preview-tagline" id="previewTagline">Your tagline will show here</div>
              <div class="mci-preview-desc" id="previewDesc">Your description will appear here.</div>

              <div class="mci-preview-divider"></div>

              <div class="mci-preview-meta">
                <div class="mci-preview-meta-row" id="previewCity"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>—</span></div>
                <div class="mci-preview-meta-row" id="previewPhone"><i class="bi bi-telephone" aria-hidden="true"></i><span>—</span></div>
                <div class="mci-preview-meta-row" id="previewWebsite"><i class="bi bi-globe2" aria-hidden="true"></i><span>—</span></div>
              </div>
            </div>

            <div class="mci-preview-footer">
              <div class="text-muted small text-center">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Updates as you type
              </div>
            </div>
          </div>
          <div class="mci-submit-summary" id="submitSummary">
            <div class="mci-submit-summary__title"><i class="bi bi-card-checklist me-1" aria-hidden="true"></i>Your listing at a glance</div>
            <div class="mci-submit-summary__body">
              <span id="summBizName" class="mci-submit-summary__item fw-semibold">—</span>
              <span class="mci-submit-summary__sep">·</span>
              <span id="summCategory" class="mci-submit-summary__item text-muted">No category</span>
              <span class="mci-submit-summary__sep">·</span>
              <span id="summCity" class="mci-submit-summary__item text-muted">No city</span>
            </div>
          </div>
          <!-- (Removed external preview CTA; inline preview is shown above) -->
          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="agreeTerms" required />
            <label class="form-check-label" for="agreeTerms">I agree to the <a href="/terms.php">Terms of Service</a> for listing on My City Info.</label>
          </div>
          <button class="btn btn-dark btn-lg px-5 w-100" type="submit" id="submitBtn">
            <i class="bi bi-check2-circle me-2" aria-hidden="true"></i>Submit listing
          </button>
          <p class="text-muted small text-center mt-3 mb-0">UI-only — backend save and moderation will connect when ready.</p>
        </div>

        <div class="mci-step-footer d-flex align-items-center gap-3 mt-4">
          <button type="button" class="btn btn-outline-dark" id="prevStep" style="display:none">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
          </button>
          <button type="button" class="btn btn-dark px-4" id="nextStep">
            Continue <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
          </button>
          <span class="text-muted small ms-auto" id="stepCounter">Step 1 of 7</span>
        </div>
      </form>
    </div>

    <!-- Live preview removed (post-login UX) -->
  </div>
</div>

<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div class="fw-semibold fs-6"><i class="bi bi-pin-map me-2" aria-hidden="true"></i>Set location pin</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="bg-light border rounded-3 p-4 text-center mb-3">
          <div class="text-muted small mb-3">Enter coordinates manually for now.</div>
          <div class="row g-3 text-start">
            <div class="col-12 col-md-6">
              <label class="form-label mci-field-label" for="modalLatitude">Latitude</label>
              <input class="form-control" id="modalLatitude" type="text" placeholder="e.g. 28.6139" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label mci-field-label" for="modalLongitude">Longitude</label>
              <input class="form-control" id="modalLongitude" type="text" placeholder="e.g. 77.2090" />
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal" id="applyPinBtn"><i class="bi bi-check2 me-1"></i>Apply pin</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-1">
        <div class="fw-semibold fs-6" id="cropModalLabel"><i class="bi bi-crop me-2" aria-hidden="true"></i><span id="cropModalTitle">Crop image</span></div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-2">
        <div class="mci-crop-container">
          <img id="cropModalImage" src="" alt="" style="max-width:100%;display:block;" />
        </div>
        <p class="text-muted small text-center mt-2 mb-0">
          <i class="bi bi-arrows-move me-1" aria-hidden="true"></i>Drag to reposition · <i class="bi bi-zoom-in me-1" aria-hidden="true"></i>Scroll to zoom
        </p>
      </div>
      <div class="modal-footer border-0 pt-1">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-dark" id="applyCropBtn"><i class="bi bi-check2 me-1"></i>Apply crop</button>
      </div>
    </div>
  </div>
</div>
