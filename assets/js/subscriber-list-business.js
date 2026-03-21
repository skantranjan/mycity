/**
 * 7-step listing wizard for /subscriber/list-business/
 * (loads after jQuery, Bootstrap, Cropper.js)
 */
$(function () {
  var TOTAL_STEPS = 7;
  var currentStep = 1;

  // If CP manages categories via localStorage, mirror them into the subscriber wizard.
  try {
    var cpCatsRaw = localStorage.getItem('mci_cp_categories');
    var cpCats = cpCatsRaw ? JSON.parse(cpCatsRaw) : [];
    if (Array.isArray(cpCats) && cpCats.length && $('#category').length) {
      var currentVal = $('#category').val();
      $('#category').empty();
      $('#category').append('<option value="">Choose a category…</option>');
      cpCats.forEach(function (c) {
        var name = (typeof c === 'string') ? c : (c && (c.name || c.title) ? (c.name || c.title) : '');
        name = (name || '').toString().trim();
        if (!name) return;
        $('#category').append('<option value="' + $('<div>').text(name).html() + '">' + $('<div>').text(name).html() + '</option>');
      });
      $('#category').append('<option value="__request_new__">Request a new category…</option>');
      if (currentVal) {
        $('#category').val(currentVal);
      }
    }
  } catch (e0) {}

  function goTo(n) {
    if (n < 1 || n > TOTAL_STEPS) return;

    if (n > currentStep) {
      $('[data-step="' + currentStep + '"]').filter('.mci-step-btn').addClass('is-done');
    }

    $('.mci-step[data-step="' + currentStep + '"]').removeClass('is-active').addClass('is-leaving');
    setTimeout(function () {
      $('.mci-step.is-leaving').hide().removeClass('is-leaving');
      $('.mci-step[data-step="' + n + '"]').show().addClass('is-active');
    }, 180);

    currentStep = n;

    $('.mci-step-btn').each(function () {
      var s = parseInt($(this).data('step'));
      $(this)
        .toggleClass('is-active', s === n)
        .toggleClass('is-done', s < n);
      $(this).attr('aria-current', s === n ? 'step' : 'false');
    });

    var pct = Math.round((n / TOTAL_STEPS) * 100);
    $('#stepProgressFill').css('width', pct + '%');
    $('#mciSubmitWrap').attr('aria-valuenow', pct);

    if (n > 1) { $('#prevStep').show(); } else { $('#prevStep').hide(); }
    if (n < TOTAL_STEPS) { $('#nextStep').show(); } else { $('#nextStep').hide(); }
    $('#stepCounter').text('Step ' + n + ' of ' + TOTAL_STEPS);

    var top = $('.mci-steps-nav').offset().top - 80;
    $('html,body').animate({ scrollTop: top }, 220, 'swing');

    if (n === TOTAL_STEPS) {
      updateSummary();
      // Refresh preview card contents once it becomes visible on Publish step.
      updatePreview();
    }
  }

  $('.mci-step').hide().removeClass('is-active');
  $('.mci-step[data-step="1"]').show().addClass('is-active');
  $('.mci-step-btn[data-step="1"]').addClass('is-active').attr('aria-current', 'step');
  $('#stepProgressFill').css('width', Math.round((1 / TOTAL_STEPS) * 100) + '%');
  $('#prevStep').hide();
  $('#nextStep').show();
  $('#stepCounter').text('Step 1 of ' + TOTAL_STEPS);

  $('#nextStep').on('click', function () { goTo(currentStep + 1); });
  $('#prevStep').on('click', function () { goTo(currentStep - 1); });

  $(document).on('click', '.mci-step-btn.is-done, .mci-step-btn.is-active', function () {
    goTo(parseInt($(this).data('step')));
  });

  function updatePreview() {
    var name = $('#listing_title').val().trim() || 'Your business name';
    var cat  = $('#category').val() || 'Category';
    if (cat === '__request_new__') {
      var req = ($('#requested_category_name').val() || '').trim();
      cat = req || 'Requested category';
    }
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

  function syncCategoryRequestUI() {
    var isReq = $('#category').val() === '__request_new__';
    $('#is_category_request').val(isReq ? '1' : '0');
    $('#categoryRequestFields').toggleClass('d-none', !isReq);
    $('#requested_category_name').prop('required', !!isReq);
    $('#category_request_reason').prop('required', !!isReq);
  }

  $('#category').on('change input', function () {
    syncCategoryRequestUI();
    updatePreview();
    if (currentStep === 7) updateSummary();
  });

  // Keep summary/preview synced when the requested name changes.
  $('#requested_category_name').on('input change', function () {
    updatePreview();
    if (currentStep === 7) updateSummary();
  });

  $('#listing_title, #category, #tagline, #description, #city, #phone, #website').on('input change', updatePreview);
  updatePreview();
  syncCategoryRequestUI();

  // ── URL slug (listing_slug + city for full path) ─────────────────
  var existingSlugs = [];
  try {
    var slugJsonEl = document.getElementById('mciExistingSlugs');
    if (slugJsonEl && slugJsonEl.textContent) {
      existingSlugs = JSON.parse(slugJsonEl.textContent);
    }
  } catch (eSl) { existingSlugs = []; }

  var slugAutoFromName = true;

  function mciSlugify(str) {
    if (!str) return '';
    return str.toString().toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }

  function mciFullListingSlug(baseSlug, cityVal) {
    var b = mciSlugify(baseSlug);
    var c = mciSlugify(cityVal || '');
    if (!b) return '';
    if (!c) return b;
    return b + '-' + c;
  }

  function mciUpdateBusinessUrlPreview() {
    if (!$('#listing_slug').length) return;
    var base = $('#listing_slug').val().trim();
    var city = $('#city').val().trim();
    var full = mciFullListingSlug(base, city);
    var $wrap = $('#mciBusinessUrlPreview');
    if (!$wrap.length) return;
    var origin = ($wrap.data('origin') || '').toString().replace(/\/$/, '') || window.location.origin.replace(/\/$/, '') || 'https://mycityinfo.com';
    var $txt = $('#mciBusinessUrlPreviewText');
    if (!full) {
      $txt.text('\u2014');
      return;
    }
    $txt.text(origin + '/business/' + full + '/');
  }

  function mciSuggestFreeBaseSlug(baseName) {
    var b = mciSlugify(baseName);
    var city = $('#city').val().trim();
    if (!b) return '';
    for (var i = 0; i < 100; i++) {
      var trialBase = i === 0 ? b : (b + '-' + (i + 1));
      var full = mciFullListingSlug(trialBase, city);
      if (full && existingSlugs.indexOf(full) === -1) return trialBase;
    }
    return b + '-' + Date.now();
  }

  $('#listing_title').on('input', function () {
    if (!$('#listing_slug').length) return;
    if (!slugAutoFromName) return;
    var s = mciSlugify($(this).val());
    $('#listing_slug').val(s);
    mciUpdateBusinessUrlPreview();
  });

  $('#listing_slug').on('input', function () {
    if (!$(this).length) return;
    var manual = mciSlugify($(this).val());
    $(this).val(manual);
    var auto = mciSlugify($('#listing_title').val());
    slugAutoFromName = !manual || manual === auto;
    mciUpdateBusinessUrlPreview();
  });

  $('#city').on('input change', function () {
    mciUpdateBusinessUrlPreview();
  });

  $(document).on('click', '#mciApplySuggestedSlug', function () {
    var sug = $(this).data('suggestedBase');
    if (!sug) return;
    $('#listing_slug').val(String(sug));
    slugAutoFromName = false;
    mciUpdateBusinessUrlPreview();
    $('#mciSlugStatus').html('<span class="text-success"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>Slug updated. Use \u201cCheck availability\u201d again to confirm.</span>');
  });

  $('#mciCheckSlugBtn').on('click', function () {
    if (!$('#listing_slug').length) return;
    var base = $('#listing_slug').val().trim();
    var city = $('#city').val().trim();
    var full = mciFullListingSlug(base, city);
    var $st = $('#mciSlugStatus');
    if (!$st.length) return;
    $st.empty();
    if (!mciSlugify(base)) {
      $st.html('<span class="text-danger">Enter a business name or slug first.</span>');
      return;
    }
    if (!full) {
      $st.html('<span class="text-danger">Could not build a URL slug.</span>');
      return;
    }
    var taken = existingSlugs.indexOf(full) !== -1;
    if (!taken) {
      $st.html('<span class="text-success"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>This URL is available.</span>');
      return;
    }
    var suggested = mciSuggestFreeBaseSlug(base);
    var msg = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>That URL is already taken.</span>';
    if (suggested) {
      msg += ' Suggested base slug: <button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="mciApplySuggestedSlug" data-suggested-base="' + String(suggested).replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '">' + suggested + '</button>';
    }
    $st.html(msg);
  });

  mciUpdateBusinessUrlPreview();

  // ── Public publish step: posting type + account fields ───────────
  function syncPostingType() {
    if (!$('.mci-posting-card').length) return;
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

  (function () {
    var $desc = $('#description');
    var $cnt  = $('#descCount');
    $desc.on('input', function () {
      $cnt.text($desc.val().length + ' / 1200');
    });
    $cnt.text($desc.val().length + ' / 1200');
  }());

  function updateSummary() {
    $('#summBizName').text($('#listing_title').val().trim() || '\u2014');
    var cat = $('#category').val() || 'No category';
    if (cat === '__request_new__') {
      var req = ($('#requested_category_name').val() || '').trim();
      cat = req || 'Requested category';
    }
    $('#summCategory').text(cat);
    $('#summCity').text($('#city').val().trim() || 'No city');
  }

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

  var faqTmpl = document.getElementById('faqItemTemplate');
  $('#addFaqBtn').on('click', function () {
    if (!faqTmpl || !faqTmpl.content) return;
    var clone = faqTmpl.content.firstElementChild.cloneNode(true);
    clone.setAttribute('data-faq-index', $('#faqItems .mci-faq-item').length);
    clone.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
    $('#faqItems').append(clone);
  });
  $(document).on('click', '.removeFaqBtn', function () {
    $(this).closest('.mci-faq-item').remove();
  });

  var cropperInstance = null;
  var cropTarget      = null;
  var cropModal       = new bootstrap.Modal(document.getElementById('cropModal'), {});

  $(document).on('click', '.mci-img-upload-tile .mci-img-upload-tile__preview, .mci-img-upload-tile .mci-img-upload-btn', function (e) {
    e.stopPropagation();
    $(this).closest('.mci-img-upload-tile').find('.mci-img-file-input').trigger('click');
  });

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

      if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }

      cropModal.show();
    };
    reader.readAsDataURL(file);
    this.value = '';
  });

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

  document.getElementById('cropModal').addEventListener('hidden.bs.modal', function () {
    if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
  });

  $('#applyCropBtn').on('click', function () {
    if (!cropperInstance || !cropTarget) return;

    var isSquare = cropTarget.aspect === 1;
    var outW = isSquare ? 600 : 1280;
    var outH = isSquare ? 600 : Math.round(1280 / cropTarget.aspect);

    var canvas = cropperInstance.getCroppedCanvas({ width: outW, height: outH, imageSmoothingQuality: 'high' });
    var dataUrl = canvas.toDataURL('image/jpeg', 0.88);

    cropTarget.hiddenInput.value = dataUrl;

    var $preview = cropTarget.$tile.find('.mci-img-upload-tile__preview, .mci-img-upload-tile__preview--banner').first();
    $preview.find('.mci-img-upload-tile__placeholder').hide();
    $preview.find('.mci-img-tile-result').remove();
    $('<img class="mci-img-tile-result" alt="preview" />').attr('src', dataUrl).appendTo($preview);

    cropTarget.$tile.addClass('has-image');

    if (cropTarget.isLogo) {
      $('#previewPhotoImg').attr('src', dataUrl).removeClass('d-none');
      $('#previewPhotoPlaceholder').addClass('d-none');
    }

    cropModal.hide();
  });

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
    dropArea.addEventListener('keydown', function (e) {
      // Make the upload tile keyboard accessible (Enter / Space).
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        fileInput.click();
      }
    });
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

  $('#applyPinBtn').on('click', function () {
    $('input[name="latitude"]').val($('#modalLatitude').val());
    $('input[name="longitude"]').val($('#modalLongitude').val());
  });

  $(document).on('click', '.mci-social-login-btn', function () {
    var provider = $(this).data('provider');
    alert(provider + ' sign-in will be connected when OAuth is configured. Your listing data is safe.');
  });

  var LS_KEY = 'mci_listing_preview';

  function collectFormData() {
    var data = {};

    $('#mciSubmitForm').find('input:not([type=file]):not([type=radio]):not([type=checkbox]), textarea, select').each(function () {
      if (this.name) data[this.name] = this.value;
    });

    $('#mciSubmitForm').find('input[type=radio]:checked').each(function () {
      data[this.name] = this.value;
    });

    var svcs = [];
    $('#serviceItems .mci-faq-item').each(function () {
      svcs.push({
        name: $(this).find('input[name="service_name[]"]').val() || '',
        desc: $(this).find('textarea[name="service_desc[]"]').val() || ''
      });
    });
    data._services = svcs;

    var faqs = [];
    $('#faqItems .mci-faq-item').each(function () {
      faqs.push({
        q: $(this).find('input[name="faq_question[]"]').val() || '',
        a: $(this).find('textarea[name="faq_answer[]"]').val() || ''
      });
    });
    data._faqs = faqs;

    ['img_logo','img_profile','img_banner'].forEach(function (n) {
      var v = $('input[name="' + n + '"]').val();
      if (v) data[n] = v;
    });

    // If user requested a new category, ensure listing payload uses the requested name.
    var isReq = ($('#is_category_request').val() === '1') || ($('#category').val() === '__request_new__');
    if (isReq) {
      var requestedName = ($('#requested_category_name').val() || '').trim();
      if (requestedName) data['category'] = requestedName;
    }

    return data;
  }

  function savePreview() {
    try { localStorage.setItem(LS_KEY, JSON.stringify(collectFormData())); } catch(e) {}
  }

  var saveTimer;
  $('#mciSubmitForm').on('input change', function () {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(savePreview, 600);
  });

  $('#previewListingBtn').on('click', function () {
    savePreview();
    // Prevent tabnabbing when opening preview in a new tab.
    window.open('/listing-preview/', '_blank', 'noopener,noreferrer');
  });

  $('#mciSubmitForm').on('submit', function (e) {
    // Ensure the latest form state is captured before any queue/save logic.
    try { savePreview(); } catch(e0) {}

    // If user chose "Request a new category...", create the CP category request via API.
    try {
      var isReq = ($('#is_category_request').val() === '1') || ($('#category').val() === '__request_new__');
      var requestedName = ($('#requested_category_name').val() || '').trim();
      var reason = ($('#category_request_reason').val() || '').trim();
      if (isReq && !requestedName) {
        alert('Please enter the requested category name.');
        return false;
      }

      if (isReq && requestedName) {
        // Create CP queue entry via API first.
        // If API auth isn't wired yet, fall back to localStorage queue (demo behavior).
        e.preventDefault();

        var payload = {
          requested_category_name: requestedName,
          category_request_reason: reason
        };

        var fallbackToLocalStorage = function () {
          var reqKey = 'mci_cp_category_requests';
          var arr = [];
          try { arr = JSON.parse(localStorage.getItem(reqKey) || '[]'); } catch (e2) { arr = []; }
          if (!Array.isArray(arr)) arr = [];

          var requester = ($('#mciRequesterLabel').val() || 'Subscriber').trim() || 'Subscriber';
          arr.push({
            requester: requester,
            category: requestedName,
            reason: reason,
            createdAt: new Date().toISOString(),
            status: 'pending'
          });

          localStorage.setItem(reqKey, JSON.stringify(arr));
        };

        try {
          fetch('/api/v1/subscriber/category-requests', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload)
          })
            .then(function (res) {
              if (!res.ok) {
                fallbackToLocalStorage();
                alert('Category request saved. (API auth not ready yet)');
              }
              return res.json().catch(function () { return {}; });
            })
            .then(function () {
              // Go show the super-admin request list.
              window.location.href = '/cp/categories/';
            })
            .catch(function () {
              fallbackToLocalStorage();
              alert('Category request saved. (Offline/demo fallback)');
              window.location.href = '/cp/categories/';
            });
        } catch (fetchErr) {
          fallbackToLocalStorage();
          alert('Category request saved. (Demo fallback)');
          window.location.href = '/cp/categories/';
        }

        return false;
      }
    } catch (e) {}

    // Clear saved preview only for real subscriber submissions.
    // CP super-admin anonymous submission needs LS_KEY to remain available.
    try {
      var postingType = ($('input[name="posting_type"]:checked').val() || $('input[name="posting_type"]').val() || 'registered').toString();
      if (postingType === 'registered') {
        localStorage.removeItem(LS_KEY);
      }
    } catch (e3) {}
  });
});
