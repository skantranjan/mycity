/**
 * subscriber-listing-edit.js
 *
 * Runs AFTER subscriber-list-business.js.
 * 1) Pre-fills the wizard form from window._mciEditListing
 * 2) Overrides the form submit to PUT instead of POST
 */
$(function () {
  // Give the wizard JS a moment to finish its own $(function(){...}) setup
  setTimeout(function () {
    var b   = window._mciEditListing;
    var id  = window._mciEditListingId;

    if (!b || !id) return;  // not in edit mode

    // ── Pre-fill step 1: core business fields ────────────────────────────────

    // Name (triggers slug auto-generation in wizard)
    $('#listing_title').val(b.name || '').trigger('change');

    // Slug — set the raw base slug and mark it as manually set so the wizard
    // stops overwriting it when the title changes.
    if (b.slug) {
      $('#listing_slug').val(b.slug).trigger('input');
    }

    $('#tagline').val(b.tagline || '');
    $('#description').val(b.description || '').trigger('input'); // fires char counter
    $('#descriptionEditor').html(b.description || '');
    $('#established_year').val(b.established_year || '');
    $('#price_range').val(b.price_range || '');
    $('#video_url').val(b.video_url || '');
    $('#mciSeoFields').removeClass('d-none');
    $('#seo_page_title').val(b.page_title || '');
    $('#seo_meta_description').val(b.meta_description || '');
    $('#seo_meta_keywords').val(b.meta_keywords || '');

    // Category — set the <select>, let wizard fire its change handler which
    // populates subcategories and the hidden field.
    if (b.parent_category_id) {
      $('#category').val(String(b.parent_category_id)).trigger('change');
      // The wizard's change handler sets #categoryIdHidden; belt-and-braces
      setTimeout(function () {
        $('#categoryIdHidden').val(b.parent_category_id);

        // Subcategories — tick the checkboxes once the DOM has rendered them
        if (b.subcategories && b.subcategories.length) {
          b.subcategories.forEach(function (sc) {
            $('#subcat_' + sc.id).prop('checked', true);
          });
        }
      }, 300);
    }

    // Tags — inject into the wizard's selectedTags array by calling mciAddTag
    // indirectly: we use the typeahead input + Enter key event to keep the
    // wizard's internal state consistent.  A simpler approach is to manipulate
    // the DOM directly since selectedTags is private; we do it via the exposed
    // chip-rendering logic.  We dispatch a custom approach: for each tag we
    // append a hidden input + a chip element matching the wizard's structure.
    if (b.tags && b.tags.length) {
      // The wizard exposes mciAddTag only inside the IIFE so we can't call it.
      // Instead we trigger the keyboard flow via a synthetic value + Enter.
      // However, tags must already be in allTags for id matching; since allTags
      // loads asynchronously we wait briefly then inject.
      setTimeout(function () {
        b.tags.forEach(function (tag) {
          var $input = $('#tagTypeahead');
          $input.val(tag.name);
          // Simulate Enter keydown so the wizard's handler picks up the tag
          var ev = $.Event('keydown');
          ev.key = 'Enter';
          $input.trigger(ev);
        });
      }, 600);
    }

    // ── Pre-fill step 4: primary branch (location + contact) ─────────────────
    var primaryBranch = (b.branches && b.branches.length) ? b.branches[0] : null;
    if (primaryBranch) {
      var $block = $('.mci-branch-block').first();

      $block.find('input[name="full_address[]"]').val(primaryBranch.address_line1 || '');
      $block.find('input[name="address_line2[]"]').val(primaryBranch.address_line2 || '');
      $block.find('input[name="pincode[]"]').val(primaryBranch.pincode || '');
      $block.find('input[name="latitude[]"]').val(primaryBranch.latitude || '');
      $block.find('input[name="longitude[]"]').val(primaryBranch.longitude || '');
      $block.find('input[name="phone[]"]').val(primaryBranch.phone_primary || '');
      $block.find('input[name="phone_secondary[]"]').val(primaryBranch.phone_secondary || '');
      $block.find('input[name="whatsapp[]"]').val(primaryBranch.whatsapp_number || '');
      $block.find('input[name="email_contact[]"]').val(primaryBranch.email_contact || '');
      $block.find('input[name="website[]"]').val(primaryBranch.website || '');

      // Country → State → City cascade
      // Step 1: set country and trigger change (loads states)
      var country = primaryBranch.country || 'India';
      var state   = primaryBranch.state   || '';
      var city    = primaryBranch.city    || '';

      $block.find('.mci-country-select').val(country).trigger('change');

      // Step 2: once states load (~500 ms) set state and trigger change (loads cities)
      setTimeout(function () {
        $block.find('.mci-state-select').val(state).trigger('change');
        $block.find('input[name="state[]"]').val(state);

        // Step 3: once cities load (~500 ms) set city
        setTimeout(function () {
          $block.find('.mci-city-select').val(city);
          $block.find('input[name="city[]"]').val(city).trigger('change');
        }, 600);
      }, 600);
    }

    // ── Pre-fill social links ─────────────────────────────────────────────────
    // The API returns social_links as [{platform, url}] (db platform names).
    // Map back to the form field ids.
    var platformToFieldId = {
      'facebook':         '#social_facebook',
      'instagram':        '#social_instagram',
      'twitter':          '#social_x',
      'youtube':          '#social_youtube',
      'linkedin':         '#social_linkedin',
      'tiktok':           '#social_tiktok',
      'pinterest':        '#social_pinterest',
      'telegram':         '#social_telegram',
      'threads':          '#social_threads',
      'snapchat':         '#social_snapchat',
      'whatsapp_channel': '#social_whatsapp_channel',
    };
    if (b.social_links && b.social_links.length) {
      b.social_links.forEach(function (sl) {
        var fieldId = platformToFieldId[sl.platform];
        if (fieldId) {
          $(fieldId).val(sl.url || '');
        }
      });
    }

    // ── Pre-fill step 2: products ─────────────────────────────────────────────
    if (b.products && b.products.length) {
      b.products.forEach(function (p) {
        $('#addProductBtn').trigger('click');
        var $rows = $('#productItems .mci-item-row');
        var $row  = $rows.last();
        $row.find('input[name="product_name[]"]').val(p.name || '');
        $row.find('textarea[name="product_desc[]"]').val(p.description || '');
        $row.find('input[name="product_price_min[]"]').val(p.price_min || '');
        $row.find('input[name="product_price_max[]"]').val(p.price_max || '');
      });
    }

    // ── Pre-fill step 3: services ─────────────────────────────────────────────
    if (b.services && b.services.length) {
      b.services.forEach(function (s) {
        $('#addServiceBtn').trigger('click');
        var $rows = $('#serviceItems .mci-item-row');
        var $row  = $rows.last();
        $row.find('input[name="service_name[]"]').val(s.name || '');
        $row.find('textarea[name="service_desc[]"]').val(s.description || '');
        $row.find('input[name="service_price_min[]"]').val(s.price_min || '');
        $row.find('input[name="service_price_max[]"]').val(s.price_max || '');
      });
    }

    // ── Pre-fill step 7: FAQs ─────────────────────────────────────────────────
    if (b.faqs && b.faqs.length) {
      b.faqs.forEach(function (f) {
        $('#addFaqBtn').trigger('click');
        var $items = $('#faqItems .mci-faq-item');
        var $item  = $items.last();
        $item.find('input[name="faq_question[]"]').val(f.question || '');
        $item.find('textarea[name="faq_answer[]"]').val(f.answer || '');
      });
    }

    // ── Override form submit → PUT ────────────────────────────────────────────
    // The wizard's submit handler is already bound; replace it entirely.
    setTimeout(function () {
      $('#mciSubmitForm').off('submit').on('submit', function (e) {
        e.preventDefault();

        var $btn     = $('#submitBtn');
        var btnLabel = '<i class="bi bi-check2-circle me-2" aria-hidden="true"></i>Save changes';

        function setPhase(msg) {
          $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + msg
          );
        }
        function resetBtn() {
          $btn.prop('disabled', false).html(btnLabel);
        }
        function showFeedback(html, cls) {
          var $fb = $('#mciSubmitFeedback');
          if ($fb.length) {
            $fb.removeClass('d-none alert-success alert-danger')
               .addClass('alert ' + (cls || 'alert-danger'))
               .html(html);
          } else {
            alert(html.replace(/<[^>]+>/g, ''));
          }
        }

        setPhase('Saving\u2026');

        // ── Build payload (mirrors buildPayload() inside the wizard IIFE) ──────
        // Sync location hidden inputs first
        $('#branchList .mci-branch-block').each(function () {
          var $b = $(this);

          var $countrySel    = $b.find('.mci-country-select');
          var $countryOther  = $b.find('input[name="country_other[]"]');
          var $countryHidden = $b.find('input[name="country[]"]');
          var countryVal     = $countrySel.val();
          if (countryVal === 'other' || countryVal === '') {
            $countryHidden.val($countryOther.val().trim() || 'India');
          } else {
            $countryHidden.val(countryVal || 'India');
          }

          var $stateSel    = $b.find('.mci-state-select');
          var $stateOther  = $b.find('input[name="state_other[]"]');
          var $stateHidden = $b.find('input[name="state[]"]');
          var stateSelVal  = $stateSel.val();
          if (stateSelVal && stateSelVal !== 'other') {
            $stateHidden.val(stateSelVal);
          } else {
            $stateHidden.val($stateOther.val().trim());
          }

          var $citySel    = $b.find('.mci-city-select');
          var $cityOther  = $b.find('.mci-city-other');
          var $cityHidden = $b.find('input[name="city[]"]');
          var citySelVal  = $citySel.val();
          if (citySelVal && citySelVal !== 'other') {
            $cityHidden.val(citySelVal);
          } else {
            $cityHidden.val($cityOther.val().trim());
          }
        });

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
            image_path:  ''
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
            image_path:  ''
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

        // Tag ids from the wizard's hidden JSON field
        var tagIds = [];
        try {
          var tagJson = $('#tagIdsHidden').val();
          if (tagJson) { tagIds = JSON.parse(tagJson) || []; }
        } catch (te) {}

        // Branches
        var branches = [];
        $('#branchList .mci-branch-block').each(function () {
          var $bl = $(this);
          branches.push({
            branch_label:    $bl.find('input[name="branch_label[]"]').val().trim(),
            full_address:    $bl.find('input[name="full_address[]"]').val().trim(),
            address_line2:   $bl.find('input[name="address_line2[]"]').val().trim(),
            city:            $bl.find('input[name="city[]"]').val().trim(),
            state:           $bl.find('input[name="state[]"]').val().trim(),
            country:         $bl.find('input[name="country[]"]').val().trim() || 'India',
            pincode:         $bl.find('input[name="pincode[]"]').val().trim(),
            latitude:        $bl.find('input[name="latitude[]"]').val().trim(),
            longitude:       $bl.find('input[name="longitude[]"]').val().trim(),
            phone:           $bl.find('input[name="phone[]"]').val().trim(),
            phone_secondary: $bl.find('input[name="phone_secondary[]"]').val().trim(),
            whatsapp:        $bl.find('input[name="whatsapp[]"]').val().trim(),
            email_contact:   $bl.find('input[name="email_contact[]"]').val().trim(),
            website:         $bl.find('input[name="website[]"]').val().trim()
          });
        });

        var primaryBranchPayload = branches[0] || {};
        primaryBranchPayload.social_links = {
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

        // Hours
        var days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        var hoursObj = {};
        days.forEach(function (day) {
          var open = $('input[name="hours[open][' + day + ']"]').is(':checked');
          hoursObj[day] = {
            open:        open,
            slot1_start: $('select[name="hours[slot1_start][' + day + ']"]').val() || '',
            slot1_end:   $('select[name="hours[slot1_end][' + day + ']"]').val()   || '',
            slot2_start: $('select[name="hours[slot2_start][' + day + ']"]').val() || '',
            slot2_end:   $('select[name="hours[slot2_end][' + day + ']"]').val()   || ''
          };
        });
        primaryBranchPayload.hours = hoursObj;

        var payload = {
          context: 'subscriber',
          group: {
            name:             $('#listing_title').val().trim(),
            slug:             $('#listing_slug').val().trim(),
            tagline:          $('#tagline').val().trim(),
            description:      $('#description').val().trim(),
            established_year: $('#established_year').val() ? parseInt($('#established_year').val()) : null,
            category_id:      parseInt($('#categoryIdHidden').val()) || 0,
            subcategory_ids:  subcategoryIds,
            tag_ids:          tagIds,
            price_range:      $('#price_range').val() || '',
            video_url:        $('#video_url').val().trim(),
            page_title:       $('#seo_page_title').val().trim(),
            meta_description: $('#seo_meta_description').val().trim(),
            meta_keywords:    $('#seo_meta_keywords').val().trim(),
            logo_path:        '',
            banner_path:      ''
          },
          branch:   primaryBranchPayload,
          branches: branches,
          products: products,
          services: services,
          faqs:     faqs
        };

        // ── PUT /api/v1/subscriber/businesses/{id} ────────────────────────────
        fetch('/api/v1/subscriber/businesses/' + encodeURIComponent(id), {
          method:      'PUT',
          credentials: 'include',
          headers:     { 'Content-Type': 'application/json' },
          body:        JSON.stringify(payload)
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, status: r.status, data: d }; }); })
        .then(function (res) {
          if (!res.ok || !res.data.ok) {
            var msg = 'Update failed.';
            if (res.data && res.data.detail) { msg = res.data.detail; }
            else if (res.data && res.data.error) { msg = res.data.error; }
            showFeedback('<i class="bi bi-exclamation-triangle-fill me-2"></i>' + msg, 'alert-danger');
            resetBtn();
            return;
          }
          showFeedback('<i class="bi bi-check-circle-fill me-2"></i>Listing updated successfully. Redirecting\u2026', 'alert-success');
          setTimeout(function () {
            window.location.href = '/subscriber/listings/';
          }, 1200);
        })
        .catch(function () {
          showFeedback('<i class="bi bi-wifi-off me-2"></i>Network error. Please check your connection and try again.', 'alert-danger');
          resetBtn();
        });
      });
    }, 0);

  }, 50); // end outer setTimeout — runs after wizard's $(function(){...})
});
