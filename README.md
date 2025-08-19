# CF Login Customizer

A comprehensive WordPress plugin that allows you to customize your login page and change the login URL for enhanced security.

## Features

### 🔐 Security Features
- **Custom Login URL**: Change your login URL from `/wp-login.php` to a custom slug
- **Brute Force Protection**: Limit login attempts and temporarily block IP addresses
- **Security Logging**: Track failed login attempts and security events
- **Automatic Redirect**: Redirect users from the original login URL to your custom URL

### 🎨 Customization Features
- **Custom Logo**: Upload your own logo to replace the WordPress logo
- **Color Customization**: Change background, form, and text colors
- **Custom CSS**: Add your own CSS for advanced styling
- **Header & Footer Text**: Customize the header URL and footer text
- **Theme Integration**: Match your login page with your current theme

### 🛡️ Additional Security
- **IP Blocking**: Automatically block IPs after multiple failed attempts
- **Lockout Duration**: Configurable lockout periods
- **Security Monitoring**: Detailed logs of all login attempts

## Installation

1. Upload the `cf-login-customizer` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > CF Login Customizer to configure your options

## Configuration

### Basic Setup

1. **Enable Custom Login URL**: Check this option to activate the custom login URL feature
2. **Custom Login Slug**: Enter a unique slug for your login URL (e.g., `secure-login`, `my-admin`, etc.)
3. **Save Settings**: Click "Save Changes" to activate the new login URL

### Appearance Customization

- **Logo URL**: Enter the URL of your custom logo
- **Header Text**: Customize the text that appears in the login header
- **Header URL**: Set the URL that the header logo links to
- **Background Color**: Choose the background color for the login page
- **Form Background Color**: Set the background color for the login form
- **Text Color**: Customize the text color
- **Footer Text**: Add custom text to the login page footer
- **Custom CSS**: Add additional CSS for advanced styling

### Security Settings

- **Enable Security Logging**: Track all login attempts and security events
- **Maximum Login Attempts**: Set the number of failed attempts before blocking
- **Lockout Duration**: Configure how long IPs are blocked (in minutes)

## Usage

### Custom Login URL

Once configured, your login URL will change from:
```
https://yoursite.com/wp-login.php
```

To:
```
https://yoursite.com/your-custom-slug/
```

### Security Best Practices

1. **Choose a Unique Slug**: Avoid common words like "login", "admin", or "wp-admin"
2. **Use Complex Slugs**: Combine letters, numbers, and special characters
3. **Keep it Private**: Don't share your custom login URL publicly
4. **Regular Updates**: Change your custom slug periodically for enhanced security

### Troubleshooting

#### Custom Login URL Not Working

1. **Flush Rewrite Rules**: Go to Settings > Permalinks and click "Save Changes"
2. **Check Permissions**: Ensure your server supports URL rewriting
3. **Clear Cache**: Clear any caching plugins or server cache
4. **Check .htaccess**: Ensure your .htaccess file is writable

#### Login Form Not Loading

1. **Check Plugin Status**: Ensure the plugin is activated
2. **Verify Settings**: Check that custom login is enabled in settings
3. **Test URL**: Try accessing your custom login URL directly
4. **Check Logs**: Review security logs for any blocked attempts

## Security Logs

The plugin maintains detailed logs of:
- Failed login attempts
- Successful logins
- IP blocking events
- Security violations

Access logs through the admin panel when security logging is enabled.

## File Structure

```
cf-login-customizer/
├── cf-login.php                 # Main plugin file
├── uninstall.php               # Cleanup on uninstall
├── README.md                   # This file
├── assets/
│   └── css/
│       └── admin-style.css     # Admin styles
├── includes/
│   ├── admin/
│   │   └── class-admin.php     # Admin interface
│   ├── class-security.php      # Security features
│   └── class-theme-integration.php # Theme integration
└── test-login.php              # Testing utilities
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- URL rewriting enabled (mod_rewrite)

## Support

For support and questions:
- Check the troubleshooting section above
- Review the security logs for issues
- Ensure all requirements are met

## Changelog

### Version 1.0.0
- Initial release
- Custom login URL functionality
- Security features and logging
- Appearance customization
- Theme integration

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by CodeFixr - https://codefixr.com
