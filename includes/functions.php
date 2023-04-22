<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

//add the settings page and submenu page.
function rcp_fai_reports_display_settings_page()
{
    // Check if the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Display the settings page content
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
}

function rcp_fai_reports_add_admin_menu()
{
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



function rcp_fai_reports_display_chatgpt_api_page()
{
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
function rcp_fai_reports_register_settings()
{
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
function rcp_fai_reports_chatgpt_api_key_callback()
{
    $api_key = get_option('rcp_fai_reports_chatgpt_api_key');
    ?>
    <input type="text" name="rcp_fai_reports_chatgpt_api_key" id="rcp_fai_reports_chatgpt_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
    <?php
}

// Add a new tab called "Friendly Reports" in the RCP Reports page
function rcp_fai_reports_add_tab()
{
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'earnings';
    $tab = 'friendly_reports';

    echo '<a href="?page=rcp-reports&tab=' . $tab . '" class="nav-tab' . ($active_tab == $tab ? ' nav-tab-active' : '') . '">' . __('Friendly Reports', 'rcp') . '</a>';
}
add_action('rcp_reports_tabs', 'rcp_fai_reports_add_tab');


// Step 2: Display the content for the "Friendly Reports" tab
function rcp_fai_reports_friendly_reports_content($tab)
{
    if ('friendly_reports' !== $tab) {
        return;
    }
    
    // Your code to display the "Run Report" button and friendly report content
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#run-report").click(function() {
                // Replace the welcome message and button with the friendly report content
                $("#friendly-report-intro").html(""); // You can also add a loading message here if you'd like

                // Your code to generate and display the friendly report
            });
        });
    </script>
    
    <div id="friendly-report-intro">
        <p>Hi there! To view a friendly report of your membership site's earnings, click the button below!</p>
        <button id="run-report">Run Report</button>
    </div>
    <?php
}
add_action('rcp_reports_tab', 'rcp_fai_reports_friendly_reports_content');

function rcp_fai_display_friendly_reports_tab()
{
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'earnings';

    if ('friendly_reports' === $active_tab) {
        rcp_fai_reports_content();
    }
}
add_action('rcp_reports_page_bottom', 'rcp_fai_display_friendly_reports_tab');


function rcp_fai_reports_content()
{
    global $wpdb;

    // Display the wrapper div
    echo '<div class="rcp_friendly_reports_wrapper">';

    if (isset($_POST['get_friendly_report'])) {

        // Get the first name of the logged-in user
        $current_user = wp_get_current_user();
        $first_name = $current_user->user_firstname;

        // Set a default greeting if the first name is empty
        $greeting = empty($first_name) ? 'Hello, friend!' : "Hello, {$first_name}!";

        // Get the date range for the previous day
        $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_date = date('Y-m-d H:i:s'); // Update this line to use the current date and time

        // Query for new active memberships added yesterday until the moment the button is clicked
        $new_memberships_query = "SELECT COUNT(*) FROM {$wpdb->prefix}rcp_memberships WHERE status = 'active' AND created_date >= '$start_date' AND created_date <= '$end_date'";
        $new_memberships_yesterday = intval($wpdb->get_var($new_memberships_query));


        // Query for total monthly revenue from active monthly memberships
        $monthly_revenue_query = "SELECT SUM(m.recurring_amount) AS total_monthly_revenue FROM {$wpdb->prefix}rcp_memberships AS m JOIN {$wpdb->prefix}restrict_content_pro AS s ON m.object_id = s.id WHERE m.status = 'active' AND m.recurring_amount > 0 AND s.duration_unit = 'month'";
        $total_monthly_revenue = floatval($wpdb->get_var($monthly_revenue_query));

        // Query for total daily revenue from active daily memberships
        $daily_revenue_query = "SELECT SUM(m.recurring_amount) AS total_daily_revenue FROM {$wpdb->prefix}rcp_memberships AS m JOIN {$wpdb->prefix}restrict_content_pro AS s ON m.object_id = s.id WHERE m.status = 'active' AND m.recurring_amount > 0 AND s.duration_unit = 'day'";
        $total_daily_revenue = floatval($wpdb->get_var($daily_revenue_query));

        // Query for total annual revenue from active annual memberships
        $annual_revenue_query = "SELECT SUM(m.recurring_amount) AS total_annual_revenue FROM {$wpdb->prefix}rcp_memberships AS m JOIN {$wpdb->prefix}restrict_content_pro AS s ON m.object_id = s.id WHERE m.status = 'active' AND m.recurring_amount > 0 AND s.duration_unit = 'year'";
        $total_annual_revenue = floatval($wpdb->get_var($annual_revenue_query));
    
        // Query for total revenue generated in the current month
        $current_month_start_date = date('Y-m-01 00:00:00');
        $current_month_end_date = date('Y-m-d H:i:s');
        $current_month_revenue_query = "SELECT SUM(amount) AS current_month_revenue FROM {$wpdb->prefix}rcp_payments WHERE date >= '$current_month_start_date' AND date <= '$current_month_end_date' AND status IN ('complete', 'refunded')";
        $current_month_revenue = floatval($wpdb->get_var($current_month_revenue_query));

        // Query for total revenue generated during the same time in the previous year
        $previous_year_start_date = date('Y-m-d 00:00:00', strtotime('-1 year', strtotime($current_month_start_date)));
        $previous_year_end_date = date('Y-m-d H:i:s', strtotime('-1 year', strtotime($current_month_end_date)));
        $previous_year_revenue_query = "SELECT SUM(amount) AS previous_year_revenue FROM {$wpdb->prefix}rcp_payments WHERE date >= '$previous_year_start_date' AND date <= '$previous_year_end_date' AND status IN ('complete', 'refunded')";
        $previous_year_revenue = floatval($wpdb->get_var($previous_year_revenue_query));


      // Get the ChatGPT report with the new data
      $api_key = get_option('rcp_fai_reports_chatgpt_api_key');
      $chatgpt_response = rcp_fai_reports_get_chatgpt_report($api_key, $new_memberships_yesterday, $total_monthly_revenue, $total_daily_revenue, $total_annual_revenue, $first_name, $current_month_revenue, $previous_year_revenue);

      // Display the report content in the browser
    echo '<div id="report_content">';
    echo $chatgpt_response;
    echo '</div>';
  }

  // Display the button and the loading message
  echo '<form method="POST" id="friendly_report_form">';
  echo '<input type="submit" name="get_friendly_report" value="Get My Friendly Report" class="button-primary" />';
  echo '</form>';
  echo '<p id="loading_message" style="display: none;">Generating report...</p>';


  // Add JavaScript to handle the button click and display the loading message
  echo '<script>
  document.getElementById("friendly_report_form").addEventListener("submit", function() {
      document.getElementById("loading_message").style.display = "block";
  });
</script>';
}


