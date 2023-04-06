<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

//add the settings page and submenu page.
function rcp_fai_reports_display_settings_page() {
    // Check if the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Display the settings page content
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
}

function rcp_fai_reports_add_admin_menu() {
    add_menu_page(
        __('RCP Friendly AI Reports', 'rcp-friendly-ai-reports'),
        __('RCP Friendly AI Reports', 'rcp-friendly-ai-reports'),
        'manage_options',
        'rcp_fai_reports_settings',
        'rcp_fai_reports_display_settings_page',
        'dashicons-chart-pie',
        30
    );

    add_submenu_page(
        'rcp_fai_reports_settings',
        __('ChatGPT API', 'rcp-friendly-ai-reports'),
        __('ChatGPT API', 'rcp-friendly-ai-reports'),
        'manage_options',
        'rcp_fai_reports_chatgpt_api',
        'rcp_fai_reports_display_chatgpt_api_page'
    );
}
add_action('admin_menu', 'rcp_fai_reports_add_admin_menu');



function rcp_fai_reports_display_chatgpt_api_page() {
    // Check if the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Display the ChatGPT API page content
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('rcp_fai_reports_options_group');
            do_settings_sections('chatgpt_api');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// register settings for ChaptGPT API key
function rcp_fai_reports_register_settings() {
    register_setting(
        'rcp_fai_reports_options_group',
        'rcp_fai_reports_chatgpt_api_key',
        'sanitize_text_field'
    );

    add_settings_section(
        'rcp_fai_reports_chatgpt_api_section',
        __('ChatGPT API Settings', 'rcp-friendly-ai-reports'),
        null,
        'chatgpt_api'
    );

    add_settings_field(
        'rcp_fai_reports_chatgpt_api_key',
        __('ChatGPT API Key', 'rcp-friendly-ai-reports'),
        'rcp_fai_reports_chatgpt_api_key_callback',
        'chatgpt_api',
        'rcp_fai_reports_chatgpt_api_section'
    );
}
add_action('admin_init', 'rcp_fai_reports_register_settings');

// display API key input field
function rcp_fai_reports_chatgpt_api_key_callback() {
    $api_key = get_option('rcp_fai_reports_chatgpt_api_key');
    ?>
    <input type="text" name="rcp_fai_reports_chatgpt_api_key" id="rcp_fai_reports_chatgpt_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
    <?php
}

//create admin dashbaord widget
function rcp_fai_reports_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'rcp_fai_reports_dashboard_widget',
        __('Restrict Content Pro Reports', 'rcp-friendly-ai-reports'),
        'rcp_fai_reports_dashboard_widget_callback'
    );
}
add_action('wp_dashboard_setup', 'rcp_fai_reports_add_dashboard_widget');

// fallback for dashboard widget content
function rcp_fai_reports_dashboard_widget_callback() {
    ?>
    <button id="rcp_fai_reports_run_report_button"><?php _e('Run Report', 'rcp-friendly-ai-reports'); ?></button>
    <div id="rcp_fai_reports_result"></div>
    <?php
}

//enque admin.js 
function rcp_fai_reports_enqueue_admin_scripts($hook) {
    if ('index.php' !== $hook) {
        return;
    }

    wp_enqueue_script(
        'rcp_fai_reports_admin_script',
        RCP_FAI_REPORTS_PLUGIN_URL . 'admin/admin.js',
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
}
add_action('admin_enqueue_scripts', 'rcp_fai_reports_enqueue_admin_scripts');

//call chatgpt api and return response
function rcp_fai_reports_get_chatgpt_report($api_key, $new_memberships_yesterday, $total_monthly_revenue, $greeting, $model = 'gpt-3.5-turbo') {
    $client = new Client([
        'base_uri' => 'https://api.openai.com/',
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
    ]);

    $params = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a friendly, cheerful person who loves to give reports based on the data given to you'
            ],
            [
                'role' => 'user',
                'content' => "{$greeting} Since yesterday, {$new_memberships_yesterday} new active memberships were added, and the total monthly revenue from active monthly memberships is {$total_monthly_revenue}. Answer in a friendly way like you are a super happy assistant, and I am your boss who just came into the office, and you are excited to share this information with me."
            ],
            
        ],
    ];

    try {
        $response = $client->post('v1/chat/completions', [
            'json' => $params,
        ]);

        $response_data = json_decode($response->getBody(), true);
        return $response_data['choices'][0]['message']['content'];
    } catch (ClientException $e) {
        return 'Error: ' . $e->getMessage();
    }
}




// server side ajax handler to run SQL query and fetch ChatGPT response
function rcp_fai_reports_run_report_ajax_handler() {
    check_ajax_referer('rcp_fai_reports_nonce', 'nonce');

    global $wpdb;

    // Get the first name of the logged-in user
    $current_user = wp_get_current_user();
    $first_name = $current_user->user_firstname;

    // Set a default greeting if the first name is empty
    $greeting = empty($first_name) ? 'Hello there!' : "Hello {$first_name}!";

      // Get the date range for the previous day
      $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
      $end_date = date('Y-m-d H:i:s'); // Update this line to use the current date and time
  
      // Query for new active memberships added yesterday until the moment the button is clicked
      $new_memberships_query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}rcp_memberships WHERE status = 'active' AND created_date >= %s AND created_date <= %s;", $start_date, $end_date);
      $new_memberships_yesterday = intval($wpdb->get_var($new_memberships_query));


    // Query for total monthly revenue from active monthly memberships
    $monthly_revenue_query = $wpdb->prepare("SELECT SUM(recurring_amount) AS total_monthly_revenue FROM {$wpdb->prefix}rcp_memberships WHERE status = 'active' AND recurring_amount > 0;");
    $monthly_revenue_result = $wpdb->get_var($monthly_revenue_query);
    $total_monthly_revenue = floatval($monthly_revenue_result);

     // Get the ChatGPT report with the new data
     $api_key = get_option('rcp_fai_reports_chatgpt_api_key');
     $chatgpt_response = rcp_fai_reports_get_chatgpt_report($api_key, $new_memberships_yesterday, $total_monthly_revenue, $greeting);


    echo $chatgpt_response;

    wp_die();
}





add_action('wp_ajax_rcp_fai_reports_run_report', 'rcp_fai_reports_run_report_ajax_handler');