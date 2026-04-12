<?php
/** @var array<int, string> $categories */
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$times = [];
for ($m = 0; $m < 24 * 60; $m += 30) {
    $h = intdiv($m, 60);
    $mm = $m % 60;
    $times[] = sprintf('%02d:%02d', $h, $mm);
}

$submitPublicGuest = !empty($submitPublicGuest);
$submitHideStep7InlinePreview = !empty($submitHideStep7InlinePreview);
if (!isset($mciExistingListingSlugs)) {
    require_once __DIR__ . '/../../includes/mci_directory_listings.php';
    /** @var list<array<string, mixed>> $mciDirectoryListings */
    $mciExistingListingSlugs = array_values(array_unique(array_filter(array_map(
        static function (array $row): string {
            return trim((string) ($row['slug'] ?? ''));
        },
        $mciDirectoryListings
    ))));
}
$mciPublicSiteOrigin = $mciPublicSiteOrigin ?? 'https://mycityinfo.com';

// Reusable wizard inner for both subscriber + CP super-admin flows.
// Caller can override these labels/behavior via variables.
$submitKicker = $submitKicker ?? 'Subscriber';
$submitTitle = $submitTitle ?? 'List your business';
$submitLead = $submitLead ?? 'Eight guided steps — save anytime; preview before you publish.';

$formOrigin = $formOrigin ?? 'ui_subscriber_listing';
$postingType = $postingType ?? 'registered';
$requesterLabel = $requesterLabel ?? 'Subscriber';

$step7HeaderDesc = $step7HeaderDesc ?? 'You’re signed in — submit when everything looks right.';
$step7AlertTitle = $step7AlertTitle ?? 'Preview &amp; publish';
$step7AlertBody = $step7AlertBody ?? 'You’re already logged in. Confirm the preview below and submit your business for listing review.';
$step7SubmitText = $step7SubmitText ?? 'Submit listing';
?>

