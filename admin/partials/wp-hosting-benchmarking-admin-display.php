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
?><div class="wrap">
<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
<div id="latency-test-container">
    <button id="start-test" class="button button-primary">Start Latency Test</button>
    <button id="stop-test" class="button button-secondary" style="display:none;">Stop Latency Test</button>
    <p id="test-status"></p>
    <div id="countdown"></div>
</div>
<div id="results-container">
    <h2>Latest Results</h2>
    <table id="latency-results" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Region</th>
                <th>Current Latency (ms)</th>
                <th>Difference</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            <!-- Results will be populated here -->
        </tbody>
    </table>
</div>
<div id="latency-graph"></div>
<button id="delete-results" class="button button-secondary">Delete All Results</button>
</div>

<style>
.latency-faster { color: green; }
.latency-slower { color: red; }
#latency-results th, #latency-results td { padding: 8px; }
</style>

<script>
jQuery(document).ready(function($) {
var countdownInterval;
var lastResults = {};

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
            console.log('Server response:', response); // Add this line
            if (response.success) {
                updateResultsTable(response.data);
                updateGraph(response.data);
            } else {
                console.error('Error in server response:', response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX request failed:', textStatus, errorThrown);
        }
    });
}
function updateResultsTable(results) {
    var tableBody = $('#latency-results tbody');
    tableBody.empty();

    results.forEach(function(result) {
        var row = $('<tr>');
        
        // Convert latency to a number if it's not already
        var latency = parseFloat(result.latency);
        
        // Check if latency is a valid number
        if (isNaN(latency)) {
            console.error('Invalid latency value:', result.latency);
            latency = 0; // or some default value
        }

        var latencyDiff = calculateLatencyDiff(result.region_name, latency);
        var diffClass = latencyDiff < 0 ? 'latency-faster' : (latencyDiff > 0 ? 'latency-slower' : '');
        var diffText = latencyDiff !== null ? (latencyDiff > 0 ? '+' : '') + latencyDiff.toFixed(1) : 'N/A';

        row.append($('<td>').text(result.region_name));
        row.append($('<td>').text(latency.toFixed(1)));
        row.append($('<td>').addClass(diffClass).text(diffText));
        row.append($('<td>').text(formatDate(result.test_time)));

        tableBody.append(row);

        // Update last results for future comparisons
        lastResults[result.region_name] = latency;
    });
}
function calculateLatencyDiff(region, currentLatency) {
    if (lastResults.hasOwnProperty(region)) {
        return currentLatency - lastResults[region];
    }
    return null;
}

function formatDate(dateString) {
    var date = new Date(dateString);
    return date.toLocaleString();
}

function updateGraph(data) {
    // Implement this function to update the graph
    // You might want to use a library like Chart.js for this
}

// ... (rest of the JavaScript code remains the same)

// Initial update and set interval for periodic updates
updateResults();
setInterval(updateResults, 60000); // Update every minute
});
</script>