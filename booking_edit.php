<?php
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    if (!csrf_valid($_POST)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }

    $ref = trim((string)($_POST['ref'] ?? ''));
    $index = find_booking_index_by_ref($ref);

    if ($index === null) {
        http_response_code(404);
        exit('Booking not found');
    }

    $existingBooking = $_SESSION['bookings'][$index];
    $w = find_workshop($existingBooking['workshop_id'] ?? $existingBooking['workshop'], $workshops);
    $workshopId = $w['id'] ?? ($existingBooking['workshop_id'] ?? '');

    // Re-use validate_booking with server-side trusted workshop ID
    $validationInput = [
        'full_name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'quantity' => $_POST['quantity'] ?? null,
        'workshop_id' => $workshopId,
    ];

    $result = validate_booking($validationInput, $workshops);

    if (!empty($result['errors'])) {
        $_SESSION['edit_errors'] = $result['errors'];
        $_SESSION['edit_old'] = $result['data'];
        redirect_to('booking_edit.php?ref=' . urlencode($ref));
    }

    // Success: Server-side recalculation and targeted update
    $d = $result['data'];
    $fee = $w['fee'] ?? ($existingBooking['quantity'] > 0 ? (int)($existingBooking['amount'] / $existingBooking['quantity']) : 0);
    $newAmount = $fee * $d['quantity'];

    $_SESSION['bookings'][$index]['full_name'] = $d['full_name'];
    $_SESSION['bookings'][$index]['email'] = $d['email'];
    $_SESSION['bookings'][$index]['quantity'] = $d['quantity'];
    $_SESSION['bookings'][$index]['amount'] = $newAmount;

    unset($_SESSION['edit_errors'], $_SESSION['edit_old']);
    $_SESSION['flash'] = 'Booking ' . $ref . ' updated successfully.';
    redirect_to('dashboard.php');
}

if ($method !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

$ref = trim((string)($_GET['ref'] ?? ''));
$index = find_booking_index_by_ref($ref);
$booking = ($index !== null) ? $_SESSION['bookings'][$index] : null;

$errors = $_SESSION['edit_errors'] ?? [];
$old = $_SESSION['edit_old'] ?? [];
unset($_SESSION['edit_errors'], $_SESSION['edit_old']);

$w = null;
$unitFee = 0;
if ($booking !== null) {
    $w = find_workshop($booking['workshop_id'] ?? $booking['workshop'], $workshops);
    $unitFee = $w['fee'] ?? ($booking['quantity'] > 0 ? (int)($booking['amount'] / $booking['quantity']) : 0);
}

$valName = $old['full_name'] ?? ($booking['full_name'] ?? '');
$valEmail = $old['email'] ?? ($booking['email'] ?? '');
$valQty = $old['quantity'] ?? ($booking['quantity'] ?? 1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Booking</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($theme) ?>">
<main class="wrap">
  <header>
    <h1>Edit Booking</h1>
    <nav>
      <a href="index.php">Catalog</a>
      <a href="dashboard.php">My bookings</a>
    </nav>
  </header>

  <?php if ($booking === null): ?>
    <div class="panel">
      <h2>Booking not found</h2>
      <p class="muted">The requested booking reference does not exist or has expired from your current session.</p>
      <p><a class="btn btn-secondary" href="dashboard.php">Back to My Bookings</a></p>
    </div>
  <?php else: ?>
    <form method="post" action="booking_edit.php" class="panel form-stack" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="ref" value="<?= e($booking['ref']) ?>">

      <label>Booking Reference
        <input class="input-readonly" value="<?= e($booking['ref']) ?>" readonly disabled>
      </label>

      <label>Workshop
        <input class="input-readonly" value="<?= e($booking['workshop']) ?>" readonly disabled>
      </label>

      <label>Full Name
        <input name="full_name" value="<?= e($valName) ?>" placeholder="Nguyen Van A">
      </label>
      <small class="error"><?= e($errors['full_name'] ?? '') ?></small>

      <label>Email
        <input name="email" value="<?= e($valEmail) ?>" placeholder="user@example.com">
      </label>
      <small class="error"><?= e($errors['email'] ?? '') ?></small>

      <label>Tickets (1–4)
        <input name="quantity" type="number" min="1" max="4" value="<?= e($valQty) ?>">
      </label>
      <small class="error"><?= e($errors['quantity'] ?? '') ?></small>

      <hr class="divider">

      <p><strong>Current total: <?= number_format($booking['amount']) ?> VND</strong></p>
      <p class="muted">Unit fee: <?= number_format($unitFee) ?> VND - Total will be recalculated after saving.</p>

      <div class="actions-cell">
        <button type="submit" class="btn">Save Changes</button>
        <a class="btn btn-secondary" href="dashboard.php">Cancel</a>
      </div>
    </form>

    <div class="callout-box">
      The server must validate name, email and ticket quantity, verify the CSRF token and update the correct booking in $_SESSION.
    </div>
    <p class="muted">Invalid data is not saved to the Session.</p>
  <?php endif; ?>
</main>
</body>
</html>
