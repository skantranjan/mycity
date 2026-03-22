<?php
$pageTitle = 'Leads - My City Info';
$activePage = '';
$subActive = 'leads';
$hideCta = true;
$appArea = 'subscriber';

$csrfAction = 'subscriber_leads_status';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$statusFlash = '';
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$statusFilter = $statusFilter !== '' ? $statusFilter : 'all';
$searchBusiness = trim((string) ($_GET['business'] ?? ''));
$fromDate = trim((string) ($_GET['from_date'] ?? ''));
$toDate = trim((string) ($_GET['to_date'] ?? ''));

$updateId = trim((string) ($_POST['lead_id'] ?? ''));
$newStatus = trim((string) ($_POST['new_status'] ?? ''));

// UI demo: hardcoded lead data
$leads = [
    [
        'id'       => 'a1b2c3d4-0001-4000-8000-000000000001',
        'listing'  => 'Property 852',
        'name'     => 'James Harrington',
        'phone'    => '+44 7700 900123',
        'email'    => 'james.h@example.com',
        'message'  => 'Hi, I am looking for a 3-bed flat near the city centre. Do you have anything available from next month?',
        'date'     => '2026-03-20',
        'when'     => '2 hours ago',
        'status'   => 'New',
    ],
    [
        'id'       => 'a1b2c3d4-0002-4000-8000-000000000002',
        'listing'  => 'JXF Painting Service',
        'name'     => 'Sara Mitchell',
        'phone'    => '+44 7700 900456',
        'email'    => 'sara.m@company.org',
        'message'  => 'We need a quote for repainting a 2,000 sq ft office space. Can you visit this week?',
        'date'     => '2026-03-19',
        'when'     => 'Yesterday',
        'status'   => 'Contacted',
    ],
    [
        'id'       => 'a1b2c3d4-0003-4000-8000-000000000003',
        'listing'  => 'Locker Shop UK Ltd',
        'name'     => 'School Admin Team',
        'phone'    => '+44 7700 900789',
        'email'    => 'ops@school-demo.org',
        'message'  => 'Need 120 student lockers with delivery by end of April. Can you provide a quote with options?',
        'date'     => '2026-03-18',
        'when'     => '2 days ago',
        'status'   => 'Converted',
    ],
    [
        'id'       => 'a1b2c3d4-0004-4000-8000-000000000004',
        'listing'  => 'Property 852',
        'name'     => 'Linda Farrow',
        'phone'    => '+44 7700 900321',
        'email'    => 'linda.f@example.net',
        'message'  => 'Interested in viewing a 1-bed studio if available. Please call me back.',
        'date'     => '2026-03-15',
        'when'     => '5 days ago',
        'status'   => 'Closed',
    ],
    [
        'id'       => 'a1b2c3d4-0005-4000-8000-000000000005',
        'listing'  => 'JXF Painting Service',
        'name'     => 'Oliver Brooks',
        'phone'    => '+44 7700 900654',
        'email'    => '',
        'message'  => 'Looking for someone to paint the exterior of my house. 3 bed semi-detached. How much roughly?',
        'date'     => '2026-03-12',
        'when'     => '8 days ago',
        'status'   => 'New',
    ],
];

$validStatuses = ['New', 'Contacted', 'Converted', 'Closed'];

// UI demo: update status in-memory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $updateId !== '' && in_array($newStatus, $validStatuses, true)) {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $statusFlash = 'error:Invalid request token. Please refresh and try again.';
    } else {
        foreach ($leads as &$lead) {
            if ((string) $lead['id'] === (string) $updateId) {
                $lead['status'] = $newStatus;
                $statusFlash = 'success:Lead status updated to "' . htmlspecialchars($newStatus, ENT_QUOTES, 'UTF-8') . '".';
                break;
            }
        }
        unset($lead);
    }
}

// Filter by status
$statusMap = [
    'all'       => 'all',
    'new'       => 'New',
    'contacted' => 'Contacted',
    'converted' => 'Converted',
    'closed'    => 'Closed',
];
$wantedStatus = $statusMap[strtolower($statusFilter)] ?? 'all';
if ($wantedStatus !== 'all') {
    $leads = array_values(array_filter($leads, static fn(array $l): bool => (string) $l['status'] === $wantedStatus));
}

// Filter by business name search
if ($searchBusiness !== '') {
    $needle = strtolower($searchBusiness);
    $leads = array_values(array_filter($leads, static fn(array $l): bool => str_contains(strtolower((string) $l['listing']), $needle)));
}

