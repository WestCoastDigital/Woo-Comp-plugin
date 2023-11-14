<?php

// Enqueue scripts and styles
function sb_enqueue_datepicker()
{
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
}
add_action('admin_enqueue_scripts', 'sb_enqueue_datepicker');

// Create options page
function sb_create_options_page()
{
    add_submenu_page(
        'woocommerce',
        __('Competition Settings', 'translate'),
        __('Competition Settings', 'translate'),
        'manage_options',
        'sb_comp_options',
        'sb_render_options_page'
    );
}
add_action('admin_menu', 'sb_create_options_page');

// Render options page
function sb_render_options_page()
{
    ?>
    <div class="wrap">
        <h2>Competition Settings</h2>
        <?php settings_errors(); ?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=sb_comp_options" class="nav-tab <?php echo empty($_GET['tab']) || $_GET['tab'] === 'sb_comp_settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=sb_comp_options&tab=sb_comp_emails" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'sb_comp_emails' ? 'nav-tab-active' : ''; ?>">Emails</a>
            <a href="?page=sb_comp_options&tab=sb_comp_terms" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'sb_comp_terms' ? 'nav-tab-active' : ''; ?>">T&Cs</a>
        </h2>

        <form method="post" action="options.php" class="sb-comp-form">
            <?php
            if (empty($_GET['tab']) || $_GET['tab'] === 'sb_comp_settings') {
                settings_fields('sb_comp_options_group');
                do_settings_fields('sb_comp_options_page', 'sb_comp_section');
            } elseif ($_GET['tab'] === 'sb_comp_emails') {
                settings_fields('sb_comp_emails_options_group');
                do_settings_fields('sb_comp_emails_options_page', 'sb_comp_emails_section');
            } elseif ($_GET['tab'] === 'sb_comp_terms') {
                settings_fields('sb_comp_terms_options_group');
                do_settings_fields('sb_comp_terms_options_page', 'sb_comp_terms_section');
            }

            submit_button();
            ?>
        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {
            function initializeDatepicker(element) {
                $(element).datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }

            // Datepicker initialization for existing fields
            initializeDatepicker('.datepicker');

            $('#add-repeatable-field').on('click', function(e) {
                e.preventDefault();
                var index = $('.repeatable-fields-list tr').length - 1;
                var newField = '<tr>';

                // Repeatable fields
                newField += '<td>';
                newField += '<input type="number" name="sb_comp_option[' + index + '][dollar]" placeholder="" />';
                newField += '</td>';
                newField += '<td>';
                newField += '<input type="number" name="sb_comp_option[' + index + '][qty]" placeholder="" />';
                newField += '</td>';
                newField += '<td>';
                newField += ' <button class="remove-field button">Remove</button>';
                newField += '</td>';
                newField += '</tr>';

                // Remove any existing "No Fields" message
                $('.repeatable-fields-list .no-fields-message').remove();

                // Append the new field
                $('.repeatable-fields-list').append(newField);

                // Show the remove button
                $('.repeatable-fields-list .remove-field').show();

                // Re-initialize datepicker for the new row
                initializeDatepicker('.repeatable-fields-list tr:last-child .datepicker');
            });

            // Event delegation for dynamically added remove buttons
            $('.repeatable-fields-list').on('click', '.remove-field', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();

                // If there are no fields left, display a "No Fields" message
                if ($('.repeatable-fields-list tr').length === 1) {
                    $('.repeatable-fields-list').html('<tr class="no-fields-message"><td colspan="3">No fields added yet.</td></tr>');
                }
            });
        });
    </script>
    <?php
}