<div class="mci-submit-wrap" id="mciSubmitWrap">
  <div class="row g-4 g-lg-5 align-items-start">
    <div class="col-12">

      <div class="mci-submit-hero mb-4">
        <p class="mci-submit-hero__kicker"><?= htmlspecialchars((string) $submitKicker, ENT_QUOTES, 'UTF-8') ?></p>
        <h1 class="mci-submit-hero__title"><?= htmlspecialchars((string) $submitTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="mci-submit-hero__lead"><?= htmlspecialchars((string) $submitLead, ENT_QUOTES, 'UTF-8') ?></p>
      </div>

      <div class="mci-steps-nav mci-steps-nav--9 mb-4" aria-label="Form progress">
        <?php
        $stepDefs = [
            ['icon' => 'bi-building', 'label' => 'Business'],
            ['icon' => 'bi-box-seam', 'label' => 'Products'],
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
            aria-label="Step <?= $n ?>: <?= htmlspecialchars($s['label']) ?>"
            <?= $n === 1 ? 'aria-current="step"' : '' ?>
          >
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

      <div class="mci-progress-bar mb-4" role="progressbar" aria-valuenow="11" aria-valuemin="0" aria-valuemax="100">
        <div class="mci-progress-bar__fill" id="stepProgressFill" style="width:14%"></div>
      </div>

      <form action="#" method="post" enctype="multipart/form-data" id="mciSubmitForm" novalidate>
        <input type="hidden" name="form_origin" value="<?= htmlspecialchars((string) $formOrigin, ENT_QUOTES, 'UTF-8') ?>" />
        <?php if (!$submitPublicGuest): ?>
        <input type="hidden" name="posting_type" value="<?= htmlspecialchars((string) $postingType, ENT_QUOTES, 'UTF-8') ?>" />
        <?php endif; ?>
        <input type="hidden" id="mciRequesterLabel" value="<?= htmlspecialchars((string) $requesterLabel, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" id="mciSubmitPublicGuest" value="<?= $submitPublicGuest ? '1' : '0' ?>" />
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($mciSubmitCsrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
        <script type="application/json" id="mciExistingSlugs"><?= json_encode($mciExistingListingSlugs, JSON_UNESCAPED_SLASHES) ?></script>

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
              <label class="form-label mci-field-label" for="listing_slug">Slug</label>
              <div class="input-group">
                <input
                  class="form-control"
                  id="listing_slug"
                  type="text"
                  name="listing_slug"
                  autocomplete="off"
                  placeholder="e.g. city-auto-care"
                  pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                  title="Lowercase letters, numbers, and hyphens only"
                />
                <button type="button" class="btn btn-outline-secondary" id="mciCheckSlugBtn">Check availability</button>
              </div>
              <div class="form-text">Auto-filled from your business name — edit if needed. Your public URL also uses your <strong>city</strong> (step 3) when set.</div>
              <div id="mciSlugStatus" class="small mt-2" aria-live="polite"></div>
              <div
                class="text-muted small mt-2 pt-1 border-top"
                id="mciBusinessUrlPreview"
                data-origin="<?= htmlspecialchars((string) $mciPublicSiteOrigin, ENT_QUOTES, 'UTF-8') ?>"
              >
                <span class="fw-semibold text-body">Your listing URL will look like:</span><br />
                <span id="mciBusinessUrlPreviewText" class="user-select-all">—</span>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label mci-field-label" for="tagline">Tagline</label>
              <input class="form-control" id="tagline" type="text" name="tagline" placeholder="One punchy line customers will remember" />
            </div>
            <div class="col-12">
              <label class="form-label mci-field-label" for="description">Description <span class="text-danger">*</span></label>
              <div class="mci-rich-editor border rounded-3 p-2 bg-white">
                <div class="d-flex flex-wrap gap-1 mb-2">
                  <button type="button" class="btn btn-sm btn-outline-secondary mci-editor-btn" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary mci-editor-btn" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary mci-editor-btn" data-cmd="insertUnorderedList"><i class="bi bi-list-ul"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary mci-editor-btn" data-cmd="createLink"><i class="bi bi-link-45deg"></i></button>
                </div>
                <div id="descriptionEditor" class="form-control" contenteditable="true" style="min-height:220px;"></div>
              </div>
              <textarea class="form-control d-none" id="description" name="description" rows="10" placeholder="Tell customers what makes your business worth visiting…" required></textarea>
              <div class="d-flex justify-content-end mt-1"><span class="form-text" id="descCount">0</span></div>
            </div>
            <div class="col-12 d-none" id="mciSeoFields">
              <div class="row g-3 border rounded-3 p-2 mx-0 bg-light-subtle">
                <div class="col-12">
                  <div class="fw-semibold small">SEO metadata (edit only)</div>
                </div>
                <div class="col-12">
                  <label class="form-label mci-field-label" for="seo_page_title">Page title</label>
                  <input class="form-control" id="seo_page_title" type="text" name="seo_page_title" maxlength="255" />
                </div>
                <div class="col-12">
                  <label class="form-label mci-field-label" for="seo_meta_description">Meta description</label>
                  <textarea class="form-control" id="seo_meta_description" name="seo_meta_description" rows="3" maxlength="500"></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label mci-field-label" for="seo_meta_keywords">Meta keywords</label>
                  <input class="form-control" id="seo_meta_keywords" type="text" name="seo_meta_keywords" maxlength="500" placeholder="keyword1, keyword2" />
                </div>
              </div>
            </div>
            <div class="col-12 col-sm-7">
              <label class="form-label mci-field-label" for="category">Category <span class="text-danger">*</span></label>
              <select class="form-select" id="category" name="category" required>
                <option value="">Loading categories…</option>
              </select>
              <input type="hidden" id="categoryIdHidden" name="category_id" />

              <input type="hidden" id="is_category_request" value="0" />

              <div id="categoryRequestFields" class="d-none mt-3">
                <div class="mci-submit-label mb-1" style="font-size:var(--mci-text-sm);">Category request</div>

                <label class="form-label small mb-1" for="requested_category_name">Requested category name</label>
                <input
                  class="form-control"
                  id="requested_category_name"
                  type="text"
                  name="requested_category_name"
                  placeholder="e.g. Homeopathy Clinic"
                  autocomplete="off"
                />

                <label class="form-label small mt-3 mb-1" for="category_request_reason">For what / why do you need it?</label>
                <textarea
                  class="form-control"
                  id="category_request_reason"
                  name="category_request_reason"
                  rows="3"
                  placeholder="Tell us what you want customers to find..."
                ></textarea>
              </div>
            </div>
            <div class="col-12">
              <div id="subcategoryContainer" class="d-none"></div>
            </div>
            <div class="col-12">
              <label class="form-label mci-field-label">Tags</label>
              <div class="mci-tag-input-wrap position-relative">
                <div class="mci-tag-selected d-flex flex-wrap gap-1 align-items-center p-2 border rounded-2 bg-white" id="tagSelectedArea" style="min-height:38px;cursor:text;">
                  <input
                    type="text"
                    class="mci-tag-typeahead border-0 outline-0 flex-grow-1 p-0"
                    id="tagTypeahead"
                    autocomplete="off"
                    placeholder="Type a tag and press comma or Enter…"
                    style="min-width:150px;outline:none;box-shadow:none;"
                  />
                </div>
                <ul class="mci-tag-suggestions list-unstyled position-absolute bg-white border rounded-2 shadow-sm mb-0 py-1 d-none" id="tagSuggestions" style="z-index:200;width:100%;max-height:180px;overflow-y:auto;top:100%;left:0;"></ul>
              </div>
              <input type="hidden" id="tagIdsHidden" name="tag_ids" value="[]" />
              <div class="form-text mt-1">Type to search existing tags. Press <kbd>,</kbd> or <kbd>Enter</kbd> to add a chip. New tags will be created if not found.</div>
            </div>
            <div class="col-12">
              <label class="form-label mci-field-label" for="established_year">Year established</label>
              <input class="form-control" id="established_year" type="number" name="established_year" placeholder="e.g. 2010" min="1800" max="2099" style="max-width:160px;" />
            </div>
          </div>
        </div>

        <!-- STEP 2 — Products -->
        <div class="mci-step" data-step="2">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--2" aria-hidden="true"><i class="bi bi-box-seam"></i></div>
            <div>
              <div class="mci-step-header__title">Products</div>
              <div class="mci-step-header__desc">Add the products you sell, with optional pricing and photos.</div>
            </div>
          </div>

          <!-- Products -->
          <div class="mci-submit-label mb-1">Products <span class="fw-normal text-muted text-lowercase">(optional)</span></div>
          <p class="text-muted small mb-3">Add each product with a description, price range, and optional photo.</p>
          <div id="productItems" class="mb-2">
            <div class="mci-faq-item mci-item-row" data-item-index="0">
              <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
              <div class="flex-grow-1">
                <input class="form-control form-control-sm mb-2" type="text" name="product_name[]" placeholder="Product name, e.g. Oil Change" aria-label="Product name" />
                <textarea class="form-control form-control-sm mb-2" name="product_desc[]" rows="2" placeholder="Short description…" aria-label="Product description"></textarea>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                  <input class="form-control form-control-sm" style="width:110px" type="number" name="product_price_min[]" placeholder="Min price" min="0" step="0.01" aria-label="Min price" />
                  <span class="text-muted small">–</span>
                  <input class="form-control form-control-sm" style="width:110px" type="number" name="product_price_max[]" placeholder="Max price" min="0" step="0.01" aria-label="Max price" />
                  <input type="hidden" name="product_price_unit[]" value="INR" />
                  <button type="button" class="btn btn-sm btn-outline-secondary mci-item-img-btn" aria-label="Upload product image">
                    <i class="bi bi-image me-1"></i>Photo
                  </button>
                  <input type="file" class="d-none mci-item-file-input" accept="image/*" />
                  <input type="hidden" name="product_image_path[]" />
                  <span class="mci-item-img-name text-muted small"></span>
                </div>
              </div>
              <button type="button" class="btn btn-sm removeProductBtn mci-faq-item__remove" aria-label="Remove product"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
          <button type="button" id="addProductBtn" class="btn btn-sm btn-outline-dark mb-4">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add product
          </button>
          <template id="productItemTemplate">
            <div class="mci-faq-item mci-item-row" data-item-index="__INDEX__">
              <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
              <div class="flex-grow-1">
                <input class="form-control form-control-sm mb-2" type="text" name="product_name[]" placeholder="Product name" aria-label="Product name" />
                <textarea class="form-control form-control-sm mb-2" name="product_desc[]" rows="2" placeholder="Short description…" aria-label="Product description"></textarea>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                  <input class="form-control form-control-sm" style="width:110px" type="number" name="product_price_min[]" placeholder="Min price" min="0" step="0.01" aria-label="Min price" />
                  <span class="text-muted small">–</span>
                  <input class="form-control form-control-sm" style="width:110px" type="number" name="product_price_max[]" placeholder="Max price" min="0" step="0.01" aria-label="Max price" />
                  <input type="hidden" name="product_price_unit[]" value="INR" />
                  <button type="button" class="btn btn-sm btn-outline-secondary mci-item-img-btn" aria-label="Upload product image">
                    <i class="bi bi-image me-1"></i>Photo
                  </button>
                  <input type="file" class="d-none mci-item-file-input" accept="image/*" />
                  <input type="hidden" name="product_image_path[]" />
                  <span class="mci-item-img-name text-muted small"></span>
                </div>
              </div>
              <button type="button" class="btn btn-sm removeProductBtn mci-faq-item__remove" aria-label="Remove product"><i class="bi bi-x-lg"></i></button>
            </div>
          </template>

          <hr class="mci-inner-divider" />

          <div class="mci-submit-label">Overall price range <span class="fw-normal text-muted text-lowercase">(optional)</span></div>
          <div class="row g-3 mt-1">
            <div class="col-12 col-md-5">
              <label class="form-label mci-field-label" for="price_range">Price range</label>
              <select class="form-select" id="price_range" name="price_range">
                <option value="">Not specified</option>
                <option value="free">$ Inexpensive</option>
                <option value="moderate">$$ Moderate</option>
                <option value="pricey">$$$ Pricey</option>
                <option value="ultra">$$$$ Ultra high</option>
              </select>
            </div>
          </div>
        </div>

        <!-- STEP 3 — Services -->
        <div class="mci-step" data-step="3">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--3" aria-hidden="true"><i class="bi bi-stars"></i></div>
            <div>
              <div class="mci-step-header__title">Services</div>
              <div class="mci-step-header__desc">Highlight the services you offer, with optional pricing and photos.</div>
            </div>
          </div>

          <!-- Services -->
          <div class="mci-submit-label mb-1">Services <span class="fw-normal text-muted text-lowercase">(optional)</span></div>
          <p class="text-muted small mb-3">Add each service with a description, price range, and optional photo.</p>
          <div id="serviceItems" class="mb-2">
            <div class="mci-faq-item mci-item-row" data-item-index="0">
              <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
              <div class="flex-grow-1">
                <input class="form-control form-control-sm mb-2" type="text" name="service_name[]" placeholder="Service name, e.g. Interior Painting" aria-label="Service name" />
                <textarea class="form-control form-control-sm mb-2" name="service_desc[]" rows="2" placeholder="Short description — what's included, turnaround…" aria-label="Service description"></textarea>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                  <input class="form-control form-control-sm" style="width:110px" type="number" name="service_price_min[]" placeholder="Min price" min="0" step="0.01" aria-label="Min price" />
                  <span class="text-muted small">–</span>
                  <input class="form-control form-control-sm" style="width:110px" type="number" name="service_price_max[]" placeholder="Max price" min="0" step="0.01" aria-label="Max price" />
                  <input type="hidden" name="service_price_unit[]" value="INR" />
                  <button type="button" class="btn btn-sm btn-outline-secondary mci-item-img-btn" aria-label="Upload service image">
                    <i class="bi bi-image me-1"></i>Photo
                  </button>
                  <input type="file" class="d-none mci-item-file-input" accept="image/*" />
                  <input type="hidden" name="service_image_path[]" />
                  <span class="mci-item-img-name text-muted small"></span>
                </div>
              </div>
              <button type="button" class="btn btn-sm removeServiceBtn mci-faq-item__remove" aria-label="Remove service"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
          <button type="button" id="addServiceBtn" class="btn btn-sm btn-outline-dark mb-4">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add service
          </button>
          <template id="serviceItemTemplate">
            <div class="mci-faq-item mci-item-row" data-item-index="__INDEX__">
              <div class="mci-faq-item__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></div>
              <div class="flex-grow-1">
                <input class="form-control form-control-sm mb-2" type="text" name="service_name[]" placeholder="Service name" aria-label="Service name" />
                <textarea class="form-control form-control-sm mb-2" name="service_desc[]" rows="2" placeholder="Short description…" aria-label="Service description"></textarea>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                  <input class="form-control form-control-sm" style="width:110px" type="number" name="service_price_min[]" placeholder="Min price" min="0" step="0.01" aria-label="Min price" />
                  <span class="text-muted small">–</span>
                  <input class="form-control form-control-sm" style="width:110px" type="number" name="service_price_max[]" placeholder="Max price" min="0" step="0.01" aria-label="Max price" />
                  <input type="hidden" name="service_price_unit[]" value="INR" />
                  <button type="button" class="btn btn-sm btn-outline-secondary mci-item-img-btn" aria-label="Upload service image">
                    <i class="bi bi-image me-1"></i>Photo
                  </button>
                  <input type="file" class="d-none mci-item-file-input" accept="image/*" />
                  <input type="hidden" name="service_image_path[]" />
                  <span class="mci-item-img-name text-muted small"></span>
                </div>
              </div>
              <button type="button" class="btn btn-sm removeServiceBtn mci-faq-item__remove" aria-label="Remove service"><i class="bi bi-x-lg"></i></button>
            </div>
          </template>
        </div>

        <!-- STEP 4 — Location & contact -->
        <div class="mci-step" data-step="4">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--4" aria-hidden="true"><i class="bi bi-geo-alt"></i></div>
            <div>
              <div class="mci-step-header__title">Location &amp; contact</div>
              <div class="mci-step-header__desc">Address, contact channels, social profiles, and additional branches.</div>
            </div>
          </div>

          <!-- Primary branch -->
          <div id="branchList">
            <div class="mci-branch-block border rounded-3 p-3 mb-3 position-relative" data-branch-index="0">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="fw-semibold small">Primary location</div>
              </div>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label" for="branch_label_0">Branch name / label</label>
                  <input class="form-control" id="branch_label_0" type="text" name="branch_label[]" placeholder="e.g. Main branch, Koramangala" />
                </div>
                <div class="col-12">
                  <label class="form-label mci-field-label" for="full_address">Address line 1</label>
                  <input class="form-control" id="full_address" type="text" name="full_address[]" placeholder="Street, building, area" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label" for="address_line2_0">Address line 2</label>
                  <input class="form-control" id="address_line2_0" type="text" name="address_line2[]" placeholder="Landmark, floor, etc." />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label" for="country_select_0">Country</label>
                  <select class="form-select mci-country-select" id="country_select_0" name="country_select[]" data-branch-index="0">
                    <option value="">Loading…</option>
                  </select>
                  <input class="form-control mt-2 d-none" id="country_other_0" name="country_other[]" type="text"
                    placeholder="Type your country" maxlength="100" data-branch-index="0" />
                  <input type="hidden" id="country_0" name="country[]" value="India" data-branch-index="0" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label" for="state_select_0">State / Province</label>
                  <select class="form-select mci-state-select" id="state_select_0" name="state_select[]" data-branch-index="0">
                    <option value="">Select country first</option>
                  </select>
                  <input class="form-control mt-2 d-none" id="state_other_0" type="text" name="state_other[]"
                    placeholder="Type your state" maxlength="100" data-branch-index="0" />
                  <input type="hidden" id="state_0" name="state[]" value="" data-branch-index="0" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label" for="city_select_0">City <span class="text-danger">*</span></label>
                  <select class="form-select mci-city-select" id="city_select_0" name="city_select[]" data-branch-index="0">
                    <option value="">Select state first</option>
                  </select>
                  <input class="form-control mt-2 d-none mci-city-other" id="city_other_0" type="text"
                    name="city_other[]" placeholder="Type city name" maxlength="100" data-branch-index="0" />
                  <input type="hidden" id="city_0" name="city[]" value="" data-branch-index="0" />
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label mci-field-label" for="pincode_0">Pincode / ZIP</label>
                  <input class="form-control" id="pincode_0" type="text" name="pincode[]" placeholder="e.g. 400001" />
                </div>
                <div class="col-6 col-md-4">
                  <label class="form-label mci-field-label" for="latitude">Latitude</label>
                  <input class="form-control" id="latitude" type="text" name="latitude[]" placeholder="e.g. 28.6139" />
                </div>
                <div class="col-6 col-md-4">
                  <label class="form-label mci-field-label" for="longitude">Longitude</label>
                  <input class="form-control" id="longitude" type="text" name="longitude[]" placeholder="e.g. 77.2090" />
                </div>
                <div class="col-12">
                  <button type="button" class="btn btn-sm btn-outline-dark mci-map-pin-btn" data-branch-index="0" data-bs-toggle="modal" data-bs-target="#mapModal">
                    <i class="bi bi-pin-map me-1" aria-hidden="true"></i>Set map pin manually
                  </button>
                </div>
                <div class="col-12"><hr class="mci-inner-divider" /></div>
                <div class="col-12 col-md-4">
                  <label class="form-label mci-field-label" for="phone">Phone</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                    <input class="form-control" id="phone" type="tel" name="phone[]" placeholder="+1 555 000 0000" />
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label mci-field-label" for="phone_secondary_0">Phone 2</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                    <input class="form-control" id="phone_secondary_0" type="tel" name="phone_secondary[]" placeholder="+1 555 000 0001" />
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label mci-field-label" for="whatsapp">WhatsApp</label>
                  <div class="input-group">
                    <span class="input-group-text text-success"><i class="bi bi-whatsapp" aria-hidden="true"></i></span>
                    <input class="form-control" id="whatsapp" type="tel" name="whatsapp[]" placeholder="+1 555 000 0000" />
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label" for="email_contact">Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                    <input class="form-control" id="email_contact" type="email" name="email_contact[]" placeholder="hello@yourbusiness.com" />
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label" for="website">Website</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                    <input class="form-control" id="website" type="url" name="website[]" placeholder="https://…" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button type="button" id="addBranchBtn" class="btn btn-sm btn-outline-dark mb-4">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add another branch
          </button>

          <template id="branchTemplate">
            <div class="mci-branch-block border rounded-3 p-3 mb-3 position-relative" data-branch-index="__INDEX__">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="fw-semibold small">Branch __NUM__</div>
                <button type="button" class="btn btn-sm btn-outline-danger mci-remove-branch-btn" aria-label="Remove branch">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label">Branch name / label</label>
                  <input class="form-control" type="text" name="branch_label[]" placeholder="e.g. North branch" />
                </div>
                <div class="col-12">
                  <label class="form-label mci-field-label">Address line 1</label>
                  <input class="form-control" type="text" name="full_address[]" placeholder="Street, building, area" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label">Address line 2</label>
                  <input class="form-control" type="text" name="address_line2[]" placeholder="Landmark, floor, etc." />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label">Country</label>
                  <select class="form-select mci-country-select" name="country_select[]" data-branch-index="__INDEX__">
                    <option value="">Select country</option>
                  </select>
                  <input class="form-control mt-2 d-none" name="country_other[]" type="text"
                    placeholder="Type your country" maxlength="100" data-branch-index="__INDEX__" />
                  <input type="hidden" name="country[]" value="India" data-branch-index="__INDEX__" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label">State / Province</label>
                  <select class="form-select mci-state-select" name="state_select[]" data-branch-index="__INDEX__">
                    <option value="">Select country first</option>
                  </select>
                  <input class="form-control mt-2 d-none" name="state_other[]" type="text"
                    placeholder="Type your state" maxlength="100" data-branch-index="__INDEX__" />
                  <input type="hidden" name="state[]" value="" data-branch-index="__INDEX__" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label">City</label>
                  <select class="form-select mci-city-select" name="city_select[]" data-branch-index="__INDEX__">
                    <option value="">Select state first</option>
                  </select>
                  <input class="form-control mt-2 d-none mci-city-other" name="city_other[]" type="text"
                    placeholder="Type city name" maxlength="100" data-branch-index="__INDEX__" />
                  <input type="hidden" name="city[]" value="" data-branch-index="__INDEX__" />
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label mci-field-label">Pincode / ZIP</label>
                  <input class="form-control" type="text" name="pincode[]" placeholder="e.g. 400001" />
                </div>
                <div class="col-6 col-md-4">
                  <label class="form-label mci-field-label">Latitude</label>
                  <input class="form-control" type="text" name="latitude[]" placeholder="e.g. 28.6139" />
                </div>
                <div class="col-6 col-md-4">
                  <label class="form-label mci-field-label">Longitude</label>
                  <input class="form-control" type="text" name="longitude[]" placeholder="e.g. 77.2090" />
                </div>
                <div class="col-12"><hr class="mci-inner-divider" /></div>
                <div class="col-12 col-md-4">
                  <label class="form-label mci-field-label">Phone</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                    <input class="form-control" type="tel" name="phone[]" placeholder="+1 555 000 0000" />
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label mci-field-label">Phone 2</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                    <input class="form-control" type="tel" name="phone_secondary[]" placeholder="+1 555 000 0001" />
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label mci-field-label">WhatsApp</label>
                  <div class="input-group">
                    <span class="input-group-text text-success"><i class="bi bi-whatsapp" aria-hidden="true"></i></span>
                    <input class="form-control" type="tel" name="whatsapp[]" placeholder="+1 555 000 0000" />
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label">Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                    <input class="form-control" type="email" name="email_contact[]" placeholder="hello@yourbusiness.com" />
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label mci-field-label">Website</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                    <input class="form-control" type="url" name="website[]" placeholder="https://…" />
                  </div>
                </div>
              </div>
            </div>
          </template>

          <hr class="mci-inner-divider" />

          <div class="row g-3">
            <div class="col-12">
              <div class="mci-submit-label mb-1">Social media &amp; links <span class="fw-normal text-muted text-lowercase">(optional)</span></div>
              <p class="text-muted small mb-0">Pages, @handles, and your video channel — all optional.</p>
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
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_pinterest">Pinterest</label>
              <div class="input-group">
                <span class="input-group-text" style="color:#e60023"><i class="bi bi-pinterest" aria-hidden="true"></i></span>
                <input class="form-control" id="social_pinterest" type="text" name="social_pinterest" placeholder="pinterest.com/yourpage" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_telegram">Telegram</label>
              <div class="input-group">
                <span class="input-group-text" style="color:#2aabee"><i class="bi bi-telegram" aria-hidden="true"></i></span>
                <input class="form-control" id="social_telegram" type="text" name="social_telegram" placeholder="t.me/yourchannel" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_threads">Threads</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-at" aria-hidden="true"></i></span>
                <input class="form-control" id="social_threads" type="text" name="social_threads" placeholder="threads.net/@yourhandle" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_snapchat">Snapchat</label>
              <div class="input-group">
                <span class="input-group-text" style="color:#fffc00;background:#222;"><i class="bi bi-snapchat" aria-hidden="true"></i></span>
                <input class="form-control" id="social_snapchat" type="text" name="social_snapchat" placeholder="@yourhandle" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="social_whatsapp_channel">WhatsApp Channel</label>
              <div class="input-group">
                <span class="input-group-text text-success"><i class="bi bi-whatsapp" aria-hidden="true"></i></span>
                <input class="form-control" id="social_whatsapp_channel" type="text" name="social_whatsapp_channel" placeholder="whatsapp.com/channel/…" />
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label mci-field-label" for="video_url">Video URL</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-play-circle" aria-hidden="true"></i></span>
                <input class="form-control" id="video_url" type="url" name="video_url" placeholder="YouTube or Vimeo link" />
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 5 — Hours only -->
        <div class="mci-step" data-step="5">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--5" aria-hidden="true"><i class="bi bi-clock"></i></div>
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
                  <select
                    class="form-select form-select-sm mci-hours-select"
                    name="hours[slot1_start][<?= $key ?>]"
                    aria-label="Open time slot 1 (<?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?>)"
                  >
                    <option value="">Open</option>
                    <?php foreach ($times as $t): ?>
                      <option><?= $t ?></option>
                    <?php endforeach; ?>
                  </select>
                  <span class="text-muted small">–</span>
                  <select
                    class="form-select form-select-sm mci-hours-select"
                    name="hours[slot1_end][<?= $key ?>]"
                    aria-label="Close time slot 1 (<?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?>)"
                  >
                    <option value="">Close</option>
                    <?php foreach ($times as $t): ?>
                      <option><?= $t ?></option>
                    <?php endforeach; ?>
                  </select>
                  <span class="text-muted small d-none d-sm-inline">2nd:</span>
                  <select
                    class="form-select form-select-sm mci-hours-select"
                    name="hours[slot2_start][<?= $key ?>]"
                    aria-label="Open time slot 2 (<?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?>)"
                  >
                    <option value="">—</option>
                    <?php foreach ($times as $t): ?>
                      <option><?= $t ?></option>
                    <?php endforeach; ?>
                  </select>
                  <span class="text-muted small">–</span>
                  <select
                    class="form-select form-select-sm mci-hours-select"
                    name="hours[slot2_end][<?= $key ?>]"
                    aria-label="Close time slot 2 (<?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?>)"
                  >
                    <option value="">—</option>
                    <?php foreach ($times as $t): ?>
                      <option><?= $t ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- STEP 6 — Photos -->
        <div class="mci-step" data-step="6">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--6" aria-hidden="true"><i class="bi bi-images"></i></div>
            <div>
              <div class="mci-step-header__title">Photos &amp; media</div>
              <div class="mci-step-header__desc">Logo, profile, banner, and gallery images.</div>
            </div>
          </div>
          <div class="mci-submit-label">Logo, profile &amp; banner</div>
          <p class="text-muted small mb-2">Crop each image to fit. All optional.</p>
          <p class="text-muted small mb-3">Maximum <strong>2&nbsp;MB per image</strong> for your listing. Photos are resized and compressed automatically when you choose them. If a file is still too large, use a free in-browser tool such as <a href="https://oneyfy.com/tools/?category=image-tools" target="_blank" rel="noopener noreferrer">Oneyfy image tools</a> (opens in a new tab), then upload again.</p>
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
              <div class="mci-drop-zone__sub">or click to browse (2&nbsp;MB max each; images are compressed automatically)</div>
              <button type="button" class="btn btn-sm btn-dark mt-2 mci-drop-zone__btn">Choose files</button>
              <input id="fileInputImages" class="d-none" type="file" name="images[]" accept="image/*" multiple />
            </div>
          </div>
          <div class="mci-photo-preview d-flex flex-wrap gap-2 mt-3" id="imagePreview"></div>
          <input type="hidden" id="galleryPathsHidden" name="gallery_paths" value="[]" />
        </div>

        <!-- STEP 7 — FAQs -->
        <div class="mci-step" data-step="7">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--7" aria-hidden="true"><i class="bi bi-question-circle"></i></div>
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

        <!-- STEP 8 — Review & publish -->
        <div class="mci-step" data-step="8">
          <div class="mci-step-header">
            <div class="mci-step-header__icon mci-step-header__icon--8" aria-hidden="true"><i class="bi bi-send"></i></div>
            <div>
              <div class="mci-step-header__title">Review &amp; publish</div>
              <div class="mci-step-header__desc"><?= htmlspecialchars((string) $step7HeaderDesc, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          </div>
          <!-- Context banner — filled by JS from window._mciSubmitContext -->
          <div id="mciContextBanner" class="alert mb-4 d-none"></div>

          <?php if (!$submitPublicGuest): ?>
          <?php else: ?>
          <p class="text-muted small mb-3"><?= htmlspecialchars((string) $step7AlertBody, ENT_QUOTES, 'UTF-8') ?></p>
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
            <p class="text-muted small mb-3">Already registered? <a href="/login/?return=<?= rawurlencode('/submit-business-listing/') ?>" class="fw-semibold">Log in</a> — your details will be linked automatically.</p>
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
          <?php endif; ?>

          <div class="mci-submit-preview mb-4<?= $submitHideStep7InlinePreview ? ' d-none' : '' ?>" id="mciStep7InlinePreview" aria-hidden="<?= $submitHideStep7InlinePreview ? 'true' : 'false' ?>">
            <div class="mci-preview-label">
              <i class="bi bi-eye me-1" aria-hidden="true"></i>Preview
            </div>

            <div class="mci-preview-photo" id="step7PreviewPhoto">
              <div class="mci-preview-photo__placeholder" id="previewPhotoPlaceholder">
                <i class="bi bi-image" aria-hidden="true"></i><span>Photos appear here</span>
              </div>
              <img id="previewPhotoImg" src="" alt="Listing photo preview" class="d-none" />
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
          <div class="mci-preview-listing-bar mb-4 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
            <div>
              <div class="fw-semibold small">Want to see how it looks?</div>
              <div class="text-muted small">Opens the full listing preview in a new tab. Your progress is saved in this browser.</div>
            </div>
            <button type="button" class="btn btn-outline-dark btn-sm flex-shrink-0" id="previewListingBtn">
              <i class="bi bi-eye me-1" aria-hidden="true"></i>Preview listing
            </button>
          </div>
          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="agreeTerms" required />
            <label class="form-check-label" for="agreeTerms">I agree to the <a href="/terms-of-use/">Terms of Service</a> for listing on My City Info. <span class="text-danger">*</span></label>
          </div>
          <div id="mciSubmitFeedback" class="alert d-none mb-3" role="alert" aria-live="polite"></div>
          <button class="btn btn-dark btn-lg px-5 w-100" type="submit" id="submitBtn">
            <i class="bi bi-check2-circle me-2" aria-hidden="true"></i><?= htmlspecialchars((string) $step7SubmitText, ENT_QUOTES, 'UTF-8') ?>
          </button>
        </div>

        <div class="mci-step-footer d-flex align-items-center gap-3 mt-4">
          <button type="button" class="btn btn-outline-dark" id="prevStep" style="display:none">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
          </button>
          <button type="button" class="btn btn-dark px-4" id="nextStep">
            Continue <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
          </button>
          <span class="text-muted small ms-auto" id="stepCounter">Step 1 of 8</span>
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
          <img id="cropModalImage" src="" alt="Image to crop" style="max-width:100%;display:block;" />
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
