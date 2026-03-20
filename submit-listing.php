<?php
$pageTitle = 'List your business - My City Info';
$activePage = 'submit';
$hideCta = true;

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" />
<link rel="stylesheet" href="/assets/css/submit-listing.css" />
HTML;

// JS is placed via $extraJS so it runs AFTER jQuery/Bootstrap are loaded
$extraJS = <<<'JS'
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
$(function () {
  var TOTAL_STEPS = 5;
  var currentStep = 1;

  // ── Step engine ──────────────────────────────────
  function goTo(n) {
    if (n < 1 || n > TOTAL_STEPS) return;

    // Mark old step done if moving forward
    if (n > currentStep) {
      $('[data-step="' + currentStep + '"]').filter('.mci-step-btn').addClass('is-done');
    }

    // Hide old step, show new step
    $('.mci-step[data-step="' + currentStep + '"]').removeClass('is-active').addClass('is-leaving');
    setTimeout(function () {
      $('.mci-step.is-leaving').hide().removeClass('is-leaving');
      $('.mci-step[data-step="' + n + '"]').show().addClass('is-active');
    }, 180);

    currentStep = n;

    // Step dots
    $('.mci-step-btn').each(function () {
      var s = parseInt($(this).data('step'));
      $(this)
        .toggleClass('is-active', s === n)
        .toggleClass('is-done', s < n);
      $(this).attr('aria-current', s === n ? 'step' : 'false');
    });

    // Progress bar
    var pct = Math.round((n / TOTAL_STEPS) * 100);
    $('#stepProgressFill').css('width', pct + '%');
    $('#mciSubmitWrap').attr('aria-valuenow', pct);

    // Nav buttons
    if (n > 1) { $('#prevStep').show(); } else { $('#prevStep').hide(); }
    if (n < TOTAL_STEPS) { $('#nextStep').show(); } else { $('#nextStep').hide(); }
    $('#stepCounter').text('Step ' + n + ' of ' + TOTAL_STEPS);

    // Scroll to top of form
    var top = $('.mci-steps-nav').offset().top - 80;
    $('html,body').animate({ scrollTop: top }, 220, 'swing');

    // Summary on step 5
    if (n === TOTAL_STEPS) updateSummary();
  }

  // Init: set up step 1 without the full goTo animation
  $('.mci-step').hide().removeClass('is-active');
  $('.mci-step[data-step="1"]').show().addClass('is-active');
  $('.mci-step-btn[data-step="1"]').addClass('is-active').attr('aria-current', 'step');
  $('#stepProgressFill').css('width', Math.round((1 / TOTAL_STEPS) * 100) + '%');
  $('#prevStep').hide();
  $('#nextStep').show();
  $('#stepCounter').text('Step 1 of ' + TOTAL_STEPS);

  $('#nextStep').on('click', function () { goTo(currentStep + 1); });
  $('#prevStep').on('click', function () { goTo(currentStep - 1); });

  // Clickable done/active dots
  $(document).on('click', '.mci-step-btn.is-done, .mci-step-btn.is-active', function () {
    goTo(parseInt($(this).data('step')));
  });

  // ── Live preview ─────────────────────────────────
  function updatePreview() {
    var name = $('#listing_title').val().trim() || 'Your business name';
    var cat  = $('#category').val() || 'Category';
    var tag  = $('#tagline').val().trim();
    var desc = $('#description').val().trim();
    var city = $('#city').val().trim();
    var ph   = $('#phone').val().trim();
    var web  = $('#website').val().trim();

    $('#previewName').text(name);
    $('#previewCategory').text(cat);
    $('#previewTagline').text(tag || '').toggle(tag.length > 0);

    var descShort = desc.length > 140 ? desc.substring(0, 140) + '\u2026' : desc;
    $('#previewDesc').text(descShort || 'Your description will appear here, showing customers a quick summary of what you offer.');

    $('#previewCity').find('span').text(city || '\u2014');
    $('#previewPhone').find('span').text(ph || '\u2014');
    $('#previewWebsite').find('span').text(web || '\u2014');
  }

  $('#listing_title, #category, #tagline, #description, #city, #phone, #website').on('input change', updatePreview);
  updatePreview();

  // ── Description counter ──────────────────────────
  (function () {
    var $desc = $('#description');
    var $cnt  = $('#descCount');
    $desc.on('input', function () {
      $cnt.text($desc.val().length + ' / 1200');
    });
    $cnt.text($desc.val().length + ' / 1200');
  }());

  // ── Summary strip (step 5) ───────────────────────
  function updateSummary() {
    $('#summBizName').text($('#listing_title').val().trim() || '\u2014');
    $('#summCategory').text($('#category').val() || 'No category');
    $('#summCity').text($('#city').val().trim() || 'No city');
  }

  // ── Posting type cards ───────────────────────────
  function syncPostingType() {
    var val = $('input[name="posting_type"]:checked').val();
    $('.mci-posting-card').each(function () {
      $(this).toggleClass('is-selected', $(this).find('input').val() === val);
    });
    if (val === 'anonymous') {
      $('#accountFields').slideUp(200);
      $('#email, #password').removeAttr('required');
    } else {
      $('#accountFields').slideDown(200);
      $('#email, #password').attr('required', 'required');
    }
  }
  $('input[name="posting_type"]').on('change', syncPostingType);
  syncPostingType();

  // ── Social pills ─────────────────────────────────
  $(document).on('change', '.mci-social-pill input', function () {
    $(this).closest('.mci-social-pill').toggleClass('is-selected', this.checked);
  });

  // ── Services add-more (FAQ-style) ────────────────
  var svcTmpl = document.getElementById('serviceItemTemplate');
  $('#addServiceBtn').on('click', function () {
    if (!svcTmpl || !svcTmpl.content) return;
    var clone = svcTmpl.content.firstElementChild.cloneNode(true);
    clone.setAttribute('data-svc-index', $('#serviceItems .mci-faq-item').length);
    clone.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
    $('#serviceItems').append(clone);
  });
  $(document).on('click', '.removeSvcBtn', function () {
    $(this).closest('.mci-faq-item').remove();
  });

  // ── FAQ add-more ─────────────────────────────────
  var faqTmpl = document.getElementById('faqItemTemplate');
  $('#addFaqBtn').on('click', function () {
    if (!faqTmpl || !faqTmpl.content) return;
    var clone = faqTmpl.content.firstElementChild.cloneNode(true);
    clone.setAttribute('data-faq-index', $('#faqItems .faq-item').length);
    clone.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
    $('#faqItems').append(clone);
  });
  $(document).on('click', '.removeFaqBtn', function () {
    $(this).closest('.faq-item').remove();
  });

  // ── Image upload tiles with crop ─────────────────
  var cropperInstance = null;
  var cropTarget      = null; // { $tile, hiddenInput, isLogo }
  var cropModal       = new bootstrap.Modal(document.getElementById('cropModal'), {});

  // Open file picker when tile preview or upload btn is clicked
  $(document).on('click', '.mci-img-upload-tile .mci-img-upload-tile__preview, .mci-img-upload-tile .mci-img-upload-btn', function (e) {
    e.stopPropagation();
    $(this).closest('.mci-img-upload-tile').find('.mci-img-file-input').trigger('click');
  });

  // File chosen → init cropper
  $(document).on('change', '.mci-img-file-input', function () {
    var file = this.files[0];
    if (!file || !file.type.startsWith('image/')) return;

    var $tile   = $(this).closest('.mci-img-upload-tile');
    var type    = $tile.data('type');
    var aspect  = parseFloat($tile.data('aspect')) || 1;

    var labels  = { logo: 'Crop logo', profile: 'Crop profile photo', banner: 'Crop banner image' };
    $('#cropModalTitle').text(labels[type] || 'Crop image');

    cropTarget = { $tile: $tile, hiddenInput: $tile.find('input[type="hidden"]')[0], isLogo: type === 'logo', aspect: aspect };

    var reader = new FileReader();
    reader.onload = function (e) {
      var img = document.getElementById('cropModalImage');
      img.src = e.target.result;

      // Destroy previous cropper before modal opens
      if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }

      cropModal.show();
    };
    reader.readAsDataURL(file);
    this.value = ''; // allow re-selecting same file
  });

  // Init Cropper.js after modal is fully shown
  document.getElementById('cropModal').addEventListener('shown.bs.modal', function () {
    if (cropperInstance) { cropperInstance.destroy(); }
    var img    = document.getElementById('cropModalImage');
    var aspect = cropTarget ? cropTarget.aspect : 1;
    cropperInstance = new Cropper(img, {
      aspectRatio:  aspect,
      viewMode:     1,
      autoCropArea: 0.9,
      responsive:   true,
      background:   false,
      guides:       true,
      center:       true,
    });
  });

  // Destroy Cropper when modal hidden (prevents stale instance)
  document.getElementById('cropModal').addEventListener('hidden.bs.modal', function () {
    if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
  });

  // Apply crop → store base64, show preview
  $('#applyCropBtn').on('click', function () {
    if (!cropperInstance || !cropTarget) return;

    var isSquare = cropTarget.aspect === 1;
    var outW = isSquare ? 600 : 1280;
    var outH = isSquare ? 600 : Math.round(1280 / cropTarget.aspect);

    var canvas = cropperInstance.getCroppedCanvas({ width: outW, height: outH, imageSmoothingQuality: 'high' });
    var dataUrl = canvas.toDataURL('image/jpeg', 0.88);

    // Store value
    cropTarget.hiddenInput.value = dataUrl;

    // Show thumbnail in tile
    var $preview = cropTarget.$tile.find('.mci-img-upload-tile__preview, .mci-img-upload-tile__preview--banner').first();
    $preview.find('.mci-img-upload-tile__placeholder').hide();
    $preview.find('.mci-img-tile-result').remove();
    $('<img class="mci-img-tile-result" alt="preview" />').attr('src', dataUrl).appendTo($preview);

    // Mark tile as having an image
    cropTarget.$tile.addClass('has-image');

    // If logo → push to live preview card too
    if (cropTarget.isLogo) {
      $('#previewPhotoImg').attr('src', dataUrl).removeClass('d-none');
      $('#previewPhotoPlaceholder').addClass('d-none');
    }

    cropModal.hide();
  });

  // ── Image drop zone (gallery) ─────────────────────
  var dropArea  = document.getElementById('dropFiles');
  var fileInput = document.getElementById('fileInputImages');
  var preview   = document.getElementById('imagePreview');

  function renderPreviews(files) {
    preview.innerHTML = '';
    $('#previewPhotoImg').addClass('d-none');
    $('#previewPhotoPlaceholder').removeClass('d-none');
    if (!files || !files.length) return;
    var first = true;
    for (var i = 0; i < Math.min(files.length, 12); i++) {
      var f = files[i];
      if (!f.type || !f.type.startsWith('image/')) continue;
      var url = URL.createObjectURL(f);
      if (first) {
        first = false;
        $('#previewPhotoImg').attr('src', url).attr('alt', f.name).removeClass('d-none');
        $('#previewPhotoPlaceholder').addClass('d-none');
      }
      var wrap = document.createElement('div');
      wrap.className = 'mci-photo-thumb';
      var img = document.createElement('img');
      img.src = url; img.alt = f.name;
      wrap.appendChild(img);
      preview.appendChild(wrap);
    }
  }

  if (dropArea && fileInput) {
    dropArea.addEventListener('click', function () { fileInput.click(); });
    ['dragover','dragenter'].forEach(function (ev) {
      dropArea.addEventListener(ev, function (e) {
        e.preventDefault(); e.stopPropagation();
        dropArea.classList.add('is-over');
      });
    });
    ['dragleave','drop'].forEach(function (ev) {
      dropArea.addEventListener(ev, function (e) {
        e.preventDefault(); e.stopPropagation();
        dropArea.classList.remove('is-over');
      });
    });
    dropArea.addEventListener('drop', function (e) {
      e.preventDefault();
      var dt = new DataTransfer();
      Array.from(e.dataTransfer.files).forEach(function (f) { dt.items.add(f); });
      fileInput.files = dt.files;
      renderPreviews(fileInput.files);
    });
    fileInput.addEventListener('change', function () { renderPreviews(fileInput.files); });
  }

  // ── Map pin apply ─────────────────────────────────
  $('#applyPinBtn').on('click', function () {
    $('input[name="latitude"]').val($('#modalLatitude').val());
    $('input[name="longitude"]').val($('#modalLongitude').val());
  });

  // ── Social login (UI placeholder) ─────────────────
  $(document).on('click', '.mci-social-login-btn', function () {
    var provider = $(this).data('provider');
    alert(provider + ' sign-in will be connected when OAuth is configured. Your listing data is safe.');
  });

  // ── localStorage helpers ──────────────────────────
  var LS_KEY = 'mci_listing_preview';

  function collectFormData() {
    var data = {};

    // Scalar fields
    $('#mciSubmitForm').find('input:not([type=file]):not([type=radio]):not([type=checkbox]), textarea, select').each(function () {
      if (this.name) data[this.name] = this.value;
    });

    // Checked radios
    $('#mciSubmitForm').find('input[type=radio]:checked').each(function () {
      data[this.name] = this.value;
    });

    // Services
    var svcs = [];
    $('#serviceItems .mci-faq-item').each(function () {
      svcs.push({
        name: $(this).find('input[name="service_name[]"]').val() || '',
        desc: $(this).find('textarea[name="service_desc[]"]').val() || ''
      });
    });
    data._services = svcs;

    // FAQs
    var faqs = [];
    $('#faqItems .mci-faq-item').each(function () {
      faqs.push({
        q: $(this).find('input[name="faq_question[]"]').val() || '',
        a: $(this).find('textarea[name="faq_answer[]"]').val() || ''
      });
    });
    data._faqs = faqs;

    // Cropped images (base64 stored in hidden inputs)
    ['img_logo','img_profile','img_banner'].forEach(function (n) {
      var v = $('input[name="' + n + '"]').val();
      if (v) data[n] = v;
    });

    return data;
  }

  function savePreview() {
    try { localStorage.setItem(LS_KEY, JSON.stringify(collectFormData())); } catch(e) {}
  }

  // Auto-save on every step change (goTo already calls this on step move)
  // and on any input change for reliability
  var saveTimer;
  $('#mciSubmitForm').on('input change', function () {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(savePreview, 600);
  });

  // ── Preview listing button ────────────────────────
  $('#previewListingBtn').on('click', function () {
    savePreview();
    window.open('/listing-preview.php', '_blank');
  });

  // ── Clear localStorage on submit ──────────────────
  $('#mciSubmitForm').on('submit', function () {
    try { localStorage.removeItem(LS_KEY); } catch(e) {}
  });
});
</script>
JS;

