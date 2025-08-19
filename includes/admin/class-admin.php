<?php
/**
 * Admin functionality for CF Login Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CF_Login_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('CF Login Customizer', 'cf-login'),
            __('CF Login Customizer', 'cf-login'),
            'manage_options',
            'cf-login-settings',
            array($this, 'admin_page')
        );
    }
    
    public function init_settings() {
        register_setting('cf_login_options', 'cf_login_options', array($this, 'sanitize_options'));
        
        // General Settings Section
        add_settings_section(
            'cf_login_general',
            __('General Settings', 'cf-login'),
            array($this, 'general_section_callback'),
            'cf-login-settings'
        );
        
        // Custom Login URL Settings
        add_settings_field(
            'enable_custom_login',
            __('Enable Custom Login URL', 'cf-login'),
            array($this, 'checkbox_field_callback'),
            'cf-login-settings',
            'cf_login_general',
            array('field' => 'enable_custom_login')
        );
        
        add_settings_field(
            'custom_login_slug',
            __('Custom Login Slug', 'cf-login'),
            array($this, 'text_field_callback'),
            'cf-login-settings',
            'cf_login_general',
            array('field' => 'custom_login_slug', 'default' => 'secure-login')
        );
        
        add_settings_field(
            'redirect_url',
            __('Redirect URL for Unauthorized Access', 'cf-login'),
            array($this, 'text_field_callback'),
            'cf-login-settings',
            'cf_login_general',
            array('field' => 'redirect_url', 'default' => home_url('/'))
        );
        
        // Appearance Settings Section
        add_settings_section(
            'cf_login_appearance',
            __('Appearance Settings', 'cf-login'),
            array($this, 'appearance_section_callback'),
            'cf-login-settings'
        );
        
        add_settings_field(
            'logo_url',
            __('Custom Logo URL', 'cf-login'),
            array($this, 'text_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'logo_url')
        );
        
        add_settings_field(
            'header_text',
            __('Header Text', 'cf-login'),
            array($this, 'text_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'header_text', 'default' => get_bloginfo('name'))
        );
        
        add_settings_field(
            'header_url',
            __('Header URL', 'cf-login'),
            array($this, 'text_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'header_url', 'default' => home_url())
        );
        
        add_settings_field(
            'background_color',
            __('Background Color', 'cf-login'),
            array($this, 'color_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'background_color', 'default' => '#f1f1f1')
        );
        
        add_settings_field(
            'background_image',
            __('Background Image URL', 'cf-login'),
            array($this, 'text_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'background_image', 'description' => __('Enter the URL of your background image. Recommended size: 1920x1080px or larger.', 'cf-login'))
        );
        
        add_settings_field(
            'enable_background_overlay',
            __('Enable Background Overlay', 'cf-login'),
            array($this, 'checkbox_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'enable_background_overlay', 'label' => __('Add a colored overlay on top of the background image for better readability', 'cf-login'))
        );
        
        add_settings_field(
            'background_overlay_color',
            __('Overlay Color', 'cf-login'),
            array($this, 'color_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'background_overlay_color', 'default' => '#000000')
        );
        
        add_settings_field(
            'background_overlay_opacity',
            __('Overlay Opacity', 'cf-login'),
            array($this, 'number_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'background_overlay_opacity', 'default' => '50', 'min' => '0', 'max' => '100', 'description' => __('Opacity percentage (0-100). 0 = transparent, 100 = solid color.', 'cf-login'))
        );
        
        add_settings_field(
            'form_background_color',
            __('Form Background Color', 'cf-login'),
            array($this, 'color_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'form_background_color', 'default' => '#ffffff')
        );
        
        add_settings_field(
            'text_color',
            __('Text Color', 'cf-login'),
            array($this, 'color_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'text_color', 'default' => '#333333')
        );
        
        add_settings_field(
            'footer_text',
            __('Footer Text', 'cf-login'),
            array($this, 'textarea_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'footer_text')
        );
        
        add_settings_field(
            'custom_css',
            __('Custom CSS', 'cf-login'),
            array($this, 'textarea_field_callback'),
            'cf-login-settings',
            'cf_login_appearance',
            array('field' => 'custom_css')
        );
        

        
        // Theme Integration Settings Section
        add_settings_section(
            'cf_login_theme',
            __('Theme Integration', 'cf-login'),
            array($this, 'theme_section_callback'),
            'cf-login-settings'
        );
        
        add_settings_field(
            'primary_color',
            __('Primary Color', 'cf-login'),
            array($this, 'color_field_callback'),
            'cf-login-settings',
            'cf_login_theme',
            array('field' => 'primary_color', 'default' => '#0073aa')
        );
        
        add_settings_field(
            'secondary_color',
            __('Secondary Color', 'cf-login'),
            array($this, 'color_field_callback'),
            'cf-login-settings',
            'cf_login_theme',
            array('field' => 'secondary_color', 'default' => '#005a87')
        );
        
        add_settings_field(
            'enable_animations',
            __('Enable Animations', 'cf-login'),
            array($this, 'checkbox_field_callback'),
            'cf-login-settings',
            'cf_login_theme',
            array('field' => 'enable_animations')
        );
        
        add_settings_field(
            'enable_dark_mode',
            __('Enable Dark Mode Support', 'cf-login'),
            array($this, 'checkbox_field_callback'),
            'cf-login-settings',
            'cf_login_theme',
            array('field' => 'enable_dark_mode')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_cf-login-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('cf-login-admin', CF_LOGIN_PLUGIN_URL . 'assets/css/admin-style.css', array(), CF_LOGIN_VERSION);
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery');
        
        wp_add_inline_script('wp-color-picker', '
            jQuery(document).ready(function($) {
                $(".cf-color-picker").wpColorPicker();
            });
        ');
    }
    
    public function handle_admin_actions() {
        if (isset($_GET['page']) && $_GET['page'] === 'cf-login-settings' && current_user_can('manage_options')) {
            

            

        }
    }
    
    public function admin_page() {
        $options = get_option('cf_login_options', array());
        $custom_slug = isset($options['custom_login_slug']) ? $options['custom_login_slug'] : 'secure-login';
        $custom_login_url = home_url('/' . $custom_slug . '/');
        ?>
        <div class="wrap cf-login-settings">
            <h1><?php _e('CF Login Customizer Settings', 'cf-login'); ?></h1>
            

            

            
            <?php if (isset($options['enable_custom_login']) && $options['enable_custom_login']): ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Custom Login URL:', 'cf-login'); ?></strong> 
                    <a href="<?php echo esc_url($custom_login_url); ?>" target="_blank"><?php echo esc_url($custom_login_url); ?></a>
                </p>
                <p>
                    <strong><?php _e('Redirect URL for Unauthorized Access:', 'cf-login'); ?></strong> 
                    <a href="<?php echo esc_url(isset($options['redirect_url']) ? $options['redirect_url'] : home_url('/')); ?>" target="_blank"><?php echo esc_url(isset($options['redirect_url']) ? $options['redirect_url'] : home_url('/')); ?></a>
                </p>
                <p><?php _e('Access to wp-admin, wp-login.php, and wp-admin.php will be blocked and redirected to the specified URL.', 'cf-login'); ?></p>
            </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('cf_login_options');
                do_settings_sections('cf-login-settings');
                submit_button();
                ?>
            </form>
            

            

            

        </div>
        <?php
    }
    
    public function general_section_callback() {
        echo '<p>' . __('Configure your custom login URL and basic settings.', 'cf-login') . '</p>';
    }
    
    public function appearance_section_callback() {
        echo '<p>' . __('Customize the appearance of your login page.', 'cf-login') . '</p>';
    }
    

    
    public function theme_section_callback() {
        $current_theme = wp_get_theme();
        echo '<p>' . sprintf(__('Customize the login page to match your current theme: %s', 'cf-login'), '<strong>' . esc_html($current_theme->get('Name')) . '</strong>') . '</p>';
    }
    
    public function checkbox_field_callback($args) {
        $options = get_option('cf_login_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : false;
        
        // Get appropriate label based on field
        $label = '';
        $description = '';
        
        switch ($field) {
            case 'enable_custom_login':
                $label = __('Enable custom login URL', 'cf-login');
                $description = __('When enabled, users will be redirected from wp-login.php to your custom URL.', 'cf-login');
                break;
            case 'enable_animations':
                $label = __('Enable animations', 'cf-login');
                $description = __('Add smooth animations to the login form.', 'cf-login');
                break;
            case 'enable_dark_mode':
                $label = __('Enable dark mode support', 'cf-login');
                $description = __('Add dark mode styling to the login page.', 'cf-login');
                break;
            default:
                $label = __('Enable', 'cf-login');
                $description = '';
        }
        ?>
        <input type="checkbox" id="<?php echo esc_attr($field); ?>" name="cf_login_options[<?php echo esc_attr($field); ?>]" value="1" <?php checked(1, $value); ?> />
        <label for="<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?></label>
        <?php if (!empty($description)): ?>
        <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public function text_field_callback($args) {
        $options = get_option('cf_login_options', array());
        $field = $args['field'];
        $default = isset($args['default']) ? $args['default'] : '';
        $value = isset($options[$field]) ? $options[$field] : $default;
        ?>
        <input type="text" id="<?php echo esc_attr($field); ?>" name="cf_login_options[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <?php
        if ($field === 'custom_login_slug') {
            echo '<p class="description">' . __('This will be your new login URL: ', 'cf-login') . '<code>' . home_url('/' . $value . '/') . '</code></p>';
        } elseif ($field === 'redirect_url') {
            echo '<p class="description">' . __('Users trying to access wp-admin or wp-login.php will be redirected to this URL.', 'cf-login') . '</p>';
        } elseif ($field === 'background_image' && isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function color_field_callback($args) {
        $options = get_option('cf_login_options', array());
        $field = $args['field'];
        $default = isset($args['default']) ? $args['default'] : '#000000';
        $value = isset($options[$field]) ? $options[$field] : $default;
        ?>
        <input type="text" id="<?php echo esc_attr($field); ?>" name="cf_login_options[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($value); ?>" class="cf-color-picker" />
        <?php
    }
    
    public function textarea_field_callback($args) {
        $options = get_option('cf_login_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        ?>
        <textarea id="<?php echo esc_attr($field); ?>" name="cf_login_options[<?php echo esc_attr($field); ?>]" rows="5" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <?php
        if ($field === 'custom_css') {
            echo '<p class="description">' . __('Add custom CSS to further style your login page.', 'cf-login') . '</p>';
        }
    }
    
    public function number_field_callback($args) {
        $options = get_option('cf_login_options', array());
        $field = $args['field'];
        $default = isset($args['default']) ? $args['default'] : 0;
        $min = isset($args['min']) ? $args['min'] : 0;
        $max = isset($args['max']) ? $args['max'] : 999;
        $value = isset($options[$field]) ? $options[$field] : $default;
        ?>
        <input type="number" id="<?php echo esc_attr($field); ?>" name="cf_login_options[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($value); ?>" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>" class="small-text" />
        <?php
        if ($field === 'lockout_duration') {
            echo '<p class="description">' . __('Duration in minutes that an IP will be blocked after exceeding maximum login attempts.', 'cf-login') . '</p>';
        } elseif ($field === 'max_login_attempts') {
            echo '<p class="description">' . __('Maximum number of failed login attempts before an IP is temporarily blocked.', 'cf-login') . '</p>';
        } elseif ($field === 'background_overlay_opacity' && isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function sanitize_options($input) {
        $sanitized = array();
        
        // Checkbox fields
        $sanitized['enable_custom_login'] = isset($input['enable_custom_login']) ? true : false;
        $sanitized['enable_background_overlay'] = isset($input['enable_background_overlay']) ? true : false;
        
        // Text fields
        $sanitized['custom_login_slug'] = sanitize_title($input['custom_login_slug']);
        $sanitized['redirect_url'] = esc_url_raw($input['redirect_url']);
        $sanitized['logo_url'] = esc_url_raw($input['logo_url']);
        $sanitized['header_text'] = sanitize_text_field($input['header_text']);
        $sanitized['header_url'] = esc_url_raw($input['header_url']);
        $sanitized['background_image'] = esc_url_raw($input['background_image']);
        $sanitized['footer_text'] = sanitize_textarea_field($input['footer_text']);
        
        // Color fields
        $sanitized['background_overlay_color'] = sanitize_hex_color($input['background_overlay_color']);
        
        // Number fields
        $sanitized['background_overlay_opacity'] = intval($input['background_overlay_opacity']);
        if ($sanitized['background_overlay_opacity'] < 0) $sanitized['background_overlay_opacity'] = 0;
        if ($sanitized['background_overlay_opacity'] > 100) $sanitized['background_overlay_opacity'] = 100;
        
        // Color fields
        $sanitized['background_color'] = sanitize_hex_color($input['background_color']);
        $sanitized['form_background_color'] = sanitize_hex_color($input['form_background_color']);
        $sanitized['text_color'] = sanitize_hex_color($input['text_color']);
        
        // CSS field
        $sanitized['custom_css'] = wp_strip_all_tags($input['custom_css']);
        

        
        // Theme fields
        $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        $sanitized['secondary_color'] = sanitize_hex_color($input['secondary_color']);
        $sanitized['enable_animations'] = isset($input['enable_animations']) ? true : false;
        $sanitized['enable_dark_mode'] = isset($input['enable_dark_mode']) ? true : false;
        
        // Flush rewrite rules if custom login is enabled
        if ($sanitized['enable_custom_login']) {
            flush_rewrite_rules();
        }
        
        return $sanitized;
    }
}