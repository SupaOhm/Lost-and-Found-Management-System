<?php
/**
 * General utility functions for the Lost & Found application
 */

/**
 * Sanitize user input to prevent XSS and other attacks
 * 
 * @param string $data The input string to sanitize
 * @return string Sanitized string
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Sanitize phone number
 * 
 * @param string $phone The phone number to sanitize
 * @return string Sanitized phone number or empty string if invalid
 */
function sanitize_phone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return $phone;
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if email is valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to a specific URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Generate a CSRF token
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token The token to verify
 * @return bool True if token is valid, false otherwise
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date for display
 * 
 * @param string $date The date string to format
 * @param string $format The format to use (default: 'F j, Y')
 * @return string Formatted date
 */
function format_date($date, $format = 'F j, Y') {
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

/**
 * Get the base URL of the application
 * 
 * @return string Base URL
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = dirname($_SERVER['SCRIPT_NAME']);
    
    return "$protocol://$host$script_name";
}

/**
 * Upload a file with validation
 * 
 * @param array $file The $_FILES array element
 * @param string $target_dir The directory to upload to
 * @param array $allowed_types Array of allowed MIME types
 * @param int $max_size Maximum file size in bytes
 * @return array ['success' => bool, 'message' => string, 'file_path' => string]
 */
function upload_file($file, $target_dir, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
    $result = [
        'success' => false,
        'message' => '',
        'file_path' => ''
    ];

    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['message'] = 'File upload error: ' . $file['error'];
        return $result;
    }

    // Check file size
    if ($file['size'] > $max_size) {
        $result['message'] = 'File is too large. Maximum size is ' . ($max_size / 1024 / 1024) . 'MB';
        return $result;
    }

    // Check file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        $result['message'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types);
        return $result;
    }

    // Create target directory if it doesn't exist
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            $result['message'] = 'Failed to create upload directory';
            return $result;
        }
    }

    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('item_', true) . '.' . $file_extension;
    $target_file = rtrim($target_dir, '/') . '/' . $new_filename;

    // Move the file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $result['success'] = true;
        $result['message'] = 'File uploaded successfully';
        $result['file_path'] = $target_file;
    } else {
        $result['message'] = 'Failed to move uploaded file';
    }

    return $result;
}

/**
 * Check if a string starts with a specific substring
 * 
 * @param string $haystack The string to search in
 * @param string $needle The substring to search for
 * @return bool True if $haystack starts with $needle
 */
function starts_with($haystack, $needle) {
    return strpos($haystack, $needle) === 0;
}

/**
 * Check if a string ends with a specific substring
 * 
 * @param string $haystack The string to search in
 * @param string $needle The substring to search for
 * @return bool True if $haystack ends with $needle
 */
function ends_with($haystack, $needle) {
    $length = strlen($needle);
    if ($length === 0) {
        return true;
    }
    return substr($haystack, -$length) === $needle;
}

/**
 * Get the current URL
 * 
 * @return string Current URL
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Set a flash message
 * 
 * @param string $type Message type (e.g., 'success', 'error', 'info')
 * @param string $message The message to display
 * @return void
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null Flash message array or null if none exists
 */
function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user has a specific role
 * 
 * @param string $role Role to check
 * @return bool True if user has the role, false otherwise
 */
function has_role($role) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    return $_SESSION['user_role'] === $role;
}

/**
 * Require user to be logged in
 * 
 * @param string $redirect URL to redirect to if not logged in
 * @return void
 */
function require_login($redirect = '/login.php') {
    if (!is_logged_in()) {
        set_flash_message('error', 'You must be logged in to access this page.');
        redirect($redirect);
    }
}

/**
 * Require user to have a specific role
 * 
 * @param string $role Required role
 * @param string $redirect URL to redirect to if role check fails
 * @return void
 */
function require_role($role, $redirect = '/') {
    require_login($redirect);
    
    if (!has_role($role)) {
        set_flash_message('error', 'You do not have permission to access this page.');
        redirect($redirect);
    }
}
