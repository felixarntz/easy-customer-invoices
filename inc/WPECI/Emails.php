<?php
/**
 * @package WPECI
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPECI;

use WPECI\Entities\Invoice;
use WPECI\Entities\Estimate;
use WPECI\Entities\Vendor;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPECI\Emails' ) ) {

	final class Emails {
		private $tags = array();

		private $current_entity = null;

		private $current_customer = null;

		private $current_vendor = null;

		private static $instance = null;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private function __construct() {
			$this->register_default_tags();
		}

		public function register_tag( $name, $description, $callback ) {
			$this->tags[ $name ] = array(
				'description' => $description,
				'callback'    => $callback,
			);
		}

		public function get_registered_tag_descriptions() {
			$descriptions = array();

			foreach ( $this->tags as $name => $data ) {
				$descriptions[ $name ] = $data['description'];
			}

			return $descriptions;
		}

		public function send_invoice( $id ) {
			$this->current_entity   = Invoice::get( $id );
			$this->current_customer = $this->current_entity->get_customer();
			$this->current_vendor   = Vendor::get();

			$pdf = new Invoice_PDF( $this->current_entity->get_data( 'title' ) );
			$pdf->render( $this->current_entity );
			$pdf_output = $pdf->finalize( 'S' );

			$footer = $this->build_email_footer();

			$to      = $this->current_customer->get_meta( 'email' );
			$subject = $this->process_subject( Util::get_invoice_email_subject() );
			$message = $this->process_message( Util::get_invoice_email_message() );
			$args    = array(
				'attachments'      => array( $pdf_output ),
				'from'             => $this->current_vendor->get_contact( 'email' ),
				'from_name'        => $this->current_vendor->get_company_info( 'name' ),
				'header_image'     => $this->current_vendor->get_company_info( 'logo_url' ),
				'background_color' => Util::get_invoice_email_background_color(),
				'highlight_color'  => Util::get_invoice_email_highlight_color(),
				'footer'           => $footer,
			);

			$email = new Email( $to, $subject, $message, $args );
			$status = $email->send();

			$this->current_entity   = null;
			$this->current_customer = null;
			$this->current_vendor   = null;

			return $status;
		}

		public function send_estimate( $id ) {
			$this->current_entity   = Estimate::get( $id );
			$this->current_customer = $this->current_entity->get_customer();
			$this->current_vendor   = Vendor::get();

			$pdf = new Invoice_PDF( $this->current_entity->get_data( 'title' ) );
			$pdf->render( $this->current_entity );
			$pdf_output = $pdf->finalize( 'S' );

			$footer = $this->build_email_footer();

			$to      = $this->current_customer->get_meta( 'email' );
			$subject = $this->process_subject( Util::get_estimate_email_subject() );
			$message = $this->process_message( Util::get_estimate_email_message() );
			$args    = array(
				'attachments'      => array( $pdf_output ),
				'from'             => $this->current_vendor->get_contact( 'email' ),
				'from_name'        => $this->current_vendor->get_company_info( 'name' ),
				'header_image'     => $this->current_vendor->get_company_info( 'logo_url' ),
				'background_color' => Util::get_estimate_email_background_color(),
				'highlight_color'  => Util::get_estimate_email_highlight_color(),
				'footer'           => $footer,
			);

			$email = new Email( $to, $subject, $message, $args );
			$status = $email->send();

			$this->current_entity   = null;
			$this->current_customer = null;
			$this->current_vendor   = null;

			return $status;
		}

		private function build_email_footer() {
			$main_name = $this->current_vendor->get_name();
			$sub_name  = $this->current_vendor->get_company_info( 'name' );
			if ( empty( $main_name ) ) {
				$main_name = $sub_name;
				$sub_name = '';
			}

			$footer = '<p><strong>' . $main_name . '</strong></p>';

			$footer .= '<p>';
			if ( ! empty( $sub_name ) ) {
				$footer .= $sub_name . '<br>';
			}
			$footer .= $this->current_vendor->get_address( 'line1' ) . '<br>';
			$footer .= $this->current_vendor->get_address( 'line2' );
			$footer .= '</p>';

			$footer .= '<p>';
			$contact_data = $this->current_vendor->get_contact();
			foreach ( $contact_data as $key => $value ) {
				if ( ! empty( $value ) ) {
					switch ( $key ) {
						case 'phone':
							$footer .= __( 'Phone:', 'easy-customer-invoices' ) . ' ' . $value . '<br>';
							break;
						case 'email':
							$footer .= __( 'Email:', 'easy-customer-invoices' ) . ' ' . $value . '<br>';
							break;
						case 'website':
							$footer .= __( 'Website:', 'easy-customer-invoices' ) . ' ' . $value . '<br>';
							break;
					}
				}
			}
			$footer .= '</p>';

			return $footer;
		}

		private function process_subject( $subject ) {
			$subject = preg_replace_callback( "/{([A-z0-9\-\_]+)}/s", array( $this, 'process_tag' ), $subject );
			$subject = strip_tags( $subject );

			return $subject;
		}

		private function process_message( $message ) {
			$message = preg_replace_callback( "/{([A-z0-9\-\_]+)}/s", array( $this, 'process_tag' ), $message );
			$message = wpautop( $message );

			return $message;
		}

		private function process_tag( $matches ) {
			$tag = $matches[1];

			if ( ! isset( $this->tags[ $tag ] ) ) {
				return $matches[0];
			}

			return call_user_func( $this->tags[ $tag ]['callback'], $this->current_entity, $this->current_customer, $this->current_vendor );
		}

		private function register_default_tags() {
			$this->register_tag( 'invoice_id', __( 'Displays the invoice ID.', 'easy-customer-invoices' ), array( $this, 'tag_entity_id' ) );
			$this->register_tag( 'invoice_total', __( 'Displays the total amount of the invoice.', 'easy-customer-invoices' ), array( $this, 'tag_entity_total' ) );
			$this->register_tag( 'estimate_id', __( 'Displays the estimate ID.', 'easy-customer-invoices' ), array( $this, 'tag_entity_id' ) );
			$this->register_tag( 'estimate_total', __( 'Displays the total amount of the estimate.', 'easy-customer-invoices' ), array( $this, 'tag_entity_total' ) );
			$this->register_tag( 'customer_id', __( 'Displays the customer ID.', 'easy-customer-invoices' ), array( $this, 'tag_customer_id' ) );
			$this->register_tag( 'customer_name', __( 'Displays the first and last name of the customer.', 'easy-customer-invoices' ), array( $this, 'tag_customer_name' ) );
			$this->register_tag( 'customer_company_name', __( 'Displays the company name of the customer.', 'easy-customer-invoices' ), array( $this, 'tag_customer_company_name' ) );
			$this->register_tag( 'vendor_name', __( 'Displays the first and last name of the vendor.', 'easy-customer-invoices' ), array( $this, 'tag_vendor_name' ) );
			$this->register_tag( 'vendor_company_name', __( 'Displays the company name of the vendor.', 'easy-customer-invoices' ), array( $this, 'tag_vendor_company_name' ) );
			$this->register_tag( 'pay_within_days', __( 'Displays the number of days in which the invoice needs to be paid.', 'easy-customer-invoices' ), array( $this, 'tag_pay_within_days' ) );
		}

		private function tag_entity_id( $entity, $customer, $vendor ) {
			return $entity->get_data( 'title' );
		}

		private function tag_entity_total( $entity, $customer, $vendor ) {
			return $entity->format_price( $entity->get_total() );
		}

		private function tag_customer_id( $invoice, $customer, $vendor ) {
			return $customer->get_data( 'title' );
		}

		private function tag_customer_name( $invoice, $customer, $vendor ) {
			return $customer->get_meta( 'first_name' ) . ' ' . $customer->get_meta( 'last_name' );
		}

		private function tag_customer_company_name( $invoice, $customer, $vendor ) {
			return $customer->get_meta( 'company_name' );
		}

		private function tag_vendor_name( $invoice, $customer, $vendor ) {
			return $vendor->get_name();
		}

		private function tag_vendor_company_name( $invoice, $customer, $vendor ) {
			return $vendor->get_company_info( 'name' );
		}

		private function tag_pay_within_days( $invoice, $customer, $vendor ) {
			return Util::get_pay_within_days();
		}
	}

}
