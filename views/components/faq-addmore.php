<?php
// FAQ add-more UI helper.
// Expected:
// - $faqCount: number of initial FAQ blocks (default 1)
// Optional:
// - $faqItems: prefilled arrays: ['question'=>[], 'answer'=>[]]

$faqCount = isset($faqCount) ? (int)$faqCount : 1;
$faqItems = $faqItems ?? [];
$questions = $faqItems['question'] ?? [];
$answers = $faqItems['answer'] ?? [];
?>

<div class="card border-0 bg-white shadow-sm">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
      <div class="fw-semibold">Frequently asked questions</div>
      <button type="button" id="addFaqBtn" class="btn btn-sm btn-outline-primary">
        + Add
      </button>
    </div>

    <div id="faqItems">
      <?php for ($i = 0; $i < $faqCount; $i++): ?>
        <div class="faq-item border rounded-3 p-3 mb-3 bg-light" data-faq-index="<?= $i ?>">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div class="flex-grow-1">
              <div class="mb-2">
                <label class="form-label">FAQ question</label>
                <input
                  class="form-control"
                  type="text"
                  name="faq_question[]"
                  value="<?= htmlspecialchars((string)($questions[$i] ?? '')) ?>"
                  placeholder="Type the FAQ question"
                />
              </div>
              <div>
                <label class="form-label">FAQ answer</label>
                <textarea
                  class="form-control"
                  name="faq_answer[]"
                  rows="3"
                  placeholder="Type the FAQ answer"
                ><?= htmlspecialchars((string)($answers[$i] ?? '')) ?></textarea>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger removeFaqBtn">
              Remove
            </button>
          </div>
        </div>
      <?php endfor; ?>
    </div>

    <!-- Template used by JS cloning -->
    <template id="faqItemTemplate">
      <div class="faq-item border rounded-3 p-3 mb-3 bg-light" data-faq-index="__INDEX__">
        <div class="d-flex align-items-start justify-content-between gap-2">
          <div class="flex-grow-1">
            <div class="mb-2">
              <label class="form-label">FAQ question</label>
              <input class="form-control" type="text" name="faq_question[]" value="" placeholder="Type the FAQ question" />
            </div>
            <div>
              <label class="form-label">FAQ answer</label>
              <textarea class="form-control" name="faq_answer[]" rows="3" placeholder="Type the FAQ answer"></textarea>
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger removeFaqBtn">Remove</button>
        </div>
      </div>
    </template>
  </div>
</div>