$categories = [
  'Airport','Amusement Park','Aquarium','Art Gallery','ATM','Automotive',
  'Bakery','Bank','Bar','Beauty Salon','Bicycle Store','Books & Stationary Store',
  'Bus Stations','Cafe','Car Dealer','Car Rental','Car Repair','Car Wash',
  'Cemetery','Church','City Attraction','Clothing Store','College',
  'Convenience Store','Courier Services','Dentist','Departmental Store',
  'Doctor','Electrician','Electronics Store','Fire Station','Florist',
  'Funeral Home','Furniture Store','Gift Shop','Government Office','Gym',
  'Hardware Store','Health','Hindu Temple','Home Appliances Products',
  'Hospital','Hotels','Industrial and Manufacturing Supplies','Insurance Agency',
  'Jewelry Store','Laundry','Lawyer','Library','Liquor Store','Locksmith',
  'Medical Store','Monuments','Mosque','Movie Theater','Museum',
  'NGO and Charitable Trusts','Night Club','Painter','Park','Pet Store',
  'Petrol Pump','Physiotherapist','Plumber','Police Station','Post Office',
  'Pre Schools and Day Care','Private Coaching Institutes','Real Estate',
  'Resorts','Restaurant','School','Services','Shoe Store','Shopping','Spa',
  'Stadium','Supermarket','Travel Agency','University','Veterinary Care',
];

