<?php
if (!current_user_can('manage_options')) {
    return;
}

// Show error/update messages
settings_errors('wp_hosting_benchmarking_messages');

?>
<div class="wrap">
    <h1><?php echo esc_html('WP Hosting Benchmarking Settings'); ?></h1>
    <form method="post" action="options.php" id="wp-hosting-benchmarking-settings-form">
        <?php
        // Output security fields for the registered setting group "wp_hosting_benchmarking_settings"
        settings_fields('wp_hosting_benchmarking_settings'); // Group name must match register_setting()

        // Output setting sections and fields for the page slug 'wp-hosting-benchmarking-settings'
        do_settings_sections('wp-hosting-benchmarking-settings'); // Page slug must match add_settings_section()
        
        // Submit button
        submit_button('Save Settings');
        ?>
    </form>
</div>


<!-- Confirmation Modal -->
<div id="data-collection-modal" style="display: none;">
    <div class="modal-content">
        <h2>Are you sure?</h2>
        <p>Are you sure you wish to not provide us with anonymous statistics? It really helps the development of this free plugin. We take privacy seriously!</p>
        <p><a href="https://fastestwordpress.com/privacy-policy" target="_blank">Learn more about our privacy policy</a></p>
        <div class="modal-buttons">
            <button id="modal-cancel" class="button">Cancel</button>
            <button id="modal-confirm" class="button button-primary">No stats for you</button>
        </div>
    </div>
</div>


<?php

