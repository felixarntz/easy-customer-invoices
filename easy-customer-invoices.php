<?php
/*
Plugin Name: Easy Customer Invoices
Plugin URI: https://wordpress.org/plugins/easy-customer-invoices/
Description: This plugin sets up a simple customer management system with PDF invoicing capabilities.
Version: 1.0.0
Author: Felix Arntz
Author URI: http://leaves-and-love.net
License: GNU General Public License v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: easy-customer-invoices
Domain Path: /languages/
Tags: wordpress, plugin, customer, invoice, billing, management, custom-post-type, custom-taxonomy
*/
/**
 * @package WPECI
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( version_compare( phpversion(), '5.3.0' ) >= 0 && ! class_exists( 'WPECI\App' ) ) {
	if ( file_exists( dirname( __FILE__ ) . '/easy-customer-invoices/vendor/autoload.php' ) ) {
		require_once dirname( __FILE__ ) . '/easy-customer-invoices/vendor/autoload.php';
	} elseif ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
		require_once dirname( __FILE__ ) . '/vendor/autoload.php';
	}
} elseif ( ! class_exists( 'LaL_WP_Plugin_Loader' ) ) {
	if ( file_exists( dirname( __FILE__ ) . '/easy-customer-invoices/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php' ) ) {
		require_once dirname( __FILE__ ) . '/easy-customer-invoices/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php';
	} elseif ( file_exists( dirname( __FILE__ ) . '/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php' ) ) {
		require_once dirname( __FILE__ ) . '/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php';
	}
}

LaL_WP_Plugin_Loader::load_plugin( array(
	'slug'					=> 'easy-customer-invoices',
	'name'					=> 'Easy Customer Invoices',
	'version'				=> '1.0.0',
	'main_file'				=> __FILE__,
	'namespace'				=> 'WPECI',
	'textdomain'			=> 'easy-customer-invoices',
), array(
	'phpversion'			=> '5.3.0',
	'wpversion'				=> '4.4',
	'plugins'				=> array(
		'post-types-definitely'	=> '0.6.1',
		'options-definitely'	=> '0.6.1',
		'wp-objects'			=> '1.0.0',
	),
) );
