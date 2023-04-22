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
function rcp_fai_reports_load_textdomain()
{
    load_plugin_textdomain('rcp-friendly-ai-reports', false, basename(RCP_FAI_REPORTS_PLUGIN_DIR) . '/languages');
}
add_action('plugins_loaded', 'rcp_fai_reports_load_textdomain');

// Plugin activation function
function rcp_fai_reports_activate()
{
    // Add any activation code here, such as creating database tables or setting default options
}
register_activation_hook(__FILE__, 'rcp_fai_reports_activate');

// Plugin deactivation function
function rcp_fai_reports_deactivate()
{
    // Add any deactivation code here, such as removing database tables or plugin options
}
register_deactivation_hook(__FILE__, 'rcp_fai_reports_deactivate');

// Enqueue scrips and styles


function rcp_fai_reports_enqueue_admin_scripts($hook)
{
// Load scripts and styles only on the RCP reports page
if ($hook !== 'restrict_page_rcp-reports') {
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

    if (false !== strpos($hook, 'rcp-reports')) {
        wp_enqueue_style('rcp_fai_rcp_styles', plugin_dir_url(__FILE__) . 'admin/css/rcp-styles.css', array(), '1.0.0');
    }
}

add_action('admin_enqueue_scripts', 'rcp_fai_reports_enqueue_admin_scripts');



//email stuff
add_action('wp_ajax_rcp_fai_reports_send_report', 'rcp_fai_reports_send_report');

function rcp_fai_reports_send_report() {
    check_ajax_referer('rcp_fai_reports_nonce');

    // Get the report data
    // You'll need to provide the appropriate variables here as arguments to the function
    $report = rcp_fai_reports_get_chatgpt_report($api_key, $new_memberships_yesterday, $total_monthly_revenue, $total_daily_revenue, $total_annual_revenue, $first_name, $current_month_revenue, $previous_year_revenue);

    // Send the report via email
    $email_sent = send_report_to_admin($report);

    if ($email_sent) {
        echo 'The report has been sent to the admin email.';
    } else {
        echo 'Failed to send the report to the admin email.';
    }
}



function send_report_to_admin($report) {
    $to = get_option('admin_email');
    $subject = 'Friendly AI Report';
    $headers = 'Content-Type: text/html; charset=UTF-8';
    $email_sent = wp_mail($to, $subject, $report, $headers);

    if ($email_sent) {
        return true;
    } else {
        return false;
    }
}
