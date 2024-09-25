<?php
?>
<div class="wrap">
    <h1><?php echo esc_html('WP Hosting Benchmarking Settings'); ?></h1>
    <form method="post" action="options.php">
        <?php
        // Output security fields for the registered setting "wp_hosting_benchmarking_settings"
        settings_fields('wp_hosting_benchmarking_settings');

        // Output setting sections and fields
        do_settings_sections('wp-hosting-benchmarking-settings');
?>      <table class="form-table">
            <tr valign="top">
                <th scope="row">Select Closest GCP Region</th>
                <td>
                    <select name="wp_hosting_benchmarking_selected_region">
                        <?php foreach ($gcp_endpoints as $endpoint): ?>
                            <option value="<?php echo esc_attr($endpoint['region_name']); ?>"
                                <?php selected($selected_region, $endpoint['region_name']); ?>>
                                <?php echo esc_html($endpoint['region_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
        // Submit button
        submit_button('Save Settings');
        ?>
    </form>
</div>
<?php

