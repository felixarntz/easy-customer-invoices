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

use WPOO\Post as Post;
use WPECI\Util as Util;

if ( ! class_exists( 'WPECI\Entities\Customer' ) ) {

	final class Customer extends Post {
		protected static function get_item( $id = null ) {
			$post = get_post( $id );
			if ( is_object( $post ) && is_a( $post, 'WP_Post' ) && 'eci_customer' === $post->post_type ) {
				return $post;
			}

			return null;
		}

		protected function __construct( $item ) {
			parent::__construct( $item );
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

		public function get_phone_number() {
			$phone_numbers = $this->get_meta( 'phone' );

			if ( 0 < count( $phone_numbers ) ) {
				return $phone_numbers[0]['number'];
			}

			return '';
		}

		public function get_revenue() {
			return floatval( get_post_meta( $this->item->ID, '_total_revenue', true ) );
		}

		public function get_country() {
			$terms = get_the_terms( $this->item->ID, 'eci_country' );
			if ( ! $terms ) {
				return null;
			}

			return Country::get( $terms[0]->term_id );
		}

		public function get_currency( $mode = 'slug' ) {
			$currency = Util::get_base_currency();

			$country = $this->get_country();
			if ( $country ) {
				$currency = $country->get_meta( 'currency' );
			}

			if ( 'slug' === $mode ) {
				return $currency;
			}

			$currencies = Util::get_currencies( $mode );

			return $currencies[ $currency ];
		}

		public function is_reverse_charge() {
			$country = $this->get_country();
			if ( $country ) {
				return $country->get_meta( 'reverse_charge' );
			}

			return false;
		}

		public function get_meta( $field = '', $single = null, $formatted = false ) {
			return parent::get_meta( $field, $single, $formatted );
		}

		public function prepare_for_api() {

		}

		public static function get_api_schema() {
			return array(
				'$schema'		=> 'http://json-schema.org/draft-04/schema#',
				'title'			=> 'customer',
				'type'			=> 'object',
				'properties'	=> array(),
			);
		}
	}

}
