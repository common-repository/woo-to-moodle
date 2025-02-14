<?php

/*
 * Plugin Name: Woo to Moodle
 * Version:     1.1
 * Plugin URI:  https://wordpress.org/plugins/woo-to-moodle
 * Description: This plugin will automatically enroll in Moodle customers who buy the course in WooCommerce.
 * Author:      Jean-Pierre Hutter
 * Author URI:  https://github.com/jphutter/wootomoo
 * Text Domain: woo-to-moodle
 * Domain Path: /languages
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */


if( ! defined( 'ABSPATH' ) ) exit;

include_once 'config.php';

function wootomoo_load_textdomain() {
    load_plugin_textdomain( 'woo-to-moodle', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'wootomoo_load_textdomain' );


//===========================   T H E   S E T T I N G S   M E N U   ===========================

include_once 'wootomoo_settings_menu.php';

// Add settings page to Wordpress Settings menu
add_action( 'admin_menu' , 'wootomoo_add_settings_menu' );

function wootomoo_add_settings_menu () {
    add_options_page( 'Moodle', 'Moodle', 'manage_options' , 'wootomoo_settings_menu', 'wootomoo_settings_menu' );

    // triggered before any other hook when a user accesses the admin area
    add_action( 'admin_init', 'wootomoo_register_setting' );
}

function wootomoo_register_setting() {
    register_setting( WOOTOMOO_SETTINGS, WOOTOMOO_URL_KEY, 'wootomoo_validate_url_key' );
    register_setting( WOOTOMOO_SETTINGS, WOOTOMOO_TOKEN_KEY, 'wootomoo_validate_token_key' );
    register_setting( WOOTOMOO_SETTINGS, WOOTOMOO_CAT_LIST );
}

function wootomoo_validate_url_key( $input ) {
    $url = filter_var( esc_url_raw( $input ), FILTER_VALIDATE_URL );
    if( null == $input || null == $url ) {
        add_settings_error( WOOTOMOO_URL_KEY, 'error', __('Invalid URL', 'woo-to-moodle') );
        return get_option( WOOTOMOO_URL_KEY );
    }
    else
        return $url;
}

function wootomoo_validate_token_key( $input ) {
    $key = sanitize_key( $input );
    if( null == $input || strlen( $key ) != 32 ) {
        add_settings_error( WOOTOMOO_TOKEN_KEY, 'error', __('The token must have 32 characters', 'woo-to-moodle') );
        return get_option( WOOTOMOO_TOKEN_KEY );
    }
    else
        return $key;
}

//===========================   T H E   A D M I N   M E N U   ===========================

include_once 'wootomoo_admin_page.php';

// Add admin page to Woocommerce Product menu
add_action( 'admin_menu', 'wootomoo_add_admin_page' );

function wootomoo_add_admin_page () {
    add_submenu_page('edit.php?post_type=product', 'Moodle', 'Moodle', 'manage_options', WOOTOMOO_LINKS_PAGE, 'wootomoo_admin_page' );
}

//===========================   A N D   T H E   A C T I O N   ===========================

include_once 'wootomoo_payment_complete.php';

register_activation_hook( __FILE__, 'wootomoo_activation' );

add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wootomoo_action_links' );
add_action( 'woocommerce_payment_complete', 'wootomoo_payment_complete_action' );

function wootomoo_activation() {
    global $wpdb;

    update_option( WOOTOMOO_VERSION, '1.1' );

    $collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wootomoo_links (
        id bigint(10) NOT NULL auto_increment,
        product_id bigint(10) NOT NULL,
        product_name varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
        course_id bigint(10) NOT NULL,
        course_name varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
        PRIMARY KEY (id)
    ) $collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

function wootomoo_action_links( $links ) {
    $links = array_merge( array(
        '<a href="' . esc_url( admin_url( 'edit.php?post_type=product&amp;page=' . WOOTOMOO_LINKS_PAGE ) ) . '">' . __( 'Admin' ) . '</a>'
    ), $links );
    return $links;
}

