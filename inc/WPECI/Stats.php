<?php
/**
 * @package WPECI
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPECI;

use WPECI\Entities\Invoice as Invoice;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPECI\Stats' ) ) {

	final class Stats {
		private $year = null;

		public function __construct( $year ) {
			$this->year = $year;
		}

		public function output_stats() {

		}

		public static function init() {
			add_action( 'wp_loaded', array( __CLASS__, 'maybe_recalculate_invoice_stats' ) );

			add_action( 'wpptd_update_post_meta_value_eci_invoice_contents', array( __CLASS__, 'trigger_change' ), 10, 2 );
			add_action( 'wpptd_update_post_meta_value_eci_invoice_payment_date', array( __CLASS__, 'trigger_change' ), 10, 2 );
			add_action( 'wpptd_update_post_meta_value_eci_invoice_paypal_fee_amount', array( __CLASS__, 'trigger_change' ), 10, 2 );
			add_action( 'wpptd_update_post_meta_value_eci_invoice_currency_factor', array( __CLASS__, 'trigger_change' ), 10, 2 );
		}

		public static function maybe_recalculate_invoice_stats() {
			$changes = get_transient( 'easy_customer_invoices_change_triggers' );
			if ( false === $changes ) {
				return;
			}

			self::recalculate_invoice_stats( absint( $changes ) );

			delete_transient( 'easy_customer_invoices_change_triggers' );
		}

		public static function recalculate_invoice_stats( $id ) {
			$invoice = Invoice::get( $id );
			if ( ! $invoice ) {
				return;
			}

			if ( ! $invoice->get_meta( 'payment_date' ) ) {
				return;
			}

			// calculate results (in base currency)
			$subtotal = $invoice->get_payment_subtotal( true );
			$tax = $invoice->get_payment_tax( true );
			$total = $invoice->get_payment_total( true );
			$fee_amount = $invoice->get_payment_fee_amount( true );

			// adjust global stats
			$global_stats = get_option( '_easy_customer_invoices_global_stats', array() );
			$global_stats = self::update_stats( $global_stats, $id, $subtotal, $tax, $total, $fee_amount );
			update_option( '_easy_customer_invoices_global_stats', $global_stats );

			// adjust year stats
			$year = $invoice->get_data( 'date', 'Y' );
			$year_stats = get_option( '_easy_customer_invoices_year_' . $year . '_stats', array() );
			$year_stats = self::update_stats( $year_stats, $id, $subtotal, $tax, $total, $fee_amount );
			update_option( '_easy_customer_invoices_year_' . $year . '_stats', $year_stats );

			// adjust yearmonth stats
			$yearmonth = $invoice->get_data( 'date', 'Ym' );
			$yearmonth_stats = get_option( '_easy_customer_invoices_yearmonth_' . $yearmonth . '_stats', array() );
			$yearmonth_stats = self::update_stats( $yearmonth_stats, $id, $subtotal, $tax, $total, $fee_amount );
			update_option( '_easy_customer_invoices_yearmonth_' . $yearmonth . '_stats', $yearmonth_stats );

			// adjust customer stats
			$customer = $invoice->get_customer();
			$customer_stats = get_post_meta( $customer->get_ID(), '_easy_customer_invoices_stats', true );
			$customer_stats = self::update_stats( $customer_stats, $id, $subtotal, $tax, $total, $fee_amount );
			update_post_meta( $customer->get_ID(), '_easy_customer_invoices_stats', $customer_stats );
		}

		public static function trigger_change( $new_value, $old_value ) {
			global $post;

			$changes = get_transient( 'easy_customer_invoices_change_triggers' );
			if ( false === $changes ) {
				set_transient( 'easy_customer_invoices_change_triggers', $post->ID, 5 * MINUTE_IN_SECONDS );
			}
		}

		private static function update_stats( $stats, $id, $subtotal, $tax, $total, $fee_amount ) {
			if ( ! is_array( $stats ) ) {
				$stats = array();
			}

			$id = absint( $id );

			$stats[ $id ] = array(
				'subtotal'	=> $subtotal,
				'tax'		=> $tax,
				'total'		=> $total,
				'fee_amount'=> $fee_amount,
			);

			$subtotal = 0.0;
			$tax = 0.0;
			$total = 0.0;
			$fee_amount = 0.0;

			foreach ( $stats as $key => $value ) {
				if ( ! is_integer( $key ) ) {
					continue;
				}

				$subtotal += $value['subtotal'];
				$tax += $value['tax'];
				$total += $value['total'];
				$fee_amount += $value['fee_amount'];
			}

			$stats['subtotal'] = $subtotal;
			$stats['tax'] = $tax;
			$stats['total'] = $total;
			$stats['fee_amount'] = $fee_amount;

			return $stats;
		}
	}

}