// send the email automatically instead of using button
function send_daily_report()
{
    global $wpdb;

   

    if (isset($_POST['get_friendly_report'])) {

        // Get the first name of the logged-in user
        $current_user = wp_get_current_user();
        $first_name = $current_user->user_firstname;

        // Set a default greeting if the first name is empty
        $greeting = empty($first_name) ? 'Hello, friend!' : "Hello, {$first_name}!";

        // Get the date range for the previous day
        $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_date = date('Y-m-d H:i:s'); // Update this line to use the current date and time

        // Query for new active memberships added yesterday until the moment the button is clicked
        $new_memberships_query = "SELECT COUNT(*) FROM {$wpdb->prefix}rcp_memberships WHERE status = 'active' AND created_date >= '$start_date' AND created_date <= '$end_date'";
        $new_memberships_yesterday = intval($wpdb->get_var($new_memberships_query));


        // Query for total monthly revenue from active monthly memberships
        $monthly_revenue_query = "SELECT SUM(m.recurring_amount) AS total_monthly_revenue FROM {$wpdb->prefix}rcp_memberships AS m JOIN {$wpdb->prefix}restrict_content_pro AS s ON m.object_id = s.id WHERE m.status = 'active' AND m.recurring_amount > 0 AND s.duration_unit = 'month'";
        $total_monthly_revenue = floatval($wpdb->get_var($monthly_revenue_query));

        // Query for total daily revenue from active daily memberships
        $daily_revenue_query = "SELECT SUM(m.recurring_amount) AS total_daily_revenue FROM {$wpdb->prefix}rcp_memberships AS m JOIN {$wpdb->prefix}restrict_content_pro AS s ON m.object_id = s.id WHERE m.status = 'active' AND m.recurring_amount > 0 AND s.duration_unit = 'day'";
        $total_daily_revenue = floatval($wpdb->get_var($daily_revenue_query));

        // Query for total annual revenue from active annual memberships
        $annual_revenue_query = "SELECT SUM(m.recurring_amount) AS total_annual_revenue FROM {$wpdb->prefix}rcp_memberships AS m JOIN {$wpdb->prefix}restrict_content_pro AS s ON m.object_id = s.id WHERE m.status = 'active' AND m.recurring_amount > 0 AND s.duration_unit = 'year'";
        $total_annual_revenue = floatval($wpdb->get_var($annual_revenue_query));
    
        // Query for total revenue generated in the current month
        $current_month_start_date = date('Y-m-01 00:00:00');
        $current_month_end_date = date('Y-m-d H:i:s');
        $current_month_revenue_query = "SELECT SUM(amount) AS current_month_revenue FROM {$wpdb->prefix}rcp_payments WHERE date >= '$current_month_start_date' AND date <= '$current_month_end_date' AND status IN ('complete', 'refunded')";
        $current_month_revenue = floatval($wpdb->get_var($current_month_revenue_query));

        // Query for total revenue generated during the same time in the previous year
        $previous_year_start_date = date('Y-m-d 00:00:00', strtotime('-1 year', strtotime($current_month_start_date)));
        $previous_year_end_date = date('Y-m-d H:i:s', strtotime('-1 year', strtotime($current_month_end_date)));
        $previous_year_revenue_query = "SELECT SUM(amount) AS previous_year_revenue FROM {$wpdb->prefix}rcp_payments WHERE date >= '$previous_year_start_date' AND date <= '$previous_year_end_date' AND status IN ('complete', 'refunded')";
        $previous_year_revenue = floatval($wpdb->get_var($previous_year_revenue_query));


        // Get the ChatGPT report with the new data
    $api_key = get_option('rcp_fai_reports_chatgpt_api_key');
    $chatgpt_response = rcp_fai_reports_get_chatgpt_report($api_key, $new_memberships_yesterday, $total_monthly_revenue, $total_daily_revenue, $total_annual_revenue, $first_name, $current_month_revenue, $previous_year_revenue);

    // Send the report content via email
    $to = get_option('admin_email');
    $subject = 'Friendly Report';
    $headers = array('Content-Type: text/html; charset=UTF-8');

    if (wp_mail($to, $subject, $chatgpt_response, $headers)) {
        // Log success message (optional)
    } else {
        // Log failure message (optional)
    }
}
}

