<?php

declare(strict_types=1);

/**
 * Expects: $tzSelected (string) current IANA id or ''.
 * Optional: $fieldId (default 'timezone'), $fieldName (default 'timezone').
 */
require_once __DIR__ . '/../../includes/mci_timezone.php';

$tzSelected = isset($tzSelected) ? (string) $tzSelected : '';
$fieldId = isset($fieldId) ? (string) $fieldId : 'timezone';
$fieldName = isset($fieldName) ? (string) $fieldName : 'timezone';

$opts = mci_timezone_list_options();
if ($tzSelected !== '') {
    $found = false;
    foreach ($opts as $o) {
        if (($o['id'] ?? '') === $tzSelected) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $orphanLabel = mci_timezone_is_valid($tzSelected)
            ? mci_timezone_display_label($tzSelected)
            : '(invalid) ' . $tzSelected;
        array_unshift($opts, [
            'id' => $tzSelected,
            'label' => $orphanLabel,
            'region' => 'Other',
            'offset_minutes' => 0,
        ]);
    }
}
?>
<div class="col-12 col-md-4">
  <label class="form-label" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>">Timezone</label>
  <input type="search" class="form-control form-control-sm mb-2" id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>_filter" placeholder="Filter by city or offset…" autocomplete="off" aria-label="Filter timezones" />
  <select class="form-select" id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>">
    <option value="">— Not set —</option>
    <?php foreach ($opts as $opt): ?>
    <option value="<?= htmlspecialchars((string) ($opt['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"<?= ($tzSelected === ($opt['id'] ?? '')) ? ' selected' : '' ?>><?= htmlspecialchars((string) ($opt['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
    <?php endforeach; ?>
  </select>
  <div class="form-text">IANA zone (e.g. America/New_York). Stored for correct date/time and DST.</div>
</div>
<script>
(function () {
  var f = document.getElementById('<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>_filter');
  var sel = document.getElementById('<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>');
  if (!f || !sel) return;
  f.addEventListener('input', function () {
    var q = (f.value || '').toLowerCase().trim();
    var opts = sel.querySelectorAll('option');
    opts.forEach(function (o) {
      if (!o.value) {
        o.hidden = false;
        return;
      }
      var t = (o.textContent || '').toLowerCase();
      var v = (o.value || '').toLowerCase();
      o.hidden = q !== '' && t.indexOf(q) === -1 && v.indexOf(q) === -1;
    });
  });
})();
</script>
