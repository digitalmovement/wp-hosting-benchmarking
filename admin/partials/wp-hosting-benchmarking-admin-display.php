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
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <div id="latency-test-container">
        <button id="start-test" class="button button-primary">Start Latency Test</button>
        <button id="stop-test" class="button button-secondary" style="display:none;">Stop Latency Test</button>
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
    var countdownInterval;

    function updateButtonState(isRunning) {
        $('#start-test').prop('disabled', isRunning).toggle(!isRunning);
        $('#stop-test').toggle(isRunning);
    }

    $('#start-test').on('click', function() {
        $.ajax({
            url: wpHostingBenchmarking.ajax_url,
            type: 'POST',
            data: {
                action: 'start_latency_test',
                nonce: wpHostingBenchmarking.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-status').text('Test started. Running for 1 hour.');
                    updateButtonState(true);
                    startCountdown(3600); // 1 hour in seconds
                } else {
                    alert(response.data);
                }
            }
        });
    });

    $('#stop-test').on('click', function() {
        $.ajax({
            url: wpHostingBenchmarking.ajax_url,
            type: 'POST',
            data: {
                action: 'stop_latency_test',
                nonce: wpHostingBenchmarking.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-status').text('Test stopped.');
                    updateButtonState(false);
                    stopCountdown();
                }
            }
        });
    });

    function startCountdown(duration) {
        var timer = duration, minutes, seconds;
        countdownInterval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            $('#countdown').text(minutes + ":" + seconds);

            if (--timer < 0) {
                stopCountdown();
                updateButtonState(false);
                $('#test-status').text('Test completed.');
            }
        }, 1000);
    }

    function stopCountdown() {
        clearInterval(countdownInterval);
        $('#countdown').text('');
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
                if (response.success) {
                    // Update #latest-results and #latency-graph with the new data
                    // You'll need to implement this based on your data structure
                    $('#latest-results').html(formatResults(response.data));
                    updateGraph(response.data);
                }
            }
        });
    }

    function formatResults(results) {
        // Implement this function to format the results as HTML
    }

    function updateGraph(data) {
        // Implement this function to update the graph
        // You might want to use a library like Chart.js for this
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
                    if (response.success) {
                        alert('All results deleted');
                        updateResults();
                    }
                }
            });
        }
    });

    // Initial update and set interval for periodic updates
    updateResults();
    setInterval(updateResults, 60000); // Update every minute
});
</script>