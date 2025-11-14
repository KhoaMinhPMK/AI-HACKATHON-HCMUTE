<?php
/**
 * Victoria AI - Response Helper
 * Standardized JSON responses for API
 */

/**
 * Set JSON response headers
 */
function setJSONHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *'); // Change to your domain in production
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Send success response
 * @param mixed $data Response data
 * @param string $message Success message
 * @param int $code HTTP status code
 */
function sendSuccess($data = null, $message = 'Success', $code = 200) {
    setJSONHeaders();
    http_response_code($code);
    
    $response = [
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send error response
 * @param string $message Error message
 * @param int $code HTTP status code
 * @param mixed $details Additional error details
 */
function sendError($message = 'An error occurred', $code = 400, $details = null) {
    setJSONHeaders();
    http_response_code($code);
    
    $response = [
        'success' => false,
        'message' => $message,
        'error' => $details,
        'timestamp' => time()
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send validation error response
 * @param array $errors Array of validation errors
 */
function sendValidationError($errors) {
    sendError('Validation failed', 422, $errors);
}

/**
 * Send unauthorized response
 * @param string $message Error message
 */
function sendUnauthorized($message = 'Unauthorized access') {
    sendError($message, 401);
}

/**
 * Send not found response
 * @param string $message Error message
 */
function sendNotFound($message = 'Resource not found') {
    sendError($message, 404);
}

/**
 * Send server error response
 * @param string $message Error message
 */
function sendServerError($message = 'Internal server error') {
    sendError($message, 500);
}

/**
 * Get request body as associative array
 * @return array|null
 */
function getRequestBody() {
    $body = file_get_contents('php://input');
    return json_decode($body, true);
}

/**
 * Get request header
 * @param string $header Header name
 * @return string|null
 */
function getRequestHeader($header) {
    $headers = getallheaders();
    return $headers[$header] ?? null;
}

/**
 * Validate required fields in request
 * @param array $data Request data
 * @param array $required Required field names
 * @return array|null Array of missing fields or null if all present
 */
function validateRequiredFields($data, $required) {
    $missing = [];
    
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    return empty($missing) ? null : $missing;
}

/**
 * Sanitize string input
 * @param string $input
 * @return string
 */
function sanitizeString($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 * @param string $email
 * @return string|false
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
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
 * Get client IP address
 * @return string
 */
function getClientIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Get user agent
 * @return string
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Log API request
 * @param string $endpoint
 * @param array $data
 */
function logAPIRequest($endpoint, $data = []) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'method' => $_SERVER['REQUEST_METHOD'],
        'ip' => getClientIP(),
        'user_agent' => getUserAgent(),
        'data' => $data
    ];
    
    error_log(json_encode($log));
}