// Setup settings fields options
function sb_setup_settings_options()
{
    add_settings_section(
        'sb_comp_section',
        'Competition Settings',
        'sb_render_settings_description',
        'sb_comp_options_page'
    );

    add_settings_field(
        'sb_comp_dates',
        '<h3>Competition Dates</h3>',
        'sb_render_start_and_end_date_fields',
        'sb_comp_options_page',
        'sb_comp_section'
    );

    add_settings_field(
        'sb_comp',
        '<h3>Competition Values</h3>',
        'sb_render_comp',
        'sb_comp_options_page',
        'sb_comp_section'
    );

    add_settings_field(
        'sb_comp_prefix',
        '<h3>Ticket Start Number</h3>',
        'sb_render_prefix_field',
        'sb_comp_options_page',
        'sb_comp_section'
    );

    add_settings_field(
        'sb_comp_roles',
        '<h3>Exclude Roles</h3>',
        'sb_render_select_field',
        'sb_comp_options_page',
        'sb_comp_section'
    );

    add_settings_field(
        'sb_comp_winner',
        '<h3>Lock Winner</h3>',
        'sb_render_checkbox_field',
        'sb_comp_options_page',
        'sb_comp_section'
    );

    register_setting('sb_comp_options_group', 'sb_comp_option');
    register_setting('sb_comp_options_group', 'sb_start_date_option');
    register_setting('sb_comp_options_group', 'sb_end_date_option');
    register_setting('sb_comp_options_group', 'sb_comp_prefix');
    register_setting('sb_comp_options_group', 'sb_comp_roles');
    register_setting('sb_comp_options_group', 'sb_comp_winner');
}

function sb_render_start_and_end_date_fields()
{
    $start_date = get_option('sb_start_date_option');
    $end_date = get_option('sb_end_date_option');

    echo '<table class="custom-date-wrapper">';

    echo '<tr>';
    echo '<th>Start Date</th>';
    echo '<th>End Date</th>';
    echo '</tr>';

    echo '<tr>';

    echo '<td>';
    echo '<div class="sb-date">';
    echo '<input type="text" class="datepicker" name="sb_start_date_option" value="' . esc_attr($start_date) . '" placeholder="Start Date" />';
    echo '<span class="icon">' . sb_render_date_icon() . '</span>';
    echo '</div>';
    echo '</td>';

    echo '<td>';
    echo '<div class="sb-date">';
    echo '<input type="text" class="datepicker" name="sb_end_date_option" value="' . esc_attr($end_date) . '" placeholder="End Date" />';
    echo '<span class="icon">' . sb_render_date_icon() . '</span>';
    echo '</div>';
    echo '</td>';

    echo '</tr>';

    echo '</table>';
}


function sb_render_prefix_field()
{
    $value = get_option('sb_comp_prefix');
    echo '<p class="description">Automatically generates a random number between 1000 and 9999.<br>This allows you to prefix with your own number.</p>';
    echo '<input type="number" name="sb_comp_prefix" value="' . esc_attr($value) . '" placeholder="" />';
}

function sb_render_checkbox_field()
{
    $value = get_option('sb_comp_winner');
    echo '<p class="description">Locks the winner so that it cannot be changed until it is reset.</p>';
    echo '<label class="toggle">';
    echo '<input type="checkbox" name="sb_comp_winner" value="1" ' . checked(1, $value, false) . ' />';
    echo '<span class="slider"></span>';
    echo '</label>';
}

function sb_render_select_field()
{
    $selected_roles = get_option('sb_comp_roles', array());
    echo '<p class="description">Choose user roles that cannot enter the competition.</p>';
?>
    <select name="sb_comp_roles[]" multiple>
        <?php
        // get roles
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
        }
        $roles = get_editable_roles();
        foreach ($roles as $key => $role) {
            $selected = in_array($key, $selected_roles) ? 'selected="selected"' : '';
            ?>
            <option value="<?= $key ?>" <?php echo $selected; ?>>
                <?= $role['name'] ?>
            </option>
        <?php } ?>
    </select>
<?php
}

function sb_render_settings_description()
{
    // echo '<p>Configure your repeatable fields here.</p>';
}

function sb_render_comp()
{
    $sb_comp = get_option('sb_comp_option');
?>
    <div class="repeatable-fields-wrapper">
        <table class="repeatable-fields-list">
            <tr>
                <th>Spend Level</th>
                <th>Tickets</th>
            </tr>
            <?php
            if ($sb_comp) {
                foreach ($sb_comp as $index => $field_group) {
                    sb_render_repeatable_field($index, $field_group);
                }
            } else {
                sb_render_repeatable_field(0, array('dollar' => '', 'qty' => ''));
            }
            ?>
        </table>
        <p><button class="button" id="add-repeatable-field">Add Entry Value</button></p>
    </div>

<?php
}

