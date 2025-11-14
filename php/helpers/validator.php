<?php
/**
 * Victoria AI - Validation Helper
 * Input validation functions
 */

/**
 * Validate Firebase UID format
 * @param string $uid
 * @return bool
 */
function isValidFirebaseUID($uid) {
    // Firebase UIDs are typically 28 characters alphanumeric
    return is_string($uid) && strlen($uid) >= 20 && strlen($uid) <= 128;
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL format
 * @param string $url
 * @return bool
 */
function isValidURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate display name
 * @param string $name
 * @return bool
 */
function isValidDisplayName($name) {
    return is_string($name) && strlen($name) >= 1 && strlen($name) <= 255;
}

/**
 * Validate auth provider
 * @param string $provider
 * @return bool
 */
function isValidAuthProvider($provider) {
    $validProviders = ['google', 'password', 'facebook', 'github'];
    return in_array($provider, $validProviders);
}

/**
 * Validate JSON string
 * @param string $json
 * @return bool
 */
function isValidJSON($json) {
    if (!is_string($json)) {
        return false;
    }
    
    json_decode($json);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Validate and sanitize user data
 * @param array $data User data from request
 * @return array Sanitized data or validation errors
 */
function validateUserData($data) {
    $errors = [];
    $sanitized = [];
    
    // Firebase UID - required
    if (empty($data['uid'])) {
        $errors[] = 'Firebase UID is required';
    } elseif (!isValidFirebaseUID($data['uid'])) {
        $errors[] = 'Invalid Firebase UID format';
    } else {
        $sanitized['firebase_uid'] = $data['uid'];
    }
    
    // Email - required
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!isValidEmail($data['email'])) {
        $errors[] = 'Invalid email format';
    } else {
        $sanitized['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    }
    
    // Display name - optional
    if (isset($data['displayName'])) {
        if (!isValidDisplayName($data['displayName'])) {
            $errors[] = 'Display name must be 1-255 characters';
        } else {
            $sanitized['display_name'] = htmlspecialchars($data['displayName'], ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Photo URL - optional
    if (isset($data['photoURL']) && !empty($data['photoURL'])) {
        if (!isValidURL($data['photoURL'])) {
            $errors[] = 'Invalid photo URL format';
        } else {
            $sanitized['photo_url'] = filter_var($data['photoURL'], FILTER_SANITIZE_URL);
        }
    }
    
    // Email verified - optional, default false
    $sanitized['email_verified'] = isset($data['emailVerified']) && $data['emailVerified'] === true ? 1 : 0;
    
    // Auth provider - optional, default 'password'
    if (isset($data['provider'])) {
        if (!isValidAuthProvider($data['provider'])) {
            $errors[] = 'Invalid auth provider';
        } else {
            $sanitized['auth_provider'] = $data['provider'];
        }
    } else {
        $sanitized['auth_provider'] = 'password';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => $sanitized
    ];
}

/**
 * Validate Firebase ID token format
 * @param string $token
 * @return bool
 */
function isValidFirebaseToken($token) {
    // Firebase tokens are JWT format (header.payload.signature)
    if (!is_string($token)) {
        return false;
    }
    
    $parts = explode('.', $token);
    return count($parts) === 3;
}

/**
 * Validate activity log data
 * @param array $data
 * @return array
 */
function validateActivityLog($data) {
    $errors = [];
    $sanitized = [];
    
    // User ID - required
    if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
        $errors[] = 'Valid user ID is required';
    } else {
        $sanitized['user_id'] = (int)$data['user_id'];
    }
    
    // Action - required
    if (empty($data['action'])) {
        $errors[] = 'Action is required';
    } else {
        $sanitized['action'] = htmlspecialchars($data['action'], ENT_QUOTES, 'UTF-8');
    }
    
    // IP address - optional
    if (isset($data['ip_address'])) {
        $sanitized['ip_address'] = filter_var($data['ip_address'], FILTER_VALIDATE_IP) ?: null;
    }
    
    // User agent - optional
    if (isset($data['user_agent'])) {
        $sanitized['user_agent'] = htmlspecialchars($data['user_agent'], ENT_QUOTES, 'UTF-8');
    }
    
    // Metadata - optional JSON
    if (isset($data['metadata'])) {
        if (is_array($data['metadata'])) {
            $sanitized['metadata'] = json_encode($data['metadata']);
        } elseif (isValidJSON($data['metadata'])) {
            $sanitized['metadata'] = $data['metadata'];
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => $sanitized
    ];
}

/**
 * Rate limiting check (simple implementation)
 * @param string $identifier IP or user ID
 * @param int $maxRequests Max requests allowed
 * @param int $timeWindow Time window in seconds
 * @return bool True if within limit
 */
function checkRateLimit($identifier, $maxRequests = 100, $timeWindow = 3600) {
    // This is a simple file-based rate limiter
    // In production, use Redis or Memcached
    
    $cacheDir = __DIR__ . '/../cache/rate-limits';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . '/' . md5($identifier) . '.txt';
    $now = time();
    
    // Read existing data
    $requests = [];
    if (file_exists($cacheFile)) {
        $data = file_get_contents($cacheFile);
        $requests = json_decode($data, true) ?: [];
    }
    
    // Filter requests within time window
    $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // Check if limit exceeded
    if (count($requests) >= $maxRequests) {
        return false;
    }
    
    // Add current request
    $requests[] = $now;
    file_put_contents($cacheFile, json_encode($requests));
    
    return true;
}
