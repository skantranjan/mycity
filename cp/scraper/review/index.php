<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/mci_require_session.php';
require_once __DIR__ . '/../../../includes/mci_session.php';
require_once __DIR__ . '/../../../api/v1/lib/config.php';
require_once __DIR__ . '/../../../api/v1/lib/db.php';
require_once __DIR__ . '/../../../api/v1/lib/scraper_service.php';
require_once __DIR__ . '/../../../api/v1/lib/jwt.php';
require_once __DIR__ . '/../../../includes/mci_cp_jwt.php';

mci_require_cp_session();

$jwtForJs = mci_cp_ensure_jwt();

$recordId = trim((string)($_GET['id'] ?? ''));
if ($recordId === '') {
    header('Location: /cp/scraper/results/');
    exit;
}

// Load the record from DB (server-side for initial render)
$pdo    = api_db();
$record = scraper_get($pdo, $recordId);

if ($record === null) {
    header('Location: /cp/scraper/results/');
    exit;
}

// Load categories for the select
$catStmt = $pdo->query('SELECT id, name, parent_id FROM mci_categories ORDER BY parent_id, name');
$allCats = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Build top-level only (parent_id = 0 or NULL)
$rootCats = array_filter($allCats, fn($c) => empty($c['parent_id']));

// Suggested tags from type hints
$typeHints    = $record['types_raw'] ?? [];
$suggestedTags = scraper_tag_hints($pdo, $typeHints);

$payload = is_array($record['payload_json']) ? $record['payload_json'] : [];
$group   = $payload['group']  ?? [];
$branch  = $payload['branch'] ?? [];
$hours   = $payload['hours']  ?? [];
$social  = $payload['social_links'] ?? [];

$sourceLabel = [
    'osm'           => 'OpenStreetMap',
    'tomtom'        => 'TomTom Places',
    'here'          => 'HERE Places',
    'google_places' => 'Google Places',
    'foursquare'    => 'Foursquare',
    'curl_scrape'   => 'cURL / HTML',
][$record['source']] ?? ucfirst($record['source']);

$statusBadge = match ($record['status']) {
    'pending_review' => '<span class="badge text-bg-warning">Pending review</span>',
    'imported'       => '<span class="badge text-bg-success">Imported</span>',
    'rejected'       => '<span class="badge text-bg-danger">Rejected</span>',
    default          => '<span class="badge text-bg-secondary">' . htmlspecialchars($record['status']) . '</span>',
};

$pageTitle = 'Review: ' . htmlspecialchars($record['name'] ?? '—') . ' — Scraper CP';
$cpActive  = 'scraper';
$hideCta   = true;
$appArea   = 'cp';

$daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$hoursMap   = [];
foreach ($hours as $h) {
    $day = strtolower(trim($h['day_of_week'] ?? ''));
    if ($day !== '') {
        $hoursMap[$day] = $h;
    }
}

