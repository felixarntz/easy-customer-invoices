<?php
/**
 * @package WPECI
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPECI\Entities;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPECI\Entities\Vendor' ) ) {

	final class Vendor {
		private static $items = array();

		public static function get() {
			if ( ! isset( self::$items['default'] ) ) {
				self::$items['default'] = new self();
			}
			return self::$items['default'];
		}

		private function __construct() {

		}
	}

}
