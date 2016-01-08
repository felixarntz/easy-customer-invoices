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

if ( ! class_exists( 'WPECI\Admin' ) ) {

	final class Admin {
		private static $instance = null;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private function __construct() {}

		public function run() {
			add_action( 'wpptd', array( $this, 'add_post_types_and_taxonomies' ) );
			add_action( 'wpod', array( $this, 'add_options' ) );
		}

		public function add_post_types_and_taxonomies( $wpptd ) {
			$wpptd->add_components( array(
				'easy_customer_invoices_menu'	=> array(
					'label'							=> __( 'Invoice Manager', 'easy-customer-invoices' ),
					'icon'							=> 'dashicons-clipboard',
					'post_types'					=> array(
						'eci_invoice'					=> array(
							'title'							=> __( 'Invoices', 'easy-customer-invoices' ),
							'singular_title'				=> __( 'Invoice', 'easy-customer-invoices' ),
							'enter_title_here'				=> __( 'Enter invoice ID', 'easy-customer-invoices' ),
							'public'						=> false,
							'show_ui'						=> true,
							'show_in_admin_bar'				=> false,
							'supports'						=> array( 'title' ),
							'position'						=> 5,
							'table_columns'					=> array(),
							'row_actions'					=> array(),
							'bulk_actions'					=> array(),
							'metaboxes'						=> array(
								'general'						=> array(
									'title'							=> __( 'General Information', 'easy-customer-invoices' ),
									'description'					=> __( 'Here you can enter general information for the invoice.', 'easy-customer-invoices' ),
									'context'						=> 'normal',
									'priority'						=> 'high',
									'fields'						=> array(
										'customer'						=> array(
											'title'							=> __( 'Customer ID', 'easy-customer-invoices' ),
											'type'							=> 'select',
											'options'						=> array(
												'posts'							=> 'eci_customer',
											),
										),
									),
								),
								'content'						=> array(
									'title'							=> __( 'Invoice Content', 'easy-customer-invoices' ),
									'description'					=> __( 'Here you can enter the actual efforts charged for in this invoice.', 'easy-customer-invoices' ),
									'context'						=> 'normal',
									'priority'						=> 'high',
									'fields'						=> array(
										'contents'						=> array(
											'title'							=> __( 'Contents', 'easy-customer-invoices' ),
											'type'							=> 'repeatable',
											'repeatable'					=> array(
												'limit'							=> 3,
												'fields'						=> array(
													'effort'						=> array(
														'title'							=> __( 'Effort', 'easy-customer-invoices' ),
														'type'							=> 'text',
													),
													'amount'						=> array(
														'title'							=> __( 'Amount', 'easy-customer-invoices' ),
														'type'							=> 'number',
														'min'							=> 10.0,
														'step'							=> 0.01,
													),
												),
											),
										),
										'tax_mode'						=> array(
											'title'							=> __( 'Tax Mode', 'easy-customer-invoices' ),
											'type'							=> 'select',
											'options'						=> Util::get_tax_modes(),
											'default'						=> Util::get_default_tax_mode(),
										),
									),
								),
								'payment'						=> array(
									'title'							=> __( 'Payment', 'easy-customer-invoices' ),
									'description'					=> __( 'Here you can payment-related data for this invoice.', 'easy-customer-invoices' ),
									'context'						=> 'normal',
									'priority'						=> 'default',
									'fields'						=> array(
										'payment_date'					=> array(
											'title'							=> __( 'Payment Date', 'easy-customer-invoices' ),
											'type'							=> 'date',
										),
										'payment_method'				=> array(
											'title'							=> __( 'Payment Method', 'easy-customer-invoices' ),
											'type'							=> 'select',
											'options'						=> Util::get_payment_methods(),
										),
										'paypal_fee_amount'				=> array(
											'title'							=> __( 'PayPal Fee Amount', 'easy-customer-invoices' ),
											'description'					=> __( 'Enter the amount that PayPal charged for the payment.', 'easy-customer-invoices' ),
											'type'							=> 'number',
											'min'							=> 1.0,
											'step'							=> 0.01,
										),
										'currency_factor'				=> array(
											'title'							=> __( 'Currency Factor', 'easy-customer-invoices' ),
											'description'					=> __( 'Enter the factor that you have to divide the amount by to calculate it in your base currency.', 'easy-customer-invoices' ),
											'type'							=> 'number',
											'min'							=> 0.01,
											'step'							=> 0.0001,
										),
									),
								),
							),
						),
						'eci_customer'					=> array(
							'title'							=> __( 'Customers', 'easy-customer-invoices' ),
							'singular_title'				=> __( 'Customer', 'easy-customer-invoices' ),
							'enter_title_here'				=> __( 'Enter customer name', 'easy-customer-invoices' ),
							'public'						=> false,
							'show_ui'						=> true,
							'show_in_admin_bar'				=> false,
							'supports'						=> array( 'title' ),
							'position'						=> 10,
							'table_columns'					=> array(),
							'metaboxes'						=> array(
								'general'						=> array(
									'title'							=> __( 'General Information', 'easy-customer-invoices' ),
									'description'					=> __( 'Here you can enter static information for the customer that will likely never change.', 'easy-customer-invoices' ),
									'context'						=> 'normal',
									'priority'						=> 'high',
									'fields'						=> array(
										'first_name'					=> array(
											'title'							=> __( 'First Name', 'easy-customer-invoices' ),
											'type'							=> 'text',
											'required'						=> true,
										),
										'last_name'						=> array(
											'title'							=> __( 'Last Name', 'easy-customer-invoices' ),
											'type'							=> 'text',
											'required'						=> true,
										),
										'company_name'					=> array(
											'title'							=> __( 'Company Name', 'easy-customer-invoices' ),
											'type'							=> 'text',
										),
									),
								),
								'details'						=> array(
									'title'							=> __( 'Details', 'easy-customer-invoices' ),
									'description'					=> __( 'Specify more detailed information about the customer. The following information may change from time to time - if it does, simply add a new row and fill in the information.', 'easy-customer-invoices' ),
									'context'						=> 'normal',
									'priority'						=> 'default',
									'fields'						=> array(
										'address'						=> array(
											'title'							=> __( 'Address', 'easy-customer-invoices' ),
											'type'							=> 'repeatable',
											'repeatable'					=> array(
												'fields'						=> array(
													'street_no'						=> array(
														'title'							=> __( 'Street & No.', 'easy-customer-invoices' ),
														'type'							=> 'text',
													),
													'zip_code'						=> array(
														'title'							=> __( 'ZIP Code', 'easy-customer-invoices' ),
														'type'							=> 'text',
													),
													'city'							=> array(
														'title'							=> __( 'City', 'easy-customer-invoices' ),
														'type'							=> 'text',
													),
													'valid_from'					=> array(
														'title'							=> __( 'Valid from...', 'easy-customer-invoices' ),
														'type'							=> 'date',
													),
												),
											),
										),
										'legal'							=> array(
											'title'							=> __( 'Legal Data', 'easy-customer-invoices' ),
											'type'							=> 'repeatable',
											'repeatable'					=> array(
												'fields'						=> array(
													'tax_number'					=> array(
														'title'							=> __( 'Tax Number', 'easy-customer-invoices' ),
														'type'							=> 'text',
													),
													'tax_id'						=> array(
														'title'							=> __( 'Tax ID', 'easy-customer-invoices' ),
														'type'							=> 'text',
													),
													'valid_from'					=> array(
														'title'							=> __( 'Valid from...', 'easy-customer-invoices' ),
														'type'							=> 'date',
													),
												),
											),
										),
									),
								),
								'contact'						=> array(
									'title'							=> __( 'Contact Methods', 'easy-customer-invoices' ),
									'description'					=> __( 'Add some contact methods to the customer.', 'easy-customer-invoices' ),
									'context'						=> 'side',
									'priority'						=> 'high',
									'fields'						=> array(
										'email'							=> array(
											'title'							=> __( 'Email Address', 'easy-customer-invoices' ),
											'type'							=> 'email',
										),
										'phone'							=> array(
											'title'							=> __( 'Phone', 'easy-customer-invoices' ),
											'type'							=> 'repeatable',
											'repeatable'					=> array(
												'fields'						=> array(
													'label'							=> array(
														'title'							=> __( 'Label', 'easy-customer-invoices' ),
														'type'							=> 'text',
													),
													'number'						=> array(
														'title'							=> __( 'Phone Number', 'easy-customer-invoices' ),
														'type'							=> 'tel',
													),
												),
											),
										),
									),
								),
							),
							'taxonomies'					=> array(
								'eci_country'					=> array(
									'title'							=> __( 'Countries', 'easy-customer-invoices' ),
									'singular_title'				=> __( 'Country', 'easy-customer-invoices' ),
									'public'						=> false,
									'show_ui'						=> true,
									'show_tagcloud'					=> false,
									'hierarchical'					=> false,
									'metaboxes'						=> array(
										'general'						=> array(
											'title'							=> __( 'General Information', 'easy-customer-invoices' ),
											'description'					=> __( 'Here you can enter general information related to this country.', 'easy-customer-invoices' ),
											'context'						=> 'normal',
											'priority'						=> 'high',
											'fields'						=> array(
												'currency'						=> array(
													'title'							=> __( 'Currency', 'easy-customer-invoices' ),
													'type'							=> 'select',
													'options'						=> Util::get_currencies(),
													'default'						=> Util::get_base_currency(),
												),
												'reverse_charge'				=> array(
													'title'							=> __( 'Reverse Charge', 'easy-customer-invoices' ),
													'type'							=> 'checkbox',
													'label'							=> __( 'Is this country reverse charge eligible?', 'easy-customer-invoices' ),
													'default'						=> false,
												),
											),
										),
										'invoice_text'					=> array(
											'title'							=> __( 'Invoice Texts', 'easy-customer-invoices' ),
											'description'					=> __( 'Here you can enter some text for the different sections of an invoice. These sentences should be localized for this country&#039;s language.', 'easy-customer-invoices' ),
											'context'						=> 'normal',
											'priority'						=> 'default',
											'fields'						=> array(
												'tax_amount_text'				=> array(
													'title'							=> __( 'Tax Amount', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'The tax rate is %s %.',
												),
												'no_tax_text'					=> array(
													'title'							=> __( 'Small Business Regulation', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'Due to the small business regulation in §19 UStG no additional tax is charged.',
												),
												'reverse_charge_text'			=> array(
													'title'							=> __( 'Reverse Charge', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'Due to §13b UStG the recipient is liable for payment of the tax (reverse charge).',
												),
												'paid_text'						=> array(
													'title'							=> __( 'Paid on X with Y', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'The invoice has been paid on %1$s via %2$s.',
												),
											),
										),
									),
								),
							),
						),
					),
				),
			), 'easy-customer-invoices' );
		}

		public function add_options( $wpod ) {
			$wpod->add_components( array(
				'easy_customer_invoices_menu'	=> array(
					'label'							=> __( 'Invoice Manager', 'easy-customer-invoices' ),
					'icon'							=> 'dashicons-clipboard',
					'screens'						=> array(
						'easy_customer_invoices_settings'=> array(
							'title'							=> __( 'Settings', 'easy-customer-invoices' ),
							'label'							=> __( 'Settings', 'easy-customer-invoices' ),
							'position'						=> 50,
							'tabs'							=> array(
								'easy_customer_invoices_vendor'	=> array(
									'title'							=> __( 'Vendor Data', 'easy-customer-invoices' ),
									'description'					=> __( 'Here you should enter information about your own business or about yourself as a vendor.', 'easy-customer-invoices' ),
									'mode'							=> 'draggable',
									'sections'						=> array(
										'general'						=> array(
											'title'							=> __( 'General Information', 'easy-customer-invoices' ),
											'description'					=> __( 'Here you can enter static information for your business that will likely never change.', 'easy-customer-invoices' ),
											'fields'						=> array(
												'first_name'					=> array(
													'title'							=> __( 'First Name', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'required'						=> true,
												),
												'last_name'						=> array(
													'title'							=> __( 'Last Name', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'required'						=> true,
												),
												'company_name'					=> array(
													'title'							=> __( 'Company Name', 'easy-customer-invoices' ),
													'type'							=> 'text',
												),
											),
										),
										//'details'						=> array(),
										//'contact'						=> array(),
									),
								),
								//'easy_customer_invoices_data'	=> array(),
							),
						),
					),
				),
			), 'easy-customer-invoices' );
		}
	}

}
