<?php
/**
 * @package WPECI
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPECI\Entities;

use WPECI\Util as Util;

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

		public function get_name() {
			return $this->get_meta( 'first_name' ) . ' ' . $this->get_meta( 'last_name' );
		}

		public function get_company_info( $mode = '', $for_date = null, $logo_size = 'medium' ) {
			$company = Util::get_relevant( $this->get_meta( 'company' ), $for_date );
			if ( empty( $mode ) ) {
				return $company;
			}

			switch ( $mode ) {
				case 'name':
					return $company['name'];
				case 'logo_url':
					return wp_get_attachment_image_url( $company['logo'], $logo_size );
				case 'logo_path':
					return Util::get_attachment_image_path( $company['logo'], $logo_size );
				case 'logo_id':
				default:
					return $company['logo'];
			}
		}

		public function get_address( $mode = '', $for_date = null ) {
			$address = Util::get_relevant( $this->get_meta( 'address' ), $for_date );
			if ( empty( $mode ) ) {
				return $address;
			}

			switch ( $mode ) {
				case 'line1':
					return $address['street_no'];
				case 'line2':
					return $address['zip_code'] . ' ' . $address['city'];
				case 'full':
					return $address['street_no'] . ', ' . $address['zip_code'] . ' ' . $address['city'];
				default:
					if ( ! isset( $address[ $mode ] ) ) {
						return null;
					}
					return $address[ $mode ];
			}
		}

		public function get_contact( $mode = '', $for_date = null ) {
			$contact = Util::get_relevant( $this->get_meta( 'contact' ), $for_date );
			if ( empty( $mode ) ) {
				return $contact;
			}

			if ( ! isset( $contact[ $mode ] ) ) {
				return null;
			}

			return $contact[ $mode ];
		}

		public function get_bank_account_info( $mode = '', $for_date = null ) {
			$bank_account = Util::get_relevant( $this->get_meta( 'bank_account' ), $for_date );
			if ( empty( $mode ) ) {
				return $bank_account;
			}

			if ( ! isset( $bank_account[ $mode ] ) ) {
				return null;
			}

			return $bank_account[ $mode ];
		}

		public function get_legal_info( $mode = '', $for_date = null ) {
			$legal = Util::get_relevant( $this->get_meta( 'legal' ), $for_date );
			if ( empty( $mode ) ) {
				return $legal;
			}

			if ( ! isset( $legal[ $mode ] ) ) {
				return null;
			}

			return $legal[ $mode ];
		}

		public function get_meta( $field = '' ) {
			if ( $field ) {
				if ( function_exists( 'wpod_get_option' ) ) {
					return wpod_get_option( 'easy_customer_invoices_vendor', $field );
				}
				$options = get_option( 'easy_customer_invoices_vendor', array() );
				if ( isset( $options[ $field ] ) ) {
					return $options[ $field ];
				}
				return null;
			} else {
				if ( function_exists( 'wpod_get_options' ) ) {
					return wpod_get_options( 'easy_customer_invoices_vendor' );
				}
				return get_option( 'easy_customer_invoices_vendor', array() );
			}
		}
	}

}
