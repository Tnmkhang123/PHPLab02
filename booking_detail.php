<?php
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

$ref = trim((string)($_GET['ref'] ?? ''));
$booking = find_booking_by_ref($ref);

$w = null;
$unitFee = 0;
if ($booking !== null) {
    $w = find_workshop($booking['workshop_id'] ?? $booking['workshop'], $workshops);
    $unitFee = $w['fee'] ?? ($booking['quantity'] > 0 ? (int)($booking['amount'] / $booking['quantity']) : 0);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Booking Details</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($theme) ?>">
<main class="wrap">
  <header>
    <h1>Booking Details</h1>
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
    <div class="panel">
      <table class="detail-table">
        <tbody>
          <tr>
            <th>Booking Reference</th>
            <td><span class="badge-ref"><?= e($booking['ref']) ?></span></td>
          </tr>
          <tr>
            <th>Attendee</th>
            <td><?= e($booking['full_name']) ?></td>
          </tr>
          <tr>
            <th>Email</th>
            <td><?= e($booking['email']) ?></td>
          </tr>
          <tr>
            <th>Workshop</th>
            <td><?= e($booking['workshop']) ?></td>
          </tr>
          <tr>
            <th>Quantity</th>
            <td><?= e($booking['quantity']) ?> tickets</td>
          </tr>
          <tr>
            <th>Unit Fee</th>
            <td><?= number_format($unitFee) ?> VND</td>
          </tr>
          <tr>
            <th>Total Amount</th>
            <td><strong><?= number_format($booking['amount']) ?> VND</strong></td>
          </tr>
        </tbody>
      </table>

      <hr class="divider">

      <div class="actions-cell">
        <a class="btn btn-warning" href="booking_edit.php?ref=<?= urlencode($booking['ref']) ?>">Edit this booking</a>
        <a class="btn btn-secondary" href="dashboard.php">Back to My Bookings</a>
      </div>
    </div>
    <p class="muted">Booking information is read from the current PHP session.</p>
  <?php endif; ?>
</main>
</body>
</html>
