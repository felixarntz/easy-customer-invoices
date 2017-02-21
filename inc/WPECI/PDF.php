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

	abstract class PDF extends FPDF {
		protected $pdf_title = '';
		protected $pdf_margin = 15;

		protected $pdf_fonts = array(
			'default'		=> array( 'Arial', '', 12, 7 ),
			'default_bold'	=> array( 'Arial', 'B', 12, 7 ),
			'tall'			=> array( 'Arial', '', 15, 12 ),
			'tall_bold'		=> array( 'Arial', 'B', 15, 12 ),
			'small'			=> array( 'Arial', '', 7, 5 ),
			'small_bold'	=> array( 'Arial', 'B', 7, 5 ),
			'header'		=> array( 'Arial', '', 15, 7 ),
			'header_bold'	=> array( 'Arial', 'B', 15, 7 ),
		);
		protected $current_font = null;
		protected $current_height = null;

		protected $current_invoice = null;
		protected $current_customer = null;
		protected $current_vendor = null;

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

		public function render( $entity ) {
			$customer = $entity->get_customer();
			$country = $customer->get_country();
			$vendor = Vendor::get();

			$this->AddPage( 'P' );

			$this->render_header( $entity, $customer, $country, $vendor );
			$this->render_main( $entity, $customer, $country, $vendor );
			$this->render_footer( $entity, $customer, $country, $vendor );
		}

		public function finalize( $output_mode = 'I' ) {
			return $this->Output( $this->pdf_title . '.pdf', $output_mode );
		}

		protected function render_header( $entity, $customer, $country, $vendor ) {
			$entity_date = $entity->get_data( 'date', 'Ymd' );

			// Logo
			$logo_path = $vendor->get_company_info( 'logo_path', $entity_date, 'medium' );
			if ( ! empty( $logo_path ) ) {
				$this->Image( $logo_path, $this->pdf_margin, $this->pdf_margin, 80 );
			}

			// Customer ID (and Customer Tax ID)
			$this->SetXY( $this->pdf_margin + 110, 60 );
			$this->SetCurrentFont( 'header' );
			$this->WriteCell( $country->get_meta( 'customer_id_text' ) . ' ' . $customer->get_data( 'title' ), 'R', 2 );
			if ( $customer->get_legal_info( 'tax_id', $entity_date ) ) {
				$this->WriteCell( $country->get_meta( 'customer_tax_id_text' ) . ' ' . $customer->get_legal_info( 'tax_id', $entity_date ), 'R', 2 );
			}

			// Customer Address
			$this->SetXY( $this->pdf_margin, 60 );
			$this->SetCurrentFont( 'header_bold' );
			if ( ! empty( $customer->get_meta( 'company_name' ) ) ) {
				$this->WriteCell( $customer->get_meta( 'company_name' ), 'L', 2 );
			} else {
				$this->WriteCell( $customer->get_meta( 'first_name' ) . ' ' . $customer->get_meta( 'last_name' ), 'L', 2 );
			}
			$this->WriteCell( $customer->get_address( 'line1', $entity_date ), 'L', 2 );
			$this->WriteCell( $customer->get_address( 'line2', $entity_date ), 'L', 2 );
		}

		protected abstract function render_main( $entity, $customer, $country, $vendor );

		protected function render_footer( $entity, $customer, $country, $vendor ) {
			$entity_date = $entity->get_data( 'date', 'Ymd' );

			$left_col = array();
			if ( ( $vendor_company_name = $vendor->get_company_info( 'name', $entity_date ) ) ) {
				$left_col[] = $vendor_company_name;
			}
			if ( ( $vendor_address_line1 = trim( $vendor->get_address( 'line1', $entity_date ) ) ) ) {
				$left_col[] = $vendor_address_line1;
			}
			if ( ( $vendor_address_line2 = trim( $vendor->get_address( 'line2', $entity_date ) ) ) ) {
				$left_col[] = $vendor_address_line2;
			}
			if ( ( $vendor_phone = $vendor->get_contact( 'phone', $entity_date ) ) ) {
				$left_col[] = $country->get_meta( 'vendor_phone_text' ) . ' ' . $vendor_phone;
			}
			if ( ( $vendor_email = $vendor->get_contact( 'email', $entity_date ) ) ) {
				$left_col[] = $country->get_meta( 'vendor_email_text' ) . ' ' . $vendor_email;
			}
			if ( ( $vendor_website = $vendor->get_contact( 'website', $entity_date ) ) ) {
				$left_col[] = $country->get_meta( 'vendor_website_text' ) . ' ' . $vendor_website;
			}

			$center_col = array();
			if ( ( $vendor_bank_name = $vendor->get_bank_account_info( 'bank_name', $entity_date ) ) ) {
				$center_col[] = $vendor_bank_name;
			}
			if ( ( $vendor_bank_account = $vendor->get_bank_account_info( 'account_number', $entity_date ) ) ) {
				$center_col[] = $country->get_meta( 'vendor_bank_account_text' ) . ' ' . $vendor_bank_account;
			}
			if ( ( $vendor_bank_code = $vendor->get_bank_account_info( 'bank_code', $entity_date ) ) ) {
				$center_col[] = $country->get_meta( 'vendor_bank_code_text' ) . ' ' . $vendor_bank_code;
			}
			if ( ( $vendor_iban = $vendor->get_bank_account_info( 'iban', $entity_date ) ) ) {
				$center_col[] = 'IBAN: ' . $vendor_iban;
			}
			if ( ( $vendor_bic = $vendor->get_bank_account_info( 'bic', $entity_date ) ) ) {
				$center_col[] = 'BIC (SWIFT): ' . $vendor_bic;
			}

			$right_col = array();
			if ( ( $vendor_name = $vendor->get_name() ) ) {
				$right_col[] = $country->get_meta( 'vendor_owner_text' ) . ' ' . $vendor_name;
			}
			if ( ( $vendor_tax_number = $vendor->get_legal_info( 'tax_number', $entity_date ) ) ) {
				$right_col[] = $country->get_meta( 'vendor_tax_number_text' ) . ' ' . $vendor_tax_number;
			}
			if ( ( $vendor_tax_id = $vendor->get_legal_info( 'tax_id', $entity_date ) ) ) {
				$right_col[] = $country->get_meta( 'vendor_tax_id_text' ) . ' ' . $vendor_tax_id;
			}
			if ( ( $vendor_tax_authority = $vendor->get_legal_info( 'tax_authority', $entity_date ) ) ) {
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

		protected function WriteCell( $text, $align, $ln, $width = 0, $border = 0, $fill = false ) {
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

		protected function WriteMultiCell( $text, $align, $ln, $width = 0, $border = 0, $fill = false, $line_count = 0 ) {
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

		protected function SetCurrentFont( $name ) {
			if ( isset( $this->pdf_fonts[ $name ] ) ) {
				$this->SetFont( $this->pdf_fonts[ $name ][0], $this->pdf_fonts[ $name ][1], $this->pdf_fonts[ $name ][2] );
				$this->current_font = $name;
				$this->current_height = $this->pdf_fonts[ $name ][3];
			}
		}

		protected function SetColor( $color ) {
			if ( is_string( $color ) ) {
				$color = $this->HexToRGB( $color );
			}
			$this->SetTextColor( $color[0], $color[1], $color[2] );
			$this->SetDrawColor( $color[0], $color[1], $color[2] );
		}

		protected function SetBackgroundColor( $color ) {
			if ( is_string( $color ) ) {
				$color = $this->HexToRGB( $color );
			}
			$this->SetFillColor( $color[0], $color[1], $color[2] );
		}

		protected function HexToRGB( $hex ) {
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

		protected function EncodeValue( $value ) {
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
