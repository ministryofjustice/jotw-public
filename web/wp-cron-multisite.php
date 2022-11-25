<?php
/**
 * This file wp-cron-multisite.php is intended to run the cron job for all sites in the WordPress MultiSite network.
 * Sources: Code created to working state by Tenno Networks Ltd. Website: https://tennonetworks.com
 * With some code love and enhancement by WPMU Dev (www.wpmudev.org)
 * and also thanks to Steve Klingler to spread out crons over 30 seconds and invoke the right protocol.
 * The code may be shared and published as long as credits with a link is given to Tenno Networks Ltd (www.tennonetworks.com)
 * © 2019 All Rights reserved Tenno Networks Ltd.
 */

$message = "";
$message .= "[". date('M d, Y h:i:s') ."] Starting WordPress MultiSite Cron Job\n";
/** Define ABSPATH as this file's directory */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __FILE__ ) . '/wp/' );
}
if ( file_exists( ABSPATH . 'wp-load.php' ) ) {
    $message .= "[". date('h:i:s') ."] Loading WordPress: " . ABSPATH . "wp-load.php\n";
    include( ABSPATH . 'wp-load.php' );
}else{
    $message .= "[". date('h:i:s') ."] File does not exist: " . ABSPATH . "wp-load.php\n";
}
$message .= "[" . date('h:i:s') . "] WordPress Loaded Successfully\n";
global $wpdb;
$sql = $wpdb->prepare("SELECT domain, path FROM $wpdb->blogs WHERE archived='0' AND deleted ='0' LIMIT 0,300", '');

$blogs = $wpdb->get_results($sql);

$my_delay = (30*1000000)/count($blogs); // 30 microseconds divided by the number of sites
$message .= "[". date('h:i:s') ."] There are " . count($blogs) . " sites\n";
$message .= "[". date('h:i:s') ."] Delay between jobs is " . $my_delay . " microseconds\n";

foreach($blogs as $blog) {
    $command = "http://" . $blog->domain . ($blog->path ? $blog->path : '/') . 'wp-cron-custom.php?doing_wp_cron';//='.microtime( true );
    $message .= "[". date('h:i:s') ."] Curl: " . $command . "\n";
    $ch = curl_init($command);


    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_URL, $command);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, 'a=b&c=d&e=f');
    $headers[] = 'Content-Type:application/x-www-form-urlencoded';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


    //$rc = curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //WAS FALSE
    $rc = curl_exec($ch);
    curl_close($ch);
    usleep($my_delay); // sleep the calculated amount of time to spread out the calls over 30 secconds

    //error_log( print_r( $rc, true ) );
}
$message .= "[". date('M d, Y h:i:s') ."] Finished WordPress Multisite Cron Job\n";
$message .= "[". date('M d, Y h:i:s') ."] =====================================\n";

echo $message; //mail message
//wp_mail("your@email.com","[yoursitename] Multisite Cron", $message);
?>
