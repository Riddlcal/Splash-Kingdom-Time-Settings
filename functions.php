add_action('init', 'pages_tax');
function pages_tax() {
    register_taxonomy('things', 'page', array(
        'label' => __('Things'),
        'rewrite' => array('slug' => 'things'),
        'hierarchical' => true,
    ));
}

add_action('admin_menu', 'sk_add_settings_page');
function sk_add_settings_page() {
    add_menu_page('Splash Kingdom Time Settings', 'Splash Kingdom Time Settings', 'manage_options', 'splash_kingdom_settings', 'sk_render_settings_page', 'dashicons-clock');
    add_action('admin_footer', 'sk_enqueue_js_data');
}

wp_enqueue_script('sk-timer-script', get_stylesheet_directory_uri() . '/sk-timer-2.js', array(), time(), false);

function sk_enqueue_timer_script() {
    $script_url = '/wp-content/themes/divi-child-theme/sk-timer-2.js';
    $deps = array();
    $ver = time();
    $in_footer = false;
    wp_localize_script('sk-timer-script', 'skData', array('nonce' => wp_create_nonce('wp_rest')));
    wp_enqueue_script('sk-timer-script', $script_url, $deps, $ver, $in_footer);
}
add_action('wp_enqueue_scripts', 'sk_enqueue_timer_script');

function sk_enqueue_admin_scripts($hook_suffix) {
    if ($hook_suffix == 'toplevel_page_splash_kingdom_settings') {
        wp_enqueue_style('jquery-timepicker-css', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.14.1/jquery.timepicker.min.css');
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-timepicker-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.14.1/jquery.timepicker.min.js', array('jquery'), null, true);

        // Initialize the time picker
        wp_add_inline_script('jquery-timepicker-js', '
            jQuery(document).ready(function($) {
				$("input.timepicker").timepicker({
					timeFormat: "g:i A",
					step: 60,
					dynamic: false,
					dropdown: true,
					scrollbar: true,
					noneOption: [
					{
						label: "Closed",
						value: "null",
						className: "ui-timepicker-none"
						}
					]
				});
			});
        ');
    }
}
add_action('admin_enqueue_scripts', 'sk_enqueue_admin_scripts');

function sk_enqueue_js_data() {
    ?>
    <?php
}
add_action('wp_footer', 'sk_enqueue_js_data');

function sk_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Splash Kingdom Time Settings</h1>
        <form id="splash-kingdom-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('splash_kingdom_settings_nonce', 'splash_kingdom_settings_nonce'); ?>
            <?php settings_fields('splash_kingdom_options'); ?>
            <?php do_settings_sections('splash_kingdom_settings'); ?>
            <input type="hidden" name="action" value="save_splash_kingdom_settings">
            <?php submit_button(); ?>
            <input type="hidden" name="redirect_to" value="<?php echo admin_url('admin.php?page=splash_kingdom_settings'); ?>">
        </form>
    </div>
    <script>
        function submitForm() {
            let e = document.getElementById("splash-kingdom-form"),
                o = new FormData(e);
            fetch("/submit-form.php", {
                method: "POST",
                headers: {
                    "X-WP-Nonce": skData.nonce
                },
                body: o
            }).then(e => e.json()).then(e => {
                e && e.success ? window.location.replace('<?php echo admin_url('admin.php?page=splash_kingdom_settings'); ?>') : console.error("Failed to save form data:", e.message)
            }).catch(e => {
                console.error("Error submitting form:", e)
            })
        }
        document.getElementById("splash-kingdom-form").addEventListener("submit", submitForm);
    </script>
	<style>
		.ui-timepicker-wrapper li {
			font-size: 15px;
		}
		.ui-timepicker-wrapper {
   			 width: 180px;
		}
	</style>
    <?php
}
add_action('admin_init', 'sk_register_settings');

function save_splash_kingdom_settings() {
    $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : admin_url('admin.php?page=splash_kingdom_settings');
    wp_safe_redirect($redirect_to);
    exit;
}

function sk_register_settings() {
    register_setting('splash_kingdom_options', 'splash_kingdom_opening_times', 'sk_sanitize_opening_times');
    $parks = array('Paradise Island', 'Air Patrol', 'Wild West', 'Timber Falls');
    foreach ($parks as $park) {
        add_settings_section('sk_' . $park . '_section', ucfirst(str_replace('park', '', $park)) . ' Opening and Closing Times', function () use ($park) {
            sk_park_section_callback($park);
        }, 'splash_kingdom_settings');
    }
}
add_action('admin_post_save_splash_kingdom_settings', 'sk_save_opening_times');

function sk_save_opening_times() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    if (isset($_POST['splash_kingdom_opening_times'])) {
        $opening_times = sk_sanitize_opening_times($_POST['splash_kingdom_opening_times']);
        update_option('splash_kingdom_opening_times', $opening_times);
    }
    $response = array('message' => 'Form data saved successfully.');
    wp_send_json_success($response);
}

