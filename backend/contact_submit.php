<?php
/**
 * Contact Form Submission Endpoint
 * 
 * Handles messages sent via the contact form.
 * Method: POST
 * Data: name, email, message
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log attempts
$log_file = 'contact_submissions.log'; // Log in the same directory
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Contact Form Submission\n" . "POST data: " . print_r($_POST, true) . "\n\n", FILE_APPEND);

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../contact.html?error=invalid_method');
    exit;
}

// Check for required fields
if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['message'])) {
    header('Location: ../contact.html?error=missing_fields');
    exit;
}

// --- Basic Server-Side Validation ---
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$message = trim($_POST['message']);

if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($message) < 10) {
     header('Location: ../contact.html?error=validation_failed');
     exit;
}

// --- Process the submission (e.g., send email, save to DB) ---
// For this example, we'll just log it and pretend it was sent.
// In a real application, you would use PHP's mail() function or a library like PHPMailer.

$to = "admin@helphub.com"; // Your admin email
$subject = "New Contact Form Message from HelpHub";
$body = "Name: " . htmlspecialchars($name) . "\n";
$body .= "Email: " . htmlspecialchars($email) . "\n\n";
$body .= "Message:\n" . htmlspecialchars($message);
$headers = "From: " . htmlspecialchars($email);

// Simulate sending email (replace with actual mail() call if configured)
$mail_sent = true; // mail($to, $subject, $body, $headers); 

if ($mail_sent) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Message processed successfully (simulated send).\n\n", FILE_APPEND);
    // Redirect back to contact page with success message
    header('Location: ../contact.html?success=message_sent');
    exit;
} else {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Message processing failed (simulated send failure).\n\n", FILE_APPEND);
    // Redirect back with error
    header('Location: ../contact.html?error=send_failed');
    exit;
}
?>
