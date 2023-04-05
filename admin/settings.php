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
            'ajaxUrl' => admin_url('admin-ajax.php')
            // 'nonce'   => wp_create_nonce('rcp_fai_reports_nonce')
        )
    );
}
add_action('admin_enqueue_scripts', 'rcp_fai_reports_enqueue_admin_scripts');

//call chatgpt api and return response
function rcp_fai_reports_get_chatgpt_report( $api_key, $total_memberships, $model = 'gpt-3.5-turbo') {
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
                'content' => 'You are a helpful assistant that can generate a report of total memberships'
            ],
            [
                
                    'role' => 'user',
                    'content' => 'There are ' . $total_memberships . 'total memberships.' . 'Tell me how many memberships there are in a friendly manner'
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
    // check_ajax_referer('rcp_fai_reports_nonce', 'nonce');

    global $wpdb;

    $table_prefix = $wpdb->prefix;
    $memberships_table = $table_prefix . 'rcp_memberships';
    error_log(print_r($memberships_table,true));
    
    $query = $wpdb->prepare("SELECT COUNT(*) AS total_memberships FROM {$memberships_table};");
    // $query = $wpdb->prepare("SELECT COUNT(*) AS total_memberships FROM wp_rcp_memberships;");

    error_log(print_r($query,true));
    $result = $wpdb->get_var($query);
    error_log(print_r($result,true));
    $total_memberships = intval($result);
    $total_memberships = 3;
    error_log(print_r($total_memberships,true));
   

    $api_key = get_option('rcp_fai_reports_chatgpt_api_key');
    


    $chatgpt_response = rcp_fai_reports_get_chatgpt_report($api_key, $total_memberships);


    echo $chatgpt_response;

    wp_die();
}

add_action('wp_ajax_rcp_fai_reports_run_report', 'rcp_fai_reports_run_report_ajax_handler'); 