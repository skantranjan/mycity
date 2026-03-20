<?php
// Business hours grid for submit page.
// Renders:
// - Day checkbox
// - 1st time slot start/end (dropdowns)
// - 2nd time slot start/end (dropdowns; always present but can be left blank)
//
// Optional inputs:
// - $days: array of day labels (default Mon..Sun)
// - $namePrefix: prefix for input names (default 'hours')

$days = $days ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$namePrefix = $namePrefix ?? 'hours';

// Generate time options at 30-min steps.
$times = [];
for ($m = 0; $m < 24 * 60; $m += 30) {
  $h = intdiv($m, 60);
  $mm = $m % 60;
  $times[] = sprintf('%02d:%02d', $h, $mm);
}

// Keep selected values if they exist (later we can wire backend).
$selected = $selected ?? [];
function opt($t, $selected): string {
  $sel = ((string)$t === (string)$selected) ? 'selected' : '';
  return '<option value="' . htmlspecialchars((string)$t) . '" ' . $sel . '>' . htmlspecialchars((string)$t) . '</option>';
}
?>
<div class="table-responsive">
  <table class="table table-bordered align-middle bg-white">
    <thead class="table-light">
      <tr>
        <th style="min-width: 140px;">Day</th>
        <th style="min-width: 240px;">Slot 1</th>
        <th style="min-width: 240px;">Slot 2</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($days as $day): ?>
        <?php
          $key = strtolower($day);
          $openName = $namePrefix . '[open][' . $key . ']';
          $start1Name = $namePrefix . '[slot1_start][' . $key . ']';
          $end1Name = $namePrefix . '[slot1_end][' . $key . ']';
          $start2Name = $namePrefix . '[slot2_start][' . $key . ']';
          $end2Name = $namePrefix . '[slot2_end][' . $key . ']';

          $isOpen = isset($selected['open'][$key]) ? (bool)$selected['open'][$key] : false;
          $s1 = $selected['slot1_start'][$key] ?? '';
          $e1 = $selected['slot1_end'][$key] ?? '';
          $s2 = $selected['slot2_start'][$key] ?? '';
          $e2 = $selected['slot2_end'][$key] ?? '';
        ?>
        <tr>
          <td class="align-middle">
            <label class="form-check-label">
              <input
                class="form-check-input me-2"
                type="checkbox"
                name="<?= htmlspecialchars($openName) ?>"
                value="1"
                <?= $isOpen ? 'checked' : '' ?>
              />
              <?= htmlspecialchars($day) ?>
            </label>
          </td>

          <td>
            <div class="row g-2">
              <div class="col-6">
                <select class="form-select" name="<?= htmlspecialchars($start1Name) ?>">
                  <option value="">Start</option>
                  <?php foreach ($times as $t): ?>
                    <?= opt($t, $s1) ?>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6">
                <select class="form-select" name="<?= htmlspecialchars($end1Name) ?>">
                  <option value="">End</option>
                  <?php foreach ($times as $t): ?>
                    <?= opt($t, $e1) ?>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </td>

          <td>
            <div class="row g-2">
              <div class="col-6">
                <select class="form-select" name="<?= htmlspecialchars($start2Name) ?>">
                  <option value="">2nd start</option>
                  <?php foreach ($times as $t): ?>
                    <?= opt($t, $s2) ?>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6">
                <select class="form-select" name="<?= htmlspecialchars($end2Name) ?>">
                  <option value="">2nd end</option>
                  <?php foreach ($times as $t): ?>
                    <?= opt($t, $e2) ?>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-text">Leave blank if not applicable.</div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

