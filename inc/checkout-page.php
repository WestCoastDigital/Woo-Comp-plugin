<?php
// Hook into the checkout process to generate ticket allocations
function sb_competition_generate_tickets_on_checkout($order_id)
{
    // Get the excluded user roles from your options page settings
    $excl_roles = get_option('sb_comp_roles');

    // get the logged in user
    $user = wp_get_current_user();

    // get the user roles
    $user_roles = $user->roles;
    // check if the user has any of the excluded roles
    $has_excl_role = false;
    foreach ($user_roles as $role) {
        if (in_array($role, $excl_roles)) {
            $has_excl_role = true;
        }
    }
    // if the user has any of the excluded roles, exit without generating tickets
    if ($has_excl_role) {
        return;
    }

    // Get the order object
    $order = wc_get_order($order_id);

    // Check if there is already a ticket allocation for this order
    $ticket_numbers = get_post_meta($order_id, '_sb_competition_tickets', true);
    if ($ticket_numbers) {
        return; // The ticket allocation already exists, exit without generating tickets.
    }

    // Get the order total
    $order_total = $order->get_total();

    // Get the competition start and end dates from your options page settings
    // default to 7 days before today and yesterday so it is expired
    $start_date = get_option('sb_start_date_option') ?? '';
    if ($start_date == '') {
        // 7 days before today
        $start_date = date('Y-m-d', strtotime('-7 days'));
    }
    $end_date = get_option('sb_end_date_option') ?? '';
    if ($end_date == '') {
        // yesterday
        $end_date = date('Y-m-d', strtotime('-1 days'));
    }

    // Check if the current date is within the competition date range
    $current_date = current_time('mysql');
    if ($current_date < $start_date || $current_date > $end_date) {
        return; // The competition is not active, exit without generating tickets.
    }

    // Get the spend thresholds from your options page settings
    $spend_thresholds = get_option('sb_comp_option') ?? '';

    // Initialize the ticket count
    $ticket_count = 0;

    // Calculate the ticket count based on the order total and spend thresholds
    foreach ($spend_thresholds as $tickets) {
        $threshold = intval($tickets['dollar']);
        if ($order_total >= $threshold) {
            $entries = $tickets['qty'];
            // convert entries into integer
            $ticket_count = intval($entries);
        }
    }

    // If the ticket count is 0, exit without generating tickets
    if ($ticket_count == 0) {
        return;
    }

    // Get the ticket prefix from your options page settings
    $ticket_prefix = get_option('sb_comp_prefix') ?? '';

    // Generate and save random ticket numbers
    $ticket_numbers = array();
    for ($i = 0; $i < $ticket_count; $i++) {
        // Generate a random ticket number (you can customize this as needed)
        $random_ticket_number = mt_rand(1000, 9999); // Example: Generate a random 4-digit number
        // Check the random numbers dont exist in database
        $args = array(
            'post_type' => 'shop_order',
            'meta_query' => array(
                array(
                    'key' => '_sb_competition_tickets',
                    'value' => $ticket_prefix . $random_ticket_number,
                    'compare' => 'LIKE'
                )
            )
        );
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            // if the ticket number exists, generate a new one
            $random_ticket_number = mt_rand(1000, 9999);
        }
        // Add the prefixed ticket number to the array
        $ticket_numbers[] = $ticket_prefix . $random_ticket_number;
    }

    // Save the generated ticket numbers in the order's custom data for reference
    update_post_meta($order_id, '_sb_competition_tickets', $ticket_numbers);

    // Send the ticket numbers to the customer via email
    sb_competition_send_ticket_numbers_email($order_id, $ticket_numbers);
}

// Hook into the checkout process
add_action('woocommerce_checkout_order_processed', 'sb_competition_generate_tickets_on_checkout', 20);
add_action('woocommerce_thankyou', 'sb_competition_generate_tickets_on_checkout', 10, 1);

