<?php
/*
Plugin Name: BBPress Bulk Unsubscribe
Plugin URI: http://www.vibethemes.com/
Description: This plugin adds option to bulk unsubscribe users from BBPress forums and topics
Version: 1.0 
Author: VibeThemes
Author URI: http://www.vibethemes.com/
Text Domain: bbpbu
Domain Path: /languages/
*/
if ( !defined( 'ABSPATH' ) ) exit;


include_once 'includes/init.php';
include_once 'includes/tools.php';

add_action('plugins_loaded','bbpbu_translations');
function bbpbu_translations(){
    $locale = apply_filters("plugin_locale", get_locale(), 'bbpbu');
    $lang_dir = dirname( __FILE__ ) . '/languages/';
    $mofile        = sprintf( '%1$s-%2$s.mo', 'bbpbu', $locale );
    $mofile_local  = $lang_dir . $mofile;
    $mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;

    if ( file_exists( $mofile_global ) ) {
        load_textdomain( 'bbpbu', $mofile_global );
    } else {
        load_textdomain( 'bbpbu', $mofile_local );
    }  
}