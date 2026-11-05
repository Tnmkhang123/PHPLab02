<?php
declare(strict_types=1);

/**
 * Escape output for safe rendering in HTML.
 */
function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Normalize text by stripping tags, trimming whitespace, and collapsing multiple spaces.
 */
function normalize_text(string $text): string {
    $text = strip_tags(trim($text));
    return preg_replace('/\s+/u', ' ', $text) ?? $text;
}

/**
 * Validate booking inputs against workshop constraints.
 */
function validate_booking(array $input, array $workshops): array {
    $errors = [];
    $name = normalize_text((string)($input['full_name'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));
    $workshopId = (string)($input['workshop_id'] ?? '');
    $qtyRaw = $input['quantity'] ?? null;
    $qty = filter_var($qtyRaw, FILTER_VALIDATE_INT);

    if ($name === '') {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($name) < 2 || strlen($name) > 120) {
        $errors['full_name'] = 'Name must have 2–120 bytes.';
    }

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Invalid email format.';
    }

    if (!isset($workshops[$workshopId])) {
        $errors['workshop_id'] = 'Please select an existing workshop.';
    }

    if ($qty === false || $qty < 1 || $qty > 4) {
        $errors['quantity'] = 'Quantity must be an integer from 1 to 4.';
    } elseif (isset($workshops[$workshopId]) && $qty > $workshops[$workshopId]['seats']) {
        $errors['quantity'] = 'Not enough seats for this booking.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'full_name' => $name,
            'email' => $email,
            'workshop_id' => $workshopId,
            'quantity' => $qty === false ? 0 : $qty,
        ],
    ];
}

/**
 * Perform a 303 See Other redirect and exit immediately.
 */
function redirect_to(string $path): never {
    header('Location: ' . $path, true, 303);
    exit;
}

/**
 * Validate CSRF token using timing-safe comparison.
 */
function csrf_valid(array $post): bool {
    return is_string($post['csrf_token'] ?? null)
        && hash_equals($_SESSION['csrf_token'] ?? '', $post['csrf_token']);
}

/**
 * Find the array index of a booking in $_SESSION['bookings'] by its reference.
 * Returns null if not found.
 */
function find_booking_index_by_ref(string $ref): ?int {
    if ($ref === '' || !isset($_SESSION['bookings']) || !is_array($_SESSION['bookings'])) {
        return null;
    }
    foreach ($_SESSION['bookings'] as $index => $booking) {
        if (isset($booking['ref']) && hash_equals($booking['ref'], $ref)) {
            return (int)$index;
        }
    }
    return null;
}

/**
 * Find a booking by its reference. Returns null if not found.
 */
function find_booking_by_ref(string $ref): ?array {
    $index = find_booking_index_by_ref($ref);
    if ($index === null) {
        return null;
    }
    return $_SESSION['bookings'][$index];
}

/**
 * Locate workshop by id or title in the catalog.
 */
function find_workshop(string $idOrTitle, array $workshops): ?array {
    if (isset($workshops[$idOrTitle])) {
        return $workshops[$idOrTitle];
    }
    foreach ($workshops as $w) {
        if ($w['title'] === $idOrTitle || $w['id'] === $idOrTitle) {
            return $w;
        }
    }
    return null;
}
