<?php
/*
Plugin Name: SB Competition Plugin
Plugin URI: https://example.com/
Description: A simple plugin to offer entries into a competition based on how much the customer spends on your WooCommerce Store.
Version: 1.0.0
Author: Your Name
Author URI: https://example.com/
License: GPL2
*/

// Check woocommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // add banner to admin
    add_action('admin_notices', 'sb_competition_plugin_admin_notice');
    return;
} else {
    // loop through the inc folder and if is php file, include it
    foreach (glob(plugin_dir_path(__FILE__) . "inc/*.php") as $file) {
        include_once $file;
    }
}

function sb_competition_plugin_admin_notice()
{
?>
    <div class="notice notice-error is-dismissible">
        <p><?= __('SB Competition Plugin requires WooCommerce to be installed and active', 'translate') ?>.</p>
    </div>
<?php
}
