<?php

class Wp_Hosting_Benchmarking_DB {
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';

        $sql = "CREATE TABLE $table_name (
        latency_difference float DEFAULT NULL,
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            test_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            region_name varchar(255) NOT NULL,
            latency float NOT NULL,
            PRIMARY KEY  (id),
            KEY region_name (region_name),
            KEY test_time (test_time)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function insert_result($region_name, $latency) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
        $wpdb->insert(
            $table_name,
            array(
                'test_time' => current_time('mysql'),
                'region_name' => $region_name,
                'latency' => $latency
            )
        );
    }

    public function get_latest_results() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY test_time DESC LIMIT 10");
    }

    public function get_latest_results_by_region() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
        
        $query = "
            SELECT r1.*
            FROM $table_name r1
            INNER JOIN (
                SELECT region_name, MAX(test_time) as max_time
                FROM $table_name
                GROUP BY region_name
            ) r2 ON r1.region_name = r2.region_name AND r1.test_time = r2.max_time
            ORDER BY r1.region_name
        ";

        return $wpdb->get_results($query);
    }


    public function delete_all_results() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
        $wpdb->query("TRUNCATE TABLE $table_name");
    }

    public function purge_old_results() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hosting_benchmarking_results';
        $one_week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE test_time < %s", $one_week_ago));
    }
}