function sb_render_repeatable_field($index, $field_group)
{
    echo '<tr>';

    echo '<td>';
    echo '<input type="number" name="sb_comp_option[' . $index . '][dollar]" id="sb_comp_option_' . $index . '_dollar" value="' . esc_attr($field_group['dollar']) . '" placeholder="" />';
    echo '</td>';

    echo '<td>';
    echo '<input type="number" name="sb_comp_option[' . $index . '][qty]" id="sb_comp_option_' . $index . '_qty" value="' . esc_attr($field_group['qty']) . '" placeholder="" />';
    echo '</td>';

    echo '<td>';
    echo ' <button class="remove-field button">Remove</button>';
    echo '</td>';
    echo '</tr>';
}

// Extra Fields
function setup_sb_comp_emails_options()
{
    add_settings_section(
        'sb_comp_emails_section',
        'Extra Fields Section',
        'sb_render_comp_emails_section',
        'sb_comp_emails_options_page'
    );

    add_settings_field(
        'sb_email_header',
        '<h3>Email Subject</h3>',
        'sb_render_email_fields',
        'sb_comp_emails_options_page',
        'sb_comp_emails_section'
    );

    register_setting('sb_comp_emails_options_group', 'sb_email_header_option');
    register_setting('sb_comp_emails_options_group', 'sb_email_body_option'); // Added for the WYSIWYG editor
}


function sb_render_comp_emails_section()
{
    // echo '<p>Configure your extra fields here.</p>';
}

function sb_render_email_fields()
{
    $sb_email_header = get_option('sb_email_header_option');
?>
    <div class="extra-field-wrapper">
        <input type="text" size="200" name="sb_email_header_option" id="sb_email_header_option" value="<?php echo esc_attr(isset($sb_email_header) ? $sb_email_header : 'Your ' . get_bloginfo('name') . ' Competition Ticket Numbers'); ?>" placeholder="Your  <?= get_bloginfo('name') ?> Competition Ticket Numbers" />

        <h3>Email Body</h3>
        <p class="description">Use shortcode <code>[ticket_numbers]</code> to show the table of ticket numbers and <code>[ticket_qty]</code> to show the number of tickets</p>
        <?php
        $extra_content = get_option('sb_email_body_option');
        $editor_id = 'sb_email_body_option';

        wp_editor($extra_content, $editor_id, array(
            'textarea_name' => 'sb_email_body_option',
        ));
        ?>
    </div>
<?php
}


add_action('admin_init', 'sb_setup_settings_options');
add_action('admin_init', 'setup_sb_comp_emails_options');

// Add this within your theme's functions.php file or create a custom plugin

function setup_sb_comp_terms_options()
{
    add_settings_section(
        'sb_comp_terms_section',
        'Additional Tab Section',
        'sb_render_comp_terms_section',
        'sb_comp_terms_options_page'
    );

    add_settings_field(
        'sb_comp_terms_option',
        '<h3>Terms & Conditions</h3>',
        'sb_render_terms_field',
        'sb_comp_terms_options_page',
        'sb_comp_terms_section'
    );

    register_setting('sb_comp_terms_options_group', 'sb_comp_terms_option_option');
}

function sb_render_comp_terms_section()
{
    // echo '<p>Configure your additional tab fields here.</p>';
}

function sb_render_terms_field()
{
    $sb_comp_terms_option = get_option('sb_comp_terms_option_option');
    $editor_id = 'sb_comp_terms_option_option';

    echo '<p class="description">To display on a page use shortcode <code>[sb-comp-terms]</code></p>';

    wp_editor($sb_comp_terms_option, $editor_id, array(
        'textarea_name' => 'sb_comp_terms_option_option',
    ));
}

add_action('admin_init', 'setup_sb_comp_terms_options');

function sb_render_date_icon()
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M20 20h-4v-4h4v4zm-6-10h-4v4h4v-4zm6 0h-4v4h4v-4zm-12 6h-4v4h4v-4zm6 0h-4v4h4v-4zm-6-6h-4v4h4v-4zm16-8v22h-24v-22h3v1c0 1.103.897 2 2 2s2-.897 2-2v-1h10v1c0 1.103.897 2 2 2s2-.897 2-2v-1h3zm-2 6h-20v14h20v-14zm-2-7c0-.552-.447-1-1-1s-1 .448-1 1v2c0 .552.447 1 1 1s1-.448 1-1v-2zm-14 2c0 .552-.447 1-1 1s-1-.448-1-1v-2c0-.552.447-1 1-1s1 .448 1 1v2z"/></svg>';
    return $svg;
}

