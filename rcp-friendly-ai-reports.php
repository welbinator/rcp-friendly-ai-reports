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

// Include the settings file from the 'admin' folder
require_once RCP_FAI_REPORTS_PLUGIN_DIR . 'admin/settings.php';

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

// Main plugin functionality goes here
