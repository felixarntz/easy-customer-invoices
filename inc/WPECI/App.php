<?php
/**
 * @package WPECI
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPECI;

use LaL_WP_Plugin as Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPECI\App' ) ) {
	/**
	 * This class initializes the plugin.
	 *
	 * It also triggers the action and filter to hook into and contains all API functions of the plugin.
	 *
	 * @since 0.5.0
	 */
	final class App extends Plugin {

		/**
		 * @since 0.5.0
		 * @var array Holds the plugin data.
		 */
		protected static $_args = array();

		/**
		 * Class constructor.
		 *
		 * This is protected on purpose since it is called by the parent class' singleton.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $args array of class arguments (passed by the plugin utility class)
		 */
		protected function __construct( $args ) {
			parent::__construct( $args );
		}

		/**
		 * The run() method.
		 *
		 * This will initialize the plugin on the 'after_setup_theme' action.
		 *
		 * @internal
		 * @since 0.5.0
		 */
		protected function run() {
			$path  = ! empty( $_SERVER['REQUEST_URI'] ) ? '/' . ltrim( $_SERVER['REQUEST_URI'], '/' ) : '/';
			$query = ! empty( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';

			$is_rest = defined( 'REST_REQUEST' ) && REST_REQUEST || strpos( $path, '/' . rest_get_url_prefix() ) || strpos( $query, '?rest_route=' );

			if ( ! is_admin() && ! $is_rest ) {
				return;
			}

			Admin::instance()->run();
			API::Instance()->run();
			Stats::init();
		}
	}
}
