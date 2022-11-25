<?php
/**
 * This file is dependent on wp-cron-multisite.php which will will call this file
 * to run all the cron jobs on the WPMU network's sub-websites.
 * WordPress Cron Implementation for hosts, which do not offer CRON or for which
 * the user has not set up a CRON job pointing to this file.
 * Coded with Love by WPMU DEv (wpmudev.org) and Tenno Networks Ltd
 * The HTTP request to this file will not slow down the visitor who happens to
 * visit when the cron job is needed to run.
 * The code may be shared and published as long as credits with a link is given to Tenno Networks Ltd (www.tennonetworks.com)
 * © 2019 All Rights reserved Tenno Networks Ltd.
 *
 * @package WordPress
 */

ignore_user_abort(true);

if ( !empty($_POST) || defined('DOING_AJAX') || defined('DOING_CRON') )
    die();

/**
 * Tell WordPress we are doing the CRON task.
 *
 * @var bool
 */
define('DOING_CRON', true);

if ( !defined('ABSPATH') ) {
    /** Set up WordPress environment */
    require_once( dirname( __FILE__ ) . '/wp-load.php' );
}
cron_write_log("[Custom Cron] Starting Cron |==============================================================");
/**
 * Retrieves the cron lock.
 *
 * Returns the uncached `doing_cron` transient.
 *
 * @ignore
 * @since 3.3.0
 *
 * @return string|false Value of the `doing_cron` transient, 0|false otherwise.
 */
function _get_cron_lock() {

    global $wpdb;
    cron_write_log("[Custom Cron] Get cron lock");
    $value = 0;
    if ( wp_using_ext_object_cache() ) {
        /*
         * Skip local cache and force re-fetch of doing_cron transient
         * in case another process updated the cache.
         */
        $value = wp_cache_get( 'doing_cron', 'transient', true );
        cron_write_log("[Custom Cron] Using ext_object_cache doing_cron: " . $value);
    } else {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", '_transient_doing_cron' ) );
        if ( is_object( $row ) )
            $value = $row->option_value;

        cron_write_log("[Custom Cron] Using db doing_cron: " .$value);
    }

    return $value;
}

if ( false === $crons = _get_cron_array() ){
    cron_write_log("[Custom Cron] Cron Array Emmpty");
    die();
}else{
    cron_write_log("[Custom Cron] There are crons in cron array");
    //cron_write_log("[Custom Cron] Cron Array: " . print_r( $crons, true) );
}

$keys = array_keys( $crons );
$gmt_time = microtime( true );

if ( isset($keys[0]) && $keys[0] > $gmt_time ) {
    cron_write_log("[Custom Cron] Keys[0] > gmt_time. Exiting (maybe too early to run this one)");
    die();
}


// The cron lock: a unix timestamp from when the cron was spawned.
$doing_cron_transient = get_transient( 'doing_cron' );
cron_write_log("[Custom Cron] Doing Cron Transient Value: " . $doing_cron_transient);

// Use global $doing_wp_cron lock otherwise use the GET lock. If no lock, trying grabbing a new lock.
if ( empty( $doing_wp_cron ) ) {
    cron_write_log("[Custom Cron] doing_wp_cron is empty");
    if ( empty( $_GET[ 'doing_wp_cron' ] ) ) {
        cron_write_log("[Custom Cron] GET['doing_wp_cron'] is empty. Called from external job");
        // Called from external script/job. Try setting a lock.
        if ( $doing_cron_transient && ( $doing_cron_transient + WP_CRON_LOCK_TIMEOUT > $gmt_time ) )
            return;
        $doing_cron_transient = $doing_wp_cron = sprintf( '%.22F', microtime( true ) );
        set_transient( 'doing_cron', $doing_wp_cron );
        cron_write_log("[Custom Cron] Set transient: " .$doing_wp_cron);
    } else {
        $doing_wp_cron = $_GET[ 'doing_wp_cron' ];

        cron_write_log("[Custom Cron] GET[doing_wp_cron] NOT empty. Doing_wp_cron_transient : " . $doing_wp_cron_transient . ", doing_wp_cron: " .$doing_wp_cron);
        $doing_cron_transient = $doing_wp_cron = $_GET[ 'doing_wp_cron' ];

        cron_write_log("[Custom Cron] GET[doing_wp_cron] NOT empty. Doing_wp_cron_transient : " . $doing_wp_cron_transient . ", doing_wp_cron: " .$doing_wp_cron);
    }
}else{
    cron_write_log("[Custom Cron] doing_wp_cron is NOT empty");
}

/*
 * The cron lock (a unix timestamp set when the cron was spawned),
 * must match $doing_wp_cron (the "key").
 */
if ( $doing_cron_transient != $doing_wp_cron ){
    cron_write_log("[Custom Cron] cron_transient and wp_cron do not match");
    return;
}else{
    cron_write_log("[Custom Cron] Matching cron_transient and wp_cron. Continue...");
}

foreach ( $crons as $timestamp => $cronhooks ) {
    if ( $timestamp > $gmt_time ){
        cron_write_log("[Custom Cron] Time stamp > gmt_time: $timestamp > $gmt_time. Exiting");
        break;
    }else{
        cron_write_log("[Custom Cron] timestamp <= gmt_time. Continue");
    }
    cron_write_log("[Custom Cron] For each Cron Hooks as hook keys");
    foreach ( $cronhooks as $hook => $keys ) {
        cron_write_log("[Custom Cron] Hook: " . $hook . print_r($keys, true));
        foreach ( $keys as $k => $v ) {

            $schedule = $v['schedule'];
            cron_write_log("[Custom Cron] Schedule: " . $v['schedule'] );

            if ( $schedule != false ) {
                cron_write_log("[Custom Cron] Schedule not false");
                $new_args = array($timestamp, $schedule, $hook, $v['args']);
                call_user_func_array('wp_reschedule_event', $new_args);
                cron_write_log("[Custom Cron] Reschedule: ");
                cron_write_log("[Custom Cron] New args: " .print_r($new_args,true) );
            }else{
                cron_write_log("[Custom Cron] Schedule = false. Do nothing.");
            }

            wp_unschedule_event( $timestamp, $hook, $v['args'] );

            /**
             * Fires scheduled events.
             *
             * @ignore
             * @since 2.1.0
             *
             * @param string $hook Name of the hook that was scheduled to be fired.
             * @param array  $args The arguments to be passed to the hook.
             */
            do_action_ref_array( $hook, $v['args'] );

            cron_write_log("[Custom Cron] Doing action_ref_array : ");
            cron_write_log("[Custom Cron] Hook: " .$hook . "|  " . print_r( $v['args'],true));

            // If the hook ran too long and another cron process stole the lock, quit.
            if ( _get_cron_lock() != $doing_wp_cron ){
                cron_write_log("[Custom Cron] Got Cron Lock. Exiting");
                return;
            }else{
                cron_write_log("[Custom Cron] No Cron Lock. Continue");
            }
        }
    }
}

if ( _get_cron_lock() == $doing_wp_cron ){
    cron_write_log("[Custom Cron] get_cron_lock = doing_wp_cron. Delete doing_cron transient");
    delete_transient( 'doing_cron' );
}

cron_write_log("[Custom Cron] Done |==============================================================");

function cron_write_log($message){

        $file = WP_CONTENT_DIR . "/debug-custom-cron.log";

        $handle = fopen( $file, 'ab' );
        $data = date( "[Y-m-d H:i:s]" ) . $message . "\r\n";
        @fwrite($handle, $data);
        @fclose($handle);
    
}

die();