ob_start();
?>
<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">

    <!-- ── Page header ─────────────────────────────────────────────────── -->
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
      <div>
        <a href="/cp/scraper/results/" class="text-muted text-decoration-none small">
          <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to queue
        </a>
        <div class="fw-semibold mt-1" style="font-size:var(--mci-text-xl);">
          <?= htmlspecialchars($record['name'] ?? '—') ?>
          <?= $statusBadge ?>
        </div>
        <div class="text-muted small mt-1">Source: <strong><?= htmlspecialchars($sourceLabel) ?></strong>
          · Scraped: <?= htmlspecialchars(substr((string)$record['created_at'], 0, 10)) ?>
        </div>
      </div>
    </div>

    <?php if ($record['status'] !== 'pending_review'): ?>
      <div class="alert alert-info">
        This record has already been <strong><?= htmlspecialchars($record['status']) ?></strong>
        and is read-only.
        <?php if ($record['status'] === 'rejected' && $record['rejection_reason']): ?>
          Reason: <?= htmlspecialchars($record['rejection_reason']) ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div id="mciFlash" class="d-none mb-3"></div>

    <div class="row g-4">

      <!-- ── LEFT: Source data (read-only) ───────────────────────────── -->
      <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-3">
            <div class="fw-semibold small text-uppercase text-muted mb-3">Raw source data</div>

            <?php if ($record['source_url']): ?>
              <div class="mb-2">
                <a href="<?= htmlspecialchars($record['source_url']) ?>" target="_blank" rel="noopener noreferrer"
                   class="btn btn-sm btn-outline-secondary w-100">
                  <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> View on <?= htmlspecialchars($sourceLabel) ?>
                </a>
              </div>
            <?php endif; ?>

            <?php if ($record['latitude'] && $record['longitude']): ?>
              <div class="mb-2">
                <a href="https://www.google.com/maps?q=<?= urlencode($record['latitude'] . ',' . $record['longitude']) ?>"
                   target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info w-100">
                  <i class="bi bi-map me-1" aria-hidden="true"></i> Google Maps
                </a>
              </div>
            <?php endif; ?>

            <dl class="small mb-0" style="row-gap:0.25rem;display:grid;grid-template-columns:auto 1fr;gap:0.2rem 0.5rem;">
              <dt class="text-muted">Source ID</dt>
              <dd class="text-truncate" title="<?= htmlspecialchars($record['source_id'] ?? '') ?>"><?= htmlspecialchars(substr((string)$record['source_id'], 0, 30)) ?></dd>

              <dt class="text-muted">Address</dt>
              <dd><?= htmlspecialchars($record['address'] ?? '—') ?></dd>

              <dt class="text-muted">Phone</dt>
              <dd><?= htmlspecialchars($record['phone'] ?? '—') ?></dd>

              <dt class="text-muted">Website</dt>
              <dd class="text-truncate">
                <?php if ($record['website']): ?>
                  <a href="<?= htmlspecialchars($record['website']) ?>" target="_blank" rel="noopener noreferrer">
                    <?= htmlspecialchars(parse_url($record['website'], PHP_URL_HOST) ?: $record['website']) ?>
                  </a>
                <?php else: ?>—<?php endif; ?>
              </dd>

              <dt class="text-muted">Category hint</dt>
              <dd><?= htmlspecialchars($record['category_hint'] ?? '—') ?></dd>

              <?php if (!empty($record['types_raw'])): ?>
              <dt class="text-muted">Types</dt>
              <dd>
                <?php foreach ($record['types_raw'] as $t): ?>
                  <span class="badge text-bg-light border me-1"><?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
              </dd>
              <?php endif; ?>

              <?php if (!empty($payload['hours_raw'])): ?>
              <dt class="text-muted">Hours (raw)</dt>
              <dd class="text-muted small" style="white-space:pre-wrap;"><?= htmlspecialchars($payload['hours_raw']) ?></dd>
              <?php endif; ?>
            </dl>
          </div>
        </div>
      </div>

      <!-- ── RIGHT: Import form ───────────────────────────────────────── -->
      <div class="col-12 col-md-8">
        <form id="mciReviewForm" novalidate>
          <input type="hidden" id="mciRecordId" value="<?= htmlspecialchars($recordId) ?>">

          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
              <div class="fw-semibold small text-uppercase text-muted mb-3">Business details</div>

              <div class="row g-3">
                <div class="col-12">
                  <label for="mciName" class="form-label fw-semibold">Business name <span class="text-danger">*</span></label>
                  <input type="text" id="mciName" name="name" class="form-control" required
                         value="<?= htmlspecialchars($group['name'] ?? $record['name'] ?? '') ?>">
                </div>

                <div class="col-12">
                  <label for="mciCategory" class="form-label fw-semibold">
                    Category <span class="text-danger">*</span>
                    <span class="text-muted fw-normal small ms-1">(required to import)</span>
                  </label>
                  <select id="mciCategory" name="parent_category_id" class="form-select" required>
                    <option value="0">— Select a category —</option>
                    <?php foreach ($rootCats as $cat): ?>
                      <option value="<?= (int)$cat['id'] ?>"
                        <?= ((int)($group['parent_category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (empty($group['parent_category_id'])): ?>
                    <div class="form-text text-danger fw-semibold">
                      <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>
                      Category must be set before importing.
                    </div>
                  <?php endif; ?>
                </div>

                <div class="col-12 col-sm-6">
                  <label for="mciTagline" class="form-label">Tagline</label>
                  <input type="text" id="mciTagline" name="tagline" class="form-control"
                         value="<?= htmlspecialchars($group['tagline'] ?? '') ?>">
                </div>
                <div class="col-12 col-sm-6">
                  <label for="mciEstYear" class="form-label">Established year</label>
                  <input type="number" id="mciEstYear" name="established_year" class="form-control"
                         min="1800" max="<?= date('Y') ?>"
                         value="<?= htmlspecialchars((string)($group['established_year'] ?? '')) ?>">
                </div>

                <div class="col-12">
                  <label for="mciDesc" class="form-label">Description</label>
                  <textarea id="mciDesc" name="description" class="form-control" rows="3"><?= htmlspecialchars($group['description'] ?? '') ?></textarea>
                </div>

                <div class="col-12 col-sm-6">
                  <label for="mciWebsite" class="form-label">Website</label>
                  <input type="url" id="mciWebsite" name="website_url" class="form-control"
                         value="<?= htmlspecialchars($group['website_url'] ?? '') ?>">
                </div>
                <div class="col-12 col-sm-6">
                  <label for="mciEmail" class="form-label">Email</label>
                  <input type="email" id="mciEmail" name="email" class="form-control"
                         value="<?= htmlspecialchars($group['email'] ?? '') ?>">
                </div>

                <div class="col-12 col-sm-6">
                  <label for="mciPriceRange" class="form-label">Price range</label>
                  <select id="mciPriceRange" name="price_range" class="form-select">
                    <option value="">— None —</option>
                    <?php foreach (['free', 'moderate', 'pricey', 'ultra'] as $pr): ?>
                      <option value="<?= $pr ?>" <?= ($group['price_range'] ?? '') === $pr ? 'selected' : '' ?>>
                        <?= ucfirst($pr) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-12 col-sm-6">
                  <label for="mciPageTitle" class="form-label">SEO page title</label>
                  <input type="text" id="mciPageTitle" name="page_title" class="form-control"
                         value="<?= htmlspecialchars($group['page_title'] ?? '') ?>">
                </div>
              </div>
            </div>
          </div><!-- /card -->

          <!-- ── Address & contact ───────────────────────────────────── -->
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
              <div class="fw-semibold small text-uppercase text-muted mb-3">Address &amp; contact</div>
              <div class="row g-3">
                <div class="col-12">
                  <label for="mciAddrLine1" class="form-label">Address line 1</label>
                  <input type="text" id="mciAddrLine1" name="address_line1" class="form-control"
                         value="<?= htmlspecialchars($branch['address_line1'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label for="mciAddrLine2" class="form-label">Address line 2</label>
                  <input type="text" id="mciAddrLine2" name="address_line2" class="form-control"
                         value="<?= htmlspecialchars($branch['address_line2'] ?? '') ?>">
                </div>
                <div class="col-12 col-sm-4">
                  <label for="mciCity" class="form-label">City</label>
                  <input type="text" id="mciCity" name="city" class="form-control"
                         value="<?= htmlspecialchars($branch['city'] ?? '') ?>">
                </div>
                <div class="col-12 col-sm-4">
                  <label for="mciState" class="form-label">State</label>
                  <input type="text" id="mciState" name="state" class="form-control"
                         value="<?= htmlspecialchars($branch['state'] ?? '') ?>">
                </div>
                <div class="col-12 col-sm-4">
                  <label for="mciPincode" class="form-label">Pincode</label>
                  <input type="text" id="mciPincode" name="pincode" class="form-control"
                         value="<?= htmlspecialchars($branch['pincode'] ?? '') ?>">
                </div>
                <div class="col-12 col-sm-4">
                  <label for="mciLat" class="form-label">Latitude</label>
                  <input type="text" id="mciLat" name="latitude" class="form-control"
                         value="<?= htmlspecialchars((string)($branch['latitude'] ?? '')) ?>">
                </div>
                <div class="col-12 col-sm-4">
                  <label for="mciLon" class="form-label">Longitude</label>
                  <input type="text" id="mciLon" name="longitude" class="form-control"
                         value="<?= htmlspecialchars((string)($branch['longitude'] ?? '')) ?>">
                </div>
                <div class="col-12 col-sm-4">
                  <label for="mciPhone" class="form-label">Phone (primary)</label>
                  <input type="text" id="mciPhone" name="phone_primary" class="form-control"
                         value="<?= htmlspecialchars($branch['phone_primary'] ?? '') ?>">
                </div>
                <div class="col-12 col-sm-4">
                  <label for="mciPhone2" class="form-label">Phone (secondary)</label>
                  <input type="text" id="mciPhone2" name="phone_secondary" class="form-control"
                         value="<?= htmlspecialchars($branch['phone_secondary'] ?? '') ?>">
                </div>
                <div class="col-12 col-sm-4">
                  <label for="mciWhatsapp" class="form-label">WhatsApp</label>
                  <input type="text" id="mciWhatsapp" name="whatsapp_number" class="form-control"
                         value="<?= htmlspecialchars($branch['whatsapp_number'] ?? '') ?>">
                </div>
              </div>
            </div>
          </div>

          <!-- ── Opening hours ───────────────────────────────────────── -->
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
              <div class="fw-semibold small text-uppercase text-muted mb-3">Opening hours</div>
              <?php if (!empty($payload['hours_raw'])): ?>
                <div class="text-muted small mb-2">
                  <i class="bi bi-info-circle me-1"></i>
                  Raw hours string: <code><?= htmlspecialchars($payload['hours_raw']) ?></code>
                </div>
              <?php endif; ?>
              <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                  <thead><tr><th>Day</th><th>Closed</th><th>Opens at</th><th>Closes at</th></tr></thead>
                  <tbody>
                    <?php foreach ($daysOfWeek as $day):
                        $h = $hoursMap[$day] ?? [];
                        $isClosed  = !empty($h['is_closed']);
                        $opensAt   = $h['opens_at']  ?? '';
                        $closesAt  = $h['closes_at'] ?? '';
                    ?>
                    <tr>
                      <td class="fw-semibold small text-capitalize"><?= $day ?></td>
                      <td>
                        <input type="checkbox" class="form-check-input mci-hour-closed"
                               name="hours[<?= $day ?>][is_closed]"
                               id="hc_<?= $day ?>" <?= $isClosed ? 'checked' : '' ?>
                               data-day="<?= $day ?>">
                      </td>
                      <td>
                        <input type="time" class="form-control form-control-sm mci-hour-open"
                               name="hours[<?= $day ?>][opens_at]" id="ho_<?= $day ?>"
                               value="<?= htmlspecialchars($opensAt) ?>"
                               <?= $isClosed ? 'disabled' : '' ?>>
                      </td>
                      <td>
                        <input type="time" class="form-control form-control-sm mci-hour-close"
                               name="hours[<?= $day ?>][closes_at]" id="hc2_<?= $day ?>"
                               value="<?= htmlspecialchars($closesAt) ?>"
                               <?= $isClosed ? 'disabled' : '' ?>>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- ── Suggested tags ─────────────────────────────────────── -->
          <?php if (!empty($suggestedTags)): ?>
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
              <div class="fw-semibold small text-uppercase text-muted mb-2">Suggested tags</div>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach ($suggestedTags as $tag): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="tag_ids[]"
                           value="<?= (int)$tag['id'] ?>" id="tag_<?= (int)$tag['id'] ?>"
                           <?= in_array($tag['id'], $group['tag_ids'] ?? [], false) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="tag_<?= (int)$tag['id'] ?>">
                      <?= htmlspecialchars($tag['name']) ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- ── Social links ────────────────────────────────────────── -->
          <?php if (!empty($social)): ?>
          <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
              <div class="fw-semibold small text-uppercase text-muted mb-3">Social links</div>
              <div class="row g-2">
                <?php foreach ($social as $i => $sl): ?>
                <div class="col-12 col-sm-6">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text text-capitalize"><?= htmlspecialchars($sl['platform'] ?? '') ?></span>
                    <input type="url" class="form-control"
                           name="social[<?= $i ?>][url]"
                           value="<?= htmlspecialchars($sl['url'] ?? '') ?>">
                    <input type="hidden" name="social[<?= $i ?>][platform]"
                           value="<?= htmlspecialchars($sl['platform'] ?? '') ?>">
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- ── Action buttons ──────────────────────────────────────── -->
          <?php if ($record['status'] === 'pending_review'): ?>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" id="mciSaveBtn" class="btn btn-outline-secondary">
              <i class="bi bi-floppy me-1" aria-hidden="true"></i>Save changes
            </button>
            <button type="button" id="mciImportLiveBtn" class="btn btn-success">
              <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Import as Live
            </button>
            <button type="button" id="mciImportDraftBtn" class="btn btn-outline-primary">
              <i class="bi bi-file-earmark me-1" aria-hidden="true"></i>Import as Draft
            </button>
            <button type="button" id="mciRejectBtn" class="btn btn-outline-danger ms-auto">
              <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Reject
            </button>
          </div>
          <?php endif; ?>

        </form>
      </div><!-- /col right -->
    </div><!-- /row inner -->

    <!-- ── Reject modal ────────────────────────────────────────────────── -->
    <div class="modal fade" id="mciRejectModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Reject record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <label for="mciRejectReason" class="form-label small fw-semibold">Reason (optional)</label>
            <input type="text" id="mciRejectReason" class="form-control form-control-sm"
                   placeholder="e.g. duplicate, irrelevant, wrong city…">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-sm btn-danger" id="mciRejectConfirm">Reject</button>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /col outer -->
</div><!-- /row outer -->

<script>
(function () {
  'use strict';

  const API      = '/api/v1/cp/scraper';
  const JWT      = <?= json_encode($jwtForJs) ?>;
  const AUTH     = JWT ? { 'Authorization': 'Bearer ' + JWT } : {};
  const recordId = document.getElementById('mciRecordId').value;

  // ── Hours: toggle disabled on closed checkbox ───────────────────────────
  document.querySelectorAll('.mci-hour-closed').forEach(function (cb) {
    cb.addEventListener('change', function () {
      const day   = cb.dataset.day;
      const open  = document.getElementById('ho_'  + day);
      const close = document.getElementById('hc2_' + day);
      if (open)  open.disabled  = cb.checked;
      if (close) close.disabled = cb.checked;
    });
  });

  // ── Build payload from form ─────────────────────────────────────────────
  function buildPayload() {
    const f     = document.getElementById('mciReviewForm');
    const tags  = [...f.querySelectorAll('input[name="tag_ids[]"]:checked')].map(i => parseInt(i.value));

    const hours = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'].map(function (day) {
      const closed = document.getElementById('hc_'  + day)?.checked ?? false;
      const opens  = document.getElementById('ho_'  + day)?.value   || null;
      const closes = document.getElementById('hc2_' + day)?.value   || null;
      return {
        day_of_week: day,
        opens_at:    closed ? null : opens,
        closes_at:   closed ? null : closes,
        is_closed:   closed,
      };
    });

    const social = [];
    f.querySelectorAll('input[name^="social["]').forEach(function (inp) {
      const m = inp.name.match(/^social\[(\d+)\]\[(\w+)\]$/);
      if (!m) return;
      const i = parseInt(m[1]);
      if (!social[i]) social[i] = {};
      social[i][m[2]] = inp.value;
    });

    return {
      source:      '<?= htmlspecialchars($record['source']) ?>',
      data_source: '<?= htmlspecialchars($payload['data_source'] ?? ('scrape_' . $record['source'])) ?>',
      group: {
        name:               val('mciName'),
        tagline:            val('mciTagline'),
        description:        val('mciDesc'),
        established_year:   intOrNull(val('mciEstYear')),
        website_url:        val('mciWebsite'),
        email:              val('mciEmail'),
        parent_category_id: parseInt(val('mciCategory')) || 0,
        price_range:        val('mciPriceRange') || null,
        page_title:         val('mciPageTitle'),
        meta_description:   null,
        meta_keywords:      null,
        tag_ids:            tags,
        tag_hints:          [],
      },
      branch: {
        address_line1:   val('mciAddrLine1'),
        address_line2:   val('mciAddrLine2') || null,
        city:            val('mciCity'),
        state:           val('mciState')    || null,
        country:         'India',
        pincode:         val('mciPincode')  || null,
        latitude:        floatOrNull(val('mciLat')),
        longitude:       floatOrNull(val('mciLon')),
        phone_primary:   val('mciPhone')    || null,
        phone_secondary: val('mciPhone2')   || null,
        whatsapp_number: val('mciWhatsapp') || null,
      },
      hours:        hours,
      hours_raw:    <?= json_encode($payload['hours_raw'] ?? null) ?>,
      social_links: social.filter(Boolean),
    };
  }

  function val(id) {
    const el = document.getElementById(id);
    return el ? el.value.trim() : '';
  }
  function intOrNull(s) { const n = parseInt(s); return isNaN(n) ? null : n; }
  function floatOrNull(s) { const n = parseFloat(s); return isNaN(n) ? null : n; }

  function showFlash(msg, type = 'success') {
    const el = document.getElementById('mciFlash');
    el.className = `alert alert-${type}`;
    el.textContent = msg;
    el.classList.remove('d-none');
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function setButtonsDisabled(disabled) {
    ['mciSaveBtn','mciImportLiveBtn','mciImportDraftBtn','mciRejectBtn'].forEach(function (id) {
      const el = document.getElementById(id);
      if (el) el.disabled = disabled;
    });
  }

  // ── Save changes ────────────────────────────────────────────────────────
  document.getElementById('mciSaveBtn')?.addEventListener('click', async function () {
    setButtonsDisabled(true);
    try {
      const res  = await fetch(`${API}/results/${encodeURIComponent(recordId)}/update`, {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/json', ...AUTH },
        body: JSON.stringify({ payload: buildPayload() }),
      });
      const json = await res.json();
      json.ok ? showFlash('Changes saved.') : showFlash('Save failed: ' + (json.error || ''), 'danger');
    } catch (e) {
      showFlash('Network error.', 'danger');
    } finally {
      setButtonsDisabled(false);
    }
  });

  // ── Import ──────────────────────────────────────────────────────────────
  async function doImport(status) {
    const catId = parseInt(document.getElementById('mciCategory').value) || 0;
    if (catId <= 0) {
      document.getElementById('mciCategory').classList.add('is-invalid');
      showFlash('Please select a category before importing.', 'warning');
      document.getElementById('mciCategory').scrollIntoView({ behavior: 'smooth' });
      return;
    }
    document.getElementById('mciCategory').classList.remove('is-invalid');

    setButtonsDisabled(true);

    // First save payload, then approve
    try {
      await fetch(`${API}/results/${encodeURIComponent(recordId)}/update`, {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/json', ...AUTH },
        body: JSON.stringify({ payload: buildPayload() }),
      });
    } catch (_) {}

    try {
      const res  = await fetch(`${API}/results/${encodeURIComponent(recordId)}/approve`, {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/json', ...AUTH },
        body: JSON.stringify({ status }),
      });
      const json = await res.json();
      if (json.ok) {
        showFlash(`Imported successfully as ${status}! Redirecting…`);
        setTimeout(() => { window.location.href = '/cp/scraper/results/'; }, 1500);
      } else {
        showFlash('Import failed: ' + (json.error || ''), 'danger');
        setButtonsDisabled(false);
      }
    } catch (e) {
      showFlash('Network error.', 'danger');
      setButtonsDisabled(false);
    }
  }

  document.getElementById('mciImportLiveBtn')?.addEventListener('click', () => doImport('live'));
  document.getElementById('mciImportDraftBtn')?.addEventListener('click', () => doImport('draft'));

  // ── Reject ──────────────────────────────────────────────────────────────
  document.getElementById('mciRejectBtn')?.addEventListener('click', function () {
    document.getElementById('mciRejectReason').value = '';
    new bootstrap.Modal(document.getElementById('mciRejectModal')).show();
  });

  document.getElementById('mciRejectConfirm')?.addEventListener('click', async function () {
    const reason = document.getElementById('mciRejectReason').value.trim() || null;
    try {
      const res  = await fetch(`${API}/results/${encodeURIComponent(recordId)}/reject`, {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/json', ...AUTH },
        body: JSON.stringify({ reason }),
      });
      const json = await res.json();
      bootstrap.Modal.getInstance(document.getElementById('mciRejectModal'))?.hide();
      if (json.ok) {
        showFlash('Record rejected. Redirecting…');
        setTimeout(() => { window.location.href = '/cp/scraper/results/'; }, 1200);
      }
    } catch (_) {}
  });
}());
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../../views/layout.php';
?>
