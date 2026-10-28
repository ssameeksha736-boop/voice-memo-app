<?php

/**
 * Escapes a value for HTML output. Accepts `mixed` rather than `?string`
 * because form fields are attacker-controlled and PHP lets a client submit
 * any field as an array (e.g. `email[]=x`) -- with a strict `?string` hint,
 * that reaches htmlspecialchars() and throws an uncaught TypeError, caught
 * only by the router's generic 500. A non-scalar value is rendered as ''.
 */
function e(mixed $value): string
{
    if (!is_scalar($value)) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Safely reads a request field as a string. Guards the same array-typed-field
 * case as e() above, for the many call sites that pass a raw $_POST/$_GET
 * value straight into trim()/e()/old() -- a non-scalar value is treated as
 * the given default instead of throwing.
 */
function str_field(array $data, string $key, string $default = ''): string
{
    $value = $data[$key] ?? $default;
    return is_scalar($value) ? (string)$value : $default;
}

/**
 * Redirects to a front-controller route, which may carry its own query string
 * (e.g. 'user/memos/view?id=7').
 *
 * Naive concatenation produced `/index.php?route=user/memos/view?id=7`, where
 * the FIRST '?' starts the query string and every later one is just a literal
 * character -- so PHP parses a single parameter `route=user/memos/view?id=7`
 * and `$_GET['id']` never exists. The router then fails to match the route and
 * renders a 404. This hit the two most common actions in the app: saving a new
 * memo and saving an edit both redirected the user to a "not found" page even
 * though the memo had saved correctly (and starring from a memo's detail page,
 * which posts the same shape through `back`).
 *
 * Splitting the route from its query and rejoining with '&' keeps both as
 * separate, parseable parameters.
 */
function redirect(string $route): void
{
    $query = '';
    if (($pos = strpos($route, '?')) !== false) {
        $query = '&' . substr($route, $pos + 1);
        $route = substr($route, 0, $pos);
    }

    header('Location: /index.php?route=' . $route . $query);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function flash_get(string $type): ?string
{
    if (empty($_SESSION['flash'][$type])) {
        return null;
    }
    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $message;
}

function old(array $data, string $key, string $default = ''): string
{
    return e($data[$key] ?? $default);
}

function format_date(?string $date): string
{
    if (empty($date)) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts === false ? '-' : date('d M Y', $ts);
}

function format_datetime(?string $datetime): string
{
    if (empty($datetime)) {
        return '-';
    }
    $ts = strtotime($datetime);
    return $ts === false ? '-' : date('d M Y, h:i A', $ts);
}

/** Formats whole seconds as m:ss (or h:mm:ss past an hour) for memo durations. */
function format_duration(?int $seconds): string
{
    $seconds = max(0, (int)$seconds);
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;
    if ($h > 0) {
        return sprintf('%d:%02d:%02d', $h, $m, $s);
    }
    return sprintf('%d:%02d', $m, $s);
}

/** Bytes -> human-readable size, for the admin storage-usage stat. */
function format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    $units = ['KB', 'MB', 'GB'];
    $value = $bytes / 1024;
    foreach ($units as $unit) {
        if ($value < 1024 || $unit === 'GB') {
            return round($value, 1) . ' ' . $unit;
        }
        $value /= 1024;
    }
    return round($value, 1) . ' GB';
}
