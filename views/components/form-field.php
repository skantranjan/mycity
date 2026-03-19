<?php
// Generic Bootstrap form field component.
// Expected:
// - $label (string)
// - $name (string)
// Optional:
// - $type (string, default 'text')
// - $value (string)
// - $placeholder (string)
// - $help (string)
// - $required (bool)

$label = $label ?? '';
$name = $name ?? '';
$type = $type ?? 'text';
$value = $value ?? '';
$placeholder = $placeholder ?? '';
$help = $help ?? '';
$required = (bool)($required ?? false);

$id = $id ?? ('field_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $name));
?>
<div class="mb-3">
  <?php if ($label !== ''): ?>
    <label for="<?= htmlspecialchars($id) ?>" class="form-label">
      <?= htmlspecialchars($label) ?>
      <?php if ($required): ?>
        <span class="text-danger">*</span>
      <?php endif; ?>
    </label>
  <?php endif; ?>

  <input
    id="<?= htmlspecialchars($id) ?>"
    name="<?= htmlspecialchars($name) ?>"
    type="<?= htmlspecialchars($type) ?>"
    class="form-control"
    value="<?= htmlspecialchars((string)$value) ?>"
    placeholder="<?= htmlspecialchars($placeholder) ?>"
    <?= $required ? 'required' : '' ?>
  />

  <?php if ($help !== ''): ?>
    <div class="form-text"><?= htmlspecialchars($help) ?></div>
  <?php endif; ?>
</div>

