<?php
// Ensure this file is being included by a parent file
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <div class="server-performance-content">
        <h2>Server Performance Tests</h2>
        <p>This page will contain various server performance tests and their results. Implement your server performance testing logic here.</p>
        <!-- Add your server performance testing content here -->
    </div>
    <div id="server-performance-tabs">
        <ul>
            <li><a href="#tab-overview">Overview</a></li>
            <li><a href="#tab-cpu-memory">CPU & Memory</a></li>
            <li><a href="#tab-filesystem">Filesystem</a></li>
            <li><a href="#tab-database">Database</a></li>
            <li><a href="#tab-object-cache">Object Cache</a></li>
        </ul>
        
        <div id="tab-overview">
            <h2>Server Performance Overview</h2>
            <button id="start-stop-test" class="button button-primary" data-status="<?php echo esc_attr(get_option('wp_hosting_benchmarking_test_status', 'stopped')); ?>">
                <?php echo get_option('wp_hosting_benchmarking_test_status', 'stopped') === 'running' ? 'Stop Test' : 'Start Test'; ?>
            </button>
            <div id="test-progress" style="display: none;">
                <p>Test in progress... You can leave this page and come back later to see the results.</p>
            </div>
            <div id="results-chart-container" style="width: 80%; margin: 20px auto;">
                <canvas id="results-chart"></canvas>
            </div>
        </div>
        
        <div id="tab-cpu-memory">
            <h2>CPU & Memory Performance</h2>
            <!-- CPU & Memory specific content will go here -->
        </div>
        
        <div id="tab-filesystem">
            <h2>Filesystem Performance</h2>
            <!-- Filesystem specific content will go here -->
        </div>
        
        <div id="tab-database">
            <h2>Database Performance</h2>
            <!-- Database specific content will go here -->
        </div>
        
        <div id="tab-object-cache">
            <h2>Object Cache Performance</h2>
            <!-- Object Cache specific content will go here -->
        </div>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    $("#server-performance-tabs").tabs();
    
    var testStatus = '<?php echo esc_js(get_option('wp_hosting_benchmarking_performance_test_status', 'stopped')); ?>';
    var chart;
    
    function updateButtonState(status) {
        var $button = $('#start-stop-test');
        $button.data('status', status);
        $button.text(status === 'running' ? 'Stop Test' : 'Start Test');
        $('#test-progress').toggle(status === 'running');
    }
    
    $('#start-stop-test').on('click', function() {
        var status = $(this).data('status');
        var newStatus = status === 'running' ? 'stopped' : 'running';
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wp_hosting_benchmarking_performance_toggle_test',
                status: newStatus,
                _ajax_nonce: '<?php echo wp_create_nonce("wp_hosting_benchmarking_performance_toggle_test"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    updateButtonState(newStatus);
                    if (newStatus === 'running') {
                        startBackgroundTest();
                    }
                } else {
                    alert('Failed to toggle test status. Please try again.');
                }
            }
        });
    });
    
    function startBackgroundTest() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wp_hosting_benchmarking_performance_run_test',
                _ajax_nonce: '<?php echo wp_create_nonce("wp_hosting_benchmarking_performance_run_test"); ?>'
            }
        });
    }
    
    function loadResults() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wp_hosting_benchmarking_performance_get_results',
                _ajax_nonce: '<?php echo wp_create_nonce("wp_hosting_benchmarking_performance_get_results"); ?>'
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayResults(response.data);
                }
            }
        });
    }
    
    function displayResults(data) {
        var ctx = document.getElementById('results-chart').getContext('2d');
        
        if (chart) {
            chart.destroy();
        }
        
        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['CPU & Memory', 'Filesystem', 'Database', 'Object Cache'],
                datasets: [
                    {
                        label: 'Your Results',
                        data: [
                            data.cpu_memory,
                            data.filesystem,
                            data.database,
                            data.object_cache
                        ],
                        backgroundColor: 'rgba(75, 192, 192, 0.6)'
                    },
                    {
                        label: 'Industry Average',
                        data: [
                            data.industry_avg.cpu_memory,
                            data.industry_avg.filesystem,
                            data.industry_avg.database,
                            data.industry_avg.object_cache
                        ],
                        backgroundColor: 'rgba(153, 102, 255, 0.6)'
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Time (seconds)'
                        }
                    }
                }
            }
        });
    }
    
    updateButtonState(testStatus);
    loadResults();
});
</script>
