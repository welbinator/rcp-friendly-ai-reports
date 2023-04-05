<?php

global $wpdb;

$table_prefix = $wpdb->prefix;
$memberships_table = $table_prefix . 'rcp_memberships';

$query = $wpdb->prepare("SELECT COUNT(*) AS new_memberships
                         FROM {$memberships_table}
                         WHERE created_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                         AND status = 'active';");

$result = $wpdb->get_var($query);
$number_of_new_memberships_7_days = intval($result);

echo "Number of new memberships in the last 7 days: " . $number_of_new_memberships_7_days;
