<?php
$pageTitle = 'Submit Your Business Listing - My City Info';
$activePage = 'submit';

$categories = [
  'Airport',
  'Amusement Park',
  'Aquarium',
  'Art Gallery',
  'ATM',
  'Automotive',
  'Bakery',
  'Bank',
  'Bar',
  'Beauty Salon',
  'Bicycle Store',
  'Books & Stationary Store',
  'Bus Stations',
  'Cafe',
  'Car Dealer',
  'Car Rental',
  'Car Repair',
  'Car Wash',
  'Cemetery',
  'Church',
  'City Attraction',
  'Clothing Store',
  'College',
  'Convenience Store',
  'Courier Services',
  'Dentist',
  'Departmental Store',
  'Doctor',
  'Electrician',
  'Electronics Store',
  'Fire Station',
  'Florist',
  'Funeral Home',
  'Furniture Store',
  'Gift Shop',
  'Government Office',
  'Gym',
  'Hardware Store',
  'Health',
  'Hindu Temple',
  'Home Appliances Products',
  'Hospital',
  'Hotels',
  'Industrial and Manufacturing Supplies',
  'Insurance Agency',
  'Jewelry Store',
  'Laundry',
  'Lawyer',
  'Library',
  'Liquor Store',
  'Locksmith',
  'Medical Store',
  'Monuments',
  'Mosque',
  'Movie Theater',
  'Museum',
  'NGO and Charitable Trusts',
  'Night Club',
  'Painter',
  'Park',
  'Pet Store',
  'Petrol Pump',
  'Physiotherapist',
  'Plumber',
  'Police Station',
  'Post Office',
  'Pre Schools and Day Care',
  'Private Coaching Institutes',
  'Real Estate',
  'Resorts',
  'Restaurant',
  'School',
  'Services',
  'Shoe Store',
  'Shopping',
  'Spa',
  'Stadium',
  'Supermarket',
  'Travel Agency',
  'University',
  'Veterinary Care',
];

ob_start();
?>

