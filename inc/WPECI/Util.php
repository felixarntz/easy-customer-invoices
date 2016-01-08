<?php
/**
 * @package WPECI
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPECI;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPECI\Util' ) ) {

	final class Util {
		public static function get_currencies( $mode = 'title' ) {
			$_currencies = array(
				'USD'	=> array( __( 'US Dollar (%s)', 'easy-customer-invoices' ), '&#36;', 36 ),
				'EUR'	=> array( __( 'Euro (%s)', 'easy-customer-invoices' ), '&euro;', 128 ),
				'GBP'	=> array( __( 'Pound (%s)', 'easy-customer-invoices' ), '&pound;', 163 ),
				'JPY'	=> array( __( 'Japanese Yen (%s)', 'easy-customer-invoices' ), '&yen;', 165 ),
			);

			$currencies = array();

			switch ( $mode ) {
				case 'chr':
					foreach ( $_currencies as $abbr => $data ) {
						$currencies[ $abbr ] = $data[2];
					}
					break;
				case 'html':
					foreach ( $_currencies as $abbr => $data ) {
						$currencies[ $abbr ] = $data[1];
					}
					break;
				case 'title':
				default:
					foreach ( $_currencies as $abbr => $data ) {
						$currencies[ $abbr ] = sprintf( $data[0], $data[1] );
					}
					break;
			}

			return $currencies;
		}

		public static function get_base_currency() {
			//TODO: get this via option
			return 'EUR';
		}

		public static function get_tax_modes() {
			return array(
				'none'		=> __( 'Do not add tax', 'easy-customer-invoices' ),
				'include'	=> __( 'Include tax', 'easy-customer-invoices' ),
				'add'		=> __( 'Add tax', 'easy-customer-invoices' ),
			);
		}

		public static function get_default_tax_mode() {
			//TODO: get this via option
			return 'add';
		}

		public static function get_payment_methods() {
			return array(
				'deposit'	=> __( 'Deposit', 'easy-customer-invoices' ),
				'paypal'	=> __( 'PayPal', 'easy-customer-invoices' ),
			);
		}
	}

}
