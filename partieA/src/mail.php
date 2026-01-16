<?php
/**
 * Mail helper functions for sending emails via MailHog
 * Uses SMTP connection to MailHog for local email testing
 */

require_once __DIR__ . '/config.php';

/**
 * Send email via SMTP (MailHog compatible)
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $fromEmail Sender email
 * @param string $fromName Sender name
 * @return bool
 */
function sendMail(string $to, string $subject, string $body, string $fromEmail = '', string $fromName = ''): bool {
    $fromEmail = $fromEmail ?: MAIL_FROM;
    $fromName = $fromName ?: MAIL_FROM_NAME;
    
    try {
        $socket = @fsockopen(MAIL_HOST, MAIL_PORT, $errno, $errstr, 10);
        
        if (!$socket) {
            error_log("Failed to connect to mail server: $errstr ($errno)");
            return false;
        }
        
        // Read greeting
        fgets($socket, 512);
        
        // EHLO
        fwrite($socket, "EHLO localhost\r\n");
        while ($line = fgets($socket, 512)) {
            if (substr($line, 3, 1) == ' ') break;
        }
        
        // MAIL FROM
        fwrite($socket, "MAIL FROM:<$fromEmail>\r\n");
        fgets($socket, 512);
        
        // RCPT TO
        fwrite($socket, "RCPT TO:<$to>\r\n");
        fgets($socket, 512);
        
        // DATA
        fwrite($socket, "DATA\r\n");
        fgets($socket, 512);
        
        // Headers
        $headers = "From: $fromName <$fromEmail>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "\r\n";
        
        // Body
        $message = $headers . $body . "\r\n.\r\n";
        fwrite($socket, $message);
        fgets($socket, 512);
        
        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
    } catch (Exception $e) {
        error_log("Mail sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send order confirmation email
 * @param array $order Order data
 * @param array $items Order items
 * @param string $customerEmail Customer email
 * @return bool
 */
function sendOrderConfirmation(array $order, array $items, string $customerEmail): bool {
    $subject = "Order Confirmation #" . $order['id'] . " - " . APP_NAME;
    
    $itemsHtml = '';
    foreach ($items as $item) {
        $subtotal = number_format($item['price'] * $item['quantity'], 2);
        $itemsHtml .= "<tr>
            <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['name']}</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>{$item['quantity']}</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>€" . number_format($item['price'], 2) . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>€{$subtotal}</td>
        </tr>";
    }
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Order Confirmation</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h1 style='color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;'>
                Order Confirmation
            </h1>
            
            <p>Dear {$order['customer_name']},</p>
            
            <p>Thank you for your order! We have received your order and it is being processed.</p>
            
            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <h3 style='margin-top: 0;'>Order Details</h3>
                <p><strong>Order Number:</strong> #{$order['id']}</p>
                <p><strong>Date:</strong> {$order['created_at']}</p>
                <p><strong>Status:</strong> {$order['status']}</p>
            </div>
            
            <h3>Shipping Information</h3>
            <p>
                {$order['customer_name']}<br>
                {$order['address']}<br>
                Phone: {$order['phone']}
            </p>
            
            <h3>Order Items</h3>
            <table style='width: 100%; border-collapse: collapse;'>
                <thead>
                    <tr style='background: #3498db; color: white;'>
                        <th style='padding: 10px; text-align: left;'>Product</th>
                        <th style='padding: 10px; text-align: center;'>Qty</th>
                        <th style='padding: 10px; text-align: right;'>Price</th>
                        <th style='padding: 10px; text-align: right;'>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
                <tfoot>
                    <tr style='background: #f8f9fa;'>
                        <td colspan='3' style='padding: 10px; text-align: right;'><strong>Total:</strong></td>
                        <td style='padding: 10px; text-align: right;'><strong>€" . number_format($order['total'], 2) . "</strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <p style='margin-top: 30px;'>
                If you have any questions about your order, please contact us.
            </p>
            
            <p style='color: #7f8c8d; font-size: 12px; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 15px;'>
                This email was sent from " . APP_NAME . ". Please do not reply to this email.
            </p>
        </div>
    </body>
    </html>";
    
    return sendMail($customerEmail, $subject, $body);
}