// Filter by date range
$fromDateObj = DateTime::createFromFormat('Y-m-d', $fromDate) ?: null;
$toDateObj   = DateTime::createFromFormat('Y-m-d', $toDate) ?: null;
if ($fromDateObj || $toDateObj) {
    $leads = array_values(array_filter($leads, static function (array $l) use ($fromDateObj, $toDateObj): bool {
        $itemDate = DateTime::createFromFormat('Y-m-d', (string) ($l['date'] ?? '')) ?: null;
        if ($itemDate === null) return false;
        if ($fromDateObj && $itemDate < $fromDateObj) return false;
        if ($toDateObj   && $itemDate > $toDateObj)   return false;
        return true;
    }));
}

$totalAll  = 5;
$totalNew  = 2;
$totalCont = 1;
$totalConv = 1;

// JSON-encode leads for JS split panel
$leadsJson = json_encode(
    array_map(static fn(array $l) => [
        'id'      => $l['id'],
        'name'    => $l['name'],
        'phone'   => $l['phone'],
        'email'   => $l['email'],
        'listing' => $l['listing'],
        'message' => $l['message'],
        'date'    => $l['date'],
        'status'  => $l['status'],
    ], $leads),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">

    <!-- Stats strip -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
          <div class="fw-bold fs-4"><?= $totalAll ?></div>
          <div class="text-muted small">Total Leads</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
          <div class="fw-bold fs-4 text-primary"><?= $totalNew ?></div>
          <div class="text-muted small">New</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
          <div class="fw-bold fs-4 text-warning"><?= $totalCont ?></div>
          <div class="text-muted small">Contacted</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
          <div class="fw-bold fs-4 text-success"><?= $totalConv ?></div>
          <div class="text-muted small">Converted</div>
        </div>
      </div>
    </div>

    <!-- Split-panel: table + detail panel -->
    <div class="d-flex gap-3 align-items-start" id="mciLeadsSplit">

      <!-- Left: leads table card -->
      <div class="card border-0 shadow-sm bg-white flex-grow-1 mci-leads-list-col" id="mciLeadsListCol">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div>
              <div class="fw-semibold">Leads</div>
              <div class="text-muted small">Enquiries from customers interested in your listings.</div>
            </div>
            <span class="badge text-bg-light border px-3 py-2">Showing: <?= count($leads) ?></span>
          </div>

          <?php if ($statusFlash !== ''): ?>
            <?php [$flashType, $flashMsg] = explode(':', $statusFlash, 2); ?>
            <div class="alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> py-2 small mb-3" role="status">
              <?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <!-- Filter bar -->
          <form method="get" class="mb-3">
            <div class="row g-2 align-items-end">
              <div class="col-6 col-lg-auto">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm" aria-label="Filter by lead status">
                  <option value="all"      <?= $wantedStatus === 'all'       ? 'selected' : '' ?>>All</option>
                  <option value="new"      <?= $wantedStatus === 'New'       ? 'selected' : '' ?>>New</option>
                  <option value="contacted"<?= $wantedStatus === 'Contacted' ? 'selected' : '' ?>>Contacted</option>
                  <option value="converted"<?= $wantedStatus === 'Converted' ? 'selected' : '' ?>>Converted</option>
                  <option value="closed"   <?= $wantedStatus === 'Closed'    ? 'selected' : '' ?>>Closed</option>
                </select>
              </div>
              <div class="col-6 col-lg-auto">
                <label class="form-label small mb-1">Business</label>
                <input type="text" name="business" class="form-control form-control-sm" placeholder="Search business…"
                  value="<?= htmlspecialchars($searchBusiness, ENT_QUOTES, 'UTF-8') ?>" style="min-width:130px;" />
              </div>
              <div class="col-6 col-lg-auto">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="from_date" class="form-control form-control-sm"
                  value="<?= htmlspecialchars($fromDate, ENT_QUOTES, 'UTF-8') ?>" />
              </div>
              <div class="col-6 col-lg-auto">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="to_date" class="form-control form-control-sm"
                  value="<?= htmlspecialchars($toDate, ENT_QUOTES, 'UTF-8') ?>" />
              </div>
              <div class="col-12 col-lg-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-dark">Filter</button>
                <?php if ($statusFilter !== 'all' || $searchBusiness !== '' || $fromDate !== '' || $toDate !== ''): ?>
                  <a href="/subscriber/leads/" class="btn btn-sm btn-outline-secondary">Clear</a>
                <?php endif; ?>
              </div>
            </div>
          </form>

          <?php if (count($leads) === 0): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-2 d-block mb-2" aria-hidden="true"></i>
              <div class="fw-semibold">No leads found</div>
              <div class="small mt-1">Try adjusting your filters, or <a href="/subscriber/listings/">manage your listings</a> to attract more enquiries.</div>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle bg-white mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Lead</th>
                    <th>Listing</th>
                    <th>Date</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="mciLeadsTableBody">
                  <?php foreach ($leads as $lead):
                    $badgeClass = match($lead['status']) {
                        'New'       => 'text-bg-primary',
                        'Contacted' => 'text-bg-warning',
                        'Converted' => 'text-bg-success',
                        'Closed'    => 'text-bg-secondary',
                        default     => 'text-bg-light border',
                    };
                  ?>
                    <tr class="mci-lead-row" style="cursor:pointer;"
                      data-lead-id="<?= htmlspecialchars($lead['id'], ENT_QUOTES) ?>">
                      <td>
                        <div class="fw-semibold small"><?= htmlspecialchars($lead['name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($lead['phone']) ?></div>
                      </td>
                      <td class="small"><?= htmlspecialchars($lead['listing']) ?></td>
                      <td class="small text-muted text-nowrap"><?= htmlspecialchars($lead['when']) ?></td>
                      <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($lead['status']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right: detail panel (hidden until a row is clicked) -->
      <div class="card border-0 shadow-sm bg-white mci-lead-detail-panel" id="mciLeadDetailPanel" hidden
        style="width:340px;min-width:300px;flex-shrink:0;">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="fw-semibold">Lead Detail</div>
            <button type="button" class="btn-close" id="mciLeadPanelClose" aria-label="Close detail"></button>
          </div>

          <dl class="row mb-3">
            <dt class="col-4 text-muted small">Name</dt>
            <dd class="col-8 small" id="ldName">—</dd>
            <dt class="col-4 text-muted small">Phone</dt>
            <dd class="col-8 small" id="ldPhone">—</dd>
            <dt class="col-4 text-muted small">Email</dt>
            <dd class="col-8 small" id="ldEmail">—</dd>
            <dt class="col-4 text-muted small">Listing</dt>
            <dd class="col-8 small" id="ldListing">—</dd>
            <dt class="col-4 text-muted small">Date</dt>
            <dd class="col-8 small" id="ldDate">—</dd>
            <dt class="col-4 text-muted small">Status</dt>
            <dd class="col-8 small" id="ldStatus">—</dd>
          </dl>

          <div class="mb-3">
            <div class="text-muted small mb-1">Message</div>
            <div class="small border rounded p-2 bg-light" id="ldMessage" style="white-space:pre-wrap;word-break:break-word;">—</div>
          </div>

          <!-- Inline status update -->
          <form method="post" action="" class="d-flex align-items-center gap-2 mt-3">
            <input type="hidden" name="lead_id" id="ldUpdateId" value="" />
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <select name="new_status" id="ldUpdateStatus" class="form-select form-select-sm" aria-label="Update status" style="flex:1;">
              <?php foreach ($validStatuses as $vs): ?>
                <option value="<?= htmlspecialchars($vs) ?>"><?= htmlspecialchars($vs) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-dark text-nowrap">Update</button>
          </form>
        </div>
      </div>

    </div><!-- /split -->

  </div>
</div>

<style>
#mciLeadsSplit { flex-wrap: nowrap; }
.mci-lead-row.is-active { background: var(--mci-color-primary-soft, #eef2ff); }
@media (max-width: 767px) {
  #mciLeadsSplit { flex-wrap: wrap; }
  .mci-lead-detail-panel { width: 100% !important; min-width: 0 !important; }
}
</style>

<script>
(function () {
  var leadsData = <?= $leadsJson ?>;
  var leadsMap  = {};
  leadsData.forEach(function (l) { leadsMap[l.id] = l; });

  var panel     = document.getElementById('mciLeadDetailPanel');
  var closeBtn  = document.getElementById('mciLeadPanelClose');
  var activeRow = null;

  function setText(id, val) {
    var el = document.getElementById(id);
    if (el) el.textContent = val || '—';
  }

  function openPanel(lead) {
    setText('ldName',    lead.name);
    setText('ldPhone',   lead.phone);
    setText('ldEmail',   lead.email);
    setText('ldListing', lead.listing);
    setText('ldDate',    lead.date);
    setText('ldStatus',  lead.status);
    setText('ldMessage', lead.message);

    var updId  = document.getElementById('ldUpdateId');
    var updSel = document.getElementById('ldUpdateStatus');
    if (updId)  updId.value  = lead.id;
    if (updSel) updSel.value = lead.status;

    panel.hidden = false;
  }

  function closePanel() {
    panel.hidden = true;
    if (activeRow) { activeRow.classList.remove('is-active'); activeRow = null; }
  }

  document.querySelectorAll('.mci-lead-row').forEach(function (row) {
    row.addEventListener('click', function () {
      var id   = row.dataset.leadId;
      var lead = leadsMap[id];
      if (!lead) return;

      if (activeRow) activeRow.classList.remove('is-active');
      row.classList.add('is-active');
      activeRow = row;

      openPanel(lead);
    });
  });

  if (closeBtn) closeBtn.addEventListener('click', closePanel);
}());
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