<div class="row g-4">
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-2">
      <div>
        <div class="fw-semibold">Submit Your Listing</div>
        <div class="text-muted small">Add your business details. It will appear after approval if submitted anonymously.</div>
      </div>
      <a class="btn btn-sm btn-outline-secondary" href="/index.php">Home</a>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <form action="#" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
          <input type="hidden" name="form_origin" value="ui_submit_listing" />

          <!-- Posting type -->
          <div class="mb-4">
            <div class="fw-semibold mb-2">How do you want to post?</div>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <div class="form-check card p-3 h-100">
                  <input class="form-check-input" type="radio" name="posting_type" id="postingRegistered" value="registered" checked />
                  <label class="form-check-label" for="postingRegistered">
                    Post as registered user
                  </label>
                  <div id="hintRegistered" class="text-muted small mt-2">
                    Posting can go live immediately.
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="form-check card p-3 h-100">
                  <input class="form-check-input" type="radio" name="posting_type" id="postingAnonymous" value="anonymous" />
                  <label class="form-check-label" for="postingAnonymous">
                    Post anonymously (requires approval)
                  </label>
                  <div id="hintAnonymous" class="text-muted small mt-2" style="display:none;">
                    Super admin approval is required before it appears in search.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4">
            <!-- Primary details -->
            <div class="col-12">
              <div class="fw-semibold mb-3">Primary listing details</div>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Listing Title *</label>
                    <input class="form-control" type="text" name="listing_title" placeholder="Put your listing title here" required />
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Tagline</label>
                    <input class="form-control" type="text" name="tagline" placeholder="Add a short tagline (optional)" />
                  </div>
                </div>

                <div class="col-12">
                  <div class="card border-0 bg-light p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                      <div>
                        <div class="fw-semibold">Address (Geolocation)</div>
                        <div class="text-muted small">Use the drop pin UI or fill in coordinates manually.</div>
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mapModal">
                        Drop Pin
                      </button>
                    </div>

                    <div class="row g-3">
                      <div class="col-12">
                        <label class="form-label">Full Address</label>
                        <input class="form-control" type="text" name="full_address" placeholder="Start typing and select a location" />
                      </div>
                      <div class="col-12 col-md-4">
                        <label class="form-label">Latitude</label>
                        <input class="form-control" type="text" name="latitude" placeholder="e.g. 25.5941" />
                      </div>
                      <div class="col-12 col-md-4">
                        <label class="form-label">Longitude</label>
                        <input class="form-control" type="text" name="longitude" placeholder="e.g. 85.1376" />
                      </div>
                      <div class="col-12 col-md-4">
                        <label class="form-label">City</label>
                        <input class="form-control" type="text" name="city" placeholder="City name for search filters" />
                      </div>
                    </div>

                    <div class="row g-3 mt-1">
                      <div class="col-12 col-md-4">
                        <label class="form-label">Phone</label>
                        <input class="form-control" type="text" name="phone" placeholder="Phone number" />
                      </div>
                      <div class="col-12 col-md-4">
                        <label class="form-label">Whatsapp</label>
                        <input class="form-control" type="text" name="whatsapp" placeholder="Whatsapp number" />
                      </div>
                      <div class="col-12 col-md-4">
                        <label class="form-label">Website</label>
                        <input class="form-control" type="url" name="website" placeholder="https://example.com" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Category & services -->
            <div class="col-12">
              <div class="fw-semibold mb-3">Category & services</div>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label">Category *</label>
                  <select class="form-select" name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $c): ?>
                      <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Services / Products</label>
                  <input class="form-control" type="text" name="services" placeholder="e.g. Electric wiring, LED installation" />
                </div>
              </div>
            </div>

            <!-- Price details -->
            <div class="col-12">
              <div class="fw-semibold mb-3">Price details</div>
              <div class="row g-3">
                <div class="col-12 col-md-4">
                  <label class="form-label">Price Range</label>
                  <select class="form-select" name="price_range">
                    <option value="">Not specified</option>
                    <option value="free">₹ (Inexpensive)</option>
                    <option value="moderate">₹₹ (Moderate)</option>
                    <option value="pricey">₹₹₹ (Pricey)</option>
                    <option value="ultra">₹₹₹₹ (Ultra High)</option>
                  </select>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label">Price From</label>
                  <input class="form-control" type="text" name="price_from" placeholder="Min price (optional)" />
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label">Price To</label>
                  <input class="form-control" type="text" name="price_to" placeholder="Max price (optional)" />
                </div>
              </div>
            </div>

            <!-- Business hours -->
            <div class="col-12">
              <div class="fw-semibold mb-3">Business hours</div>
              <?php include __DIR__ . '/views/components/business-hours-grid.php'; ?>
            </div>

            <!-- Social media -->
            <div class="col-12">
              <div class="fw-semibold mb-3">Social media</div>
              <div class="row g-3">
                <div class="col-12 col-md-4">
                  <label class="form-label">Select platforms</label>
                  <div class="d-flex flex-wrap gap-2">
                    <?php
                    $socials = ['Instagram', 'Youtube', 'LinkedIn', 'Facebook', 'Twitter'];
                    foreach ($socials as $s):
                    ?>
                      <label class="btn btn-outline-secondary btn-sm mb-0">
                        <input class="form-check-input me-1" type="checkbox" name="socials[]" value="<?= htmlspecialchars($s) ?>" style="position:relative; top:1px;" />
                        <?= htmlspecialchars($s) ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
                <div class="col-12 col-md-8">
                  <label class="form-label">Social links (optional)</label>
                  <input class="form-control" type="text" name="social_links" placeholder="Paste URLs or handles (e.g. https://instagram.com/yourpage)" />
                </div>
              </div>
            </div>

            <!-- FAQ -->
            <div class="col-12">
              <?php
              $faqCount = 1;
              include __DIR__ . '/views/components/faq-addmore.php';
              ?>
            </div>

            <!-- More info -->
            <div class="col-12">
              <div class="fw-semibold mb-3">More info</div>
              <div class="mb-3">
                <label class="form-label">Description *</label>
                <textarea class="form-control" name="description" rows="5" placeholder="Detail description about your listing" required></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Tags or keywords</label>
                <input class="form-control" type="text" name="tags" placeholder="Comma separated keywords (e.g. plumbing, emergency, repairs)" />
              </div>
              <div class="mb-3">
                <label class="form-label">Business video (Optional)</label>
                <input class="form-control" type="url" name="video_url" placeholder="https://youtube.com/..." />
              </div>
            </div>

            <!-- Media -->
            <div class="col-12">
              <div class="fw-semibold mb-3">Media</div>
              <div class="row g-3">
                <div class="col-12">
                  <div
                    id="dropFiles"
                    class="border rounded-4 p-4 bg-white text-center cursor-pointer"
                    style="border-style:dashed;"
                    role="button"
                    tabindex="0"
                    aria-label="Upload images"
                  >
                    <div class="fw-semibold">Drop files here or click to upload</div>
                    <div class="text-muted small">Upload images for your listing (PNG/JPG/WebP).</div>
                    <button type="button" class="btn btn-sm btn-primary mt-2">Browse Files</button>
                    <input
                      id="fileInputImages"
                      class="d-none"
                      type="file"
                      name="images[]"
                      accept="image/*"
                      multiple
                    />
                  </div>
                </div>

                <div class="col-12">
                  <div class="d-flex flex-wrap gap-2" id="imagePreview"></div>
                  <div class="text-muted small mt-2">Image preview shown locally (backend integration pending).</div>
                </div>
              </div>
            </div>

            <!-- Signup for notifications -->
            <div class="col-12">
              <div class="fw-semibold mb-3">Account (optional for UI)</div>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label">Email to signup & receive notification *</label>
                  <input class="form-control" type="email" name="email" placeholder="name@example.com" required />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Password *</label>
                  <input class="form-control" type="password" name="password" placeholder="Create a password" required />
                </div>
              </div>
              <div class="text-muted small mt-2">
                Already have an account? <a href="/login.php">Log in</a>
              </div>
            </div>

            <!-- Agreement -->
            <div class="col-12">
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="agreeTerms" required />
                <label class="form-check-label" for="agreeTerms">
                  I Agree - you accept our Terms & Conditions for posting this ad.
                </label>
              </div>
              <button class="btn btn-dark" type="submit">Submit listing</button>
              <div class="text-muted small mt-2">
                UI-only right now. Backend wiring for moderation/live flow will be added later.
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Map picker modal (placeholder for UI-first) -->
<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div class="fw-semibold">Drop Pin</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="bg-light border rounded-3 p-4 text-center">
          <div class="fw-semibold mb-2">Map UI (coming next)</div>
          <div class="text-muted small mb-3">
            This modal is a placeholder. In the next phase we can integrate Google Maps or a map picker.
          </div>
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Latitude</label>
              <input class="form-control" id="modalLatitude" type="text" placeholder="Latitude" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Longitude</label>
              <input class="form-control" id="modalLongitude" type="text" placeholder="Longitude" />
            </div>
          </div>
          <div class="text-muted small mt-3">
            Click "Apply" to copy values to the main form.
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button
          type="button"
          class="btn btn-primary"
          data-bs-dismiss="modal"
          id="applyPinBtn"
        >Apply</button>
      </div>
    </div>
  </div>
