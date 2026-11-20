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
?>