ob_start();
?>

<div class="mci-submit-wrap">
  <div class="row g-4 g-lg-5 align-items-start">

    <!-- ─── Left: wizard ──────────────────────────────── -->
    <div class="col-12 col-lg-7">

      <!-- Hero -->
      <div class="mci-submit-hero mb-4">
        <p class="mci-submit-hero__kicker">My City Info</p>
        <h1 class="mci-submit-hero__title">List your business</h1>
        <p class="mci-submit-hero__lead">Five quick steps — your listing will be visible to thousands of locals searching nearby.</p>
      </div>

      <!-- Step indicators -->
      <div class="mci-steps-nav mb-4" aria-label="Form progress">
        <?php
        $steps = [
          ['icon'=>'bi-building','label'=>'Business'],
          ['icon'=>'bi-geo-alt', 'label'=>'Location'],
          ['icon'=>'bi-clock',   'label'=>'Hours'],
          ['icon'=>'bi-images',  'label'=>'Photos'],
          ['icon'=>'bi-send',    'label'=>'Publish'],
        ];
        foreach ($steps as $i => $s):
          $n = $i + 1;
        ?>
          <button
            type="button"
            class="mci-step-btn <?= $n === 1 ? 'is-active' : '' ?>"
            data-step="<?= $n ?>"
            aria-current="<?= $n === 1 ? 'step' : 'false' ?>"
            aria-label="Step <?= $n ?>: <?= htmlspecialchars($s['label']) ?>"
          >
            <span class="mci-step-btn__dot">
              <i class="bi <?= htmlspecialchars($s['icon']) ?>"></i>
              <span class="mci-step-btn__check"><i class="bi bi-check-lg"></i></span>
            </span>
            <span class="mci-step-btn__label"><?= htmlspecialchars($s['label']) ?></span>
          </button>
          <?php if ($n < count($steps)): ?>
            <span class="mci-step-connector" aria-hidden="true"></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <!-- Progress bar -->
      <div class="mci-progress-bar mb-4" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
        <div class="mci-progress-bar__fill" id="stepProgressFill" style="width:20%"></div>
      </div>

      <!-- ─── Form ──────────────────────────────────────── -->
      <form action="#" method="post" enctype="multipart/form-data" id="mciSubmitForm" novalidate>
        <input type="hidden" name="form_origin" value="ui_submit_listing" />

        <!-- ════ STEP 1 — Business identity ════ -->
        <div class="mci-step is-active" data-step="1">
          <div class="mci-step-header">
            <div class="mci-step-header__icon" aria-hidden="true"><i class="bi bi-building"></i></div>
            <div>
              <div class="mci-step-header__title">Tell us about your business</div>
              <div class="mci-step-header__desc">Start with the basics — name, what you do, and who you're for.</div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label mci-field-label" for="listing_title">
                Business name <span class="text-danger">*</span>
              </label>
              <input
                class="form-control form-control-lg"
                id="listing_title"
                type="text"
                name="listing_title"
                placeholder="e.g. City Auto Care"
                required
                autocomplete="organization"
              />
            </div>
            <div class="col-12">
              <label class="form-label mci-field-label" for="tagline">Tagline</label>
              <input class="form-control" id="tagline" type="text" name="tagline" placeholder="One punchy line customers will remember" />
              <div class="form-text">Optional — appears under your name on the listing page.</div>
            </div>
            <div class="col-12 col-sm-7">
              <label class="form-label mci-field-label" for="category">
                Category <span class="text-danger">*</span>
              </label>
              <select class="form-select" id="category" name="category" required>
                <option value="">Choose a category…</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label mci-field-label" for="description">
                Description <span class="text-danger">*</span>
              </label>
              <textarea
                class="form-control"
                id="description"
                name="description"
                rows="4"
                maxlength="1200"
                placeholder="Tell customers what makes your business worth visiting…"
                required
              ></textarea>
              <div class="d-flex justify-content-end mt-1">
                <span class="form-text" id="descCount">0 / 1200</span>
              </div>
            </div>

            <!-- Services / products — FAQ-style add-more -->
            <div class="col-12 mt-2">
              <div class="mci-submit-label mb-1">Services &amp; products <span class="fw-normal text-muted text-lowercase">(optional)</span></div>
              <p class="text-muted small mb-3">List each service or product you offer — customers see these as highlights on your listing.</p>

              <div id="serviceItems" class="mb-2">
                <div class="mci-faq-item" data-svc-index="0">
                  <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
                  <div class="flex-grow-1">
                    <input class="form-control form-control-sm mb-2" type="text" name="service_name[]" placeholder="Name, e.g. Interior Painting" />
                    <textarea class="form-control form-control-sm" name="service_desc[]" rows="2" placeholder="Short description — what's included, price range, turnaround…"></textarea>
                  </div>
                  <button type="button" class="btn btn-sm removeSvcBtn mci-faq-item__remove" aria-label="Remove service"><i class="bi bi-x-lg"></i></button>
                </div>
              </div>
              <button type="button" id="addServiceBtn" class="btn btn-sm btn-outline-dark">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add service
              </button>

              <template id="serviceItemTemplate">
                <div class="mci-faq-item" data-svc-index="__INDEX__">
                  <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
                  <div class="flex-grow-1">
                    <input class="form-control form-control-sm mb-2" type="text" name="service_name[]" placeholder="Name, e.g. Interior Painting" />
                    <textarea class="form-control form-control-sm" name="service_desc[]" rows="2" placeholder="Short description — what's included, price range, turnaround…"></textarea>
                  </div>
                  <button type="button" class="btn btn-sm removeSvcBtn mci-faq-item__remove" aria-label="Remove service"><i class="bi bi-x-lg"></i></button>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- ════ STEP 2 — Location & contact ════ -->
        <div class="mci-step" data-step="2">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--2" aria-hidden="true"><i class="bi bi-geo-alt"></i></div>
            <div>
              <div class="mci-step-header__title">Where are you?</div>
              <div class="mci-step-header__desc">Help customers find you and get in touch quickly.</div>
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
            <div class="col-6 col-md-3.5">
              <label class="form-label mci-field-label" for="latitude">Latitude</label>
              <input class="form-control" id="latitude" type="text" name="latitude" placeholder="e.g. 28.6139" />
            </div>
            <div class="col-6 col-md-3.5">
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
              <div class="mci-submit-label mb-1">Social media <span class="fw-normal text-muted text-lowercase">(all optional)</span></div>
              <p class="text-muted small mb-0">Add your pages or profiles so customers can follow you.</p>
            </div>

            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_facebook">Facebook</label>
              <div class="input-group">
                <span class="input-group-text mci-social-icon--fb"><i class="bi bi-facebook" aria-hidden="true"></i></span>
                <input class="form-control" id="social_facebook" type="text" name="social_facebook" placeholder="facebook.com/yourpage or @handle" />
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
                <input class="form-control" id="social_youtube" type="text" name="social_youtube" placeholder="youtube.com/@yourchannel" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_linkedin">LinkedIn</label>
              <div class="input-group">
                <span class="input-group-text mci-social-icon--li"><i class="bi bi-linkedin" aria-hidden="true"></i></span>
                <input class="form-control" id="social_linkedin" type="text" name="social_linkedin" placeholder="linkedin.com/company/yourco" />
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

        <!-- ════ STEP 3 — Hours & pricing ════ -->
        <div class="mci-step" data-step="3">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--3" aria-hidden="true"><i class="bi bi-clock"></i></div>
            <div>
              <div class="mci-step-header__title">Hours &amp; pricing</div>
              <div class="mci-step-header__desc">Let customers know when you're open and what to expect on pricing.</div>
            </div>
          </div>

          <div class="mci-submit-label">Business hours</div>
          <div class="mci-hours-compact mb-4">
            <?php
            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            $times = [];
            for ($m = 0; $m < 24*60; $m += 30) {
              $h = intdiv($m, 60); $mm = $m % 60;
              $times[] = sprintf('%02d:%02d', $h, $mm);
            }
            foreach ($days as $day):
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

          <div class="mci-submit-label">Pricing <span class="fw-normal text-muted text-lowercase">(optional)</span></div>
          <div class="row g-3 mb-4">
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

        <!-- ════ STEP 4 — Photos & FAQs ════ -->
        <div class="mci-step" data-step="4">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--4" aria-hidden="true"><i class="bi bi-images"></i></div>
            <div>
              <div class="mci-step-header__title">Bring it to life</div>
              <div class="mci-step-header__desc">Listings with photos get significantly more views. FAQs answer questions before customers have to ask.</div>
            </div>
          </div>

          <!-- ── Branded images: logo, profile, banner ────────── -->
          <div class="mci-submit-label">Logo, profile &amp; banner</div>
          <p class="text-muted small mb-3">Each image is cropped to the right shape. All optional but recommended — they appear prominently on your listing page.</p>

          <!-- Logo + Profile side by side -->
          <div class="mci-img-upload-grid">
            <div class="mci-img-upload-tile" data-type="logo" data-aspect="1">
              <div class="mci-img-upload-tile__preview">
                <div class="mci-img-upload-tile__placeholder">
                  <i class="bi bi-shop" aria-hidden="true"></i>
                  <span>No logo yet</span>
                </div>
              </div>
              <div class="mci-img-upload-tile__footer">
                <div class="mci-img-upload-tile__info">
                  <strong>Logo</strong>
                  <span>Square · 1:1</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark mci-img-upload-btn">
                  <i class="bi bi-upload me-1" aria-hidden="true"></i>Upload
                </button>
              </div>
              <input type="file" class="d-none mci-img-file-input" accept="image/*" />
              <input type="hidden" name="img_logo" />
            </div>

            <div class="mci-img-upload-tile mci-img-upload-tile--circle" data-type="profile" data-aspect="1">
              <div class="mci-img-upload-tile__preview">
                <div class="mci-img-upload-tile__placeholder">
                  <i class="bi bi-person-circle" aria-hidden="true"></i>
                  <span>No photo yet</span>
                </div>
              </div>
              <div class="mci-img-upload-tile__footer">
                <div class="mci-img-upload-tile__info">
                  <strong>Profile photo</strong>
                  <span>Square · 1:1</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark mci-img-upload-btn">
                  <i class="bi bi-upload me-1" aria-hidden="true"></i>Upload
                </button>
              </div>
              <input type="file" class="d-none mci-img-file-input" accept="image/*" />
              <input type="hidden" name="img_profile" />
            </div>
          </div>

          <!-- Banner full-width -->
          <div class="mci-img-upload-tile mci-img-upload-tile--banner mt-3" data-type="banner" data-aspect="3.2">
            <div class="mci-img-upload-tile__preview mci-img-upload-tile__preview--banner">
              <div class="mci-img-upload-tile__placeholder">
                <i class="bi bi-panorama" aria-hidden="true"></i>
                <span>No banner yet</span>
              </div>
            </div>
            <div class="mci-img-upload-tile__footer">
              <div class="mci-img-upload-tile__info">
                <strong>Banner image</strong>
                <span>Wide · 16:5</span>
              </div>
              <button type="button" class="btn btn-sm btn-outline-dark mci-img-upload-btn">
                <i class="bi bi-upload me-1" aria-hidden="true"></i>Upload
              </button>
            </div>
            <input type="file" class="d-none mci-img-file-input" accept="image/*" />
            <input type="hidden" name="img_banner" />
          </div>

          <hr class="mci-inner-divider mt-4 mb-3" />

          <!-- ── Gallery drop zone ──────────────────────────────── -->
          <div class="mci-submit-label">Gallery photos</div>
          <div
            id="dropFiles"
            class="mci-drop-zone"
            role="button"
            tabindex="0"
            aria-label="Upload gallery images"
          >
            <div class="mci-drop-zone__inner">
              <div class="mci-drop-zone__icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></div>
              <div class="mci-drop-zone__title">Drop photos here</div>
              <div class="mci-drop-zone__sub">or click to browse — PNG, JPG, WebP</div>
              <button type="button" class="btn btn-sm btn-dark mt-2 mci-drop-zone__btn">Choose files</button>
              <input id="fileInputImages" class="d-none" type="file" name="images[]" accept="image/*" multiple />
            </div>
          </div>
          <div class="mci-photo-preview d-flex flex-wrap gap-2 mt-3" id="imagePreview"></div>

          <!-- Extras -->
          <div class="row g-3 mt-1 mb-4">
            <div class="col-12">
              <label class="form-label mci-field-label" for="tags">Tags</label>
              <input class="form-control" id="tags" type="text" name="tags" placeholder="vegan, delivery, 24/7, parking…" />
              <div class="form-text">Comma-separated — helps with search filters.</div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label mci-field-label" for="video_url">Video URL</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-play-circle" aria-hidden="true"></i></span>
                <input class="form-control" id="video_url" type="url" name="video_url" placeholder="YouTube or Vimeo link" />
              </div>
            </div>
          </div>

          <!-- FAQs -->
          <div class="mci-submit-label">FAQs <span class="fw-normal text-muted text-lowercase">(optional)</span></div>
          <p class="text-muted small mb-3">Pre-answer common questions so customers can decide quickly.</p>
          <div id="faqItems" class="mb-2">
            <div class="faq-item mci-faq-item" data-faq-index="0">
              <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
              <div class="flex-grow-1">
                <input class="form-control form-control-sm mb-2" type="text" name="faq_question[]" placeholder="Question, e.g. Do you offer home delivery?" />
                <textarea class="form-control form-control-sm" name="faq_answer[]" rows="2" placeholder="Your answer…"></textarea>
              </div>
              <button type="button" class="btn btn-sm removeFaqBtn mci-faq-item__remove" aria-label="Remove FAQ"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
          <button type="button" id="addFaqBtn" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add another FAQ
          </button>

          <template id="faqItemTemplate">
            <div class="faq-item mci-faq-item" data-faq-index="__INDEX__">
              <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
              <div class="flex-grow-1">
                <input class="form-control form-control-sm mb-2" type="text" name="faq_question[]" placeholder="Question, e.g. Do you offer home delivery?" />
                <textarea class="form-control form-control-sm" name="faq_answer[]" rows="2" placeholder="Your answer…"></textarea>
              </div>
              <button type="button" class="btn btn-sm removeFaqBtn mci-faq-item__remove" aria-label="Remove FAQ"><i class="bi bi-x-lg"></i></button>
            </div>
          </template>
        </div>

        <!-- ════ STEP 5 — Account & publish ════ -->
        <div class="mci-step" data-step="5">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--5" aria-hidden="true"><i class="bi bi-send"></i></div>
            <div>
              <div class="mci-step-header__title">Almost there!</div>
              <div class="mci-step-header__desc">Create an account or post anonymously — your listing will be reviewed and published.</div>
            </div>
          </div>

          <!-- Posting type -->
          <div class="mci-posting-cards mb-4">
            <label class="mci-posting-card">
              <input type="radio" name="posting_type" value="registered" checked class="visually-hidden mci-posting-card__radio" />
              <span class="mci-posting-card__icon"><i class="bi bi-person-check-fill"></i></span>
              <div>
                <div class="mci-posting-card__title">With an account</div>
                <div class="mci-posting-card__desc">Faster publication, manage your listing anytime, receive review alerts.</div>
              </div>
              <span class="mci-posting-card__check"><i class="bi bi-check-circle-fill"></i></span>
            </label>
            <label class="mci-posting-card">
              <input type="radio" name="posting_type" value="anonymous" class="visually-hidden mci-posting-card__radio" />
              <span class="mci-posting-card__icon mci-posting-card__icon--anon"><i class="bi bi-incognito"></i></span>
              <div>
                <div class="mci-posting-card__title">Anonymous</div>
                <div class="mci-posting-card__desc">No account needed. Admin reviews the listing before it goes live.</div>
              </div>
              <span class="mci-posting-card__check"><i class="bi bi-check-circle-fill"></i></span>
            </label>
          </div>

          <!-- Account fields (shown for registered) -->
          <div id="accountFields">
            <div class="row g-3 mb-3">
              <div class="col-12 col-md-6">
                <label class="form-label mci-field-label" for="email">Email <span class="text-danger">*</span></label>
                <input class="form-control" id="email" type="email" name="email" placeholder="you@example.com" autocomplete="email" />
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label mci-field-label" for="password">Password <span class="text-danger">*</span></label>
                <input class="form-control" id="password" type="password" name="password" placeholder="Create a password" autocomplete="new-password" />
              </div>
            </div>
            <p class="text-muted small mb-3">Already registered? <a href="/login.php" class="fw-semibold">Log in</a> — your details will be linked automatically.</p>

            <!-- Social login -->
            <div class="mci-or-divider mb-3"><span>or continue with</span></div>
            <div class="d-flex gap-2 mb-4 flex-wrap">
              <button type="button" class="btn btn-outline-secondary flex-fill mci-social-login-btn" data-provider="Google">
                <svg class="me-1" width="17" height="17" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                  <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                  <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                  <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                  <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Google
              </button>
              <button type="button" class="btn btn-outline-secondary flex-fill mci-social-login-btn" data-provider="Facebook">
                <i class="bi bi-facebook me-1" aria-hidden="true" style="color:#1877f2"></i>Facebook
              </button>
              <button type="button" class="btn btn-outline-secondary flex-fill mci-social-login-btn" data-provider="Apple">
                <i class="bi bi-apple me-1" aria-hidden="true"></i>Apple
              </button>
            </div>
          </div>

          <!-- Summary strip -->
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

          <!-- Preview listing bar -->
          <div class="mci-preview-listing-bar mb-4">
            <div>
              <div class="fw-semibold small">Want to see how it looks?</div>
              <div class="text-muted small">Opens the full listing detail page in a new tab. Your progress is saved.</div>
            </div>
            <button type="button" class="btn btn-outline-dark btn-sm flex-shrink-0" id="previewListingBtn">
              <i class="bi bi-eye me-1" aria-hidden="true"></i>Preview listing
            </button>
          </div>

          <!-- Terms -->
          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="agreeTerms" required />
            <label class="form-check-label" for="agreeTerms">
              I agree to the <a href="/terms.php">Terms of Service</a> for listing on My City Info.
            </label>
          </div>

          <button class="btn btn-dark btn-lg px-5 w-100" type="submit" id="submitBtn">
            <i class="bi bi-check2-circle me-2" aria-hidden="true"></i>List your business
          </button>
          <p class="text-muted small text-center mt-3 mb-0">UI-only — save + moderation will connect when the backend is ready.</p>
        </div>

        <!-- ─── Step navigation ─────────────────────────── -->
        <div class="mci-step-footer d-flex align-items-center gap-3 mt-4">
          <button type="button" class="btn btn-outline-dark" id="prevStep" style="display:none">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
          </button>
          <button type="button" class="btn btn-dark px-4" id="nextStep">
            Continue <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
          </button>
          <span class="text-muted small ms-auto" id="stepCounter">Step 1 of 5</span>
        </div>
      </form>
    </div>

    <!-- ─── Right: live preview card ──────────────────── -->
    <div class="col-12 col-lg-5 d-none d-lg-block">
      <div class="mci-submit-preview" id="livePreview">
        <div class="mci-preview-label">
          <i class="bi bi-eye me-1" aria-hidden="true"></i>Live preview
        </div>

        <!-- Photo -->
        <div class="mci-preview-photo" id="previewPhoto">
          <div class="mci-preview-photo__placeholder" id="previewPhotoPlaceholder">
            <i class="bi bi-image" aria-hidden="true"></i>
            <span>Photos you upload appear here</span>
          </div>
          <img id="previewPhotoImg" src="" alt="" class="d-none" />
        </div>

        <!-- Details -->
        <div class="mci-preview-body">
          <div class="mci-preview-category" id="previewCategory">Category</div>
          <div class="mci-preview-name" id="previewName">Your business name</div>
          <div class="mci-preview-tagline" id="previewTagline">Your tagline will show here</div>
          <div class="mci-preview-desc" id="previewDesc">Your description will appear here, showing customers a quick summary of what you offer.</div>

          <div class="mci-preview-divider"></div>

          <div class="mci-preview-meta">
            <div class="mci-preview-meta-row" id="previewCity">
              <i class="bi bi-geo-alt" aria-hidden="true"></i>
              <span>—</span>
            </div>
            <div class="mci-preview-meta-row" id="previewPhone">
              <i class="bi bi-telephone" aria-hidden="true"></i>
              <span>—</span>
            </div>
            <div class="mci-preview-meta-row" id="previewWebsite">
              <i class="bi bi-globe2" aria-hidden="true"></i>
              <span>—</span>
            </div>
          </div>
        </div>

        <div class="mci-preview-footer">
          <div class="text-muted small text-center">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Preview updates as you type
          </div>
        </div>
      </div>

      <!-- Benefits list below preview -->
      <ul class="mci-submit-benefits mt-4">
        <li><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i><span>Free to submit — no hidden fees</span></li>
        <li><i class="bi bi-people-fill" aria-hidden="true"></i><span>Reach thousands of local searchers</span></li>
        <li><i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit or update your listing anytime</span></li>
        <li><i class="bi bi-shield-check-fill" aria-hidden="true"></i><span>Reviewed before going live</span></li>
      </ul>
    </div>

  </div>
