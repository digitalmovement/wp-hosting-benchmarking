<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://fastestwordpress.com
 * @since      1.0.0
 *
 * @package    Wp_Hosting_Benchmarking
 * @subpackage Wp_Hosting_Benchmarking/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Hosting_Benchmarking
 * @subpackage Wp_Hosting_Benchmarking/admin
 * @author     Fastest WordPress <help@fastestwordpress.com>
 */
class Wp_Hosting_Benchmarking_Admin {

	private $plugin_name;
    private $version;
    private $db;
    private $api;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->init_components();
      // Hook into 'admin_enqueue_scripts' to enqueue scripts/styles
      add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
      add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
      add_action('admin_init', array($this, 'register_settings'));
     }

	private function init_components() {
        $this->db = new Wp_Hosting_Benchmarking_DB();
        $this->api = new Wp_Hosting_Benchmarking_API();
    }

   /**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-hosting-benchmarking-admin.css', array(), $this->version, 'all' );
    }

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
    public function enqueue_scripts() {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
        wp_enqueue_script('luxon', 'https://cdn.jsdelivr.net/npm/luxon/build/global/luxon.min.js', array(), null, true);
        wp_enqueue_script('chartjs-adapter-luxon', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon', array('chart-js'), null, true);
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-hosting-benchmarking-admin.js', array('jquery'), $this->version, false);

        // Localize script after enqueuing
        wp_localize_script($this->plugin_name, 'wpHostingBenchmarking', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_hosting_benchmarking_nonce'), // Create nonce properly within this hook
            'selected_region' => get_option('wp_hosting_benchmarking_selected_region') // Pass the selected region            
        ));
    }
  /**
     * Add admin menu item for WP Benchmarking
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            'WP Benchmarking',
            'WP Benchmarking',
            'manage_options',
            'wp-hosting-benchmarking',
            array( $this, 'display_plugin_admin_page' ),
            'dashicons-performance',
            999 // Set a high number to ensure it's the last menu item
        );

        // Add a submenu for Settings
        add_submenu_page(
            'wp-hosting-benchmarking',    // Parent slug
            'Settings',                   // Page title
            'Settings',                   // Menu title
            'manage_options',             // Capability
            'wp-hosting-benchmarking-settings',  // Menu slug
            array($this, 'display_settings_page')  // Callback function
        );
    }

    /**
     * Display admin page content
     */

	public function display_plugin_admin_page() {
        include_once 'partials/wp-hosting-benchmarking-admin-display.php';
    }

    public function display_settings_page() {
        include_once 'partials/wp-hosting-benchmarking-settings-display.php';
    }

    

	public function start_latency_test() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');

        if (!wp_next_scheduled('wp_hosting_benchmarking_cron_hook')) {
            $start_time = time();
            update_option('wp_hosting_benchmarking_start_time', $start_time);
            wp_schedule_event($start_time, 'five_minutes', 'wp_hosting_benchmarking_cron_hook');

            // Run the first test immediately
            $endpoints = $this->api->get_gcp_endpoints();
            foreach ($endpoints as $endpoint) {
                $latency = $this->api->ping_endpoint($endpoint['url']);
                if ($latency !== false) {
                    $this->db->insert_result($endpoint['region_name'], $latency);
                }
            }

            wp_send_json_success(array(
                'message' => 'Test started successfully',
                'start_time' => $start_time
            ));
        } else {
            wp_send_json_error('Test is already running');
        }
    }
    
    public function reset_latency_test() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');

        wp_clear_scheduled_hook('wp_hosting_benchmarking_cron_hook');
        delete_option('wp_hosting_benchmarking_start_time');
        wp_send_json_success('Test reset successfully');
    
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');

        if (!wp_next_scheduled('wp_hosting_benchmarking_cron_hook')) {
            wp_schedule_event(time(), 'five_minutes', 'wp_hosting_benchmarking_cron_hook');
            update_option('wp_hosting_benchmarking_start_time', time());
            
            // Run the first test immediately
            $endpoints = $this->api->get_gcp_endpoints();
            foreach ($endpoints as $endpoint) {
                $latency = $this->api->ping_endpoint($endpoint['url']);
                if ($latency !== false) {
                    $this->db->insert_result($endpoint['region_name'], $latency);
                }
            }
            
            wp_send_json_success('Test started successfully');
        } else {
            wp_send_json_error('Test is already running');
        }
    }

	
    public function stop_latency_test() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');

        wp_clear_scheduled_hook('wp_hosting_benchmarking_cron_hook');
        delete_option('wp_hosting_benchmarking_start_time');
        wp_send_json_success('Test stopped successfully');
    }


    public function get_latest_results() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');
		if (!$this->db) {
            wp_send_json_error('Database object not initialized');
            return;
        }

        //$results = $this->db->get_latest_results();
		$latest_results = $this->db->get_latest_results_by_region();
		$fastest_and_slowest = $this->db->get_fastest_and_slowest_results();

        /*
		$results = array_map(function($result) {
			$result->latency = (float) $result->latency;
			return $result;
		}, $results);
        */

        // Merge the data
        foreach ($latest_results as &$result) {
            foreach ($fastest_and_slowest as $fas_slow) {
                if ($result->region_name === $fas_slow->region_name) {
                    $result->fastest_latency = $fas_slow->fastest_latency;
                    $result->slowest_latency = $fas_slow->slowest_latency;
                    break;
                }
            }
        }

		
        wp_send_json_success($latest_results);


    }

    public function get_results_for_time_range() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');
        
        $time_range = isset($_POST['time_range']) ? sanitize_text_field($_POST['time_range']) : '24_hours';
    
        // Fetch results from DB based on the time range
        $results = $this->db->get_results_by_time_range($time_range);
        $fastest_and_slowest = $this->db->get_fastest_and_slowest_results();

        // Merge the data
        foreach ($results as &$result) {
            foreach ($fastest_and_slowest as $fas_slow) {
                if ($result->region_name === $fas_slow->region_name) {                        
                    $result->fastest_latency = $fas_slow->fastest_latency;
                    $result->slowest_latency = $fas_slow->slowest_latency;
                    break;
                }
            }
        }

                
                
        if (!empty($results)) {
            wp_send_json_success($results);
        } else {
            wp_send_json_error('No results found for the selected time range.');
        }
    }

    
    public function delete_all_results() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');
        $this->db->delete_all_results();
        wp_send_json_success('All results deleted');
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        // Register a new setting for "wp_hosting_benchmarking_settings"
        register_setting('wp_hosting_benchmarking_settings', 'wp_hosting_benchmarking_option');
    register_setting('wp_hosting_benchmarking_settings', 'wp_hosting_benchmarking_selected_region');

    // Add a new section in the "Settings" page
    add_settings_section(
        'wp_hosting_benchmarking_section', // Section ID
        'General Settings',                // Section title
        null,                              // Section callback (not needed)
        'wp-hosting-benchmarking-settings' // Page slug
    );

    // Add a new field to the "General Settings" section
    add_settings_field(
        'wp_hosting_benchmarking_field',    // Field ID
        'Sample Setting',                  // Field title
        array($this, 'render_sample_setting_field'), // Callback function to render the field
        'wp-hosting-benchmarking-settings', // Page slug
        'wp_hosting_benchmarking_section'   // Section ID (fixed to match)
    );

    // Add a settings field (dropdown for GCP regions)
    add_settings_field(
        'wp_hosting_benchmarking_selected_region', // Field ID
        'Select Closest GCP Region',               // Field title
        array($this, 'gcp_region_dropdown_callback'), // Callback to display the dropdown
        'wp-hosting-benchmarking-settings',        // Page slug
        'wp_hosting_benchmarking_section'          // Section ID (fixed to match)
    );





    }

    /**
     * Render the field for the sample setting.
     */
    public function render_sample_setting_field() {
        $option = get_option('wp_hosting_benchmarking_option');
        ?>
        <input type="text" name="wp_hosting_benchmarking_option" value="<?php echo esc_attr($option); ?>">
        <?php
    }

    // Callback to display the GCP region dropdown
    public function gcp_region_dropdown_callback() {

        $gcp_endpoints = $this->api->get_gcp_endpoints(); // Fetch GCP endpoints
        $selected_region = get_option('wp_hosting_benchmarking_selected_region'); // Get selected region

        if (!empty($gcp_endpoints)) {
            echo '<select name="wp_hosting_benchmarking_selected_region">';
            foreach ($gcp_endpoints as $endpoint) {
                $region_name = esc_attr($endpoint['region_name']);
                echo '<option value="' . $region_name . '"' . selected($selected_region, $region_name, false) . '>';
                echo esc_html($region_name);
                echo '</option>';
            }
            echo '</select>';
        } else {
            echo '<p>No GCP endpoints available.</p>';
        }
           // Explanation text
        echo '<p class="description">Please select the region closest to where most of your customers or visitors are based. ';
    }
}
