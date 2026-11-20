<?php
function base64url_encode($data)
{
  $b64 = base64_encode($data);
  
  if ($b64 === false) {
    return false;
  }
  $url = strtr($b64, '+/', '-_');
  return rtrim($url, '=');
}

function base64url_decode($data, $strict = false)
{
  $b64 = strtr($data, '-_', '+/');
  return base64_decode($b64, $strict);
}

function CountDaysBitweenDatesPHP($DateFrom, $DateTo) {
    // Return empty if any date missing
    if (empty($DateFrom) || empty($DateTo)) {
        return "";
    }

    // Try to convert to timestamps
    $dt1 = strtotime($DateFrom);
    $dt2 = strtotime($DateTo);

    // Invalid date check
    if ($dt1 === false || $dt2 === false) {
        return "";
    }

    // Calculate difference in seconds
    $timeDiff = $dt2 - $dt1;

    // If ToDate is earlier than FromDate
    if ($timeDiff < 0) {
        return "";
    }

    // Convert seconds to full days (inclusive count)
    $days = floor($timeDiff / (60 * 60 * 24)) + 1;

    return $days;
}


function splitFullName($fullName) {
    // Remove extra white spaces
    $fullName = trim(preg_replace('/\s+/', ' ', $fullName));

    // Split name into parts
    $nameParts = explode(' ', $fullName);
    $count = count($nameParts);

    $firstName = '';
    $middleName = '';
    $lastName = '';

    if ($count === 1) {
        $firstName = $nameParts[0];
    } elseif ($count === 2) {
        $firstName = $nameParts[0];
        $lastName = $nameParts[1];
    } elseif ($count >= 3) {
        $firstName = $nameParts[0];
        $lastName = $nameParts[$count - 1];
        $middleName = implode(' ', array_slice($nameParts, 1, $count - 2));
    }

    return [
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'last_name' => $lastName,
    ];
}

function interpolateQuery($query, $params) {
    $escapedParams = array_map(function ($param) {
        if (is_string($param)) {
            return "'" . addslashes($param) . "'";
        } elseif (is_null($param)) {
            return "NULL";
        }
        return $param;
    }, $params);

    $parts = explode('?', $query);
    $fullQuery = '';

    foreach ($parts as $index => $part) {
        $fullQuery .= $part;
        if (isset($escapedParams[$index])) {
            $fullQuery .= $escapedParams[$index];
        }
    }
    return $fullQuery;
}

/**
 * Generates a self-contained SVG Data URI avatar based on first letter of first name and last name.
 */
function generateSelfAvatarPHP($firstName, $lastName = '', $bgColor = '#1b7a48', $textColor = '#ffffff', $size = 128) {
    $fn = trim($firstName ?? '');
    $ln = trim($lastName ?? '');

    if (empty($ln) && strpos($fn, ' ') !== false) {
        $parts = array_values(array_filter(explode(' ', $fn)));
        $fn = $parts[0] ?? '';
        $ln = count($parts) > 1 ? end($parts) : '';
    }

    $firstChar = !empty($fn) ? mb_strtoupper(mb_substr($fn, 0, 1, 'UTF-8')) : '';
    $lastChar  = !empty($ln) ? mb_strtoupper(mb_substr($ln, 0, 1, 'UTF-8')) : '';

    $initials = ($firstChar . $lastChar) ?: 'L';

    $fontSize = round($size * 0.42);
    $rx       = round($size / 2);

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">'
         . '<rect width="100%" height="100%" fill="' . htmlspecialchars($bgColor) . '" rx="' . $rx . '"/>'
         . '<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="' . htmlspecialchars($textColor) . '" font-family="system-ui, -apple-system, sans-serif" font-size="' . $fontSize . '" font-weight="700">' . htmlspecialchars($initials) . '</text>'
         . '</svg>';

    return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
}
?>