</div>

<!-- Map picker modal -->
<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div class="fw-semibold fs-6">
          <i class="bi bi-pin-map me-2" aria-hidden="true"></i>Set location pin
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="bg-light border rounded-3 p-4 text-center mb-3">
          <div class="text-muted small mb-3">Map integration coming — enter coordinates manually for now.</div>
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
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal" id="applyPinBtn">
          <i class="bi bi-check2 me-1" aria-hidden="true"></i>Apply pin
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Image crop modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-1">
        <div class="fw-semibold fs-6" id="cropModalLabel">
          <i class="bi bi-crop me-2" aria-hidden="true"></i><span id="cropModalTitle">Crop image</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-2">
        <div class="mci-crop-container">
          <img id="cropModalImage" src="" alt="" style="max-width:100%;display:block;" />
        </div>
        <p class="text-muted small text-center mt-2 mb-0">
          <i class="bi bi-arrows-move me-1" aria-hidden="true"></i>Drag to reposition &nbsp;·&nbsp;
          <i class="bi bi-zoom-in me-1" aria-hidden="true"></i>Scroll or pinch to zoom
        </p>
      </div>
      <div class="modal-footer border-0 pt-1">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-dark" id="applyCropBtn">
          <i class="bi bi-check2 me-1" aria-hidden="true"></i>Apply crop
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>
