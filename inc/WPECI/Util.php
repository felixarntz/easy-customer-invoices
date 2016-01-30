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

		public static function get_tax_modes() {
			return array(
				'none'		=> __( 'Do not add tax', 'easy-customer-invoices' ),
				'include'	=> __( 'Include tax', 'easy-customer-invoices' ),
				'add'		=> __( 'Add tax', 'easy-customer-invoices' ),
			);
		}

		public static function get_payment_methods() {
			return array(
				'deposit'	=> __( 'Deposit', 'easy-customer-invoices' ),
				'paypal'	=> __( 'PayPal', 'easy-customer-invoices' ),
			);
		}

		public static function get_reference_prefix() {
			return wpod_get_option( 'easy_customer_invoices_data', 'reference_prefix' );
		}

		public static function get_base_currency() {
			return wpod_get_option( 'easy_customer_invoices_data', 'base_currency' );
		}

		public static function get_default_tax_mode() {
			return wpod_get_option( 'easy_customer_invoices_data', 'default_tax_mode' );
		}

		public static function get_default_payment_method() {
			return wpod_get_option( 'easy_customer_invoices_data', 'default_payment_method' );
		}

		public static function get_tax_amount() {
			return wpod_get_option( 'easy_customer_invoices_data', 'tax_amount' );
		}

		public static function get_pdf_text_color() {
			return wpod_get_option( 'easy_customer_invoices_data', 'text_color' );
		}

		public static function get_pdf_fill_color() {
			return wpod_get_option( 'easy_customer_invoices_data', 'fill_color' );
		}

		public static function get_relevant( $data, $reference_date = null, $date_field = 'valid_from' ) {
			if ( null === $reference_date ) {
				$reference_date = current_time( 'Ymd' );
			}

			if ( 0 === count( $data ) ) {
				return array();
			}

			$key = count( $data ) - 1;
			while ( 0 <= $key && $reference_date < $data[ $key ][ $date_field ] ) {
				$key--;
			}

			if ( 0 > $key ) {
				$key = 0;
			}

			unset( $data[ $key ][ $date_field ] );
			return $data[ $key ];
		}

		public static function get_years() {
			global $wpdb;

			$years = get_transient( 'easy_customer_invoices_years' );
			if ( false === $years ) {
				$query = "SELECT DISTINCT Year( post_date ) AS year FROM $wpdb->posts WHERE post_type = %s ORDER BY post_date DESC";

				$years = $wpdb->get_col( $wpdb->prepare( $query, 'eci_invoice' ) );
				if ( ! $years ) {
					$years = array( absint( date( 'Y' ) ) );
				} else {
					$years = array_map( 'absint', $years );
				}

				set_transient( 'easy_customer_invoices_years', json_encode( $years ), WEEK_IN_SECONDS );
			} else {
				$years = json_decode( $years );
			}

			return $years;
		}

		public static function get_attachment_image_path( $attachment_id, $size = 'thumbnail' ) {
			$url = wp_get_attachment_image_url( $attachment_id, $size, false );
			$upload_dir = wp_upload_dir();

			return str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
		}

		public static function format_price( $price, $currency_mode = 'html' ) {
			$currency = self::get_base_currency();
			$currencies = self::get_currencies( $currency_mode );

			if ( 'chr' === $currency_mode ) {
				$currencies[ $currency ] = '[CHR' . $currencies[ $currency ] . ']';
			}

			return number_format_i18n( $price, 2 ) . ' ' . $currencies[ $currency ];
		}

		public static function make_invoice_id( $year = null ) {
			if ( null === $year ) {
				$year = current_time( 'Y' );
			}

			$invoices = get_posts( array(
				'posts_per_page'	=> 1,
				'post_type'			=> 'eci_invoice',
				'post_status'		=> 'publish',
				'year'				=> absint( $year ),
				'orderby'			=> 'date',
				'order'				=> 'DESC',
			) );

			$prefix = 'RE';
			$num_pad = 4;

			$count = 1;
			if ( 0 < count( $invoices ) ) {
				$count += absint( substr( get_the_title( $invoices[0]->ID ), strlen( $prefix ), $num_pad ) );
			}

			return $prefix . zeroise( $count, $num_pad ) . '-' . $year;
		}

		public static function make_customer_id( $first_name, $last_name, $year = null, $old_id = null ) {
			if ( null === $year ) {
				$year = current_time( 'Y' );
			}

			if ( null === $old_id ) {
				$customers = get_posts( array(
					'posts_per_page'	=> 1,
					'post_type'			=> 'eci_customer',
					'post_status'		=> 'publish',
					'orderby'			=> 'date',
					'order'				=> 'DESC',
				) );

				$count = 1;
				if ( 0 < count( $customers ) ) {
					$count += absint( substr( get_the_title( $customers[0]->ID ), 6, 4 ) );
				}

				$year = substr( $year, 2, 2 );
			} else {
				$count = absint( substr( $old_id, 6, 4 ) );
				$year = substr( $old_id, 0, 2 );
			}

			$first_name = self::word_to_number( $first_name );
			$last_name = self::word_to_number( $last_name );

			$checksum = absint( substr( $year, 0, 1 ) ) + absint( substr( $year, 1, 1 ) ) + $first_name + $last_name + $count;

			return zeroise( $year, 2 ) . zeroise( $first_name, 2 ) . zeroise( $last_name, 2 ) . zeroise( $count, 4 ) . ( $checksum % 10 );
		}

		private static function word_to_number( $word ) {
			$letter = substr( strtolower( $word ), 0, 1 );
			switch ( $letter ) {
				case utf8_decode( chr( 195 ) . chr( 164 ) ):
					return 27;
				case utf8_decode( chr( 195 ) . chr( 182 ) ):
					return 28;
				case utf8_decode( chr( 195 ) . chr( 188 ) ):
					return 28;
				case utf8_decode( chr( 195 ) . chr( 159 ) ):
					return 29;
				default:
					return ord( $letter ) - 96;
			}
		}
	}

}
