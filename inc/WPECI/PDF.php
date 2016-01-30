<?php
/**
 * @package WPECI
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPECI;

use FPDF as FPDF;
use WPECI\Entities\Vendor as Vendor;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPECI\PDF' ) ) {

	final class PDF extends FPDF {
		private $pdf_title = '';
		private $pdf_margin = 15;

		private $pdf_fonts = array(
			'default'		=> array( 'Arial', '', 12, 7 ),
			'default_bold'	=> array( 'Arial', 'B', 12, 7 ),
			'tall'			=> array( 'Arial', '', 15, 12 ),
			'tall_bold'		=> array( 'Arial', 'B', 15, 12 ),
			'small'			=> array( 'Arial', '', 7, 5 ),
			'small_bold'	=> array( 'Arial', 'B', 7, 5 ),
			'header'		=> array( 'Arial', '', 15, 7 ),
			'header_bold'	=> array( 'Arial', 'B', 15, 7 ),
		);
		private $current_font = null;
		private $current_height = null;

		private $current_invoice = null;
		private $current_customer = null;
		private $current_vendor = null;

		public function __construct( $title ) {
			$this->pdf_title = $title;

			parent::__construct( 'P', 'mm', 'A4' );

			$this->SetTitle( $this->pdf_title );
			$this->SetAutoPageBreak( false );
			$this->SetMargins( $this->pdf_margin, $this->pdf_margin, $this->pdf_margin );
			$this->SetColor( Util::get_pdf_text_color() );
			$this->SetBackgroundColor( Util::get_pdf_fill_color() );
			$this->SetCurrentFont( 'default' );
		}

		public function render( $invoice ) {
			$customer = $invoice->get_customer();
			$country = $customer->get_country();
			$vendor = Vendor::get();

			$invoice_date = $invoice->get_data( 'date', 'Ymd' );

			$this->AddPage( 'P' );

			// Logo
			$this->Image( $vendor->get_company_info( 'logo_path', $invoice_date, 'medium' ), $this->pdf_margin, $this->pdf_margin, 80 );

			// Customer ID (and Customer Tax ID)
			$this->SetXY( $this->pdf_margin + 110, 60 );
			$this->SetCurrentFont( 'header' );
			$this->WriteCell( $country->get_meta( 'customer_id_text' ) . ' ' . $customer->get_data( 'title' ), 'R', 2 );
			if ( $customer->get_legal_info( 'tax_id', $invoice_date ) ) {
				$this->WriteCell( $country->get_meta( 'customer_tax_id_text' ) . ' ' . $customer->get_legal_info( 'tax_id', $invoice_date ), 'R', 2 );
			}

			// Customer Address
			$this->SetXY( $this->pdf_margin, 60 );
			$this->SetCurrentFont( 'header_bold' );
			if ( ! empty( $customer->get_meta( 'company_name' ) ) ) {
				$this->WriteCell( $customer->get_meta( 'company_name' ), 'L', 2 );
			} else {
				$this->WriteCell( $customer->get_meta( 'first_name' ) . ' ' . $customer->get_meta( 'last_name' ), 'L', 2 );
			}
			$this->WriteCell( $customer->get_address( 'line1', $invoice_date ), 'L', 2 );
			$this->WriteCell( $customer->get_address( 'line2', $invoice_date ), 'L', 2 );

			// Invoice ID
			$this->SetXY( $this->pdf_margin, 85 );
			$this->SetCurrentFont( 'tall' );
			$this->WriteCell( $country->get_meta( 'invoice_id_text' ) . ' ' . $invoice->get_data( 'title' ), 'L', 1 );

			// Invoice Meta
			$this->SetCurrentFont( 'default' );
			$this->WriteCell( $country->get_meta( 'reference_text' ) . ' ' . Util::get_reference_prefix() . $invoice->get_data( 'title' ), 'L', 1 );
			if ( ! empty( $invoice->get_meta( 'service_period' ) ) ) {
				$this->WriteCell( $country->get_meta( 'service_period_text' ) . ' ' . $invoice->get_meta( 'service_period' ), 'L', 1 );
			}
			$this->WriteCell( $country->get_meta( 'invoice_date_text' ) . ' ' . $invoice->get_data( 'date', $country->get_meta( 'date_format' ) ), 'L', 1 );
			$this->Ln( 8 );

			// Invoice Contents
			$this->WriteCell( '', 'L', 0, 1, 'TLB', true );
			$this->WriteCell( $country->get_meta( 'effort_text' ), 'L', 0, 150, 'TB', true );
			$this->WriteCell( $country->get_meta( 'amount_text' ), 'R', 1, 0, 'TRB', true );
			$this->Cell( 0, 4, '', 0, 1, 'L', false );
			foreach ( $invoice->get_meta( 'contents' ) as $content ) {
				$this->WriteCell( '', 'L', 0, 1 );
				$this->WriteCell( $content['effort'], 'L', 0, 150 );
				$this->WriteCell( $invoice->format_price( $content['amount'], 'chr' ), 'R', 1, 0 );
			}

			// Invoice Results
			$this->SetCurrentFont( 'default_bold' );
			$this->WriteCell( $country->get_meta( 'subtotal_text' ), 'R', 0, 151 );
			$this->WriteCell( $invoice->format_price( $invoice->get_subtotal(), 'chr' ), 'R', 1, 0 );
			if ( 'none' !== $invoice->get_meta( 'tax_mode' ) && ! $customer->is_reverse_charge() ) {
				$this->WriteCell( sprintf( $country->get_meta( 'tax_text' ), Util::get_tax_amount() ), 'R', 0, 151 );
				$this->WriteCell( $invoice->format_price( $invoice->get_tax(), 'chr' ), 'R', 1, 0 );
			}
			$this->SetCurrentFont( 'tall_bold' );
			$this->WriteCell( $country->get_meta( 'total_text' ), 'R', 0, 151 );
			$this->WriteCell( $invoice->format_price( $invoice->get_total(), 'chr' ), 'R', 1, 0 );
			$this->Ln( 8 );

			// Tax Notes
			$this->SetCurrentFont( 'default' );
			if ( 'none' !== $invoice->get_meta( 'tax_mode' ) && $customer->is_reverse_charge() ) {
				$this->WriteMultiCell( $country->get_meta( 'reverse_charge_text' ) . ' ' . sprintf( $country->get_meta( 'tax_amount_text' ), Util::get_tax_amount() ), 'L', 1 );
			} elseif ( 'none' === $invoice->get_meta( 'tax_mode' ) ) {
				$this->WriteMultiCell( $country->get_meta( 'no_tax_text' ), 'L', 1 );
			}

			// Payment Notes
			if ( $invoice->get_meta( 'payment_date' ) ) {
				$this->WriteMultiCell( sprintf( $country->get_meta( 'paid_text' ), $invoice->get_meta( 'payment_date', null, array( 'format' => $country->get_meta( 'date_format' ) ) ), $invoice->get_payment_method_name() ) . ' ' . $country->get_meta( 'thank_you_text' ), 'L', 1 );
				$this->Ln();
				$amount_note = '';
				if ( 'paypal' === $invoice->get_meta( 'payment_method' ) ) {
					$amount_note .= sprintf( $country->get_meta( 'paypal_fee_text' ), Util::format_price( $invoice->get_payment_fee_amount( true ), 'chr' ) );
				}
				if ( $country->get_meta( 'currency' ) !== Util::get_base_currency() ) {
					if ( ! empty( $amount_note ) ) {
						$amount_note .= ' ';
					}
					$amount_note .= sprintf( $country->get_meta( 'revenue_text' ), Util::format_price( $invoice->get_payment_total( true ), 'chr' ) );
				}
				$this->WriteMultiCell( $amount_note, 'L', 1 );
			} else {
				$this->WriteMultiCell( $country->get_meta( 'pay_text' ) . ' ' . $country->get_meta( 'thank_you_text' ), 'L', 1 );
			}

			$left_col = array();
			if ( ( $vendor_company_name = $vendor->get_company_info( 'name', $invoice_date ) ) ) {
				$left_col[] = $vendor_company_name;
			}
			if ( ( $vendor_address_line1 = trim( $vendor->get_address( 'line1', $invoice_date ) ) ) ) {
				$left_col[] = $vendor_address_line1;
			}
			if ( ( $vendor_address_line2 = trim( $vendor->get_address( 'line2', $invoice_date ) ) ) ) {
				$left_col[] = $vendor_address_line2;
			}
			if ( ( $vendor_phone = $vendor->get_contact( 'phone', $invoice_date ) ) ) {
				$left_col[] = $country->get_meta( 'vendor_phone_text' ) . ' ' . $vendor_phone;
			}
			if ( ( $vendor_email = $vendor->get_contact( 'email', $invoice_date ) ) ) {
				$left_col[] = $country->get_meta( 'vendor_email_text' ) . ' ' . $vendor_email;
			}
			if ( ( $vendor_website = $vendor->get_contact( 'website', $invoice_date ) ) ) {
				$left_col[] = $country->get_meta( 'vendor_website_text' ) . ' ' . $vendor_website;
			}

			$center_col = array();
			if ( ( $vendor_bank_name = $vendor->get_bank_account_info( 'bank_name', $invoice_date ) ) ) {
				$center_col[] = $vendor_bank_name;
			}
			if ( ( $vendor_bank_account = $vendor->get_bank_account_info( 'account_number', $invoice_date ) ) ) {
				$center_col[] = $country->get_meta( 'vendor_bank_account_text' ) . ' ' . $vendor_bank_account;
			}
			if ( ( $vendor_bank_code = $vendor->get_bank_account_info( 'bank_code', $invoice_date ) ) ) {
				$center_col[] = $country->get_meta( 'vendor_bank_code_text' ) . ' ' . $vendor_bank_code;
			}
			if ( ( $vendor_iban = $vendor->get_bank_account_info( 'iban', $invoice_date ) ) ) {
				$center_col[] = 'IBAN: ' . $vendor_iban;
			}
			if ( ( $vendor_bic = $vendor->get_bank_account_info( 'bic', $invoice_date ) ) ) {
				$center_col[] = 'BIC (SWIFT): ' . $vendor_bic;
			}

			$right_col = array();
			if ( ( $vendor_name = $vendor->get_name() ) ) {
				$right_col[] = $country->get_meta( 'vendor_owner_text' ) . ' ' . $vendor_name;
			}
			if ( ( $vendor_tax_number = $vendor->get_legal_info( 'tax_number', $invoice_date ) ) ) {
				$right_col[] = $country->get_meta( 'vendor_tax_number_text' ) . ' ' . $vendor_tax_number;
			}
			if ( ( $vendor_tax_id = $vendor->get_legal_info( 'tax_id', $invoice_date ) ) ) {
				$right_col[] = $country->get_meta( 'vendor_tax_id_text' ) . ' ' . $vendor_tax_id;
			}
			if ( ( $vendor_tax_authority = $vendor->get_legal_info( 'tax_authority', $invoice_date ) ) ) {
				$right_col[] = $vendor_tax_authority;
			}

			$this->SetXY( $this->pdf_margin, -50 );
			$this->SetCurrentFont( 'small' );
			$this->WriteCell( '', 'L', 0, 1, 'T' );
			$this->WriteMultiCell( implode( "\n", $left_col ), 'L', 1, 60, 'T' );
			$this->SetXY( $this->pdf_margin + 61, -50 );
			$this->WriteMultiCell( implode( "\n", $center_col ), 'L', 1, 60, 'T' );
			$this->SetXY( $this->pdf_margin + 121, -50 );
			$this->WriteMultiCell( implode( "\n", $right_col ), 'L', 1, 0, 'T' );
		}

		public function finalize( $output_mode = 'I' ) {
			return $this->Output( $this->pdf_title . '.pdf', $output_mode );
		}

		private function WriteCell( $text, $align, $ln, $width = 0, $border = 0, $fill = false ) {
			if ( ! empty( $text ) ) {
				$text = $this->EncodeValue( $text );
			}

			$orig_font_size = $font_size = $this->FontSizePt;
			if ( 0 < $width ) {
				while ( $this->GetStringWidth( $text ) > $width ) {
					$this->SetFontSize( $font_size -= 0.2 );
				}
			}

			$this->Cell( $width, $this->current_height, $text, $border, $ln, $align, $fill );

			if ( $orig_font_size !== $font_size ) {
				$this->SetFontSize( $orig_font_size );
			}
		}

		private function WriteMultiCell( $text, $align, $ln, $width = 0, $border = 0, $fill = false, $line_count = 0 ) {
			if ( ! empty( $text ) ) {
				$text = $this->EncodeValue( $text );
			}

			$orig_font_size = $font_size = $this->FontSizePt;
			if ( 0 < $width && 0 < $line_count ) {
				while ( $this->GetStringWidth( $text ) > $width * $line_count ) {
					$this->SetFontSize( $font_size -= 0.2 );
				}
			}

			$this->MultiCell( $width, $this->current_height, $text, $border, $align, $fill );

			if ( $orig_font_size !== $font_size ) {
				$this->SetFontSize( $orig_font_size );
			}

			if ( 0 < $ln ) {
				$this->Ln( $this->current_height );
			}
		}

		private function SetCurrentFont( $name ) {
			if ( isset( $this->pdf_fonts[ $name ] ) ) {
				$this->SetFont( $this->pdf_fonts[ $name ][0], $this->pdf_fonts[ $name ][1], $this->pdf_fonts[ $name ][2] );
				$this->current_font = $name;
				$this->current_height = $this->pdf_fonts[ $name ][3];
			}
		}

		private function SetColor( $color ) {
			if ( is_string( $color ) ) {
				$color = $this->HexToRGB( $color );
			}
			$this->SetTextColor( $color[0], $color[1], $color[2] );
			$this->SetDrawColor( $color[0], $color[1], $color[2] );
		}

		private function SetBackgroundColor( $color ) {
			if ( is_string( $color ) ) {
				$color = $this->HexToRGB( $color );
			}
			$this->SetFillColor( $color[0], $color[1], $color[2] );
		}

		private function HexToRGB( $hex ) {
			$hex = str_replace( '#', '', $hex );
			$rgb = array();

			if ( 3 === strlen( $hex ) ) {
				$rgb[] = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
				$rgb[] = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
				$rgb[] = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
			} else {
				$rgb[] = hexdec( substr( $hex, 0, 2 ) );
				$rgb[] = hexdec( substr( $hex, 2, 2 ) );
				$rgb[] = hexdec( substr( $hex, 4, 2 ) );
			}

			return $rgb;
		}

		private function EncodeValue( $value ) {
			if ( is_float( $value ) ) {
				return number_format_i18n( $value, 2 );
			} elseif ( is_int( $value ) ) {
				return number_format_i18n( $value, 0 );
			} else {
				$value = html_entity_decode( $value, ENT_NOQUOTES );
				if ( function_exists( 'iconv' ) ) {
					$value = iconv( 'UTF-8', 'ISO8859-1', $value );
				}
				$value = preg_replace_callback( '/\[CHR([0-9]+)\]/', function( $matches ) {
					if ( isset( $matches[1] ) ) {
						return chr( absint( $matches[1] ) );
					}
					return '';
				}, $value );
				return $value;
			}
		}
	}

}
