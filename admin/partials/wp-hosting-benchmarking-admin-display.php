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

// ... (previous JavaScript code remains the same)

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
                updateResultsTable(response.data);
                updateGraph(response.data);
            }
        }
    });
}

function updateResultsTable(results) {
    var tableBody = $('#latency-results tbody');
    tableBody.empty();

    results.forEach(function(result) {
        var row = $('<tr>');
        var latencyDiff = calculateLatencyDiff(result.region_name, result.latency);
        var diffClass = latencyDiff < 0 ? 'latency-faster' : (latencyDiff > 0 ? 'latency-slower' : '');
        var diffText = latencyDiff !== null ? (latencyDiff > 0 ? '+' : '') + latencyDiff.toFixed(1) : 'N/A';

        row.append($('<td>').text(result.region_name));
        row.append($('<td>').text(result.latency.toFixed(1)));
        row.append($('<td>').addClass(diffClass).text(diffText));
        row.append($('<td>').text(formatDate(result.test_time)));

        tableBody.append(row);

        // Update last results for future comparisons
        lastResults[result.region_name] = result.latency;
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