# Email Integration Research - Nexcess & Google Workspace

## Nexcess Webmail Integration Options

### Option 1: SMTP via Nexcess Mail Server
```php
// Typical Nexcess SMTP settings
$smtpConfig = [
    'host' => 'mail.yourdomain.com', // or your server's hostname
    'port' => 587, // or 465 for SSL
    'security' => 'tls', // or 'ssl'
    'username' => 'admin@cforkids.org',
    'password' => 'your_email_password',
    'from_email' => 'admin@cforkids.org',
    'from_name' => 'Christmas for Kids'
];
```

**Pros:**
- Direct integration with hosting
- No external dependencies
- Typically included in hosting plan
- Good deliverability for transactional emails

**Cons:**  
- Limited to hosting provider's mail server capabilities
- May have sending limits
- Less advanced features than Google Workspace

### Option 2: Google Workspace SMTP
```php
// Google Workspace SMTP settings
$smtpConfig = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'security' => 'tls',
    'username' => 'admin@cforkids.org', // Your Google Workspace email
    'password' => 'your_app_password', // App-specific password
    'from_email' => 'admin@cforkids.org',
    'from_name' => 'Christmas for Kids'
];
```

**Pros:**
- Excellent deliverability
- Advanced spam protection
- Better email management tools
- Higher sending limits
- Professional appearance

**Cons:**
- Requires Google Workspace subscription
- Need to set up app passwords or OAuth
- Additional configuration complexity

## Recommended Email Architecture

### PHPMailer Integration
```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class CFK_Email_Manager {
    private PHPMailer $mailer;
    
    public function __construct(array $config) {
        $this->mailer = new PHPMailer(true);
        $this->setupSMTP($config);
    }
    
    private function setupSMTP(array $config): void {
        $this->mailer->isSMTP();
        $this->mailer->Host = $config['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $config['username'];
        $this->mailer->Password = $config['password'];
        $this->mailer->SMTPSecure = $config['security'];
        $this->mailer->Port = $config['port'];
        $this->mailer->setFrom($config['from_email'], $config['from_name']);
    }
}
```

### Email Templates System
```php
class EmailTemplate {
    const SPONSORSHIP_REQUEST = 'sponsorship_request';
    const SPONSORSHIP_CONFIRMED = 'sponsorship_confirmed'; 
    const ADMIN_NOTIFICATION = 'admin_notification';
    const SPONSOR_THANK_YOU = 'sponsor_thank_you';
    
    public static function render(string $template, array $data): string;
    public static function getSubject(string $template, array $data): string;
}
```

## Email Configuration Options

### Environment-Based Configuration
```php
// config/config.php additions
$emailConfig = [
    'provider' => $isProduction ? 'google_workspace' : 'nexcess',
    
    'nexcess' => [
        'host' => 'mail.' . $_SERVER['HTTP_HOST'],
        'port' => 587,
        'security' => 'tls',
        'username' => 'admin@cforkids.org',
        'password' => getenv('NEXCESS_EMAIL_PASSWORD'),
    ],
    
    'google_workspace' => [
        'host' => 'smtp.gmail.com', 
        'port' => 587,
        'security' => 'tls',
        'username' => 'admin@cforkids.org',
        'password' => getenv('GOOGLE_APP_PASSWORD'),
    ]
];
```

## Required Email Templates

### 1. Sponsorship Request (to Admin)
```
Subject: New Sponsorship Request - {child_name}

A new sponsorship request has been submitted:

Child: {child_name} (ID: {child_id})
Sponsor: {sponsor_name}
Email: {sponsor_email}  
Phone: {sponsor_phone}

Please review and confirm this sponsorship in the admin panel:
{admin_link}
```

### 2. Sponsorship Confirmation (to Sponsor)
```
Subject: Thank you for sponsoring {child_name}!

Dear {sponsor_name},

Thank you for choosing to sponsor {child_name} for Christmas! 
Your generosity will make their holiday truly special.

Next Steps:
- We'll contact you within 2 business days with gift details
- Gift delivery deadline: {deadline_date}
- Questions? Reply to this email

Child Details:
- Age: {age} years old
- Interests: {interests}
- Clothing sizes: Shirt {shirt_size}, Pants {pant_size}, Shoes {shoe_size}

Thank you for making Christmas magical!

Christmas for Kids Team
```

### 3. Admin Notification (Multiple Triggers)
```
Subject: CFK Admin Alert - {alert_type}

{alert_message}

Action Required: {action_needed}
View in Admin Panel: {admin_link}

Sent automatically from Christmas for Kids system.
```

## Implementation Plan

### Phase 1: Basic Email Setup
1. Install PHPMailer via Composer
2. Create email configuration system
3. Build basic EmailManager class
4. Test with both Nexcess and Google Workspace

### Phase 2: Template System
1. Create HTML email templates
2. Build template rendering system
3. Add variable substitution
4. Create fallback text versions

### Phase 3: Integration Points
1. Sponsorship request notifications
2. Admin confirmation emails
3. Status update communications
4. System alert emails

## Security Considerations

### Email Security
- Store passwords as environment variables
- Use app-specific passwords for Google
- Validate all email addresses
- Rate limiting on email sending
- Log all email attempts

### Template Security
- Sanitize all template variables
- Prevent email header injection
- Validate recipient addresses
- Use proper encoding (UTF-8)

## Testing Strategy

### Development Testing
- Use email catching tools (MailHog, Mailtrap)
- Test with both providers
- Verify template rendering
- Check deliverability

### Production Testing
- Send test emails to multiple providers
- Monitor bounce rates
- Track delivery confirmations
- Test spam folder placement

## Questions for Implementation

1. **Primary Provider**: Should we default to Google Workspace for better deliverability?

2. **Backup Provider**: Should we implement failover from Google to Nexcess if Google fails?

3. **Email Volume**: Expected emails per day? (affects provider choice and limits)

4. **Authentication**: Prefer OAuth2 or app passwords for Google Workspace?

5. **Branding**: Need custom email templates with Christmas for Kids branding?

**Recommendation**: Start with Google Workspace SMTP for reliability, with Nexcess as backup option. This provides maximum deliverability for critical sponsorship communications.