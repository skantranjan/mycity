<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';

mci_require_cp_session();

$pageTitle = 'CP Dashboard - My City Info';
$activePage = '';
$cpActive = 'dashboard';
$hideCta = true;
$appArea = 'cp';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">Dashboard</div>
            <div class="text-muted small">Admin overview (UI demo).</div>
          </div>
          <div class="text-muted small">Super admin only</div>
        </div>

        <?php if (($_GET['notice'] ?? '') === 'forbidden'): ?>
          <div class="alert alert-warning py-2 small mb-3" role="status">That page is only available to super admins.</div>
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-12 col-md-4">
            <div class="bg-light border rounded-3 p-3 h-100">
              <div class="text-muted small">Registered Subscribers</div>
              <div class="fs-4 fw-bold">128</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="bg-light border rounded-3 p-3 h-100">
              <div class="text-muted small">Total listings</div>
              <div class="fs-4 fw-bold">342</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="bg-light border rounded-3 p-3 h-100">
              <div class="text-muted small">Pending approvals</div>
              <div class="fs-4 fw-bold">14</div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <div class="fw-semibold mb-2">Recent moderation queue</div>
          <div class="table-responsive">
            <table class="table table-bordered align-middle bg-white">
              <thead class="table-light">
                <tr>
                  <th>Listing</th>
                  <th>Submitted as</th>
                  <th>Requested action</th>
                  <th style="min-width: 220px;">Admin actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $rows = [
                  ['title' => 'Locker Shop UK Ltd', 'by' => 'Anonymous', 'action' => 'Approve & make live'],
                  ['title' => 'JXF Painting Service', 'by' => 'Registered', 'action' => 'Verify details'],
                  ['title' => 'Hunter Hill Physiotherapy', 'by' => 'Anonymous', 'action' => 'Approve & open for claim'],
                ];
                foreach ($rows as $r):
                ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($r['title']) ?></td>
                    <td><span class="badge text-bg-light border"><?= htmlspecialchars($r['by']) ?></span></td>
                    <td class="text-muted small"><?= htmlspecialchars($r['action']) ?></td>
                    <td>
                      <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-sm btn-outline-dark" href="/cp/anonymous-approvals/">Review</a>
                        <button class="btn btn-sm btn-outline-secondary" type="button" disabled>Reject</button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="text-muted small mt-2">Approval workflow wiring comes in a later phase.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>

