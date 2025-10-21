<?php

declare(strict_types=1);

/**
 * Magic Link Email Template
 * HTML and plain text templates for magic link authentication emails
 */

if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class MagicLinkEmailTemplate
{
    /**
     * Get HTML email template
     */
    public static function getHtmlTemplate(string $loginLink, int $expirationMinutes): string
    {
        $appName = config('app_name', 'Christmas for Kids');
        $expirationTime = date('g:i A', time() + ($expirationMinutes * 60));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login Link - {$appName}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #2c5530 0%, #1a3a1d 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .message {
            font-size: 14px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .cta-button {
            display: inline-block;
            background-color: #2c5530;
            color: white !important;
            padding: 14px 32px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            cursor: pointer;
        }
        .cta-button:hover {
            background-color: #1a3a1d;
            text-decoration: none;
        }
        .info-box {
            background-color: #f9f9f9;
            border-left: 4px solid #c41e3a;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box strong {
            color: #c41e3a;
            display: block;
            margin-bottom: 5px;
        }
        .security-notice {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 13px;
        }
        .security-notice strong {
            color: #856404;
        }
        .footer {
            background-color: #f9f9f9;
            border-top: 1px solid #ddd;
            padding: 20px 30px;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
        .footer a {
            color: #2c5530;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎄 {$appName}</h1>
            <p style="margin: 10px 0 0 0; font-size: 14px;">Admin Portal Login</p>
        </div>

        <div class="content">
            <p class="greeting">Hello Admin,</p>

            <p class="message">
                You requested to log in to the {$appName} admin portal. Click the button below to complete your login.
                <strong>This link will expire at {$expirationTime} ({$expirationMinutes} minutes from now).</strong>
            </p>

            <div style="text-align: center;">
                <a href="{$loginLink}" class="cta-button">Login to Admin Panel</a>
            </div>

            <p style="text-align: center; font-size: 13px; color: #999; margin-top: 20px;">
                Or copy and paste this link in your browser:<br>
                <code style="word-break: break-all; background: #f5f5f5; padding: 10px; display: block; margin-top: 10px; border-radius: 4px; font-size: 11px;">
                    {$loginLink}
                </code>
            </p>

            <div class="info-box">
                <strong>⏰ Link Expires Soon</strong>
                This magic link is only valid for {$expirationMinutes} minutes. If it expires, you can request a new one.
            </div>

            <div class="security-notice">
                <strong>🔒 Security Reminder:</strong> Never share this link with anyone. Do not forward this email. This link is unique and will only work once.
            </div>

            <p style="font-size: 13px; color: #999;">
                If you didn't request this login link or have any concerns, please contact the system administrator immediately.
            </p>
        </div>

        <div class="footer">
            <p style="margin: 0;">© 2025 {$appName}. All rights reserved.</p>
            <p style="margin: 10px 0 0 0; font-size: 11px;">This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get plain text email template
     */
    public static function getPlainTextTemplate(string $loginLink, int $expirationMinutes): string
    {
        $appName = config('app_name', 'Christmas for Kids');
        $expirationTime = date('g:i A', time() + ($expirationMinutes * 60));

        return <<<TEXT
{$appName} - Admin Portal Login
========================================

Hello Admin,

You requested to log in to the {$appName} admin portal. Use the link below to complete your login.

This link will expire at {$expirationTime} ({$expirationMinutes} minutes from now).

LOGIN LINK:
{$loginLink}

SECURITY REMINDER:
- Never share this link with anyone
- Do not forward this email
- This link is unique and will only work once
- Do not respond to this email

If you didn't request this login link or have any concerns, please contact the system administrator immediately.

========================================
© 2025 {$appName}. All rights reserved.
This is an automated email. Please do not reply.
TEXT;
    }
}
