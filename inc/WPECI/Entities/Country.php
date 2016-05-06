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

use WPOO\Term as Term;

if ( ! class_exists( 'WPECI\Entities\Country' ) ) {

	final class Country extends Term {
		protected static function get_item( $id = null ) {
			if ( null !== $id ) {
				$term = get_term( $id );
				if ( is_object( $term ) && is_a( $term, 'WP_Term' ) && 'eci_country' === $term->taxonomy ) {
					return $term;
				}
			}

			return null;
		}

		protected function __construct( $item ) {
			parent::__construct( $item );
		}

		public function get_meta( $field = '', $single = null, $formatted = false ) {
			return parent::get_meta( $field, $single, $formatted );
		}

		public function prepare_for_api() {
			$data = array(
				'id'					=> $this->get_ID(),
				'type'					=> 'eci_country',
				'title'					=> $this->get_data( 'name' ),
				'currency'				=> $this->get_meta( 'currency' ),
				'decimal_separator'		=> $this->get_meta( 'decimal_separator' ),
				'thousands_separator'	=> $this->get_meta( 'thousands_separator' ),
				'date_format'			=> $this->get_meta( 'date_format' ),
				'reverse_charge'		=> $this->get_meta( 'reverse_charge' ),
			);

			return $data;
		}
	}

}
