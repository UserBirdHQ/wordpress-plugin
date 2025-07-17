<?php
/**
 * Plugin Name: Userbird-analytics
 * Description: Add Userbird tracking to your site
 * Version: 1.0
 * Author: Userbird
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('USERBIRD_PLUGIN', __FILE__);
define('USERBIRD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('USERBIRD_BASENAME', plugin_basename(__FILE__));
define('USERBIRD_URL', plugin_dir_url(__FILE__));

// Register activation hook
register_activation_hook(__FILE__, 'userbird_activate');

function userbird_activate() {
    // Default settings
    $default_options = array(
        'tracking_id' => '',
        'script_url' => 'https://cdn.userbird.com/analytics.min.js'
    );
    
    // Add options if they don't exist
    add_option('userbird_options', $default_options);
}

// Get plugin options
function userbird_get_options() {
    $options = get_option('userbird_options');
    
    // Set defaults if options don't exist
    if (!$options) {
        $options = array(
            'tracking_id' => '',
            'script_url' => 'https://cdn.userbird.com/analytics.min.js'
        );
    }
    
    // Make sure all expected options exist
    if (!isset($options['script_url']) || empty($options['script_url'])) {
        $options['script_url'] = 'https://cdn.userbird.com/analytics.min.js';
    }
    
    // Make sure tracking_id exists
    if (!isset($options['tracking_id'])) {
        $options['tracking_id'] = '';
    }
    
    return $options;
}


// Register and enqueue frontend tracking script
function userbird_register_script() {
    $options = userbird_get_options();
    
    // Only proceed if we have a valid tracking ID with the correct format
    if (!empty($options['tracking_id']) && strpos($options['tracking_id'], '/') !== false) {
        // Parse tracking ID to get site_id
        // Format: workspace_id/site_id
        $tracking_parts = explode('/', $options['tracking_id']);
        $site_id = sanitize_text_field($tracking_parts[1]);
        $script_url = !empty($options['script_url']) ? esc_url($options['script_url']) : 'https://cdn.userbird.com/analytics.min.js';
        
        // Register the script first
        wp_register_script(
            'userbird',         // Handle
            $script_url,               // Source
            array(),                   // Dependencies
            '1.7',                     // Version (matching plugin version)
            false                      // Load in header
        );
        
        // For Userbird, we need to use the script loader tag filter
        // to add the data-site attribute properly
        if (!empty($site_id)) {
            add_filter('script_loader_tag', function($tag, $handle, $src) use ($site_id) {
                if ('userbird' !== $handle) {
                    return $tag;
                }
                // Replace the script tag with one that includes our data-site attribute
                return str_replace('<script', '<script data-site="' . esc_attr($site_id) . '"', $tag);
            }, 10, 3);
        }
        
        // Enqueue the script
        wp_enqueue_script('userbird');
    }
}
add_action('wp_enqueue_scripts', 'userbird_register_script');

// Add admin menu with hover dropdown
function userbird_add_admin_menu() {
    // Add settings page under Settings menu
    add_options_page(
        'Userbird Settings',
        'Userbird',
        'manage_options',
        'userbird',
        'userbird_options_page'
    );
    
    // Add the top-level menu as a blank non-functional item
    add_menu_page(
        'Userbird',       // Page title
        'Userbird',       // Menu title
        'manage_options',        // Capability
        'userbird-noop',  // Menu slug - will be completely disabled by JS
        function() {             // Empty callback that does nothing
            wp_die('This page intentionally left blank.');
        },
        'dashicons-analytics',   // Using dashboard icon
        30                       // Position
    );
    
    // Add submenu items
    add_submenu_page(
        'userbird-noop',  // Parent slug
        'Open Analytics',        // Page title
        'Open Analytics',        // Menu title
        'manage_options',        // Capability
        'userbird-open',  // Menu slug
        function() {             // Empty callback
            wp_die('Redirecting...');
        }
    );
    
    add_submenu_page(
        'userbird-noop',  // Parent slug
        'Settings',              // Page title
        'Settings',              // Menu title
        'manage_options',        // Capability
        'userbird',       // Menu slug (points to settings page)
        'userbird_options_page'
    );
    
    // Remove the duplicate first submenu item
    remove_submenu_page('userbird-noop', 'userbird-noop');
}
add_action('admin_menu', 'userbird_add_admin_menu');

// Register and enqueue admin styles
function userbird_admin_styles() {
    wp_register_style(
        'userbird-admin',      // Handle
        USERBIRD_URL . 'assets/userbird-admin.css',  // Source
        array(),                      // Dependencies
        '1.0.4'                       // Version for cache management
    );
    wp_enqueue_style('userbird-admin');
}
add_action('admin_enqueue_scripts', 'userbird_admin_styles');

// No custom class needed, using standard dashicon

// Register and enqueue admin JavaScript
function userbird_admin_scripts() {
    // Register the script but don't output it yet
    wp_register_script(
        'userbird-admin',      // Handle
        false,                         // No actual file, we'll use inline script
        array('jquery'),               // Dependencies
        '1.0.4',                       // Version
        true                           // In footer
    );
    
    // Get the options to pass to our script
    $options = userbird_get_options();
    
    // Localize the script with our data - wp_localize_script handles JSON encoding and escaping
    wp_localize_script(
        'userbird-admin',
        'userbirdData',
        array(
            'options' => array(
                'tracking_id' => isset($options['tracking_id']) ? esc_js($options['tracking_id']) : '',
                'script_url' => isset($options['script_url']) ? esc_url($options['script_url']) : ''
            ),
            'adminUrl' => esc_url(admin_url())
        )
    );
    
    // Add the inline script content
    $inline_script = "
        document.addEventListener('DOMContentLoaded', function() {
            // First find the main menu item
            var mainMenuItem = document.querySelector('a.toplevel_page_userbird-noop');
            
            if (mainMenuItem) {
                // Completely disable clicks
                mainMenuItem.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }, true);
                
                // Set attributes to make it non-clickable
                mainMenuItem.setAttribute('onclick', 'return false;');
                mainMenuItem.setAttribute('href', 'javascript:void(0);');
            }
            
            // Handle click on \"Open Analytics\" submenu item
            var analyticsLink = document.querySelector('a[href=\"admin.php?page=userbird-open\"]');
            if (analyticsLink) {
                analyticsLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    var options = userbirdData.options;
                    var tracking_id = options.tracking_id;
                    
                    if (!tracking_id || tracking_id.indexOf('/') === -1) {
                        alert('Please configure your Tracking ID in the format \"workspace_id/site_id\" in Userbird settings.');
                        return;
                    }
                    
                    // Extract workspace_id and site_id from tracking_id
                    var parts = tracking_id.split('/');
                    var workspace_id = parts[0];
                    var site_id = parts[1];
                    
                    window.open('https://app.userbird.com/' + workspace_id + '/site/' + site_id, '_blank');
                });
            }
        });
    ";
    
    // Add inline script and enqueue it
    wp_add_inline_script('userbird-admin', $inline_script);
    wp_enqueue_script('userbird-admin');
}
add_action('admin_enqueue_scripts', 'userbird_admin_scripts');

// Add Settings link to plugins page
function userbird_add_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=userbird">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . USERBIRD_BASENAME, 'userbird_add_settings_link');

// Register settings
function userbird_register_settings() {
    register_setting('userbird_options_group', 'userbird_options', 'userbird_validate_options');
}
add_action('admin_init', 'userbird_register_settings');

// Validate options
function userbird_validate_options($input) {
    $valid = array();
    
    // Validate tracking_id - ensure it has the correct format (workspace_id/site_id)
    $tracking_id = sanitize_text_field($input['tracking_id']);
    
    // Check if the tracking ID contains a slash
    if (empty($tracking_id)) {
        add_settings_error(
            'userbird_options',
            'tracking_id',
            'Tracking ID cannot be empty.',
            'error'
        );
        $valid['tracking_id'] = '';
    } elseif (strpos($tracking_id, '/') === false) {
        // Tracking ID must contain a slash
        add_settings_error(
            'userbird_options',
            'tracking_id',
            'Tracking ID must be in the format "workspace_id/site_id".',
            'error'
        );
        // Keep the input value so the user can correct it
        $valid['tracking_id'] = $tracking_id;
    } else {
        // Valid format
        $valid['tracking_id'] = $tracking_id;
    }
    
    $valid['script_url'] = esc_url_raw($input['script_url']);
    return $valid;
}

// Settings page
function userbird_options_page() {
    // Get options
    $options = userbird_get_options();
    
    // Set a default tracking_id value based on existing data if tracking_id isn't set
    if (empty($options['tracking_id']) && !empty($options['workspace_id']) && !empty($options['site_id'])) {
        $options['tracking_id'] = $options['workspace_id'] . '/' . $options['site_id'];
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('userbird_options_group');
            do_settings_sections('userbird_options_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Tracking ID</th>
                    <td>
                        <input type="text" name="userbird_options[tracking_id]" value="<?php echo esc_attr($options['tracking_id']); ?>" class="regular-text" required />
                        <p class="description">Enter your tracking ID in the format <code>workspace_id/site_id</code>. For example: <code>my-workspace/my-site</code></p>
                    </td>
                </tr>
                <!-- Script URL option - hidden but preserved in the database -->
                <input type="hidden" name="userbird_options[script_url]" value="<?php echo esc_url($options['script_url']); ?>" />
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}