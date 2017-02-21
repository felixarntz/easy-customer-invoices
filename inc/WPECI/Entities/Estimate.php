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

if ( ! class_exists( 'WPECI\Entities\Estimate' ) ) {

	final class Estimate extends Post {
		protected $subtotal = null;
		protected $tax = null;
		protected $total = null;

		protected static function get_item( $id = null ) {
			$post = get_post( $id );
			if ( is_object( $post ) && is_a( $post, 'WP_Post' ) && 'eci_estimate' === $post->post_type ) {
				return $post;
			}

			return null;
		}

		protected function __construct( $item ) {
			parent::__construct( $item );
		}

		public function get_subtotal() {
			if ( null === $this->subtotal ) {
				$this->calculate_results();
			}
			return $this->subtotal;
		}

		public function get_tax() {
			if ( null === $this->tax ) {
				$this->calculate_results();
			}
			return $this->tax;
		}

		public function get_total() {
			if ( null === $this->total ) {
				$this->calculate_results();
			}
			return $this->total;
		}

		public function get_customer() {
			return Customer::get( $this->get_meta( 'customer' ) );
		}

		public function get_country() {
			$customer = $this->get_customer();
			if ( ! $customer ) {
				return null;
			}

			return $customer->get_country();
		}

		public function get_currency( $mode = 'slug' ) {
			$currency = Util::get_base_currency();

			if ( 'slug' === $mode ) {
				return $currency;
			}

			$currencies = Util::get_currencies( $mode );

			return $currencies[ $currency ];
		}

		public function is_reverse_charge() {
			$country = $this->get_country();
			if ( ! $country ) {
				return false;
			}

			return $country->is_reverse_charge();
		}

		public function format_price( $price, $currency_mode = 'html' ) {
			$decimal_separator = '.';
			$thousands_separator = ',';
			$currency = $this->get_currency( $currency_mode );

			$country = $this->get_country();
			if ( $country ) {
				$decimal_separator = $country->get_meta( 'decimal_separator' );
				$thousands_separator = $country->get_meta( 'thousands_separator' );
			}

			if ( 'chr' === $currency_mode ) {
				$currency = '[CHR' . $currency . ']';
			}

			return number_format( floatval( $price ), 2, $decimal_separator, $thousands_separator ) . ' ' . $currency;
		}

		protected function calculate_results() {
			$contents = $this->get_meta( 'contents' );

			$amount = 0.0;
			foreach ( $contents as $content ) {
				$amount += $content['amount'];
			}

			$tax_mode = $this->get_meta( 'tax_mode' );
			$tax_rate = Util::get_tax_amount();

			$customer = $this->get_customer();
			if ( $customer && $customer->is_reverse_charge() ) {
				$tax_mode = 'none';
			}

			switch ( $tax_mode ) {
				case 'add':
					$this->subtotal = $amount;
					$this->tax = round( $amount * ( $tax_rate * 0.01 ), 2 );
					$this->total = $this->subtotal + $this->tax;
					break;
				case 'include':
					$this->total = $amount;
					$this->subtotal = round( $amount / ( 1.0 + $tax_rate * 0.01 ), 2 );
					$this->tax = $this->total - $this->subtotal;
					break;
				case 'none':
				default:
					$this->total = $this->subtotal = $amount;
					$this->tax = 0.0;
			}
		}

		public function get_meta( $field = '', $single = null, $formatted = false ) {
			return parent::get_meta( $field, $single, $formatted );
		}

		public function prepare_for_api() {
			$data = array(
				'id'				=> $this->get_ID(),
				'type'				=> 'eci_estimate',
				'title'				=> $this->get_data( 'title', true ),
				'date'				=> $this->get_data( 'date', true ),
				'due_date'			=> $this->get_meta( 'due_date' ),
				'message'			=> $this->get_meta( 'message' ),
				'contents'			=> $this->get_meta( 'contents' ),
				'tax_mode'			=> $this->get_meta( 'tax_mode' ),
				'currency'			=> $this->get_currency(),
				'subtotal'			=> $this->get_subtotal(),
				'total'				=> $this->get_total(),
				'tax'				=> $this->get_tax(),
				'customer'			=> null,
			);

			$customer = $this->get_customer();
			if ( $customer ) {
				$data['customer'] = $customer->prepare_for_api();
			}

			return $data;
		}

		public static function get_api_schema() {
			return array(
				'$schema'		=> 'http://json-schema.org/draft-04/schema#',
				'title'			=> 'estimate',
				'type'			=> 'object',
				'properties'	=> array(),
			);
		}
	}

}
