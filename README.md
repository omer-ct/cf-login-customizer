# CF Login Customizer

![WordPress](https://img.shields.io/badge/WordPress-%E2%89%A55.0-blue)
![PHP](https://img.shields.io/badge/PHP-%E2%89%A57.4-777bb3)
![Version](https://img.shields.io/badge/version-1.0.1-success)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-lightgrey)

A WordPress plugin to customize your login page and change the login URL. Simplified and focused: no brute-force logging, no security logs, no theme-integration extras.

## Features

### 🔐 Access Control
- **Custom Login URL**: Change your login URL from `/wp-login.php` to a custom slug.
- **Block default endpoints**: When enabled, direct access to `wp-admin` and `wp-login.php` is blocked and redirected to your chosen URL (except allowed lost/reset password actions).

### 🎨 Customization
- **Custom Logo URL**
- **Colors**: Background, form, and text colors
- **Background Image** with optional **overlay color** and **opacity**
- **Footer Text**
- **Custom CSS**
- **Improved spacing** above the form; background image scrolls (not fixed)

## Installation

1. Upload the `cf-login-customizer` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > CF Login Customizer to configure your options

## Configuration

### Basic Setup
1. **Enable Custom Login URL**
2. **Custom Login Slug**: e.g., `secure-login`, `my-admin`
3. **Redirect URL**: Where to send users who try `wp-admin` or `wp-login.php`
4. Save changes

### Appearance
- **Logo URL**
- **Background Color** and **Form Background Color**
- **Text Color**
- **Footer Text**
- **Background Image**, **Overlay Color**, **Overlay Opacity**
- **Custom CSS** (advanced)

## Usage

### Custom Login URL
Your login URL changes from:
```
https://yoursite.com/wp-login.php
```
to:
```
https://yoursite.com/your-custom-slug/
```

### Troubleshooting
1. Go to Settings > Permalinks and click "Save Changes" to refresh rewrite rules.
2. Ensure URL rewriting is supported and `.htaccess` is writable.
3. Clear caches (plugins/server).

## File Structure

```
cf-login-customizer/
├── cf-login.php                 # Main plugin file
├── uninstall.php                # Cleanup on uninstall
├── README.md                    # This file
├── assets/
│   └── css/
│       └── admin-style.css      # Admin styles
└── includes/
    └── admin/
        └── class-admin.php      # Admin interface
```

## Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- URL rewriting enabled (mod_rewrite)

## Support
- Open an issue on the repository: https://github.com/omer-ct/cf-login-customizer

## Changelog
### Version 1.0.1
- Cleanup and simplification: removed security logging/tests, theme integration, and unused files; added background image overlay and spacing improvements; updated docs and links.

### Version 1.0.0
- Initial release of the simplified plugin: custom login URL, access control redirects, background image + overlay, footer text, and appearance options.

## License
GPL v2 or later

## Credits
Developed by omer-ct — https://github.com/omer-ct

## Screenshots
1. Custom login page with background image and overlay
2. Settings page with appearance options and redirect control