// Schedule the daily report event
function schedule_daily_report() {
    if (!wp_next_scheduled('send_daily_report_event')) {
        wp_schedule_event(strtotime('today 9am'), 'daily', 'send_daily_report_event');
    }
}
add_action('wp', 'schedule_daily_report');

// Hook the send_daily_report() function to the scheduled event
add_action('send_daily_report_event', 'send_daily_report');


// send multiple inputs to chatgpt and receive multiple outputs
function rcp_fai_reports_get_chatgpt_response($api_key, $input, $model = 'gpt-3.5-turbo')
{
    $client = new Client(
        [
        'base_uri' => 'https://api.openai.com/',
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        ]
    );

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
        $response = $client->post(
            'v1/chat/completions', [
            'json' => $params,
            ]
        );

        $response_data = json_decode($response->getBody(), true);
        return $response_data['choices'][0]['message']['content'];
    } catch (ClientException $e) {
        return 'Error: ' . $e->getMessage();
    }
}

function rcp_fai_reports_get_chatgpt_report($api_key, $new_memberships_yesterday, $total_monthly_revenue, $total_daily_revenue, $total_annual_revenue, $first_name, $current_month_revenue, $previous_year_revenue)
{

        // Get the friendly greeting
        $greeting_input = "give me a friendly greeting, using the first name of the person logged in. If no first name exists, use 'friend'";
        $greeting_output = rcp_fai_reports_get_chatgpt_response($api_key, $greeting_input);
       

        // Get the new memberships report
        $new_memberships_input = "In 3 or fewer sentences, write a report on the number of new memberships gained yesterday, if that number was {$new_memberships_yesterday}.";
        $new_memberships_output = rcp_fai_reports_get_chatgpt_response($api_key, $new_memberships_input);
       

        // Get the monthly revenue report
        $total_monthly_revenue = '$' . $total_monthly_revenue;
        $monthly_revenue_input = "In 3 or fewer sentences, write a report on the current monthly revenue from active monthly subscriptions, if that dollar amount was {$total_monthly_revenue}.";
        $monthly_revenue_output = rcp_fai_reports_get_chatgpt_response($api_key, $monthly_revenue_input);
        

        // Get the daily revenue report
        $total_daily_revenue = '$' . $total_daily_revenue;
        $daily_revenue_input = "In 3 or fewer sentences, write a report on the current daily revenue from active daily subscriptions, if that dollar amount was {$total_daily_revenue}.";
        $daily_revenue_output = rcp_fai_reports_get_chatgpt_response($api_key, $daily_revenue_input);
       

        // Get the annual revenue report
        $total_annual_revenue = '$' . $total_annual_revenue;
        $annual_revenue_input = "In 3 or fewer sentences, write a report on the current annual revenue from active annual subscriptions, if that dollar amount was {$total_annual_revenue}.";
        $annual_revenue_output = rcp_fai_reports_get_chatgpt_response($api_key, $annual_revenue_input);
      

        // Get the current month revenue comparison report
        $current_month_revenue_input = "So far this month, the website has generated \${$current_month_revenue} in revenue, and during the same period last year, it generated \${$previous_year_revenue}. Compare the revenue generated in these two periods in a friendly and human-like manner.";
        $current_month_revenue_output = rcp_fai_reports_get_chatgpt_response($api_key, $current_month_revenue_input);
       
        // return content as a string
        return '<p>' . $greeting_output . '</p>' .
        '<h3>Total Memberships</h3>' .
        '<p>' . $new_memberships_output . '</p>' .
        '<h3>Monthly Revenue</h3>' .
        '<p>' . $monthly_revenue_output . '</p>' .
        '<h3>Daily Revenue</h3>' .
        '<p>' . $daily_revenue_output . '</p>' .
        '<h3>Annual Revenue</h3>' .
        '<p>' . $annual_revenue_output . '</p>' .
        '<h3>Current Month Revenue Comparison</h3>' .
        '<p>' . $current_month_revenue_output . '</p>';
}
