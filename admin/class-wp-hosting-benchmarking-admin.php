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

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Hosting_Benchmarking_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Hosting_Benchmarking_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-hosting-benchmarking-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Hosting_Benchmarking_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Hosting_Benchmarking_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-hosting-benchmarking-admin.js', array( 'jquery' ), $this->version, false );

	}
  /**
     * Add admin menu item for WP Benchmarking
     */
    public function add_admin_menu() {
        add_menu_page(
            'WP Benchmarking',
            'WP Benchmarking',
            'manage_options',
            'wp-hosting-benchmarking',
            array( $this, 'display_admin_page' ),
            'dashicons-performance',
            999 // Set a high number to ensure it's the last menu item
        );
    }

    /**
     * Display admin page content
     */
    public function display_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p>Welcome to the WP Benchmarking admin panel. Configure your benchmarking settings here.</p>
            <!-- Add your plugin's admin interface here -->
        </div>
        <?php
    }
}
