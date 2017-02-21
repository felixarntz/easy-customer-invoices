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

if ( ! class_exists( 'WPECI\Invoice_PDF' ) ) {

	final class Invoice_PDF extends PDF {
		protected function render_main( $entity, $customer, $country, $vendor ) {
			// Invoice ID
			$this->SetXY( $this->pdf_margin, 85 );
			$this->SetCurrentFont( 'tall' );
			$this->WriteCell( $country->get_meta( 'invoice_id_text' ) . ' ' . $entity->get_data( 'title' ), 'L', 1 );

			// Invoice Meta
			$this->SetCurrentFont( 'default' );
			$this->WriteCell( $country->get_meta( 'reference_text' ) . ' ' . Util::get_reference_prefix() . $entity->get_data( 'title' ), 'L', 1 );
			if ( ! empty( $entity->get_meta( 'service_period' ) ) ) {
				$this->WriteCell( $country->get_meta( 'service_period_text' ) . ' ' . $entity->get_meta( 'service_period' ), 'L', 1 );
			}
			$this->WriteCell( $country->get_meta( 'invoice_date_text' ) . ' ' . $entity->get_data( 'date', $country->get_meta( 'date_format' ) ), 'L', 1 );
			$this->Ln( 8 );

			// Invoice Contents
			$this->WriteCell( '', 'L', 0, 1, 'TLB', true );
			$this->WriteCell( $country->get_meta( 'effort_text' ), 'L', 0, 150, 'TB', true );
			$this->WriteCell( $country->get_meta( 'amount_text' ), 'R', 1, 0, 'TRB', true );
			$this->Cell( 0, 4, '', 0, 1, 'L', false );
			foreach ( $entity->get_meta( 'contents' ) as $content ) {
				$this->WriteCell( '', 'L', 0, 1 );
				$this->WriteCell( $content['effort'], 'L', 0, 150 );
				$this->WriteCell( $entity->format_price( $content['amount'], 'chr' ), 'R', 1, 0 );
			}

			// Invoice Results
			$this->SetCurrentFont( 'default_bold' );
			$this->WriteCell( $country->get_meta( 'subtotal_text' ), 'R', 0, 151 );
			$this->WriteCell( $entity->format_price( $entity->get_subtotal(), 'chr' ), 'R', 1, 0 );
			if ( 'none' !== $entity->get_meta( 'tax_mode' ) && ! $customer->is_reverse_charge() ) {
				$this->WriteCell( sprintf( $country->get_meta( 'tax_text' ), Util::get_tax_amount() ), 'R', 0, 151 );
				$this->WriteCell( $entity->format_price( $entity->get_tax(), 'chr' ), 'R', 1, 0 );
			}
			$this->SetCurrentFont( 'tall_bold' );
			$this->WriteCell( $country->get_meta( 'total_text' ), 'R', 0, 151 );
			$this->WriteCell( $entity->format_price( $entity->get_total(), 'chr' ), 'R', 1, 0 );
			$this->Ln( 8 );

			// Tax Notes
			$this->SetCurrentFont( 'default' );
			if ( 'none' !== $entity->get_meta( 'tax_mode' ) && $customer->is_reverse_charge() ) {
				$this->WriteMultiCell( $country->get_meta( 'reverse_charge_text' ) . ' ' . sprintf( $country->get_meta( 'tax_amount_text' ), Util::get_tax_amount() ), 'L', 1 );
			} elseif ( 'none' === $entity->get_meta( 'tax_mode' ) ) {
				$this->WriteMultiCell( $country->get_meta( 'no_tax_text' ), 'L', 1 );
			}

			// Payment Notes
			if ( $entity->get_meta( 'payment_date' ) ) {
				$this->WriteMultiCell( sprintf( $country->get_meta( 'paid_text' ), $entity->get_meta( 'payment_date', null, array( 'format' => $country->get_meta( 'date_format' ) ) ), $entity->get_payment_method_name() ) . ' ' . $country->get_meta( 'thank_you_text' ), 'L', 1 );
				$this->Ln();
				$payment_total = $entity->get_payment_total( true );
				$amount_note = '';
				if ( $country->get_meta( 'currency' ) !== Util::get_base_currency() ) {
					$amount_note .= sprintf( $country->get_meta( 'total_base_currency_text' ), Util::format_price( $payment_total, 'chr' ) );
				}
				$payment_fee = $entity->get_payment_fee_amount( true );
				if ( 0.0 < $payment_fee ) {
					if ( ! empty( $amount_note ) ) {
						$amount_note .= ' ';
					}
					if ( 'paypal' === $entity->get_meta( 'payment_method' ) ) {
						$amount_note .= sprintf( $country->get_meta( 'paypal_fee_text' ), Util::format_price( $payment_fee, 'chr' ) );
						$amount_note .= ' ' . sprintf( $country->get_meta( 'revenue_text' ), Util::format_price( $payment_total - $payment_fee, 'chr' ) );
					} elseif ( 'deposit' === $entity->get_meta( 'payment_method' ) ) {
						$amount_note .= sprintf( $country->get_meta( 'deposit_fee_text' ), Util::format_price( $payment_fee, 'chr' ) );
						$amount_note .= ' ' . sprintf( $country->get_meta( 'revenue_text' ), Util::format_price( $payment_total - $payment_fee, 'chr' ) );
					}
				}
				$this->WriteMultiCell( $amount_note, 'L', 1 );
			} else {
				$this->WriteMultiCell( sprintf( $country->get_meta( 'pay_text' ), Util::get_pay_within_days() ) . ' ' . $country->get_meta( 'thank_you_text' ), 'L', 1 );
			}
		}
	}

}
