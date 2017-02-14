<?php
/**
 * @package WPECI
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPECI;

use WPECI\Entities\Invoice as Invoice;
use WPECI\Entities\Customer as Customer;

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

			add_action( 'wpptd_post_type_eci_invoice_edit_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wpptd_post_type_eci_customer_edit_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_action( 'admin_notices', array( $this, 'show_overdue_invoices_notice' ) );

			add_action( 'wpod_field_after', array( $this, 'wpod_field_after' ), 10, 4 );

			add_action( 'wp_ajax_wpeci_make_invoice_id', array( $this, 'ajax_make_invoice_id' ) );
			add_action( 'wp_ajax_wpeci_make_customer_id', array( $this, 'ajax_make_customer_id' ) );

			add_filter( 'get_object_terms', array( $this, 'set_default_country' ), 10, 4 );
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
							'show_add_new_in_menu'			=> false,
							'supports'						=> array( 'title' ),
							'position'						=> 5,
							'table_columns'					=> array(
								'custom-customer'				=> array(
									'title'							=> __( 'Customer', 'easy-customer-invoices' ),
									'custom_callback'				=> array( $this, 'render_invoice_customer_column' ),
								),
								'custom-amount'					=> array(
									'title'							=> __( 'Amount', 'easy-customer-invoices' ),
									'custom_callback'				=> array( $this, 'render_invoice_amount_column' ),
								),
								'meta-payment_date'				=> array(
									'title'							=> __( 'Payment Date', 'easy-customer-invoices' ),
								),
							),
							'row_actions'					=> array(
								'show_pdf'						=> array(
									'title'							=> __( 'Show PDF Invoice', 'easy-customer-invoices' ),
									'callback'						=> array( $this, 'show_pdf_invoice' ),
								),
								'send_invoice'					=> array(
									'title'							=> __( 'Send Invoice via Email', 'easy-customer-invoices' ),
									'callback'						=> array( $this, 'send_invoice_email' ),
								),
							),
							'bulk_actions'					=> array(
								'show_pdf'						=> array(
									'title'							=> __( 'Show PDF Invoices', 'easy-customer-invoices' ),
									'callback'						=> array( $this, 'show_pdf_invoices' ),
								),
							),
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
											'options'						=> Util::get_customer_dropdown(),
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
												'limit'							=> 6,
												'fields'						=> array(
													'effort'						=> array(
														'title'							=> __( 'Effort', 'easy-customer-invoices' ),
														'type'							=> 'select',
														'options'                       => Util::get_efforts_dropdown(),
													),
													'amount'						=> array(
														'title'							=> __( 'Amount', 'easy-customer-invoices' ),
														'type'							=> 'number',
														'min'							=> 0.0,
														'step'							=> 0.01,
													),
												),
											),
										),
										'service_period'				=> array(
											'title'							=> __( 'Service Period', 'easy-customer-invoices' ),
											'type'							=> 'text',
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
										'deposit_fee_amount'			=> array(
											'title'							=> __( 'Deposit Fee Amount', 'easy-customer-invoices' ),
											'description'					=> __( 'Enter the deposit fee (if any).', 'easy-customer-invoices' ),
											'type'							=> 'number',
											'min'							=> 0.0,
											'step'							=> 0.01,
										),
										'paypal_fee_amount'				=> array(
											'title'							=> __( 'PayPal Fee Amount', 'easy-customer-invoices' ),
											'description'					=> __( 'Enter the amount that PayPal charged for the payment.', 'easy-customer-invoices' ),
											'type'							=> 'number',
											'min'							=> 0.0,
											'step'							=> 0.01,
										),
										'currency_factor'				=> array(
											'title'							=> __( 'Currency Factor', 'easy-customer-invoices' ),
											'description'					=> __( 'Enter the factor that you have to multiply the amount with to calculate it in your base currency.', 'easy-customer-invoices' ),
											'type'							=> 'number',
											'default'						=> 1.0,
											'min'							=> 0.0,
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
							'show_add_new_in_menu'			=> false,
							'supports'						=> array( 'title' ),
							'position'						=> 10,
							'table_columns'					=> array(
								'custom-name'					=> array(
									'title'							=> __( 'Name', 'easy-customer-invoices' ),
									'custom_callback'				=> array( $this, 'render_customer_name_column' ),
								),
								'custom-address'				=> array(
									'title'							=> __( 'Address', 'easy-customer-invoices' ),
									'custom_callback'				=> array( $this, 'render_customer_address_column' ),
								),
								'custom-contact'				=> array(
									'title'							=> __( 'Contact', 'easy-customer-invoices' ),
									'custom_callback'				=> array( $this, 'render_customer_contact_column' ),
								),
								'custom-revenue'				=> array(
									'title'							=> __( 'Revenue', 'easy-customer-invoices' ),
									'custom_callback'				=> array( $this, 'render_customer_revenue_column' ),
								),
							),
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
												'decimal_separator'				=> array(
													'title'							=> __( 'Decimal Separator', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> '.',
												),
												'thousands_separator'			=> array(
													'title'							=> __( 'Thousands Separator', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> ',',
												),
												'date_format'					=> array(
													'title'							=> __( 'Date Format', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Y/m/d',
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
												'customer_id_text'				=> array(
													'title'							=> __( 'Customer ID', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Your customer ID:',
												),
												'customer_tax_id_text'			=> array(
													'title'							=> __( 'Customer Tax ID', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Your Tax ID:',
												),
												'invoice_id_text'				=> array(
													'title'							=> __( 'Invoice ID', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Invoice ID:',
												),
												'reference_text'				=> array(
													'title'							=> __( 'Reference', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Reference:',
												),
												'service_period_text'			=> array(
													'title'							=> __( 'Service Period', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Service Period:',
												),
												'invoice_date_text'				=> array(
													'title'							=> __( 'Invoice Date', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Invoice Date:',
												),
												'effort_text'					=> array(
													'title'							=> __( 'Effort', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Effort',
												),
												'amount_text'					=> array(
													'title'							=> __( 'Amount', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Amount',
												),
												'subtotal_text'					=> array(
													'title'							=> __( 'Subtotal', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Subtotal:',
												),
												'tax_text'						=> array(
													'title'							=> __( 'Tax', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> '%s %% Tax:',
												),
												'total_text'					=> array(
													'title'							=> __( 'Total', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Total:',
												),
												'reverse_charge_text'			=> array(
													'title'							=> __( 'Reverse Charge', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'Due to §13b UStG the recipient is liable for payment of the tax (reverse charge).',
												),
												'tax_amount_text'				=> array(
													'title'							=> __( 'Tax Amount', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'The tax rate is %s %%.',
												),
												'no_tax_text'					=> array(
													'title'							=> __( 'Small Business Regulation', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'Due to the small business regulation in §19 UStG no tax is being charged.',
												),
												'pay_text'						=> array(
													'title'							=> __( 'Please Pay', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'Please pay the invoice within %d days and ensure to use the correct reference.',
												),
												'paid_text'						=> array(
													'title'							=> __( 'Paid on X with Y', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'The invoice has been paid on %1$s via %2$s.',
												),
												'thank_you_text'				=> array(
													'title'							=> __( 'Thank You', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'Thank you for your purchase!',
												),
												'total_base_currency_text'		=> array(
													'title'							=> __( 'Total in Base Currency', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'The total amount is %s.',
												),
												'deposit_fee_text'				=> array(
													'title'							=> __( 'Deposit Fee', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'A fee of %s has been charged for the deposit.',
												),
												'paypal_fee_text'				=> array(
													'title'							=> __( 'PayPal Fee', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'A fee of %s has been collected by PayPal.',
												),
												'revenue_text'					=> array(
													'title'							=> __( 'Revenue', 'easy-customer-invoices' ),
													'type'							=> 'textarea',
													'rows'							=> 2,
													'default'						=> 'The overall revenue is %s.',
												),
												'vendor_phone_text'				=> array(
													'title'							=> __( 'Vendor Phone', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Phone:',
												),
												'vendor_email_text'				=> array(
													'title'							=> __( 'Vendor Email', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Email:',
												),
												'vendor_website_text'			=> array(
													'title'							=> __( 'Vendor Website', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Website:',
												),
												'vendor_bank_account_text'		=> array(
													'title'							=> __( 'Vendor Bank Account', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Bank Account:',
												),
												'vendor_bank_code_text'			=> array(
													'title'							=> __( 'Vendor Bank Code', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Bank Code:',
												),
												'vendor_owner_text'				=> array(
													'title'							=> __( 'Vendor Owner', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'CEO:',
												),
												'vendor_tax_number_text'		=> array(
													'title'							=> __( 'Vendor Tax Number', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Tax Number:',
												),
												'vendor_tax_id_text'			=> array(
													'title'							=> __( 'Vendor Tax ID', 'easy-customer-invoices' ),
													'type'							=> 'text',
													'default'						=> 'Tax ID:',
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
			$years = Util::get_years();
			$year_tabs = array();

			add_action( 'wpod_screen_easy_customer_invoices_stats_enqueue_scripts', array( 'WPECI\Stats', 'enqueue_scripts' ) );

			$stats_manager = new Stats( 'all' );
			$year_tabs['easy_customer_invoices_stats_all'] = array(
				'title'       => __( 'All Years', 'easy-customer-invoices' ),
				'description' => __( 'Here you can find detailed stats about your business for all years.', 'easy-customer-invoices' ),
				'callback'    => array( $stats_manager, 'output_stats' ),
			);

			foreach( $years as $year ) {
				$stats_manager = new Stats( $year );
				$year_tabs[ 'easy_customer_invoices_stats_' . $year ] = array(
					'title'			=> $year,
					'description'	=> sprintf( __( 'Here you can find detailed stats about your business in %s.', 'easy-customer-invoices' ), $year ),
					'callback'		=> array( $stats_manager, 'output_stats' ),
				);
			}

			$default_email_message = sprintf( __( 'Hello %s,', 'easy-customer-invoices' ), '{customer_name}' ) . "\n\n";
			$default_email_message .= sprintf( __( 'You will find your invoice %1$s attached to this email. Please pay it within %2$s days.', 'easy-customer-invoices' ), '{invoice_id}', '{pay_within_days}' ) . "\n";
			$default_email_message .= sprintf( __( 'Thanks for purchasing at %s.', 'easy-customer-invoices' ), '{vendor_company_name}' ) . "\n\n";
			$default_email_message .= __( 'Regards,', 'easy-customer-invoices' ) . "\n";
			$default_email_message .= '{vendor_company_name}';

			$wpod->add_components( array(
				'easy_customer_invoices_menu'	=> array(
					'label'							=> __( 'Invoice Manager', 'easy-customer-invoices' ),
					'icon'							=> 'dashicons-clipboard',
					'screens'						=> array(
						'easy_customer_invoices_stats'	=> array(
							'title'							=> __( 'Stats', 'easy-customer-invoices' ),
							'label'							=> __( 'Stats', 'easy-customer-invoices' ),
							'position'						=> 40,
							'tabs'							=> $year_tabs,
						),
						'easy_customer_invoices_options'=> array(
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
												'company'						=> array(
													'title'							=> __( 'Company Information', 'easy-customer-invoices' ),
													'type'							=> 'repeatable',
													'repeatable'					=> array(
														'fields'						=> array(
															'name'							=> array(
																'title'							=> __( 'Name', 'easy-customer-invoices' ),
																'type'							=> 'text',
															),
															'logo'							=> array(
																'title'							=> __( 'Logo', 'easy-customer-invoices' ),
																'type'							=> 'media',
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
										'details'						=> array(
											'title'							=> __( 'Details', 'easy-customer-invoices' ),
											'description'					=> __( 'Specify more detailed information about your business. The following information may change from time to time - if it does, simply add a new row and fill in the information.', 'easy-customer-invoices' ),
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
												'contact'						=> array(
													'title'							=> __( 'Contact Information', 'easy-customer-invoices' ),
													'type'							=> 'repeatable',
													'repeatable'					=> array(
														'fields'						=> array(
															'phone'							=> array(
																'title'							=> __( 'Phone', 'easy-customer-invoices' ),
																'type'							=> 'tel',
															),
															'email'							=> array(
																'title'							=> __( 'Email', 'easy-customer-invoices' ),
																'type'							=> 'email',
															),
															'website'						=> array(
																'title'							=> __( 'Website', 'easy-customer-invoices' ),
																'type'							=> 'url',
															),
															'valid_from'					=> array(
																'title'							=> __( 'Valid from...', 'easy-customer-invoices' ),
																'type'							=> 'date',
															),
														),
													),
												),
												'bank_account'					=> array(
													'title'							=> __( 'Bank Account', 'easy-customer-invoices' ),
													'type'							=> 'repeatable',
													'repeatable'					=> array(
														'fields'						=> array(
															'account_number'				=> array(
																'title'							=> __( 'Account Number', 'easy-customer-invoices' ),
																'type'							=> 'text',
															),
															'bank_code'					=> array(
																'title'							=> __( 'Bank Code', 'easy-customer-invoices' ),
																'type'							=> 'text',
															),
															'bank_name'					=> array(
																'title'							=> __( 'Bank Name', 'easy-customer-invoices' ),
																'type'							=> 'text',
															),
															'iban'						=> array(
																'title'							=> __( 'IBAN', 'easy-customer-invoices' ),
																'type'							=> 'text',
															),
															'bic'						=> array(
																'title'							=> __( 'BIC (SWIFT)', 'easy-customer-invoices' ),
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
															'tax_authority'					=> array(
																'title'							=> __( 'Tax Authority', 'easy-customer-invoices' ),
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
									),
								),
								'easy_customer_invoices_data'	=> array(
									'title'							=> __( 'Options', 'easy-customer-invoices' ),
									'description'					=> __( 'Here you can adjust some plugin settings.', 'easy-customer-invoices' ),
									'mode'							=> 'draggable',
									'sections'						=> array(
										'general'						=> array(
											'title'							=> __( 'General', 'easy-customer-invoices' ),
											'description'					=> __( 'Define general settings for the overall behavior.', 'easy-customer-invoices' ),
											'fields'						=> array(
												'reference_prefix'				=> array(
													'title'							=> __( 'Reference Prefix', 'easy-customer-invoices' ),
													'type'							=> 'text',
												),
												'pay_within_days'				=> array(
													'title'							=> __( 'Pay Within Days', 'easy-customer-invoices' ),
													'description'					=> __( 'Enter the amount of days in which an invoice must be paid.', 'easy-customer-invoices' ),
													'type'							=> 'number',
													'min'							=> 1,
													'step'							=> 1,
													'default'						=> 14,
												),
												'base_currency'					=> array(
													'title'							=> __( 'Base Currency', 'easy-customer-invoices' ),
													'type'							=> 'select',
													'options'						=> Util::get_currencies(),
												),
												'default_tax_mode'				=> array(
													'title'							=> __( 'Default Tax Mode', 'easy-customer-invoices' ),
													'type'							=> 'select',
													'options'						=> Util::get_tax_modes(),
												),
												'default_payment_method'		=> array(
													'title'							=> __( 'Default Payment Method', 'easy-customer-invoices' ),
													'type'							=> 'select',
													'options'						=> Util::get_payment_methods(),
												),
												'tax_amount'					=> array(
													'title'							=> __( 'Tax Amount (in %)', 'easy-customer-invoices' ),
													'type'							=> 'number',
													'step'							=> 1,
												),
												'default_country'				=> array(
													'title'							=> __( 'Default Country', 'easy-customer-invoices' ),
													'type'							=> 'select',
													'options'						=> array( 'terms' => 'eci_country' ),
												),
											),
										),
										'colors'						=> array(
											'title'							=> __( 'Invoice PDF Colors', 'easy-customer-invoices' ),
											'description'					=> __( 'Specify the colors for the PDF invoices.', 'easy-customer-invoices' ),
											'fields'						=> array(
												'text_color'					=> array(
													'title'							=> __( 'Text Color', 'easy-customer-invoices' ),
													'type'							=> 'color',
													'default'						=> '#000000',
												),
												'fill_color'					=> array(
													'title'							=> __( 'Fill Color', 'easy-customer-invoices' ),
													'type'							=> 'color',
													'default'						=> '#dddddd',
												),
											),
										),
										'efforts'						=> array(
											'title'							=> __( 'Invoice Efforts', 'easy-customer-invoices' ),
											'description'					=> __( 'Specify the available efforts to put on your invoices.', 'easy-customer-invoices' ),
											'fields'						=> array(
												'invoice_efforts'				=> array(
													'title'							=> __( 'Efforts', 'easy-customer-invoices' ),
													'type'							=> 'repeatable',
													'repeatable'					=> array(
														'fields'						=> array(
															'effort'						=> array(
																'title'							=> __( 'Effort', 'easy-customer-invoices' ),
																'type'							=> 'text',
															),
															'amount'						=> array(
																'title'							=> __( 'Amount', 'easy-customer-invoices' ),
																'type'							=> 'number',
																'min'							=> 0.0,
																'step'							=> 0.01,
															),
														),
													),
												),
											),
										),
										'emails'						=> array(
											'title'							=> __( 'Invoice Emails', 'easy-customer-invoices' ),
											'description'					=> __( 'Specify data for the invoice emails.', 'easy-customer-invoices' ),
											'fields'						=> array(
												'email_subject'                 => array(
													'title'                         => __( 'Subject', 'easy-customer-invoices' ),
													'type'                          => 'text',
													'default'                       => sprintf( __( 'Your Invoice %s', 'easy-customer-invoices' ), '{invoice_id}' ),
												),
												'email_message'                 => array(
													'title'                         => __( 'Message', 'easy-customer-invoices' ),
													'type'                          => 'wysiwyg',
													'default'                       => $default_email_message,
												),
												'email_background_color'		=> array(
													'title'							=> __( 'Background Color', 'easy-customer-invoices' ),
													'type'							=> 'color',
													'default'						=> '#e5e5e5',
												),
												'email_highlight_color'			=> array(
													'title'							=> __( 'Highlight Color', 'easy-customer-invoices' ),
													'type'							=> 'color',
													'default'						=> '#0073aa',
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

		public function render_invoice_customer_column( $id ) {
			$invoice = Invoice::get( $id );

			$customer = $invoice->get_customer();
			if ( ! $customer ) {
				return;
			}

			$customer_id = $customer->get_ID();
			$customer_name = $customer->get_meta( 'company_name' );
			if ( ! $customer_name ) {
				$customer_name = $customer->get_meta( 'first_name' ) . ' ' . $customer->get_meta( 'last_name' );
			}

			echo '<a href="' . get_edit_post_link( $customer_id ) . '">' . $customer_name . '</a>';
		}

		public function render_invoice_amount_column( $id ) {
			$invoice = Invoice::get( $id );

			echo $invoice->format_price( $invoice->get_total() );
		}

		public function render_customer_name_column( $id ) {
			$customer = Customer::get( $id );

			$customer_name = $customer->get_meta( 'company_name' );
			if ( ! $customer_name ) {
				$customer_name = $customer->get_meta( 'first_name' ) . ' ' . $customer->get_meta( 'last_name' );
			}

			echo $customer_name;
		}

		public function render_customer_address_column( $id ) {
			$customer = Customer::get( $id );

			echo $customer->get_address( 'full' );
		}

		public function render_customer_contact_column( $id ) {
			$customer = Customer::get( $id );

			$email = $customer->get_meta( 'email' );
			$phone_numbers = $customer->get_meta( 'phone' );

			$output = array();
			if ( ! empty( $email ) ) {
				$output[] = __( 'Email' ) . ': <a href="mailto:' . $email . '">' . $email . '</a>';
			}
			foreach ( $phone_numbers as $phone_number ) {
				$output[] = $phone_number['label'] . ': ' . $phone_number['number'];
			}

			echo implode( '<br>', $output );
		}

		public function render_customer_revenue_column( $id ) {
			$customer = Customer::get( $id );

			$revenue = 0.0;

			$stats = get_post_meta( $customer->get_ID(), '_easy_customer_invoices_stats', true );
			if ( is_array( $stats ) && isset( $stats['total'] ) ) {
				$revenue = $stats['total'];
			}

			echo Util::format_price( $revenue );
		}

		public function show_pdf_invoice( $id ) {
			$invoice = Invoice::get( $id );

			$pdf = new PDF( $invoice->get_data( 'title' ) );
			$pdf->render( $invoice );
			$pdf->finalize();

			exit;
		}

		public function show_pdf_invoices( $ids ) {
			$pdf = new PDF( __( 'Invoices', 'easy-customer-invoices' ) );
			foreach ( $ids as $id ) {
				$invoice = Invoice::get( $id );
				$pdf->render( $invoice );
			}
			$pdf->finalize();

			exit;
		}

		public function send_invoice_email( $id ) {
			$status = Emails::instance()->send_invoice( $id );

			if ( ! $status ) {
				return new WP_Error( 'send_invoice_error', __( 'The email containing the invoice could not be sent.', 'easy-customer-invoices' ) );
			}

			return __( 'The email containing the invoice was sent successfully.', 'easy-customer-invoices' );
		}

		public function enqueue_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'easy-customer-invoices-admin', App::get_url( 'assets/admin' . $suffix . '.js' ), array( 'jquery', 'wp-util' ), App::get_info( 'version' ), true );
			wp_localize_script( 'easy-customer-invoices-admin', 'wpeci_settings', array(
				'api_root'		=> get_rest_url(),
				'api_nonce'		=> wp_create_nonce( 'wp_rest' ),
				'ajax_nonce'	=> wp_create_nonce( 'eci_ajax' ),
				'currency'		=> Util::get_base_currency(),
			) );
		}

		public function show_overdue_invoices_notice() {
			$screen = get_current_screen();

			if ( 'edit-eci_invoice' !== $screen->id ) {
				return;
			}

			$pay_within_days = Util::get_pay_within_days();

			$reference_date = current_time( 'timestamp' ) - $pay_within_days * DAY_IN_SECONDS;
			$reference_date = date_i18n( 'Y-m-d', $reference_date );

			$invoice_ids = get_posts( array(
				'fields'         => 'ids',
				'posts_per_page' => 10,
				'post_status'    => 'publish',
				'post_type'      => 'eci_invoice',
				'orderby'        => 'post_date',
				'order'          => 'ASC',
				'date_query'     => array(
					'column' => 'post_date',
					'before' => $reference_date,
				),
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'payment_date',
						'value'   => array( '', false ),
						'compare' => 'IN',
					),
				),
			) );

			if ( empty( $invoice_ids ) ) {
				return;
			}

			$base_url = add_query_arg( 'post_type', 'eci_invoice', admin_url( 'edit.php' ) );

			echo '<div class="notice notice-warning">';
			echo '<p><strong>' . __( 'Warning:', 'easy-customer-invoices' ) . '</strong> ' . __( 'The following invoices are overdue:', 'easy-customer-invoices' ) . '</p>';
			echo '<ul>';
			foreach ( $invoice_ids as $invoice_id ) {
				$title = get_the_title( $invoice_id );

				echo '<li><a href="' . esc_url( add_query_arg( 's', $title, $base_url ) ) . '">' . $title . '</a></li>';
			}
			echo '</ul>';
			echo '</div>';
		}

		public function wpod_field_after( $field_slug, $field_args, $section_slug, $tab_slug ) {
			if ( 'easy_customer_invoices_data' !== $tab_slug ) {
				return;
			}

			if ( 'email_message' !== $field_slug ) {
				return;
			}

			$tags = Emails::instance()->get_registered_tag_descriptions();
			if ( empty( $tags ) ) {
				return;
			}

			echo '<p class="description">' . __( 'You may use the following tags. These will be replaced dynamically.', 'easy-customer-invoices' ) . '</p>';
			echo '<ul style="margin:2px 0 5px;color:#666;font-style:italic;">';
			foreach ( $tags as $tag => $description ) {
				echo '<li><span style="width:300px;padding-left:20px;">{' . $tag . '} </span>' . $description . '</li>';
			}
			echo '</ul>';
		}

		public function ajax_make_invoice_id() {
			if ( ! check_ajax_referer( 'eci_ajax', 'nonce', false ) ) {
				wp_send_json_error( __( 'Missing or invalid AJAX nonce.', 'easy-customer-invoices' ) );
			}

			$year = null;
			if ( isset( $_REQUEST['year'] ) ) {
				$year = absint( $_REQUEST['year'] );
			}

			wp_send_json_success( Util::make_invoice_id( $year ) );
		}

		public function ajax_make_customer_id() {
			if ( ! check_ajax_referer( 'eci_ajax', 'nonce', false ) ) {
				wp_send_json_error( __( 'Missing or invalid AJAX nonce.', 'easy-customer-invoices' ) );
			}

			if ( ! isset( $_REQUEST['first_name'] ) ) {
				wp_send_json_error( __( 'Missing first name.', 'easy-customer-invoices' ) );
			}

			if ( ! isset( $_REQUEST['last_name'] ) ) {
				wp_send_json_error( __( 'Missing last name.', 'easy-customer-invoices' ) );
			}

			$first_name = $_REQUEST['first_name'];
			$last_name = $_REQUEST['last_name'];

			$year = null;
			if ( isset( $_REQUEST['year'] ) ) {
				$year = absint( $_REQUEST['year'] );
			}

			$old_id = null;
			if ( isset( $_REQUEST['old_id'] ) && ! empty( $_REQUEST['old_id'] ) ) {
				$old_id = $_REQUEST['old_id'];
			}

			wp_send_json_success( Util::make_customer_id( $first_name, $last_name, $year, $old_id ) );
		}

		public function set_default_country( $terms, $object_ids, $taxonomies, $args ) {
			if ( count( $taxonomies ) > 1 || ! in_array( 'eci_country', $taxonomies, true ) ) {
				return $terms;
			}

			if ( ! empty( $terms ) ) {
				return $terms;
			}

			$default_country = Util::get_default_country();
			if ( empty( $default_country ) ) {
				return $terms;
			}

			$term = get_term( $default_country );
			if ( ! $term || is_wp_error( $term ) ) {
				return $terms;
			}

			$fields = isset( $args['fields'] ) ? $args['fields'] : 'all';

			switch ( $fields ) {
				case 'ids':
					return array( (int) $term->term_id );
				case 'tt_ids':
					return array( (int) $term->term_taxonomy_id );
				case 'names':
					return array( $term->name );
				case 'slugs':
					return array( $term->slug );
				case 'id=>name':
					return array( $term->term_id => $term->name );
				case 'id=>slug':
					return array( $term->term_id => $term->slug );
				case 'count':
					return 1;
			}

			return array( $term );
		}
	}

}
