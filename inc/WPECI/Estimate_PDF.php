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

if ( ! class_exists( 'WPECI\Estimate_PDF' ) ) {

	final class Estimate_PDF extends PDF {
		protected function render_main( $entity, $customer, $country, $vendor ) {
			// Estimate ID
			$this->SetXY( $this->pdf_margin, 85 );
			$this->SetCurrentFont( 'tall' );
			$this->WriteCell( $country->get_meta( 'estimate_id_text' ) . ' ' . $entity->get_data( 'title' ), 'L', 1 );

			// Estimate Meta
			$this->SetCurrentFont( 'default' );
			$this->WriteCell( $country->get_meta( 'estimate_date_text' ) . ' ' . $entity->get_data( 'date', $country->get_meta( 'date_format' ) ), 'L', 1 );
			$this->WriteCell( $country->get_meta( 'estimate_due_date_text' ) . ' ' . date_i18n( $country->get_meta( 'date_format' ), strtotime( $entity->get_meta( 'due_date' ) ) ), 'L', 1 );
			$this->Ln( 8 );

			// Estimate Message
			$message = trim( $entity->get_data( 'message' ) );
			if ( ! empty( $message ) ) {
				$this->WriteMultiCell( $message, 'L', 1 );
				$this->Ln( 8 );
			}

			// Estimate Contents
			$this->WriteCell( '', 'L', 0, 1, 'TLB', true );
			$this->WriteCell( $country->get_meta( 'effort_text' ), 'L', 0, 150, 'TB', true );
			$this->WriteCell( $country->get_meta( 'amount_text' ), 'R', 1, 0, 'TRB', true );
			$this->Cell( 0, 4, '', 0, 1, 'L', false );
			foreach ( $entity->get_meta( 'contents' ) as $content ) {
				$this->WriteCell( '', 'L', 0, 1 );
				$this->WriteCell( $content['effort'], 'L', 0, 150 );
				$this->WriteCell( $entity->format_price( $content['amount'], 'chr' ), 'R', 1, 0 );
			}

			// Estimate Results
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
		}
	}

}
