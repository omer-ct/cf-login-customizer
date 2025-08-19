<?php
/**
 * Plugin Name: CF Login Customizer
 * Plugin URI: https://github.com/omer-ct/cf-login-customizer
 * Description: Customize your WordPress login screen and change the login URL for enhanced security.
 * Version: 1.0.1
 * Author: CodeFixr
 * Author URI: https://github.com/omer-ct
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cf-login
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CF_LOGIN_VERSION', '1.0.1');
define('CF_LOGIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CF_LOGIN_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Main plugin class
class CF_Login_Customizer {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Block access very early
        add_action('plugins_loaded', array($this, 'redirect_wp_login'), 1);
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Immediate check for blocked URLs
        $this->immediate_redirect_check();
    }
    
    private function immediate_redirect_check() {
        // Check if WordPress functions are available
        if (!function_exists('is_user_logged_in') || !function_exists('wp_safe_redirect') || !function_exists('home_url')) {
            return;
        }
        
        if ($this->is_custom_login_enabled() && !is_user_logged_in()) {
            $current_uri = $_SERVER['REQUEST_URI'] ?? '';
            $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
            $redirect_url = $this->get_option('redirect_url', $this->get_home_url('/'));
            
            // Check if this is a blocked URL
            if (strpos($current_uri, 'wp-admin') !== false || 
                strpos($current_uri, 'wp-login.php') !== false ||
                strpos($script_name, 'wp-login.php') !== false ||
                strpos($script_name, 'wp-admin') !== false) {
                
                // Allow password reset actions
                if (strpos($current_uri, 'wp-login.php') !== false) {
                    $allowed_actions = array('lostpassword', 'retrievepassword', 'resetpass', 'rp');
                    $has_allowed_action = false;
                    
                    foreach ($allowed_actions as $action) {
                        if (strpos($current_uri, 'action=' . $action) !== false) {
                            $has_allowed_action = true;
                            break;
                        }
                    }
                    
                    if (!$has_allowed_action) {
                        wp_safe_redirect($redirect_url, 302);
                        exit;
                    }
                } else {
                    wp_safe_redirect($redirect_url, 302);
                    exit;
                }
            }
        }
    }
    
    public function init() {
        // Initialize plugin components
        $this->init_hooks();
        $this->init_admin();
        

    }
    
    public function load_textdomain() {
        load_plugin_textdomain('cf-login', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function init_hooks() {
        // Add rewrite rules for custom login URL
        add_action('init', array($this, 'add_rewrite_rules'));
        
        // Flush rewrite rules on plugin activation
        register_activation_hook(__FILE__, array($this, 'flush_rewrite_rules'));
        register_deactivation_hook(__FILE__, array($this, 'flush_rewrite_rules'));
        
        // Customize login page
        add_action('login_head', array($this, 'customize_login_head'));
        add_action('login_footer', array($this, 'customize_login_footer'));
        add_filter('login_headerurl', array($this, 'customize_login_header_url'));
        add_filter('login_headertext', array($this, 'customize_login_header_text'));
        
        // Block wp-admin and wp-login.php access - multiple hooks for better coverage
        add_action('init', array($this, 'redirect_wp_login'), 1);
        add_action('wp_loaded', array($this, 'redirect_wp_login'), 1);
        add_action('template_redirect', array($this, 'redirect_wp_login'), 1);
        
        // Add query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Handle custom login template - use early priority
        add_action('template_redirect', array($this, 'handle_custom_login_template'), 1);
        
        // Alternative method using parse_request
        add_action('parse_request', array($this, 'parse_custom_login_request'));
    }
    
    private function init_admin() {
        if (is_admin()) {
            require_once CF_LOGIN_PLUGIN_PATH . 'includes/admin/class-admin.php';
            new CF_Login_Admin();
        }
    }
    

    

    

    

    
    public function add_query_vars($vars) {
        $vars[] = 'cf_login';
        return $vars;
    }
    
    public function add_rewrite_rules() {
        $custom_slug = $this->get_option('custom_login_slug', 'secure-login');
        if (!empty($custom_slug)) {
            // Main custom login URL rule
            add_rewrite_rule(
                '^' . $custom_slug . '/?$',
                'index.php?cf_login=1',
                'top'
            );
            
            // Handle form submissions and other login actions
            add_rewrite_rule(
                '^' . $custom_slug . '/wp-login\.php/?$',
                'index.php?cf_login=1',
                'top'
            );
            
            // Handle any additional paths under the custom slug
            add_rewrite_rule(
                '^' . $custom_slug . '/(.+)$',
                'index.php?cf_login=1&action=$matches[1]',
                'top'
            );
        }
    }
    
    public function flush_rewrite_rules() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
        $this->update_htaccess_rules();
    }
    
    private function update_htaccess_rules() {
        if ($this->is_custom_login_enabled()) {
            $htaccess_file = ABSPATH . '.htaccess';
            
            if (is_writable($htaccess_file)) {
                $htaccess_content = file_get_contents($htaccess_file);
                $custom_slug = $this->get_option('custom_login_slug', 'secure-login');
                $redirect_url = $this->get_option('redirect_url', $this->get_home_url('/'));
                
                // Create blocking rules
                $blocking_rules = "\n# CF Login Customizer - Block wp-admin and wp-login.php\n";
                $blocking_rules .= "<IfModule mod_rewrite.c>\n";
                $blocking_rules .= "RewriteEngine On\n";
                $blocking_rules .= "RewriteCond %{REQUEST_URI} ^/wp-admin/?$\n";
                $blocking_rules .= "RewriteCond %{REQUEST_URI} !^/wp-admin/admin-ajax\.php\n";
                $blocking_rules .= "RewriteCond %{REQUEST_URI} !^/wp-admin/admin-post\.php\n";
                $blocking_rules .= "RewriteRule .* " . $redirect_url . " [R=302,L]\n";
                $blocking_rules .= "RewriteCond %{REQUEST_URI} ^/wp-login\.php$\n";
                $blocking_rules .= "RewriteCond %{QUERY_STRING} !action=(lostpassword|retrievepassword|resetpass|rp)\n";
                $blocking_rules .= "RewriteRule .* " . $redirect_url . " [R=302,L]\n";
                $blocking_rules .= "</IfModule>\n";
                
                // Remove existing rules if they exist
                $htaccess_content = preg_replace('/# CF Login Customizer.*?<\/IfModule>\n/s', '', $htaccess_content);
                
                // Add new rules
                $htaccess_content .= $blocking_rules;
                
                file_put_contents($htaccess_file, $htaccess_content);
            }
        }
    }
    
    public function handle_custom_login_template() {
        global $wp_query;
        
        // Check if this is our custom login URL
        if (isset($wp_query->query_vars['cf_login']) && $wp_query->query_vars['cf_login'] == '1') {
            // Set up the login page
            if (!is_user_logged_in()) {
                // Load the WordPress login form
                $this->load_login_form();
                exit;
            } else {
                // User is already logged in, redirect to admin
                wp_redirect(admin_url());
                exit;
            }
        }
        
        // Alternative check using REQUEST_URI
        $custom_slug = $this->get_option('custom_login_slug', 'secure-login');
        $current_uri = $_SERVER['REQUEST_URI'];
        
        if ($this->is_custom_login_enabled() && 
            strpos($current_uri, '/' . $custom_slug . '/') !== false &&
            !is_user_logged_in()) {
            
            $this->load_login_form();
            exit;
        }
    }
    
    public function parse_custom_login_request($wp) {
        $custom_slug = $this->get_option('custom_login_slug', 'secure-login');
        
        // Check if the request matches our custom login URL
        if ($this->is_custom_login_enabled() && 
            isset($wp->request) && 
            $wp->request === $custom_slug &&
            !is_user_logged_in()) {
            
            // Set the query vars manually
            $wp->query_vars['cf_login'] = '1';
            
            // Load the login form
            $this->load_login_form();
            exit;
        }
    }
    
    private function load_login_form() {
        // Initialize variables that wp-login.php expects
        global $error, $user_login, $redirect_to;
        
        // Set default values
        $error = '';
        $user_login = '';
        $redirect_to = admin_url();
        
        // Check for action parameter
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        // Handle different actions
        switch ($action) {
            case 'lostpassword':
                $this->render_lost_password_form();
                break;
            case 'retrievepassword':
                $this->handle_lost_password_request();
                break;
            case 'resetpass':
            case 'rp':
                $this->render_reset_password_form();
                break;
            default:
                // Handle login form submission
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log'])) {
                    $this->handle_login_submission();
                }
                
                // Get any error messages
                if (isset($_GET['login']) && $_GET['login'] === 'failed') {
                    $error = __('Invalid username or password.', 'cf-login');
                }
                
                // Preserve username if provided
                if (isset($_POST['log'])) {
                    $user_login = sanitize_text_field($_POST['log']);
                }
                
                // Set the page title
                add_filter('wp_title', function() {
                    return __('Log In', 'cf-login');
                });
                
                // Load the login form template
                $this->render_login_form();
                break;
        }
        exit;
    }
    
    private function handle_login_submission() {
        // Handle the login form submission
        $user_login = sanitize_text_field($_POST['log']);
        $user_pass = $_POST['pwd'];
        $remember = isset($_POST['rememberme']);
        $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : admin_url();
        
        // Validate inputs
        if (empty($user_login) || empty($user_pass)) {
            wp_redirect(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/?login=failed'));
            exit;
        }
        
        // Attempt to authenticate
        $user = wp_authenticate($user_login, $user_pass);
        
        if (is_wp_error($user)) {
            // Login failed - redirect back with error
            wp_redirect(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/?login=failed'));
            exit;
        } else {
            // Login successful
            wp_set_auth_cookie($user->ID, $remember);
            
            // Redirect to intended destination
            wp_redirect($redirect_to);
            exit;
        }
    }
    

    
    private function render_login_form() {
        global $error, $user_login, $redirect_to;
        
        // Get the site name
        $site_name = get_bloginfo('name');
        
        // Start output
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($site_name); ?> - <?php _e('Log In', 'cf-login'); ?></title>
            <?php wp_head(); ?>
            <?php 
            $background_image = $this->get_option('background_image', '');
            $enable_overlay = $this->get_option('enable_background_overlay', false);
            $overlay_color = $this->get_option('background_overlay_color', '#000000');
            $overlay_opacity = $this->get_option('background_overlay_opacity', 50);
            ?>
            <style>
                body.login {
                    background-color: <?php echo esc_attr($this->get_option('background_color', '#f1f1f1')); ?>;
                    color: <?php echo esc_attr($this->get_option('text_color', '#333333')); ?>;
                    <?php if (!empty($background_image)): ?>
                    background-image: url('<?php echo esc_url($background_image); ?>') !important;
                    background-size: cover !important;
                    background-position: center !important;
                    background-repeat: no-repeat !important;
                    min-height: 100vh !important;
                    <?php endif; ?>
                }
                #login {
                    background-color: <?php echo esc_attr($this->get_option('form_background_color', '#ffffff')); ?>;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    padding: 20px;
                    margin: 100px auto 50px auto;
                    max-width: 400px;
                    <?php if (!empty($background_image)): ?>
                    backdrop-filter: blur(35px) !important;
                    <?php endif; ?>
                }
                #login h1 a {
                    background-size: contain;
                    width: 320px;
                    height: 120px;
                    margin-bottom: 20px;
                }
                #login form {
                    margin-top: 20px;
                }
                #login form .input {
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 12px;
                    width: 100%;
                    box-sizing: border-box;
                }
                #login form .input:focus {
                    border-color: #0073aa;
                    box-shadow: 0 0 0 1px #0073aa;
                }
                #login .button-primary {
                    background-color: #0073aa;
                    border-color: #0073aa;
                    border-radius: 4px;
                    padding: 12px 24px;
                    font-weight: 600;
                    width: 100%;
                }
                #login .button-primary:hover {
                    background-color: #005a87;
                    border-color: #005a87;
                }
                #login_error {
                    background-color: #dc3232;
                    color: white;
                    padding: 10px;
                    border-radius: 4px;
                    margin-bottom: 20px;
                }
                #footer-text {
                    text-align: center;
                    margin-top: 20px;
                    color: #666;
                    font-size: 12px;
                    line-height: 1.4;
                }
                
                <?php if (!empty($background_image) && $enable_overlay): ?>
                body::before {
                    content: '';
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: <?php echo esc_attr($overlay_color); ?>;
                    opacity: <?php echo esc_attr($overlay_opacity / 100); ?>;
                    z-index: 1;
                    pointer-events: none;
                }
                
                #login {
                    position: relative;
                    z-index: 2;
                }
                <?php endif; ?>
            </style>
        </head>
        <body class="login">
            <div id="login">
                <h1>
                    <a href="<?php echo esc_url($this->get_option('header_url', home_url('/'))); ?>">
                        <?php 
                        $logo_url = $this->get_option('logo_url', '');
                        if (!empty($logo_url)) {
                            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" style="max-width: 320px; height: auto;">';
                        } else {
                            echo esc_html($this->get_option('header_text', $site_name));
                        }
                        ?>
                    </a>
                </h1>
                
                <?php if (!empty($error)): ?>
                <div id="login_error"><?php echo esc_html($error); ?></div>
                <?php endif; ?>
                
                <form name="loginform" id="loginform" action="<?php echo esc_url(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/')); ?>" method="post">
                    <p>
                        <label for="user_login"><?php _e('Username or Email Address', 'cf-login'); ?></label>
                        <input type="text" name="log" id="user_login" class="input" value="<?php echo esc_attr($user_login); ?>" size="20" autocapitalize="off" autocomplete="username" required>
                    </p>
                    <p>
                        <label for="user_pass"><?php _e('Password', 'cf-login'); ?></label>
                        <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" autocomplete="current-password" required>
                    </p>
                    <p class="forgetmenot">
                        <input name="rememberme" type="checkbox" id="rememberme" value="forever">
                        <label for="rememberme"><?php _e('Remember Me', 'cf-login'); ?></label>
                    </p>
                    <p class="submit">
                        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Log In', 'cf-login'); ?>">
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_to); ?>">
                    </p>
                </form>
                
                <p id="nav">
                    <a href="<?php echo esc_url(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/?action=lostpassword')); ?>"><?php _e('Lost your password?', 'cf-login'); ?></a>
                </p>
                
                <p id="backtoblog">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><?php printf(__('← Back to %s', 'cf-login'), $site_name); ?></a>
                </p>
                
                <?php 
                $footer_text = $this->get_option('footer_text', '');
                if (!empty($footer_text)): ?>
                <p id="footer-text" style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
                    <?php echo esc_html($footer_text); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    private function render_lost_password_form() {
        global $error;
        $site_name = get_bloginfo('name');
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($site_name); ?> - <?php _e('Lost Password', 'cf-login'); ?></title>
            <?php wp_head(); ?>
            <?php $this->render_login_styles(); ?>
        </head>
        <body class="login">
            <div id="login">
                <h1>
                    <a href="<?php echo esc_url($this->get_option('header_url', home_url('/'))); ?>">
                        <?php 
                        $logo_url = $this->get_option('logo_url', '');
                        if (!empty($logo_url)) {
                            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" style="max-width: 320px; height: auto;">';
                        } else {
                            echo esc_html($this->get_option('header_text', $site_name));
                        }
                        ?>
                    </a>
                </h1>
                
                <?php if (!empty($error)): ?>
                <div id="login_error"><?php echo esc_html($error); ?></div>
                <?php endif; ?>
                
                <form name="lostpasswordform" id="lostpasswordform" action="<?php echo esc_url(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/?action=retrievepassword')); ?>" method="post">
                    <p><?php _e('Please enter your username or email address. You will receive a link to create a new password via email.', 'cf-login'); ?></p>
                    <p>
                        <label for="user_login"><?php _e('Username or Email Address', 'cf-login'); ?></label>
                        <input type="text" name="user_login" id="user_login" class="input" value="" size="20" autocapitalize="off" autocomplete="username" required>
                    </p>
                    <p class="submit">
                        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Get New Password', 'cf-login'); ?>">
                    </p>
                </form>
                
                <p id="nav">
                    <a href="<?php echo esc_url(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/')); ?>"><?php _e('← Back to Login', 'cf-login'); ?></a>
                </p>
                
                <?php 
                $footer_text = $this->get_option('footer_text', '');
                if (!empty($footer_text)): ?>
                <p id="footer-text" style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
                    <?php echo esc_html($footer_text); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    private function handle_lost_password_request() {
        $user_login = sanitize_text_field($_POST['user_login']);
        
        if (empty($user_login)) {
            wp_redirect(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/?action=lostpassword&error=empty'));
            exit;
        }
        
        // Use WordPress's built-in password reset functionality
        $result = retrieve_password($user_login);
        
        if (is_wp_error($result)) {
            wp_redirect(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/?action=lostpassword&error=invalid'));
            exit;
        } else {
            wp_redirect(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/?action=lostpassword&message=email_sent'));
            exit;
        }
    }
    
    private function render_reset_password_form() {
        global $error;
        $site_name = get_bloginfo('name');
        $key = isset($_GET['key']) ? $_GET['key'] : '';
        $login = isset($_GET['login']) ? $_GET['login'] : '';
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($site_name); ?> - <?php _e('Reset Password', 'cf-login'); ?></title>
            <?php wp_head(); ?>
            <?php $this->render_login_styles(); ?>
        </head>
        <body class="login">
            <div id="login">
                <h1>
                    <a href="<?php echo esc_url($this->get_option('header_url', home_url('/'))); ?>">
                        <?php 
                        $logo_url = $this->get_option('logo_url', '');
                        if (!empty($logo_url)) {
                            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" style="max-width: 320px; height: auto;">';
                        } else {
                            echo esc_html($this->get_option('header_text', $site_name));
                        }
                        ?>
                    </a>
                </h1>
                
                <?php if (!empty($error)): ?>
                <div id="login_error"><?php echo esc_html($error); ?></div>
                <?php endif; ?>
                
                <form name="resetpassform" id="resetpassform" action="<?php echo esc_url(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/?action=resetpass')); ?>" method="post">
                    <p><?php _e('Enter your new password below.', 'cf-login'); ?></p>
                    <p>
                        <label for="pass1"><?php _e('New Password', 'cf-login'); ?></label>
                        <input type="password" name="pass1" id="pass1" class="input" value="" size="20" autocomplete="new-password" required>
                    </p>
                    <p>
                        <label for="pass2"><?php _e('Confirm New Password', 'cf-login'); ?></label>
                        <input type="password" name="pass2" id="pass2" class="input" value="" size="20" autocomplete="new-password" required>
                    </p>
                    <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
                    <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
                    <p class="submit">
                        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Reset Password', 'cf-login'); ?>">
                    </p>
                </form>
                
                <p id="nav">
                    <a href="<?php echo esc_url(home_url('/' . $this->get_option('custom_login_slug', 'secure-login') . '/')); ?>"><?php _e('← Back to Login', 'cf-login'); ?></a>
                </p>
                
                <?php 
                $footer_text = $this->get_option('footer_text', '');
                if (!empty($footer_text)): ?>
                <p id="footer-text" style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
                    <?php echo esc_html($footer_text); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    private function render_login_styles() {
        $background_image = $this->get_option('background_image', '');
        $enable_overlay = $this->get_option('enable_background_overlay', false);
        $overlay_color = $this->get_option('background_overlay_color', '#000000');
        $overlay_opacity = $this->get_option('background_overlay_opacity', 50);
        ?>

        <style>
            * {
                box-sizing: border-box;
            }
            
            body {
                background-color: <?php echo esc_attr($this->get_option('background_color', '#f1f1f1')); ?> !important;
                color: <?php echo esc_attr($this->get_option('text_color', '#333333')); ?> !important;
                margin: 0 !important;
                padding: 0 !important;
                <?php if (!empty($background_image)): ?>
                background-image: url('<?php echo esc_url($background_image); ?>') !important;
                background-size: cover !important;
                background-position: center !important;
                background-repeat: no-repeat !important;
                min-height: 100vh !important;
                <?php endif; ?>
            }
            #login {
                background-color: <?php echo esc_attr($this->get_option('form_background_color', '#ffffff')); ?>;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                padding: 20px;
                margin: 100px auto 50px auto;
                max-width: 400px;
                <?php if (!empty($background_image)): ?>
                backdrop-filter: blur(35px) !important;
                <?php endif; ?>
            }
            #login h1 a {
                background-size: contain;
                width: 320px;
                height: 120px;
                margin-bottom: 20px;
            }
            #login form {
                margin-top: 20px;
            }
            #login form .input {
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 12px;
                width: 100%;
                box-sizing: border-box;
            }
            #login form .input:focus {
                border-color: #0073aa;
                box-shadow: 0 0 0 1px #0073aa;
            }
            #login .button-primary {
                background-color: #0073aa;
                border-color: #0073aa;
                border-radius: 4px;
                padding: 12px 24px;
                font-weight: 600;
                width: 100%;
            }
            #login .button-primary:hover {
                background-color: #005a87;
                border-color: #005a87;
            }
            #login_error {
                background-color: #dc3232;
                color: white;
                padding: 10px;
                border-radius: 4px;
                margin-bottom: 20px;
            }

            
            <?php if (!empty($background_image) && $enable_overlay): ?>
            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: <?php echo esc_attr($overlay_color); ?>;
                opacity: <?php echo esc_attr($overlay_opacity / 100); ?>;
                z-index: 1;
                pointer-events: none;
            }
            
            #login {
                position: relative;
                z-index: 2;
            }
            <?php endif; ?>
        </style>
        <?php
    }
    
    public function redirect_wp_login() {
        // Check if WordPress functions are available
        if (!function_exists('is_user_logged_in') || !function_exists('wp_safe_redirect') || !function_exists('home_url')) {
            return;
        }
        
        if ($this->is_custom_login_enabled() && !is_user_logged_in()) {
            $current_uri = $_SERVER['REQUEST_URI'];
            $request_uri = $_SERVER['REQUEST_URI'];
            $script_name = $_SERVER['SCRIPT_NAME'];
            $redirect_url = $this->get_option('redirect_url', $this->get_home_url('/'));
            
            // Check multiple ways the URL might be accessed
            $is_wp_admin = (
                strpos($current_uri, 'wp-admin') !== false ||
                strpos($request_uri, 'wp-admin') !== false ||
                strpos($script_name, 'wp-admin') !== false ||
                strpos($current_uri, '/wp-admin') !== false ||
                strpos($request_uri, '/wp-admin') !== false
            );
            
            $is_wp_login = (
                strpos($current_uri, 'wp-login.php') !== false ||
                strpos($request_uri, 'wp-login.php') !== false ||
                strpos($script_name, 'wp-login.php') !== false ||
                strpos($current_uri, '/wp-login.php') !== false ||
                strpos($request_uri, '/wp-login.php') !== false
            );
            
            $is_wp_admin_php = (
                strpos($current_uri, 'wp-admin.php') !== false ||
                strpos($request_uri, 'wp-admin.php') !== false ||
                strpos($script_name, 'wp-admin.php') !== false
            );
            
            // Block access to wp-admin, wp-login.php, and wp-admin.php
            if ($is_wp_admin || $is_wp_login || $is_wp_admin_php) {
                
                // Allow only specific actions on wp-login.php for password reset
                if ($is_wp_login) {
                    $allowed_actions = array('lostpassword', 'retrievepassword', 'resetpass', 'rp');
                    $has_allowed_action = false;
                    
                    foreach ($allowed_actions as $action) {
                        if (strpos($current_uri, 'action=' . $action) !== false || 
                            strpos($request_uri, 'action=' . $action) !== false) {
                            $has_allowed_action = true;
                            break;
                        }
                    }
                    
                    // If it's not an allowed action, redirect
                    if (!$has_allowed_action) {
                        wp_safe_redirect($redirect_url, 302);
                        exit;
                    }
                } else {
                    // Block all wp-admin access
                    wp_safe_redirect($redirect_url, 302);
                    exit;
                }
            }
        }
    }
    
    public function customize_login_head() {
        $custom_css = $this->get_option('custom_css', '');
        $logo_url = $this->get_option('logo_url', '');
        $background_color = $this->get_option('background_color', '#f1f1f1');
        $background_image = $this->get_option('background_image', '');
        $enable_overlay = $this->get_option('enable_background_overlay', false);
        $overlay_color = $this->get_option('background_overlay_color', '#000000');
        $overlay_opacity = $this->get_option('background_overlay_opacity', 50);
        $form_background_color = $this->get_option('form_background_color', '#ffffff');
        $text_color = $this->get_option('text_color', '#333333');
        
        echo '<style type="text/css">';
        
        if (!empty($custom_css)) {
            echo $custom_css;
        }
        
        if (!empty($logo_url)) {
            echo '
            .login h1 a {
                background-image: url(' . esc_url($logo_url) . ') !important;
                background-size: contain !important;
                width: 320px !important;
                height: 120px !important;
            }';
        }
        
        echo '
        body.login {
            background-color: ' . esc_attr($background_color) . ' !important;
            color: ' . esc_attr($text_color) . ' !important;';
        
        if (!empty($background_image)) {
            echo '
            background-image: url(' . esc_url($background_image) . ') !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;';
        }
        
        echo '
        }
        
        .login form {
            background-color: ' . esc_attr($form_background_color) . ' !important;
            border: 1px solid #ddd !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;';
        
        if (!empty($background_image)) {
            echo '
            background-color: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(35px) !important;';
        }
        
        echo '
        }
        
        .login form .input {
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            padding: 12px !important;
        }
        
        .login form .input:focus {
            border-color: #0073aa !important;
            box-shadow: 0 0 0 1px #0073aa !important;
        }
        
        .login .button-primary {
            background-color: #0073aa !important;
            border-color: #0073aa !important;
            border-radius: 4px !important;
            padding: 12px 24px !important;
            font-weight: 600 !important;
        }
        
        .login .button-primary:hover {
            background-color: #005a87 !important;
            border-color: #005a87 !important;
        }';
        
        if (!empty($background_image) && $enable_overlay) {
            echo '
            body.login::before {
                content: "" !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background-color: ' . esc_attr($overlay_color) . ' !important;
                opacity: ' . esc_attr($overlay_opacity / 100) . ' !important;
                z-index: 1 !important;
                pointer-events: none !important;
            }
            
            .login form {
                position: relative !important;
                z-index: 2 !important;
            }';
        }
        
        echo '</style>';
    }
    
    public function customize_login_footer() {
        $footer_text = $this->get_option('footer_text', '');
        if (!empty($footer_text)) {
            echo '<p style="text-align: center; margin-top: 20px; color: #666;">' . esc_html($footer_text) . '</p>';
        }
    }
    
    public function customize_login_header_url() {
        return $this->get_option('header_url', home_url());
    }
    
    public function customize_login_header_text() {
        return $this->get_option('header_text', get_bloginfo('name'));
    }
    
    private function is_custom_login_enabled() {
        // Check if WordPress functions are available
        if (!function_exists('get_option')) {
            return false;
        }
        return $this->get_option('enable_custom_login', false);
    }
    
    private function get_option($key, $default = '') {
        // Check if WordPress functions are available
        if (!function_exists('get_option')) {
            return $default;
        }
        $options = get_option('cf_login_options', array());
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    private function get_home_url($path = '') {
        // Check if WordPress functions are available
        if (!function_exists('home_url')) {
            // Fallback to constructing URL from server variables
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            return $protocol . '://' . $host . $path;
        }
        return home_url($path);
    }
}

// Initialize the plugin
CF_Login_Customizer::get_instance();

// Activation hook to ensure rewrite rules are set up
register_activation_hook(__FILE__, function() {
    // Initialize the plugin to get proper settings
    $plugin = CF_Login_Customizer::get_instance();
    
    // Add rewrite rules with default slug
    $custom_slug = 'secure-login'; // Default slug
    add_rewrite_rule(
        '^' . $custom_slug . '/?$',
        'index.php?cf_login=1',
        'top'
    );
    
    // Handle form submissions and other login actions
    add_rewrite_rule(
        '^' . $custom_slug . '/wp-login\.php/?$',
        'index.php?cf_login=1',
        'top'
    );
    
    // Handle any additional paths under the custom slug
    add_rewrite_rule(
        '^' . $custom_slug . '/(.+)$',
        'index.php?cf_login=1&action=$matches[1]',
        'top'
    );
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

// Deactivation hook to clean up
register_deactivation_hook(__FILE__, function() {
    // Flush rewrite rules to remove custom rules
    flush_rewrite_rules();
    
    // Clean up .htaccess rules
    $htaccess_file = ABSPATH . '.htaccess';
    if (file_exists($htaccess_file) && is_writable($htaccess_file)) {
        $htaccess_content = file_get_contents($htaccess_file);
        $htaccess_content = preg_replace('/# CF Login Customizer.*?<\/IfModule>\n/s', '', $htaccess_content);
        file_put_contents($htaccess_file, $htaccess_content);
    }
}); 