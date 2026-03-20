/**
 * 7-step listing wizard for /subscriber/list-business.php
 * (loads after jQuery, Bootstrap, Cropper.js)
 */
$(function () {
  var TOTAL_STEPS = 7;
  var currentStep = 1;

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
    $('#summCategory').text($('#category').val() || 'No category');
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
    window.open('/listing-preview.php', '_blank', 'noopener,noreferrer');
  });

  $('#mciSubmitForm').on('submit', function () {
    try { localStorage.removeItem(LS_KEY); } catch(e) {}
  });
});