// Function to send a custom email with ticket numbers
function sb_competition_send_ticket_numbers_email($order_id, $ticket_numbers)
{
    // Get the order object
    $order = wc_get_order($order_id);

    // Recipient email address
    $recipient_email = $order->get_billing_email();

    // Email subject
    $subject = get_option('sb_comp_email_subject') ?? '';

    // Email body
    $email_body = get_option('sb_email_body_option') ?? '';

    // make sure no blank body or subject
    if ($subject == '') {
        $subject = 'Your ' . get_bloginfo('name') . ' Competition Ticket Numbers';
    }
    if ($email_body == '') {
        $email_body = 'Your ticket numbers are: [ticket_numbers]';
    }

    // generate ticket number table
    $ticket_table = '<table style="width: 100%;">';
    foreach ($ticket_numbers as $ticket_number) {
        $ticket_table .= '<tr><td style="width: 50%;">Ticket Number: </td><td style="width: 50%;">' . $ticket_number . '</td></tr>';
    }
    $ticket_table .= '</table>';

    // generate the ticket qty
    $ticket_qty = count($ticket_numbers);

    // replace the placeholders in the email body
    $email_body = str_replace('[ticket_numbers]', $ticket_table, $email_body);
    $email_body = str_replace('[ticket_qty]', $ticket_qty, $email_body);

    // Email content
    $message = $email_body;

    // Additional email headers
    $headers = 'Content-Type: text/html; charset=utf-8';

    // Send the email
    wp_mail($recipient_email, $subject, $message, $headers);
}

// Function to add the ticket qty to the order details page
function sb_checkout_page_ticket_announcement()
{
    // get the user role
    $user = wp_get_current_user();
    $user_roles = $user->roles;
    // get the excluded user roles from your options page settings
    $excl_roles = get_option('sb_comp_roles');
    // check if the user has any of the excluded roles
    $has_excl_role = false;
    foreach ($user_roles as $role) {
        if (in_array($role, $excl_roles)) {
            $has_excl_role = true;
        }
    }

    // if the user has any of the excluded roles, exit without generating tickets
    if ($has_excl_role) {
        return;
    }

    // Get the competition start and end dates from your options page settings
    // default to 7 days before today and yesterday so it is expired
    $start_date = get_option('sb_start_date_option') ?? '';
    if ($start_date == '') {
        // 7 days before today
        $start_date = date('Y-m-d', strtotime('-7 days'));
    }
    $end_date = get_option('sb_end_date_option') ?? '';
    if ($end_date == 'R') {
        // yesterday
        $end_date = date('Y-m-d', strtotime('-1 days'));
    }
    $start_date = strtotime($start_date);
    $end_date = strtotime($end_date);

    // Check if the current date is within the competition date range
    $current_date = current_time('mysql');

    $current_date = strtotime($current_date);
    if ($current_date < $start_date && $current_date > $end_date) {
        return; // The competition is not active, exit without generating tickets.
    }
    // get the value of the order
    $order_total = WC()->cart->total;
    // get the spend thresholds from your options page settings
    $spend_thresholds = get_option('sb_comp_option') ?? '';
    // Initialize the ticket count
    $ticket_count = 0;
    // Calculate the ticket count based on the order total and spend thresholds
    foreach ($spend_thresholds as $tickets) {
        $threshold = intval($tickets['dollar']);
        if ($order_total >= $threshold) {
            $ticket_count = intval($tickets['qty']);
        }
    }
    // If the ticket count is 0, exit without generating tickets
    if ($ticket_count == 0) {
        return;
    }
    // get the plural of entry
    $plural = $ticket_count > 1 ? 'entries' : 'entry';
    $comp_name = get_option('sb_comp_name') ?? '';
    if($comp_name == '') {
        $comp_name = get_bloginfo('name') . ' Competition';
    }
    // display the ticket qty
    $content = 'Congratulations - your order entitles you to ' . $ticket_count . ' ' . $plural . ' in the ' . $comp_name . '! Please check your inbox for confirmation after you complete your order.';
    echo '<p class="sb-comp-entries">' . esc_html($content) . '</p>';
}
add_action('woocommerce_review_order_before_submit', 'sb_checkout_page_ticket_announcement');
