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
<div>
    <label for="time-range">Select Time Range:</label>
    <select id="time-range" class="time-range-dropdown">
        <option value="24_hours">Last 24 Hours</option>
        <option value="7_days">Last 7 Days</option>
        <option value="90_days">Last 90 Days</option>
    </select>
</div>
    <h2>Latest Results</h2>
    <table id="latency-results" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Region</th>
                <th>Current Latency (ms)</th>
                <th>Difference</th>
                <th>Fastest Latency (ms)</th>
                <th>Slowest Latency (ms)</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            <!-- Results will be populated here -->
        </tbody>
    </table>
</div>
<div id="graphs-container">
    <h2>Graphs for Each Region</h2>
    <div id="graph-{{region_name}}" style="height: 300px; width: 100%;">
        <canvas id="graph-{{region_name}}"></canvas>
    </div>
</div>

<button id="delete-results" class="button button-secondary">Delete All Results</button>
</div>
<script>
jQuery(document).ready(function($) {
    var countdownInterval;
    var isRunning = false; // Initialize isRunning as false
    var lastResults = {};
    var chartInstances = {};

    function updateButtonState(isRunning) {
        $('#start-test').prop('disabled', isRunning); // Disable start button if running
        $('#start-test').toggle(!isRunning);
        $('#stop-test').toggle(isRunning);
    }

    function startCountdown(duration, startTime) {
        var timer = duration - (Math.floor(Date.now() / 1000) - startTime), minutes, seconds;
        countdownInterval = setInterval(function () {
            if (timer <= 0) {
                clearInterval(countdownInterval);
                $('#test-status').text('Test completed.');
                isRunning = false; // Update running status
                updateButtonState(false); // Re-enable the start button
                return;
            }

            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            $('#countdown').text(minutes + ":" + seconds);
            timer--;
        }, 1000);
    }

    function checkTestStatus() {
        var startTime = parseInt(wpHostingBenchmarking.start_time, 10);
        if (startTime) {
            var currentTime = Math.floor(Date.now() / 1000);
            var elapsedTime = currentTime - startTime;
            if (elapsedTime < 3600) {
                $('#test-status').text('Test running...');
                isRunning = true; // Update running status
                updateButtonState(true); // Disable start button while running
                startCountdown(3600, startTime);
            }
        }
    }
    function createGraphContainer(regionName) {
    var container = $('<div>').attr('id', 'graph-container-' + regionName).css({
        height: '300px',
        width: '100%',
        margin: '20px 0' // Add some spacing between graphs
    });

    var title = $('<h3>').text('Graph for ' + regionName); // Add a title to the graph
    var canvas = $('<canvas>').attr('id', 'graph-' + regionName);

    container.append(title); // Append the title to the container
    container.append(canvas); // Append the canvas to the container
    $('#graphs-container').append(container); // Append the new container to the main container
}

function renderGraphs(results) {
    var regionData = {};

    results.forEach(function(result) {
        // Check if the region already exists in the regionData object
        if (!regionData[result.region_name]) {
            regionData[result.region_name] = {
                labels: [],
                latencies: []
            };
        }

        // Add the test_time and latency to the respective region's data
        regionData[result.region_name].labels.push(result.test_time);
        regionData[result.region_name].latencies.push(result.latency);
    });

    // Now, create or update charts for each region
    Object.keys(regionData).forEach(function(region) {
        // Dynamically create the canvas element if it doesn't exist
        if (!document.getElementById('graph-' + region)) {
            createGraphContainer(region);
        }

        var ctx = document.getElementById('graph-' + region).getContext('2d');

        // Destroy the previous chart instance if it exists
        if (chartInstances[region]) {
            chartInstances[region].destroy();
        }

        // Create a new Chart.js instance for the region
        chartInstances[region] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: regionData[region].labels, // X-axis labels (time)
                datasets: [{
                    label: 'Latency (ms) for ' + region, // Label for the dataset
                    data: regionData[region].latencies, // Y-axis data (latency)
                    borderColor: 'rgba(75, 192, 192, 1)', // Line color
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                scales: {
                    x: {
                        type: 'time', // Use time scale
                        time: {
                            unit: 'hour' // Adjust based on your data (can be 'day', 'minute', etc.)
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Latency Data for ' + region // Display region name as chart title
                    }
                }
            }
        });
    });
}

    $('#time-range').on('change', function() {
        var timeRange = $(this).val();

        $.ajax({
            url: wpHostingBenchmarking.ajax_url,
            type: 'POST',
            data: {
                action: 'get_results_for_time_range',
                nonce: wpHostingBenchmarking.nonce,
                time_range: timeRange
            },
            success: function(response) {
                if (response.success) {
                    updateResultsTable(response.data);
                    renderGraphs(response.data);
                } else {
                    alert('No results found for the selected time range.');
                }
            }
        });
    });



$('#reset-test').on('click', function() {
    $.ajax({
        url: wpHostingBenchmarking.ajax_url,
        type: 'POST',
        data: {
            action: 'reset_latency_test',
            nonce: wpHostingBenchmarking.nonce
        },
        success: function(response) {
            if (response.success) {
                $('#test-status').text('Test reset successfully.');
                updateButtonState(false);
                clearInterval(countdownInterval);
                $('#countdown').text('');
            } else {
                alert(response.data);
            }
        }
    });
});


        $('#start-test').prop('disabled', isRunning).toggle(!isRunning);
        $('#stop-test').toggle(isRunning);
    

     $('#start-test').on('click', function() {
        isRunning = true; // Set running status
        updateButtonState(true); // Disable start button

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
                    startCountdown(3600, Math.floor(Date.now() / 1000));
                } else {
                    alert(response.data);
                    isRunning = false; // Reset running status if there's an error
                    updateButtonState(false); // Re-enable start button
                }
            }
        });
    });

    // Stop test button click handler
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
                    clearInterval(countdownInterval); // Stop the countdown
                    isRunning = false; // Update running status
                    updateButtonState(false); // Re-enable start button
                    $('#countdown').text('');
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

        // Convert latency and latency difference to numbers
        var latency = parseFloat(result.latency);
        var latencyDifference = parseFloat(result.latency_difference);
        var fastestLatency = parseFloat(result.fastest_latency);
        var slowestLatency = parseFloat(result.slowest_latency);

        // Check if latency is a valid number
        if (isNaN(latency)) {
            console.error('Invalid latency value:', result.latency);
            latency = 0; // Default value if invalid
        }

        // Check if latency difference is valid
        var diffText = 'N/A';
        var diffClass = '';
        if (!isNaN(latencyDifference)) {
            diffText = latencyDifference > 0 ? '+' + latencyDifference.toFixed(1) : latencyDifference.toFixed(1);
            diffClass = latencyDifference < 0 ? 'latency-faster' : (latencyDifference > 0 ? 'latency-slower' : '');
        }

        // Create the table row with added fastest and slowest latencies
        row.append($('<td>').text(result.region_name));
        row.append($('<td>').text(latency.toFixed(1) + ' ms'));
        row.append($('<td>').addClass(diffClass).text(diffText));
        row.append($('<td>').text(fastestLatency.toFixed(1) + ' ms'));
        row.append($('<td>').text(slowestLatency.toFixed(1) + ' ms'));
        row.append($('<td>').text(formatDate(result.test_time)));

        tableBody.append(row);

        // Update last results for future comparisons
        //lastResults[result.region_name] = latency;
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
checkTestStatus();
updateResults();
setInterval(updateResults, 60000); // Update every minute

});
</script>