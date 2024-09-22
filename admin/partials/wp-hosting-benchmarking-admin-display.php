<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://fastestwordpress.com
 * @since      1.0.0
 *
 * @package    Wp_Hosting_Benchmarking
 * @subpackage Wp_Hosting_Benchmarking/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <div id="latency-test-container">
        <button id="start-test" class="button button-primary">Start Latency Test</button>
        <p id="test-status"></p>
        <div id="countdown"></div>
    </div>
    <div id="results-container">
        <h2>Latest Results</h2>
        <div id="latest-results"></div>
        <div id="latency-graph"></div>
    </div>
    <button id="delete-results" class="button button-secondary">Delete All Results</button>
</div>

<script>
jQuery(document).ready(function($) {
    $('#start-test').on('click', function() {
        $.ajax({
            url: wpHostingBenchmarking.ajax_url,
            type: 'POST',
            data: {
                action: 'start_latency_test',
                nonce: wpHostingBenchmarking.nonce
            },
            success: function(response) {
                $('#test-status').text('Test started. Running for 1 hour.');
                $('#start-test').prop('disabled', true);
                startCountdown();
            }
        });
    });

    function startCountdown() {
        // Implement countdown logic
    }

    function updateResults() {
        $.ajax({
            url: wpHostingBenchmarking.ajax_url,
            type: 'POST',
            data: {
                action: 'get_latest_results',
                nonce: wpHostingBenchmarking.nonce
            },
            success: function(response) {
                // Update #latest-results and #latency-graph with the new data
            }
        });
    }

    $('#delete-results').on('click', function() {
        if (confirm('Are you sure you want to delete all results?')) {
            $.ajax({
                url: wpHostingBenchmarking.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_all_results',
                    nonce: wpHostingBenchmarking.nonce
                },
                success: function(response) {
                    alert('All results deleted');
                    updateResults();
                }
            });
        }
    });
});
</script>