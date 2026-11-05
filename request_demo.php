<?php
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$getName = (string)($_GET['name'] ?? '');
$postName = (string)($_POST['name'] ?? '');
// $_REQUEST may combine GET, POST, and COOKIE based on PHP settings; do not trust for business logic.
$requestName = (string)($_REQUEST['name'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Request Inspector</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($theme) ?>">
<main class="wrap">
  <h1>Request Inspector</h1>
  <p>Method: <code><?= e($method) ?></code></p>
  <p>GET name: <code><?= e($getName) ?></code></p>
  <p>POST name: <code><?= e($postName) ?></code></p>
  <p>REQUEST name: <code><?= e($requestName) ?></code></p>

  <form method="get" class="panel form-row">
    <label>GET Name
      <input name="name" placeholder="GET name" value="<?= e($getName) ?>">
    </label>
    <button>Send GET</button>
  </form>

  <form method="post" class="panel form-row">
    <label>POST Name
      <input name="name" placeholder="POST name" value="<?= e($postName) ?>">
    </label>
    <button>Send POST</button>
  </form>

  <p><a href="index.php">Back to catalog</a></p>
</main>
</body>
</html>
