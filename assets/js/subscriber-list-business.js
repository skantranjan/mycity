/**
 * 7-step listing wizard
 * (loads after jQuery, Bootstrap, Cropper.js)
 *
 * Relies on window._mciSubmitContext, window._mciSubmitRedirect, window._mciSubmitBtnText
 * injected by each entry-point page.
 */
$(function () {
  var TOTAL_STEPS = 8;
  var currentStep = 1;
  var submitContext = (window._mciSubmitContext || 'guest').toString();
  var submitRedirect = (window._mciSubmitRedirect || '/').toString();

  // ── Context banner (Step 7) ──────────────────────────────────────
  var BANNER_CFG = {
    cp_admin:   { cls: 'alert-primary',  icon: 'bi-lightning-charge-fill', text: 'Goes live immediately — this listing will be visible to all users right away.' },
    subscriber: { cls: 'alert-light border', icon: 'bi-person-check-fill', text: 'Your listing will be submitted for review — our team will check it before it goes live.' },
    guest:      { cls: 'alert-warning',  icon: 'bi-info-circle-fill',      text: 'Your listing will be reviewed before going live. <a href="/login/">Log in</a> or <a href="/register/">create an account</a> to manage it later.' }
  };
  function renderContextBanner() {
    var $b = $('#mciContextBanner');
    if (!$b.length) return;
    var cfg = BANNER_CFG[submitContext] || BANNER_CFG['guest'];
    $b.removeClass('d-none alert-primary alert-light border alert-warning')
      .addClass('alert ' + cfg.cls)
      .html('<i class="bi ' + cfg.icon + ' me-2" aria-hidden="true"></i>' + cfg.text);
  }
  renderContextBanner();

  // ── Step navigation ──────────────────────────────────────────────
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
      $(this).toggleClass('is-active', s === n).toggleClass('is-done', s < n);
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
    if (n === TOTAL_STEPS) { updateSummary(); updatePreview(); }
    // Close tag suggestions when navigating
    $('#tagSuggestions').addClass('d-none');
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

  // ── API: load categories & tags ──────────────────────────────────
  var categoryTree = [];
  function mciLoadCategories() {
    fetch('/api/v1/public/categories', { credentials: 'include' })
      .then(function (r) { return r.ok ? r.json() : { categories: [] }; })
      .then(function (data) {
        categoryTree = data.categories || [];
        var $sel = $('#category');
        $sel.empty().append('<option value="">Choose a category\u2026</option>');
        categoryTree.forEach(function (c) {
          $sel.append('<option value="' + c.id + '">' + $('<div>').text(c.name).html() + '</option>');
        });
        $sel.append('<option value="__request_new__">Request a new category\u2026</option>');
      })
      .catch(function () {
        // Leave placeholder option in place on error
      });
  }

  function mciRenderSubcategories(categoryId) {
    var $cont = $('#subcategoryContainer');
    $cont.empty().addClass('d-none');
    if (!categoryId) return;
    var parent = categoryTree.find(function (c) { return c.id === categoryId; });
    if (!parent || !parent.children || !parent.children.length) return;
    $cont.append('<div class="form-text mb-2">Subcategories <span class="text-muted fw-normal">(optional)</span></div>');
    $cont.append('<div class="d-flex flex-wrap gap-2" id="subcategoryCheckboxes"></div>');
    var $boxes = $cont.find('#subcategoryCheckboxes');
    parent.children.forEach(function (child) {
      var safeId = 'subcat_' + child.id;
      var label = $('<div>').text(child.name).html();
      $boxes.append(
        '<div class="form-check form-check-inline me-0">' +
        '<input class="form-check-input mci-subcat-check" type="checkbox" id="' + safeId + '" value="' + child.id + '" />' +
        '<label class="form-check-label" for="' + safeId + '">' + label + '</label>' +
        '</div>'
      );
    });
    $cont.removeClass('d-none');
  }

  // ── Tag typeahead ─────────────────────────────────────────────────
  var allTags = [];        // [{id, name, slug}] from API — id=null for new tags
  var selectedTags = [];   // [{id, name}] — id may be null for free-form new tags

  function mciLoadTags() {
    fetch('/api/v1/public/tags', { credentials: 'include' })
      .then(function (r) { return r.ok ? r.json() : { tags: [] }; })
      .then(function (data) { allTags = data.tags || []; })
      .catch(function () {});
  }

  function mciTagIdsPayload() {
    return selectedTags.map(function (t) { return t.id; }).filter(function (id) { return id !== null; });
  }

  function mciTagNamesPayload() {
    return selectedTags.map(function (t) { return t.name; });
  }

  function mciRenderTagChips() {
    // Remove all existing chips (leave input in place)
    $('#tagSelectedArea .mci-tag-chip-selected').remove();
    var $input = $('#tagTypeahead');
    selectedTags.forEach(function (t, i) {
      var $chip = $('<span class="mci-tag-chip-selected badge bg-dark d-inline-flex align-items-center gap-1 me-1"></span>')
        .text(t.name)
        .attr('data-tag-idx', i);
      var $x = $('<button type="button" class="btn-close btn-close-white mci-tag-remove-btn" aria-label="Remove tag" style="font-size:0.6rem"></button>')
        .attr('data-tag-idx', i);
      $chip.append($x);
      $chip.insertBefore($input);
    });
    $('#tagIdsHidden').val(JSON.stringify(mciTagIdsPayload()));
  }

  function mciAddTag(name, id) {
    name = (name || '').trim();
    if (!name) return;
    // Prevent duplicates (case-insensitive)
    var lower = name.toLowerCase();
    for (var k = 0; k < selectedTags.length; k++) {
      if (selectedTags[k].name.toLowerCase() === lower) return;
    }
    selectedTags.push({ id: id || null, name: name });
    mciRenderTagChips();
  }

  function mciShowTagSuggestions(query) {
    var $ul = $('#tagSuggestions');
    $ul.empty();
    if (!query) { $ul.addClass('d-none'); return; }
    var lower = query.toLowerCase();
    var matches = allTags.filter(function (t) {
      return t.name.toLowerCase().indexOf(lower) !== -1;
    }).slice(0, 8);

    if (matches.length === 0) {
      $ul.addClass('d-none');
      return;
    }
    matches.forEach(function (t) {
      var $li = $('<li class="px-3 py-1" style="cursor:pointer;"></li>').text(t.name)
        .attr('data-tag-id', t.id)
        .attr('data-tag-name', t.name)
        .on('mousedown', function (e) {
          e.preventDefault(); // prevent blur from hiding suggestions first
          mciAddTag(t.name, t.id);
          $('#tagTypeahead').val('').focus();
          $ul.addClass('d-none');
        });
      $ul.append($li);
    });
    $ul.removeClass('d-none');
  }

  $('#tagSelectedArea').on('click', function () { $('#tagTypeahead').focus(); });

  $('#tagTypeahead').on('input', function () {
    mciShowTagSuggestions($(this).val().trim());
  });

  $('#tagTypeahead').on('keydown', function (e) {
    var val = $(this).val();
    // Comma, semicolon, or Enter → add tag
    if (e.key === ',' || e.key === ';' || e.key === 'Enter') {
      e.preventDefault();
      var trimmed = val.replace(/[,;]$/, '').trim();
      if (trimmed) {
        // Check if it matches an existing tag
        var match = allTags.find(function (t) { return t.name.toLowerCase() === trimmed.toLowerCase(); });
        mciAddTag(trimmed, match ? match.id : null);
      }
      $(this).val('');
      $('#tagSuggestions').addClass('d-none');
      return;
    }
    // Backspace on empty input removes last tag
    if (e.key === 'Backspace' && !val) {
      if (selectedTags.length) {
        selectedTags.pop();
        mciRenderTagChips();
      }
    }
  });

  $('#tagTypeahead').on('blur', function () {
    // Small delay to allow mousedown on suggestions
    setTimeout(function () { $('#tagSuggestions').addClass('d-none'); }, 150);
  });

  $(document).on('click', '.mci-tag-remove-btn', function (e) {
    e.stopPropagation();
    var idx = parseInt($(this).attr('data-tag-idx'));
    selectedTags.splice(idx, 1);
    mciRenderTagChips();
  });

  mciLoadCategories();
  mciLoadTags();

  // ── Category → update category_id hidden + subcategories ────────
  $('#category').on('change input', function () {
    var val = $(this).val();
    var isReq = val === '__request_new__';
    $('#is_category_request').val(isReq ? '1' : '0');
    $('#categoryRequestFields').toggleClass('d-none', !isReq);
    $('#requested_category_name').prop('required', !!isReq);
    $('#category_request_reason').prop('required', !!isReq);

    var catId = !isReq && val !== '' ? parseInt(val) : null;
    $('#categoryIdHidden').val(catId || '');
    mciRenderSubcategories(catId);
    updatePreview();
    if (currentStep === TOTAL_STEPS) updateSummary();
  });
  $('#requested_category_name').on('input change', function () {
    updatePreview();
    if (currentStep === TOTAL_STEPS) updateSummary();
  });

  // ── Preview + summary ────────────────────────────────────────────
  function getCategoryLabel() {
    var val = $('#category').val() || '';
    if (val === '__request_new__') {
      return ($('#requested_category_name').val() || '').trim() || 'Requested category';
    }
    var $opt = $('#category option:selected');
    return $opt.length ? $opt.text() : (val || 'Category');
  }

  function updatePreview() {
    var name = $('#listing_title').val().trim() || 'Your business name';
    var cat  = getCategoryLabel();
    var tag  = $('#tagline').val().trim();
    var desc = $('#description').val().trim();
    var city = $('input[name="city[]"]').first().val().trim();
    var ph   = $('input[name="phone[]"]').first().val().trim();
    var web  = $('input[name="website[]"]').first().val().trim();
    $('#previewName').text(name);
    $('#previewCategory').text(cat);
    $('#previewTagline').text(tag || '').toggle(tag.length > 0);
    var descShort = desc.length > 140 ? desc.substring(0, 140) + '\u2026' : desc;
    $('#previewDesc').text(descShort || 'Your description will appear here.');
    $('#previewCity').find('span').text(city || '\u2014');
    $('#previewPhone').find('span').text(ph || '\u2014');
    $('#previewWebsite').find('span').text(web || '\u2014');
  }

  function updateSummary() {
    $('#summBizName').text($('#listing_title').val().trim() || '\u2014');
    $('#summCategory').text(getCategoryLabel() || 'No category');
    $('#summCity').text($('input[name="city[]"]').first().val().trim() || 'No city');
  }

  $('#listing_title, #tagline, #description').on('input change', updatePreview);
  $(document).on('input change', 'input[name="city[]"], input[name="phone[]"], input[name="website[]"]', updatePreview);
  updatePreview();

  // ── Description counter ──────────────────────────────────────────
  (function () {
    var $d = $('#description'), $c = $('#descCount');
    $d.on('input', function () { $c.text($d.val().length + ' / 1200'); });
    $c.text($d.val().length + ' / 1200');
  }());

  // ── URL slug ─────────────────────────────────────────────────────
  var existingSlugs = [];
  try {
    var slugJsonEl = document.getElementById('mciExistingSlugs');
    if (slugJsonEl && slugJsonEl.textContent) {
      existingSlugs = JSON.parse(slugJsonEl.textContent);
    }
  } catch (eSl) {}

  var slugAutoFromName = true;

  function mciSlugify(str) {
    if (!str) return '';
    return str.toString().toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }

  function mciFullListingSlug(baseSlug, cityVal) {
    var b = mciSlugify(baseSlug), c = mciSlugify(cityVal || '');
    if (!b) return '';
    return c ? b + '-' + c : b;
  }

  function mciUpdateBusinessUrlPreview() {
    var base = ($('#listing_slug').val() || '').trim();
    var city = ($('input[name="city[]"]').first().val() || '').trim();
    var full = mciFullListingSlug(base, city);
    var $wrap = $('#mciBusinessUrlPreview');
    if (!$wrap.length) return;
    var origin = ($wrap.data('origin') || window.location.origin || 'https://mycityinfo.com').toString().replace(/\/$/, '');
    $('#mciBusinessUrlPreviewText').text(full ? origin + '/business/' + full + '/' : '\u2014');
  }

  function mciSuggestFreeBaseSlug(baseName) {
    var b = mciSlugify(baseName);
    var city = ($('input[name="city[]"]').first().val() || '').trim();
    for (var i = 0; i < 100; i++) {
      var trial = i === 0 ? b : (b + '-' + (i + 1));
      var full = mciFullListingSlug(trial, city);
      if (full && existingSlugs.indexOf(full) === -1) return trial;
    }
    return b + '-' + Date.now();
  }

  $('#listing_title').on('input', function () {
    if (!slugAutoFromName) return;
    var s = mciSlugify($(this).val());
    $('#listing_slug').val(s);
    mciUpdateBusinessUrlPreview();
  });

  $('#listing_slug').on('input', function () {
    var manual = mciSlugify($(this).val());
    $(this).val(manual);
    slugAutoFromName = !manual || manual === mciSlugify($('#listing_title').val());
    mciUpdateBusinessUrlPreview();
  });

  $(document).on('input change', 'input[name="city[]"]', function () {
    if ($(this).closest('.mci-branch-block').data('branch-index') === 0) {
      mciUpdateBusinessUrlPreview();
    }
  });

  $(document).on('click', '#mciApplySuggestedSlug', function () {
    var sug = $(this).data('suggestedBase');
    if (!sug) return;
    $('#listing_slug').val(String(sug));
    slugAutoFromName = false;
    mciUpdateBusinessUrlPreview();
    $('#mciSlugStatus').html('<span class="text-success"><i class="bi bi-check-circle me-1"></i>Slug updated.</span>');
  });

  $('#mciCheckSlugBtn').on('click', function () {
    var base = ($('#listing_slug').val() || '').trim();
    var city = ($('input[name="city[]"]').first().val() || '').trim();
    var full = mciFullListingSlug(base, city);
    var $st = $('#mciSlugStatus');
    $st.empty();
    if (!mciSlugify(base)) { $st.html('<span class="text-danger">Enter a slug first.</span>'); return; }
    var taken = existingSlugs.indexOf(full) !== -1;
    if (!taken) { $st.html('<span class="text-success"><i class="bi bi-check-circle me-1"></i>Available.</span>'); return; }
    var suggested = mciSuggestFreeBaseSlug(base);
    var msg = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>URL already taken.</span>';
    if (suggested) {
      msg += ' Try: <button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="mciApplySuggestedSlug" data-suggested-base="' + String(suggested).replace(/"/g, '&quot;') + '">' + suggested + '</button>';
    }
    $st.html(msg);
  });

  mciUpdateBusinessUrlPreview();

  // ── Public posting type + account fields ─────────────────────────
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

  // ── Products: add / remove ────────────────────────────────────────
  var prodTmpl = document.getElementById('productItemTemplate');
  $('#addProductBtn').on('click', function () {
    if (!prodTmpl || !prodTmpl.content) return;
    var clone = prodTmpl.content.firstElementChild.cloneNode(true);
    clone.setAttribute('data-item-index', $('#productItems .mci-item-row').length);
    clone.querySelectorAll('input:not([type=hidden]), textarea').forEach(function (el) { el.value = ''; });
    $('#productItems').append(clone);
  });
  $(document).on('click', '.removeProductBtn', function () {
    $(this).closest('.mci-item-row').remove();
  });

  // ── Services: add / remove ────────────────────────────────────────
  var svcTmpl = document.getElementById('serviceItemTemplate');
  $('#addServiceBtn').on('click', function () {
    if (!svcTmpl || !svcTmpl.content) return;
    var clone = svcTmpl.content.firstElementChild.cloneNode(true);
    clone.setAttribute('data-item-index', $('#serviceItems .mci-item-row').length);
    clone.querySelectorAll('input:not([type=hidden]), textarea').forEach(function (el) { el.value = ''; });
    $('#serviceItems').append(clone);
  });
  $(document).on('click', '.removeServiceBtn', function () {
    $(this).closest('.mci-item-row').remove();
  });

  // ── FAQs: add / remove ────────────────────────────────────────────
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

  // ── Cropper (logo / profile / banner) ────────────────────────────
  var cropperInstance = null;
  var cropTarget      = null;
  var cropModal       = new bootstrap.Modal(document.getElementById('cropModal'), {});

  // ── Deferred upload slot map ──────────────────────────────────────
  // Holds File/Blob objects to be uploaded after business creation.
  // Key: 'logo' | 'banner' | 'profile' | 'gallery_N' | 'product_N' | 'product_<uuid>' | 'service_N' | 'service_<uuid>'
  // Value: { file: File|Blob, type: string, subtype?: string }
  var pendingUploads = new Map();

  window.addEventListener('beforeunload', function (e) {
    if (pendingUploads.size > 0) {
      e.preventDefault();
      e.returnValue = 'You have images selected that haven\'t been uploaded yet. Leave anyway?';
    }
  });

  $(document).on('click', '.mci-img-upload-tile .mci-img-upload-tile__preview, .mci-img-upload-tile .mci-img-upload-btn', function (e) {
    e.stopPropagation();
    $(this).closest('.mci-img-upload-tile').find('.mci-img-file-input').trigger('click');
  });

  $(document).on('change', '.mci-img-file-input', function () {
    var file = this.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    var $tile  = $(this).closest('.mci-img-upload-tile');
    var type   = $tile.data('type');
    var aspect = parseFloat($tile.data('aspect')) || 1;
    var labels = { logo: 'Crop logo', profile: 'Crop profile photo', banner: 'Crop banner image' };
    $('#cropModalTitle').text(labels[type] || 'Crop image');
    cropTarget = { $tile: $tile, hiddenInput: $tile.find('input[type="hidden"]')[0], type: type, isLogo: type === 'logo', aspect: aspect };
    var reader = new FileReader();
    reader.onload = function (ev) {
      document.getElementById('cropModalImage').src = ev.target.result;
      if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
      cropModal.show();
    };
    reader.readAsDataURL(file);
    this.value = '';
  });

  document.getElementById('cropModal').addEventListener('shown.bs.modal', function () {
    if (cropperInstance) { cropperInstance.destroy(); }
    cropperInstance = new Cropper(document.getElementById('cropModalImage'), {
      aspectRatio: cropTarget ? cropTarget.aspect : 1,
      viewMode: 1, autoCropArea: 0.9, responsive: true, background: false, guides: true, center: true
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
    var type = cropTarget.type || 'logo';

    canvas.toBlob(function (blob) {
      // Store in slot map — upload happens at submit time
      pendingUploads.set(type, { file: blob, type: type });

      // Show local preview (no server call)
      var dataUrl = canvas.toDataURL('image/jpeg', 0.88);
      var $preview = cropTarget.$tile.find('.mci-img-upload-tile__preview').first();
      $preview.find('.mci-img-upload-tile__placeholder').hide();
      $preview.find('.mci-img-tile-result').remove();
      $('<img class="mci-img-tile-result" alt="preview" />').attr('src', dataUrl).appendTo($preview);
      cropTarget.$tile.addClass('has-image');
      cropTarget.hiddenInput.value = '';  // clear any stale server path
      if (cropTarget.isLogo) {
        $('#previewPhotoImg').attr('src', dataUrl).removeClass('d-none');
        $('#previewPhotoPlaceholder').addClass('d-none');
      }
    }, 'image/jpeg', 0.88);
    cropModal.hide();
  });

  // ── Item image upload (products / services) ──────────────────────
  $(document).on('click', '.mci-item-img-btn', function () {
    $(this).closest('.mci-item-row').find('.mci-item-file-input').trigger('click');
  });

  $(document).on('change', '.mci-item-file-input', function () {
    var file = this.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    var $row    = $(this).closest('.mci-item-row');
    var $input  = $row.find('input[name$="_image_path[]"]');
    var $label  = $row.find('.mci-item-img-name');
    var isProduct = $row.closest('#productItems').length > 0;
    var subtype   = isProduct ? 'products' : 'services';
    // Key by current DOM index — will be remapped to server UUID after create
    var domIndex  = $row.closest('#productItems, #serviceItems').find('.mci-item-row').index($row);
    var slotKey   = (isProduct ? 'product_' : 'service_') + domIndex;
    pendingUploads.set(slotKey, { file: file, type: 'item_image', subtype: subtype });
    $input.val('');
    $label.text(file.name);
    this.value = '';
  });

  // ── Gallery drop zone ─────────────────────────────────────────────
  var dropArea  = document.getElementById('dropFiles');
  var fileInput = document.getElementById('fileInputImages');
  var preview   = document.getElementById('imagePreview');

  function uploadGalleryFiles(files) {
    if (!files || !files.length) return;
    // Clear any previous gallery slots
    Array.from(pendingUploads.keys()).filter(function (k) {
      return k.startsWith('gallery_');
    }).forEach(function (k) { pendingUploads.delete(k); });

    preview.innerHTML = '';
    for (var i = 0; i < Math.min(files.length, 12); i++) {
      (function (f, idx) {
        if (!f.type || !f.type.startsWith('image/')) return;
        pendingUploads.set('gallery_' + idx, { file: f, type: 'gallery' });
        // Show local preview
        var url = URL.createObjectURL(f);
        var wrap = document.createElement('div');
        wrap.className = 'mci-photo-thumb';
        var img = document.createElement('img');
        img.src = url; img.alt = f.name;
        wrap.appendChild(img);
        preview.appendChild(wrap);
        if (idx === 0) {
          $('#previewPhotoImg').attr('src', url).attr('alt', f.name).removeClass('d-none');
          $('#previewPhotoPlaceholder').addClass('d-none');
        }
      })(files[i], i);
    }
    $('#galleryPathsHidden').val('');  // clear hidden field — paths set via PATCH after submit
  }

  if (dropArea && fileInput) {
    dropArea.addEventListener('click', function () { fileInput.click(); });
    dropArea.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
    });
    ['dragover','dragenter'].forEach(function (ev) {
      dropArea.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); dropArea.classList.add('is-over'); });
    });
    ['dragleave','drop'].forEach(function (ev) {
      dropArea.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); dropArea.classList.remove('is-over'); });
    });
    dropArea.addEventListener('drop', function (e) {
      e.preventDefault();
      var dt = new DataTransfer();
      Array.from(e.dataTransfer.files).forEach(function (f) { dt.items.add(f); });
      fileInput.files = dt.files;
      uploadGalleryFiles(fileInput.files);
    });
    fileInput.addEventListener('change', function () { uploadGalleryFiles(fileInput.files); });
  }

  // ── Location cascade: country → state ────────────────────────────
  var _mciCountryOptions = []; // cache from API

  function mciLoadCountries() {
    fetch('/api/v1/locations/countries')
      .then(function (r) { return r.ok ? r.json() : { countries: [] }; })
      .then(function (data) {
        _mciCountryOptions = data.countries || [];
        $('.mci-country-select').each(function () {
          mciPopulateCountrySelect($(this));
        });
      })
      .catch(function () { /* silent — user can type */ });
  }

  function mciPopulateCountrySelect($sel) {
    var current = $sel.val() || 'India';
    $sel.empty().append('<option value="">Select country</option>');
    _mciCountryOptions.forEach(function (c) {
      $sel.append($('<option>').val(c).text(c));
    });
    $sel.append('<option value="other">Other (type below)</option>');
    if (current) { $sel.val(current); }
  }

  function mciLoadStates($branchBlock, country) {
    var $stateSel = $branchBlock.find('.mci-state-select');
    $stateSel.empty().append('<option value="">Loading\u2026</option>');
    fetch('/api/v1/locations/states?country=' + encodeURIComponent(country))
      .then(function (r) { return r.ok ? r.json() : { states: [] }; })
      .then(function (data) {
        var states = data.states || [];
        $stateSel.empty().append('<option value="">Select or type below</option>');
        states.forEach(function (s) {
          $stateSel.append($('<option>').val(s).text(s));
        });
        $stateSel.append('<option value="other">Other (type below)</option>');
      })
      .catch(function () {
        $stateSel.empty().append('<option value="">Select or type below</option>');
      });
  }

  /**
   * Merge country_other / state_select values into the canonical country[] / state[]
   * hidden inputs before buildPayload() reads them.
   */
  function mciSyncLocationFields() {
    $('#branchList .mci-branch-block').each(function () {
      var $b = $(this);

      // Country
      var $countrySel    = $b.find('.mci-country-select');
      var $countryOther  = $b.find('input[name="country_other[]"]');
      var $countryHidden = $b.find('input[name="country[]"]');
      var countryVal = $countrySel.val();
      if (countryVal === 'other' || countryVal === '') {
        var typed = $countryOther.val().trim();
        $countryHidden.val(typed !== '' ? typed : 'India');
      } else {
        $countryHidden.val(countryVal || 'India');
      }

      // State
      var $stateSel   = $b.find('.mci-state-select');
      var $stateInput = $b.find('input[name="state[]"]');
      var stateSelVal = $stateSel.val();
      if (stateSelVal && stateSelVal !== 'other') {
        $stateInput.val(stateSelVal);
      }
      // If "other" or empty, leave $stateInput as whatever the user typed
    });
  }

  // Wire country→state cascade on country select change
  $(document).on('change', '.mci-country-select', function () {
    var $sel = $(this);
    var $block = $sel.closest('.mci-branch-block');
    var $countryOther = $block.find('input[name="country_other[]"]');
    var val = $sel.val();

    if (val === 'other') {
      $countryOther.removeClass('d-none');
      $block.find('.mci-state-select').empty()
        .append('<option value="">Select or type below</option>');
    } else {
      $countryOther.addClass('d-none').val('');
      if (val) { mciLoadStates($block, val); }
    }
  });

  // Wire state select → state text input sync
  $(document).on('change', '.mci-state-select', function () {
    var $sel = $(this);
    var $block = $sel.closest('.mci-branch-block');
    var $stateInput = $block.find('input[name="state[]"]');
    var val = $sel.val();
    if (val && val !== 'other') {
      $stateInput.val(val);
    } else if (val === 'other') {
      $stateInput.val('').focus();
    }
  });

  // Load countries on page load
  mciLoadCountries();

  // ── Multi-branch: add / remove ────────────────────────────────────
  var branchTmpl = document.getElementById('branchTemplate');
  $('#addBranchBtn').on('click', function () {
    if (!branchTmpl || !branchTmpl.content) return;
    var idx = $('#branchList .mci-branch-block').length;
    var clone = branchTmpl.content.firstElementChild.cloneNode(true);
    clone.setAttribute('data-branch-index', idx);
    clone.innerHTML = clone.innerHTML.replace(/__INDEX__/g, idx).replace(/__NUM__/g, idx + 1);
    $('#branchList').append(clone);
    // Wire up country/state cascade for the new branch
    var $newBlock = $('#branchList .mci-branch-block').last();
    if (_mciCountryOptions.length > 0) {
      mciPopulateCountrySelect($newBlock.find('.mci-country-select'));
    }
  });
  $(document).on('click', '.mci-remove-branch-btn', function () {
    $(this).closest('.mci-branch-block').remove();
    // Renumber remaining branch headers
    $('#branchList .mci-branch-block').each(function (i) {
      var label = i === 0 ? 'Primary location' : 'Branch ' + (i + 1);
      $(this).find('.fw-semibold.small').first().text(label);
      $(this).attr('data-branch-index', i);
    });
  });

  // ── Map pin modal ─────────────────────────────────────────────────
  var mapPinBranchIndex = 0;
  $(document).on('click', '.mci-map-pin-btn', function () {
    mapPinBranchIndex = parseInt($(this).data('branch-index')) || 0;
  });
  $('#applyPinBtn').on('click', function () {
    var $block = $('#branchList .mci-branch-block[data-branch-index="' + mapPinBranchIndex + '"]');
    if ($block.length) {
      $block.find('input[name="latitude[]"]').val($('#modalLatitude').val());
      $block.find('input[name="longitude[]"]').val($('#modalLongitude').val());
    } else {
      // fallback
      $('input[name="latitude[]"]').first().val($('#modalLatitude').val());
      $('input[name="longitude[]"]').first().val($('#modalLongitude').val());
    }
  });

  // ── Social login placeholder ──────────────────────────────────────
  $(document).on('click', '.mci-social-login-btn', function () {
    alert($(this).data('provider') + ' sign-in will be connected when OAuth is configured.');
  });

  // ── Preview listing button ────────────────────────────────────────
  $('#previewListingBtn').on('click', function () {
    window.open('/listing-preview/', '_blank', 'noopener,noreferrer');
  });

  // ── Build hours object ────────────────────────────────────────────
  function buildHoursObject() {
    var days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    var obj = {};
    days.forEach(function (day) {
      var open = $('input[name="hours[open][' + day + ']"]').is(':checked');
      obj[day] = {
        open:        open,
        slot1_start: $('select[name="hours[slot1_start][' + day + ']"]').val() || '',
        slot1_end:   $('select[name="hours[slot1_end][' + day + ']"]').val()   || '',
        slot2_start: $('select[name="hours[slot2_start][' + day + ']"]').val() || '',
        slot2_end:   $('select[name="hours[slot2_end][' + day + ']"]').val()   || '',
      };
    });
    return obj;
  }

  // ── Build submit payload ──────────────────────────────────────────
  function buildPayload() {
    mciSyncLocationFields();
    var products = [];
    $('#productItems .mci-item-row').each(function () {
      var name = $(this).find('input[name="product_name[]"]').val().trim();
      if (!name) return;
      products.push({
        name:        name,
        description: $(this).find('textarea[name="product_desc[]"]').val().trim() || '',
        price_min:   $(this).find('input[name="product_price_min[]"]').val() || null,
        price_max:   $(this).find('input[name="product_price_max[]"]').val() || null,
        price_unit:  'INR',
        image_path:  ''   // deferred — set via PATCH after create
      });
    });

    var services = [];
    $('#serviceItems .mci-item-row').each(function () {
      var name = $(this).find('input[name="service_name[]"]').val().trim();
      if (!name) return;
      services.push({
        name:        name,
        description: $(this).find('textarea[name="service_desc[]"]').val().trim() || '',
        price_min:   $(this).find('input[name="service_price_min[]"]').val() || null,
        price_max:   $(this).find('input[name="service_price_max[]"]').val() || null,
        price_unit:  'INR',
        image_path:  ''   // deferred — set via PATCH after create
      });
    });

    var faqs = [];
    $('#faqItems .mci-faq-item').each(function () {
      var q = $(this).find('input[name="faq_question[]"]').val().trim();
      if (!q) return;
      faqs.push({ question: q, answer: $(this).find('textarea[name="faq_answer[]"]').val().trim() || '' });
    });

    var subcategoryIds = [];
    $('#subcategoryContainer .mci-subcat-check:checked').each(function () {
      subcategoryIds.push(parseInt($(this).val()));
    });

    var tagIds = mciTagIdsPayload();

    // Collect all branches
    var branches = [];
    $('#branchList .mci-branch-block').each(function () {
      var $b = $(this);
      branches.push({
        branch_label:    $b.find('input[name="branch_label[]"]').val().trim(),
        full_address:    $b.find('input[name="full_address[]"]').val().trim(),
        address_line2:   $b.find('input[name="address_line2[]"]').val().trim(),
        city:            $b.find('input[name="city[]"]').val().trim(),
        state:           $b.find('input[name="state[]"]').val().trim(),
        country:         $b.find('input[name="country[]"]').val().trim() || 'India',
        pincode:         $b.find('input[name="pincode[]"]').val().trim(),
        latitude:        $b.find('input[name="latitude[]"]').val().trim(),
        longitude:       $b.find('input[name="longitude[]"]').val().trim(),
        phone:           $b.find('input[name="phone[]"]').val().trim(),
        phone_secondary: $b.find('input[name="phone_secondary[]"]').val().trim(),
        whatsapp:        $b.find('input[name="whatsapp[]"]').val().trim(),
        email_contact:   $b.find('input[name="email_contact[]"]').val().trim(),
        website:         $b.find('input[name="website[]"]').val().trim()
      });
    });
    // Primary branch carries social links + hours
    var primaryBranch = branches[0] || {};
    primaryBranch.social_links = {
      facebook:         $('#social_facebook').val().trim(),
      instagram:        $('#social_instagram').val().trim(),
      x:                $('#social_x').val().trim(),
      youtube:          $('#social_youtube').val().trim(),
      linkedin:         $('#social_linkedin').val().trim(),
      tiktok:           $('#social_tiktok').val().trim(),
      pinterest:        $('#social_pinterest').val().trim(),
      telegram:         $('#social_telegram').val().trim(),
      threads:          $('#social_threads').val().trim(),
      snapchat:         $('#social_snapchat').val().trim(),
      whatsapp_channel: $('#social_whatsapp_channel').val().trim()
    };
    primaryBranch.hours = buildHoursObject();

    var payload = {
      context:  submitContext,
      group: {
        name:             $('#listing_title').val().trim(),
        slug:             $('#listing_slug').val().trim(),
        tagline:          $('#tagline').val().trim(),
        description:      $('#description').val().trim(),
        established_year: $('#established_year').val() ? parseInt($('#established_year').val()) : null,
        category_id:      parseInt($('#categoryIdHidden').val()) || 0,
        subcategory_ids:  subcategoryIds,
        tag_ids:          tagIds,
        tag_names:        mciTagNamesPayload(),
        price_range:      $('#price_range').val() || '',
        video_url:        $('#video_url').val().trim(),
        logo_path:        '',   // deferred — set via PATCH after create
        profile_path:     '',   // deferred — set via PATCH after create
        banner_path:      ''    // deferred — set via PATCH after create
      },
      branch:   primaryBranch,
      branches: branches,
      products:      products,
      services:      services,
      faqs:          faqs,
      gallery_paths: []   // deferred — set via PATCH after create
    };

    // Guest with account
    var postingType = $('input[name="posting_type"]:checked').val() || $('input[name="posting_type"]').val() || 'registered';
    payload.posting_type = postingType;
    if (submitContext === 'guest' && postingType === 'registered') {
      payload.account = {
        email:    $('#email').val().trim(),
        password: $('#password').val()
      };
    }

    return payload;
  }

  // ── Form submit ───────────────────────────────────────────────────
  $('#mciSubmitForm').on('submit', function (e) {
    e.preventDefault();

    var $btn     = $('#submitBtn');
    var btnLabel = '<i class="bi bi-check2-circle me-2" aria-hidden="true"></i>' + (window._mciSubmitBtnText || 'Submit');

    function setPhase(msg) {
      $btn.prop('disabled', true).html(
        '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + msg
      );
    }
    function resetBtn() {
      $btn.prop('disabled', false).html(btnLabel);
    }

    setPhase('Submitting\u2026');
    var payload = buildPayload();

    // ── Phase 1: Create business ─────────────────────────────────────
    fetch('/api/v1/businesses', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
    .then(function (res) {
      if (!res.ok || !res.data.ok) {
        var msg = (res.data && res.data.error) ? res.data.error : 'Submission failed. Please try again.';
        alert(msg);
        resetBtn();
        return;
      }

      var groupId    = res.data.id;
      var productIds = res.data.product_ids || [];   // insertion-order UUIDs
      var serviceIds = res.data.service_ids || [];

      try { localStorage.removeItem('mci_listing_preview'); } catch (e2) {}

      // ── Remap temp product/service keys → server UUIDs ───────────────
      // Products: collect slot keys in Map insertion order (matches DOM order)
      var productKeys = Array.from(pendingUploads.keys()).filter(function (k) {
        return /^product_\d+$/.test(k);
      });
      productKeys.forEach(function (tempKey, idx) {
        var realId = productIds[idx];
        if (!realId) return;
        var val = pendingUploads.get(tempKey);
        pendingUploads.delete(tempKey);
        pendingUploads.set('product_' + realId, val);
      });

      var serviceKeys = Array.from(pendingUploads.keys()).filter(function (k) {
        return /^service_\d+$/.test(k);
      });
      serviceKeys.forEach(function (tempKey, idx) {
        var realId = serviceIds[idx];
        if (!realId) return;
        var val = pendingUploads.get(tempKey);
        pendingUploads.delete(tempKey);
        pendingUploads.set('service_' + realId, val);
      });

      // ── Phase 2: Upload pending images ───────────────────────────────
      var uploadEntries = Array.from(pendingUploads.entries());
      var total = uploadEntries.length;

      if (total === 0) {
        pendingUploads.clear();
        window.location.href = submitRedirect;
        return;
      }

      var uploaded = 0;
      var collectedPaths = {};  // slotKey → server path

      function uploadNext(idx) {
        if (idx >= uploadEntries.length) {
          doPatch(collectedPaths);
          return;
        }
        var slotKey = uploadEntries[idx][0];
        var entry   = uploadEntries[idx][1];
        setPhase('Uploading images\u2026 (' + (uploaded + 1) + '/' + total + ')');

        var fd = new FormData();
        fd.append('type', entry.type);
        fd.append('business_id', groupId);
        if (entry.subtype) fd.append('subtype', entry.subtype);
        fd.append('file', entry.file, slotKey + '.jpg');

        fetch('/api/v1/upload/image', { method: 'POST', credentials: 'include', body: fd })
          .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
          .then(function (data) {
            if (data.path) collectedPaths[slotKey] = data.path;
            uploaded++;
            uploadNext(idx + 1);
          })
          .catch(function () {
            // Non-fatal: skip failed upload, continue with rest
            uploaded++;
            uploadNext(idx + 1);
          });
      }

      uploadNext(0);

      // ── Phase 3: PATCH image paths ───────────────────────────────────
      function doPatch(paths) {
        setPhase('Finalising\u2026');

        var patchBody = {};
        var galleryPaths   = [];
        var productImages  = [];
        var serviceImages  = [];

        Object.keys(paths).forEach(function (key) {
          var p = paths[key];
          if (key === 'logo')              { patchBody.logo_path    = p; }
          else if (key === 'banner')       { patchBody.banner_path  = p; }
          else if (key === 'profile')      { patchBody.profile_path = p; }
          else if (key.startsWith('gallery_')) { galleryPaths.push(p); }
          else if (key.startsWith('product_')) {
            productImages.push({ id: key.replace('product_', ''), path: p });
          }
          else if (key.startsWith('service_')) {
            serviceImages.push({ id: key.replace('service_', ''), path: p });
          }
        });

        if (galleryPaths.length)  patchBody.gallery_paths  = galleryPaths;
        if (productImages.length) patchBody.product_images = productImages;
        if (serviceImages.length) patchBody.service_images = serviceImages;

        // Clear pending map before redirect so beforeunload doesn't fire
        pendingUploads.clear();

        if (Object.keys(patchBody).length === 0) {
          // All uploads failed or no paths to save
          window.location.href = submitRedirect;
          return;
        }

        fetch('/api/v1/businesses/' + groupId + '/images', {
          method: 'PATCH',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(patchBody)
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (patchRes) {
          var redirect = submitRedirect;
          if (!patchRes.ok || !patchRes.data.ok) {
            redirect += (redirect.indexOf('?') >= 0 ? '&' : '?') + 'images_failed=1';
          }
          window.location.href = redirect;
        })
        .catch(function () {
          window.location.href = submitRedirect +
            (submitRedirect.indexOf('?') >= 0 ? '&' : '?') + 'images_failed=1';
        });
      }
    })
    .catch(function () {
      alert('Network error. Please check your connection and try again.');
      resetBtn();
    });
  });
});
