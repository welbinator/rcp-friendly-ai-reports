<?php
/**
 * Plugin Name: RCP Friendly AI Reports
 * Plugin URI: https://www.example.com/rcp-friendly-ai-reports
 * Description: A WordPress plugin that generates friendly AI reports for Restrict Content Pro.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://www.example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: rcp-friendly-ai-reports
 * Domain Path: /languages
 */

// Prevent direct access to the plugin file
if (!defined('ABSPATH')) {
    exit;
}

// Define constants for the plugin
define('RCP_FAI_REPORTS_VERSION', '1.0.0');
define('RCP_FAI_REPORTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RCP_FAI_REPORTS_PLUGIN_URL', plugin_dir_url(__FILE__));


// Include Composer autoload file
require_once RCP_FAI_REPORTS_PLUGIN_DIR . 'vendor/autoload.php';

// Include the functions file from the 'includes' folder
require_once RCP_FAI_REPORTS_PLUGIN_DIR . 'includes/functions.php';

// Load plugin textdomain for translations
function rcp_fai_reports_load_textdomain() {
    load_plugin_textdomain('rcp-friendly-ai-reports', false, basename(RCP_FAI_REPORTS_PLUGIN_DIR) . '/languages');
}
add_action('plugins_loaded', 'rcp_fai_reports_load_textdomain');

// Plugin activation function
function rcp_fai_reports_activate() {
    // Add any activation code here, such as creating database tables or setting default options
}
register_activation_hook(__FILE__, 'rcp_fai_reports_activate');

// Plugin deactivation function
function rcp_fai_reports_deactivate() {
    // Add any deactivation code here, such as removing database tables or plugin options
}
register_deactivation_hook(__FILE__, 'rcp_fai_reports_deactivate');

// Enqueue scrips and styles

//admin.js 
function rcp_fai_reports_enqueue_admin_scripts($hook) {
    if ('index.php' !== $hook) {
        return;
    }

    wp_enqueue_script(
        'rcp_fai_reports_admin_script',
        RCP_FAI_REPORTS_PLUGIN_URL . 'admin/js/admin.js',
        array('jquery'),
        RCP_FAI_REPORTS_VERSION,
        true
    );

    wp_localize_script(
        'rcp_fai_reports_admin_script',
        'rcpFaiReportsAjax',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('rcp_fai_reports_nonce')
        )
    );
    wp_enqueue_style(
        'rcp-friendly-ai-reports-admin',
        plugin_dir_url(__FILE__) . 'admin/css/admin.css',
        array(),
        '1.0.0',
        'all'
    );
}
add_action('admin_enqueue_scripts', 'rcp_fai_reports_enqueue_admin_scripts');



// Main plugin functionality goes here