function sb_render_ticket_icon()
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" vstyle="enable-background:new 0 0 122.88 122.88" viewBox="0 0 122.88 122.88"><path d="m1.48 78.38 76.9-76.9C79.36.49 80.65 0 81.95 0c1.29 0 2.59.49 3.57 1.48l10.17 10.17c.55.55.61 1.4.17 2.01a9.765 9.765 0 0 0-1.3 6.16 9.704 9.704 0 0 0 2.78 5.72 9.617 9.617 0 0 0 5.72 2.78c2.14.26 4.35-.2 6.24-1.36.63-.38 1.42-.27 1.92.23l10.17 10.17c.98.98 1.48 2.28 1.48 3.57s-.49 2.59-1.48 3.57l-76.9 76.9c-.98.98-2.28 1.48-3.57 1.48a5.01 5.01 0 0 1-3.57-1.48l-10.1-10.1a1.57 1.57 0 0 1-.15-2.05 9.778 9.778 0 0 0 1.51-6.28 9.651 9.651 0 0 0-2.81-5.89 9.706 9.706 0 0 0-5.89-2.81c-2.2-.22-4.45.3-6.36 1.56-.63.42-1.46.32-1.97-.2L1.48 85.52A5.027 5.027 0 0 1 0 81.95c0-1.3.49-2.59 1.48-3.57zM80.6 3.7 3.7 80.6c-.37.37-.55.86-.55 1.35 0 .49.18.98.55 1.35l9.25 9.25c2.26-1.18 4.8-1.65 7.28-1.4 2.85.29 5.63 1.52 7.8 3.7 2.18 2.18 3.41 4.95 3.7 7.8.25 2.48-.21 5.02-1.4 7.28l9.25 9.25c.37.37.86.55 1.35.55.49 0 .98-.18 1.35-.55l76.9-76.9c.37-.37.55-.86.55-1.35 0-.49-.18-.98-.55-1.35l-9.34-9.34a12.858 12.858 0 0 1-7.15 1.2 12.874 12.874 0 0 1-11.26-11.26c-.29-2.42.11-4.91 1.2-7.15L83.3 3.7c-.37-.37-.86-.55-1.35-.55-.49-.01-.98.18-1.35.55zM25.26 73.45l38.37-38.37a5.625 5.625 0 0 1 3.98-1.64c1.45 0 2.89.55 3.98 1.64l17.64 17.64a5.6 5.6 0 0 1 1.64 3.98c0 1.44-.55 2.89-1.64 3.98L50.86 99.05a5.6 5.6 0 0 1-3.98 1.64c-1.44 0-2.88-.55-3.98-1.64L25.26 81.42c-1.1-1.1-1.64-2.54-1.64-3.98s.55-2.89 1.64-3.99zm40.6-36.15L27.48 75.67c-.48.48-.72 1.12-.72 1.76 0 .64.24 1.28.72 1.76l17.64 17.64c.48.48 1.12.72 1.76.72.64 0 1.28-.24 1.76-.72l38.37-38.37c.48-.48.72-1.12.72-1.76 0-.64-.24-1.28-.72-1.76L69.38 37.3c-.49-.49-1.12-.73-1.76-.73-.64 0-1.28.25-1.76.73z"/></svg>';
    return $svg;
}

function sb_render_css()
{
?>
    <style>
        .sb-comp-form {
            padding: 20px;
        }

        .sb-comp-form h3 {
            padding-top: 3rem;
        }

        .sb-comp-form h3:first-of-type {
            padding-top: 0;
        }

        .sb-date {
            display: flex;
            align-items: center;
        }

        .sb-date input {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            margin-right: 0;
        }

        .sb-date .icon {
            display: flex;
            background: #dcdcde;
            border: 1px solid #8c8f94;
            border-left: none;
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
            height: 28px;
            width: 30px;
            display: grid;
            place-items: center;
        }

        .sb-date .icon svg {
            fill: #50575e;
            width: 12px;
            height: 12px;
        }
        .toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 28px;
        }
        .toggle input {
            display: none;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #dcdcde;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked+.slider {
            background-color: #2271b1;
        }
        input:checked+.slider:before {
            transform: translateX(32px);
        }
    </style>
<?php
}
add_action('admin_head', 'sb_render_css');
?>