function sk_sanitize_opening_times($input) {
    $sanitized_times = array();
    foreach ($input as $park => $days) {
        $sanitized_park = array();
        foreach ($days as $day => $times) {
            $start_time = isset($times['start']) ? sanitize_text_field($times['start']) : null;
            $end_time = isset($times['end']) ? sanitize_text_field($times['end']) : null;
            $sanitized_park[$day]['start'] = $start_time;
            $sanitized_park[$day]['end'] = $end_time;
        }
        $sanitized_times[$park] = $sanitized_park;
    }
    return $sanitized_times;
}

$formatted_opening_times = array();
foreach ($saved_opening_times as $parkName => $parkData) {
    $formatted_opening_times[$parkName] = array();
    foreach ($parkData as $dayIndex => $dayData) {
        $startTime = date('H:i', strtotime($dayData['start']));
        $endTime = date('H:i', strtotime($dayData['end']));
        $formatted_opening_times[$parkName][$dayIndex] = array(
            's' => array('h' => intval(date('H', strtotime($startTime))), 'm' => intval(date('i', strtotime($startTime)))),
            'e' => array('h' => intval(date('H', strtotime($endTime))), 'm' => intval(date('i', strtotime($endTime))))
        );
    }
}
$formatted_opening_times_json = json_encode($formatted_opening_times);
wp_localize_script('sk-timer-script', 'savedOpeningTimes', $formatted_opening_times_json);

function sk_get_park_opening_times($request) {
    $park_name = $request['parkName'];
    $park_opening_times = get_option('splash_kingdom_opening_times', array());
    if (isset($park_opening_times[$park_name])) {
        $formatted_opening_times = array();
        foreach ($park_opening_times[$park_name] as $day => $times) {
            $formatted_opening_times[$day] = array('s' => $times['start'], 'e' => $times['end'],);
        }
        return rest_ensure_response($formatted_opening_times);
    } else {
        return rest_ensure_response(array('message' => 'Opening times for the specified park not available.'));
}
}

function get_day_name($day_index) {
    $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
    return $days[$day_index];
}

function sk_park_section_callback($park_name) {
    $opening_times = get_option('splash_kingdom_opening_times');
    ?>
    <table class="form-table">
        <?php for ($i = 0; $i < 7; $i++): ?>
            <tr>
                <th scope="row"><?php echo ucfirst(get_day_name($i)); ?></th>
                <td>
                    <?php
                    $start_time = $opening_times[$park_name][$i]['start'] ?? '';
                    $end_time = $opening_times[$park_name][$i]['end'] ?? '';
                    ?>
                    <input type="text" name="splash_kingdom_opening_times[<?php echo $park_name; ?>][<?php echo $i; ?>][start]" value="<?php echo $start_time; ?>" class="timepicker" placeholder="HH:MM AM/PM"/> to <input type="text" name="splash_kingdom_opening_times[<?php echo $park_name; ?>][<?php echo $i; ?>][end]" value="<?php echo $end_time; ?>" class="timepicker" placeholder="HH:MM AM/PM"/>
                </td>
            </tr>
        <?php endfor; ?>
    </table>
    <?php
}

add_action('rest_api_init', function () {
    register_rest_route('splash-kingdom/v1', '/submit-form', array(
        'methods' => 'POST',
        'callback' => 'sk_handle_ajax_request',
        'permission_callback' => '__return_true',
    ));
});

function sk_handle_ajax_request($request) {
    if (isset($request['nonce']) && wp_verify_nonce($request['nonce'], 'wp_rest')) {
        $form_data = $request->get_params();
        update_option('splash_kingdom_opening_times', $form_data['opening_times']);
        $response = array('message' => 'Form data saved successfully.', 'splash_kingdom_opening_times' => $form_data['opening_times']);
        return rest_ensure_response($response);
    } else {
        $response = array('message' => 'Invalid nonce.');
        return rest_ensure_response($response);
    }
}

add_action('rest_api_init', 'sk_register_rest_api_endpoint');

function sk_register_rest_api_endpoint() {
    register_rest_route('splash-kingdom/v1', '/opening-times', array(
        'methods' => 'GET',
        'callback' => 'sk_get_opening_times',
        'permission_callback' => '__return_true',
    ));
}

function sk_get_opening_times() {
    $opening_times = get_option('splash_kingdom_opening_times', array());
    $response = array('message' => 'Success', 'splash_kingdom_opening_times' => $opening_times,);
    return rest_ensure_response($response);
}