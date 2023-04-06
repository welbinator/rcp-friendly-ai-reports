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
        __('RCP FAI Reports', 'rcp'),
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

// send multiple inputs to chatgpt and receive multiple outputs
function rcp_fai_reports_get_chatgpt_response($api_key, $input, $model = 'gpt-3.5-turbo') {
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
                'content' => $input,
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

function rcp_fai_reports_get_chatgpt_report($api_key, $new_memberships_yesterday, $total_monthly_revenue, $total_daily_revenue, $first_name) {
    // Get the friendly greeting
    $greeting_input = "give me a friendly greeting, using the first name of the person logged in. If no first name exists, use 'friend'";
    $greeting_output = rcp_fai_reports_get_chatgpt_response($api_key, $greeting_input);
    echo '<p>' . $greeting_output . '</p>';

    // Get the new memberships report
    $new_memberships_input = "There were {$new_memberships_yesterday} since yesterday, tell me in a humanly way how many new memberships we gained since yesterday. If the number is greater than 0, be excited about it.";
    $new_memberships_output = rcp_fai_reports_get_chatgpt_response($api_key, $new_memberships_input);
    echo '<h3>Total Memberships</h3>';
    echo '<p>' . $new_memberships_output . '</p>';

    // Get the monthly revenue report
    $monthly_revenue_input = "There is {$total_monthly_revenue} in current monthly revenue from active monthly subscriptions, tell me in a humanly way how much monthly revenue we have currently.";
    $monthly_revenue_output = rcp_fai_reports_get_chatgpt_response($api_key, $monthly_revenue_input);
    echo '<h3>Monthly Revenue</h3>';
    echo '<p>' . $monthly_revenue_output . '</p>';

    // Get the daily revenue report
    $daily_revenue_input = "There is {$total_daily_revenue} in current daily revenue from active daily subscriptions, tell me in a humanly way how much daily revenue we have currently.";
    $daily_revenue_output = rcp_fai_reports_get_chatgpt_response($api_key, $daily_revenue_input);
    echo '<h3>Daily Revenue</h3>';
    echo '<p>' . $daily_revenue_output . '</p>';
}



// server side ajax handler to run SQL query and fetch ChatGPT response
function rcp_fai_reports_run_report_ajax_handler() {
    check_ajax_referer('rcp_fai_reports_nonce', 'nonce');

    global $wpdb;

    // Get the first name of the logged-in user
$current_user = wp_get_current_user();
$first_name = $current_user->user_firstname;

// Set a default greeting if the first name is empty
$greeting = empty($first_name) ? 'Hello, friend!' : "Hello, {$first_name}!";

      // Get the date range for the previous day
      $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
      $end_date = date('Y-m-d H:i:s'); // Update this line to use the current date and time
  
      // Query for new active memberships added yesterday until the moment the button is clicked
      $new_memberships_query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}rcp_memberships WHERE status = 'active' AND created_date >= %s AND created_date <= %s;", $start_date, $end_date);
      $new_memberships_yesterday = intval($wpdb->get_var($new_memberships_query));


    // Query for total monthly revenue from active monthly memberships
$monthly_revenue_query = $wpdb->prepare("SELECT SUM(m.recurring_amount) AS total_monthly_revenue FROM {$wpdb->prefix}rcp_memberships AS m JOIN {$wpdb->prefix}restrict_content_pro AS s ON m.object_id = s.id WHERE m.status = 'active' AND m.recurring_amount > 0 AND s.duration_unit = 'month';");
$total_monthly_revenue = floatval($wpdb->get_var($monthly_revenue_query));

// Query for total daily revenue from active daily memberships
$daily_revenue_query = $wpdb->prepare("SELECT SUM(m.recurring_amount) AS total_daily_revenue FROM {$wpdb->prefix}rcp_memberships AS m JOIN {$wpdb->prefix}restrict_content_pro AS s ON m.object_id = s.id WHERE m.status = 'active' AND m.recurring_amount > 0 AND s.duration_unit = 'day';");
$total_daily_revenue = floatval($wpdb->get_var($daily_revenue_query));





     // Get the ChatGPT report with the new data
     $api_key = get_option('rcp_fai_reports_chatgpt_api_key');
     $chatgpt_response = rcp_fai_reports_get_chatgpt_report($api_key, $new_memberships_yesterday, $total_monthly_revenue, $total_daily_revenue, $greeting);



    echo $chatgpt_response;

    wp_die();
}





add_action('wp_ajax_rcp_fai_reports_run_report', 'rcp_fai_reports_run_report_ajax_handler');