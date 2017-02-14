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
			global $wp_locale;

			$fields = array(
				'subtotal'   => __( 'Subtotal', 'easy-customer-invoices' ),
				'tax'        => __( 'Tax', 'easy-customer-invoices' ),
				'total'      => __( 'Total', 'easy-customer-invoices' ),
				'fee_amount' => __( 'Fees', 'easy-customer-invoices' ),
			);

			$cumulative = array();

			foreach ( $fields as $slug => $label ) {
				$cumulative[ $slug ] = 0.0;
			}

			$results = array();

			$label_label = '';

			if ( 'all' === $this->year ) {
				$label_label = __( 'Year', 'easy-customer-invoices' );

				$years = Util::get_years();

				foreach ( $years as $year ) {
					$results[ $year ] = get_option( '_easy_customer_invoices_year_' . $year . '_stats', array() );
					$results[ $year ]['label'] = $year;

					foreach ( $fields as $slug => $label ) {
						if ( isset( $results[ $year ][ $slug ] ) ) {
							$cumulative[ $slug ] += $results[ $year ][ $slug ];
						}
					}
				}
			} else {
				$label_label = __( 'Month', 'easy-customer-invoices' );

				for ( $i = 1; $i <= 12; $i++ ) {
					$month = zeroise( $i, 2 );

					$results[ $month ] = get_option( '_easy_customer_invoices_yearmonth_' . $this->year . $month . '_stats', array() );
					$results[ $month ]['label'] = $wp_locale->month[ $month ];

					foreach ( $fields as $slug => $label ) {
						if ( isset( $results[ $month ][ $slug ] ) ) {
							$cumulative[ $slug ] += $results[ $month ][ $slug ];
						}
					}
				}
			}

			?>
			<div id="results-wrap" class="superwrap">
				<div id="chart-wrap" class="wrap-primary">
					<div id="chart" class="results-chart"></div>
				</div>
				<div id="table-wrap" class="wrap-secondary">
					<table class="results-table">
						<thead>
							<tr>
								<th><?php echo $label_label; ?></th>
								<?php foreach ( $fields as $slug => $label ) : ?>
									<th><?php echo $label; ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $results as $data ) : ?>
								<tr>
									<th scope="row"><?php echo $data['label']; ?></th>
									<?php foreach ( $fields as $slug => $label ) :
										$amount = isset( $data[ $slug ] ) ? $data[ $slug ] : 0.0;
									?>
										<td class="amount-cell"><?php echo Util::format_price( $amount ); ?></td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<th scope="row"><?php _e( 'Cumulative', 'easy-customer-invoices' ); ?></th>
								<?php foreach ( $fields as $slug => $label ) : ?>
									<td class="amount-cell"><?php echo Util::format_price( $cumulative[ $slug ] ); ?></td>
								<?php endforeach; ?>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
			<?php

			$currency = Util::get_base_currency();
			$currencies = Util::get_currencies( 'chr' );

			$script_data = array(
				'amount_label' => __( 'Amount', 'easy-customer-invoices' ),
				'result_label' => $label_label,
				'currency'     => chr( $currencies[ $currency ] ),
				'fields'       => $fields,
				'results'      => array_values( $results ),
			);

			wp_localize_script( 'easy-customer-invoices-stats', 'eci_stats_data', $script_data );
		}

		public static function enqueue_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'd3', App::get_url( 'assets/vendor/d3/d3' . $suffix . '.js' ), array(), '3.5.0', true );
			wp_enqueue_script( 'c3', App::get_url( 'assets/vendor/c3/c3' . $suffix . '.js' ), array( 'd3' ), '0.4.11', true );
			wp_enqueue_script( 'easy-customer-invoices-stats', App::get_url( 'assets/stats' . $suffix . '.js' ), array( 'jquery', 'c3', 'd3' ), App::get_info( 'version' ), true );

			wp_enqueue_style( 'c3', App::get_url( 'assets/vendor/c3/c3' . $suffix . '.css' ), array(), '0.4.11' );
			wp_enqueue_style( 'easy-customer-invoices-stats', App::get_url( 'assets/stats.css' ), array(), App::get_info( 'version' ) );
		}

		public static function init() {
			add_action( 'wp_loaded', array( __CLASS__, 'maybe_recalculate_invoice_stats' ) );

			add_action( 'wpptd_update_post_meta_value_eci_invoice_contents', array( __CLASS__, 'trigger_change' ), 10, 2 );
			add_action( 'wpptd_update_post_meta_value_eci_invoice_payment_date', array( __CLASS__, 'trigger_change' ), 10, 2 );
			add_action( 'wpptd_update_post_meta_value_eci_invoice_deposit_fee_amount', array( __CLASS__, 'trigger_change' ), 10, 2 );
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
