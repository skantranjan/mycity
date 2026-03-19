<?php
// Shared page layout for server-rendered PHP pages.
// Expects:
// - $pageTitle (optional)
// - $activePage (optional: e.g. "home", "submit")
// - $content (required): HTML string
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="description" content="Explore local business, services and places in your city." />
    <title><?= htmlspecialchars($pageTitle ?? 'My City Info') ?></title>

    <!-- Tailwind (utility-first, fast iteration) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap (components + grid) -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
  </head>
  <body class="bg-gray-50 text-gray-900">
    <?php include __DIR__ . '/partials/header.php'; ?>

    <main class="py-4">
      <div class="container">
        <?php
        // $content is expected to be a safe HTML string produced by templates.
        // If later we wire backend, we can add escaping for untrusted fields.
        echo $content ?? '';
        ?>
      </div>
    </main>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <!-- JS (jQuery + Bootstrap bundle) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>
  </body>
</html>