</div>

<script>
  $(function () {
    // Posting type hints (anonymous vs registered).
    function syncPostingHints() {
      var val = $('input[name="posting_type"]:checked').val();
      $('#hintAnonymous').toggle(val === 'anonymous');
      $('#hintRegistered').toggle(val === 'registered');
    }
    $('input[name="posting_type"]').on('change', syncPostingHints);
    syncPostingHints();

    // FAQ add-more UI.
    var $faqItems = $('#faqItems');
    var tmpl = document.getElementById('faqItemTemplate');
    var addBtn = $('#addFaqBtn');

    addBtn.on('click', function () {
      if (!tmpl || !tmpl.content || !$faqItems.length) return;
      var clone = tmpl.content.firstElementChild.cloneNode(true);
      var nextIndex = $faqItems.find('.faq-item').length;
      clone.setAttribute('data-faq-index', String(nextIndex));
      clone.querySelectorAll('input, textarea').forEach(function (el) {
        el.value = '';
      });
      $faqItems.append(clone);
    });

    $(document).on('click', '.removeFaqBtn', function () {
      $(this).closest('.faq-item').remove();
    });

    // Map pin modal "Apply".
    $('#applyPinBtn').on('click', function () {
      var lat = $('#modalLatitude').val();
      var lng = $('#modalLongitude').val();
      $('input[name="latitude"]').val(lat);
      $('input[name="longitude"]').val(lng);
    });

    // Image previews + drag/drop upload.
    var dropArea = document.getElementById('dropFiles');
    var fileInput = document.getElementById('fileInputImages');
    var preview = document.getElementById('imagePreview');
    function renderPreviews(files) {
      preview.innerHTML = '';
      if (!files) return;
      var max = Math.min(files.length, 12);
      for (var i = 0; i < max; i++) {
        var f = files[i];
        if (!f.type || !f.type.startsWith('image/')) continue;
        var url = URL.createObjectURL(f);
        var img = document.createElement('img');
        img.src = url;
        img.alt = f.name;
        img.className = 'img-thumbnail';
        img.style.width = '120px';
        img.style.height = '90px';
        img.style.objectFit = 'cover';
        preview.appendChild(img);
      }
    }

    if (dropArea && fileInput) {
      // Click anywhere to open file picker.
      dropArea.addEventListener('click', function () {
        fileInput.click();
      });

      // Prevent default browser behavior.
      ['dragover', 'dragenter'].forEach(function (evtName) {
        dropArea.addEventListener(evtName, function (e) {
          e.preventDefault();
          e.stopPropagation();
          dropArea.classList.add('border-primary');
        });
      });
      ['dragleave', 'drop'].forEach(function (evtName) {
        dropArea.addEventListener(evtName, function (e) {
          e.preventDefault();
          e.stopPropagation();
          dropArea.classList.remove('border-primary');
        });
      });

      // Support drag/drop and also update the input so the form submits.
      dropArea.addEventListener('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var dtFiles = e.dataTransfer.files;
        if (!dtFiles || !dtFiles.length) return;

        var dt = new DataTransfer();
        for (var i = 0; i < dtFiles.length; i++) {
          dt.items.add(dtFiles[i]);
        }
        fileInput.files = dt.files;
        renderPreviews(fileInput.files);
      });

      fileInput.addEventListener('change', function () {
        renderPreviews(fileInput.files);
      });
    }
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>

