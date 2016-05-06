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

use WP_REST_Server as WP_REST_Server;
use WP_Query as WP_Query;
use WP_Error as WP_Error;

if ( ! class_exists( 'WPECI\API' ) ) {

	final class API {
		private static $instance = null;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private function __construct() {}

		public function run() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ), 10, 1 );
		}

		public function register_routes( $server ) {
			register_rest_route( 'wpeci', '/invoices', array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array( $this, 'get_invoices' ),
					'permission_callback'	=> array( $this, 'check_permissions' ),
					'args'					=> array(
						'page'					=> array(
							'description'			=> __( 'Current page of the invoices collection.', 'easy-customer-invoices' ),
							'type'					=> 'integer',
							'default'				=> 1,
							'sanitize_callback'		=> 'absint',
						),
						'per_page'				=> array(
							'description'			=> __( 'Maximum number of invoices to be returned in result set.', 'easy-customer-invoices' ),
							'type'					=> 'integer',
							'default'				=> 10,
							'sanitize_callback'		=> 'absint',
						),
						'customer_id'			=> array(
							'description'			=> __( 'Customer ID of the customer the invoices belong to.', 'easy-customer-invoices' ),
							'type'					=> 'integer',
							'default'				=> 0,
							'sanitize_callback'		=> 'absint',
						),
						'year'					=> array(
							'description'			=> __( 'Year of the invoices.', 'easy-customer-invoices' ),
							'type'					=> 'integer',
							'default'				=> 0,
							'sanitize_callback'		=> 'absint',
						),
						'month'					=> array(
							'description'			=> __( 'Month of the invoices.', 'easy-customer-invoices' ),
							'type'					=> 'integer',
							'default'				=> 0,
							'sanitize_callback'		=> 'absint',
						),
					),
				),
				'schema'	=> array( $this, 'get_invoice_schema' ),
			) );
			register_rest_route( 'wpeci', '/invoices/(?P<id>[\d]+)', array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array( $this, 'get_invoice' ),
					'permission_callback'	=> array( $this, 'check_permissions' ),
				),
				'schema'	=> array( $this, 'get_invoice_schema' ),
			) );

			register_rest_route( 'wpeci', '/customers', array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array( $this, 'get_customers' ),
					'permission_callback'	=> array( $this, 'check_permissions' ),
					'args'					=> array(
						'page'					=> array(
							'description'			=> __( 'Current page of the customers collection.', 'easy-customer-invoices' ),
							'type'					=> 'integer',
							'default'				=> 1,
							'sanitize_callback'		=> 'absint',
						),
						'per_page'				=> array(
							'description'			=> __( 'Maximum number of customers to be returned in result set.', 'easy-customer-invoices' ),
							'type'					=> 'integer',
							'default'				=> 10,
							'sanitize_callback'		=> 'absint',
						),
						'country_id'			=> array(
							'description'			=> __( 'Country ID of the country the customers belong to.', 'easy-customer-invoices' ),
							'type'					=> 'integer',
							'default'				=> 0,
							'sanitize_callback'		=> 'absint',
						),
						'year'					=> array(
							'description'			=> __( 'Year when the customers were registered.', 'easy-customer-invoices' ),
							'type'					=> 'integer',
							'default'				=> 0,
							'sanitize_callback'		=> 'absint',
						),
						'month'					=> array(
							'description'			=> __( 'Month when the customers were registered.', 'easy-customer-invoices' ),
							'type'					=> 'integer',
							'default'				=> 0,
							'sanitize_callback'		=> 'absint',
						),
					),
				),
				'schema'	=> array( $this, 'get_customer_schema' ),
			) );
			register_rest_route( 'wpeci', '/customers/(?P<id>[\d]+)', array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array( $this, 'get_customer' ),
					'permission_callback'	=> array( $this, 'check_permissions' ),
				),
				'schema'	=> array( $this, 'get_customer_schema' ),
			) );
		}

		public function get_invoices( $request ) {
			$invoices = array();

			$args = array(
				'post_type'			=> 'eci_invoice',
				'post_status'		=> 'publish',
				'posts_per_page'	=> $request['per_page'],
				'paged'				=> $request['page'],
			);

			if ( 0 < $request['customer_id'] ) {
				$args['meta_query'] = array(
					array(
						'key'		=> 'customer',
						'value'		=> $request['customer_id'],
						'compare'	=> '=',
						'type'		=> 'NUMERIC',
					),
				);
			}

			if ( 0 < $request['year'] ) {
				$args['year'] = $request['year'];
			}

			if ( 0 < $request['month'] ) {
				$args['monthnum'] = $request['month'];
			}

			$query = new WP_Query();
			$posts = $query->query( $args );
			foreach ( $posts as $post ) {
				$invoice = Entities\Invoice::get( $post->ID );
				$invoices[] = $invoice->prepare_for_api();
			}

			return $invoices;
		}

		public function get_invoice( $request ) {
			$invoice = Entities\Invoice::get( absint( $request['id'] ) );
			if ( null === $invoice ) {
				return new WP_Error( 'wpeci_rest_invoice_invalid_id', __( 'Invalid invoice id.', 'easy-customer-invoices' ), array( 'status' => 404 ) );
			}

			return $invoice->prepare_for_api();
		}

		public function get_invoice_schema() {
			return Entities\Invoice::get_api_schema();
		}

		public function get_customers( $request ) {
			$customers = array();

			$args = array(
				'post_type'			=> 'eci_customer',
				'post_status'		=> 'publish',
				'posts_per_page'	=> $request['per_page'],
				'paged'				=> $request['page'],
			);

			if ( 0 < $request['country_id'] ) {
				$args['tax_query'] = array(
					array(
						'taxonomy'	=> 'eci_country',
						'field'		=> 'term_id',
						'terms'		=> $request['country_id'],
						'operator'	=> 'IN',
					),
				);
			}

			if ( 0 < $request['year'] ) {
				$args['year'] = $request['year'];
			}

			if ( 0 < $request['month'] ) {
				$args['monthnum'] = $request['month'];
			}

			$query = new WP_Query();
			$posts = $query->query( $args );
			foreach ( $posts as $post ) {
				$customer = Entities\Customer::get( $post->ID );
				$customers[] = $customer->prepare_for_api();
			}

			return $customers;
		}

		public function get_customer( $request ) {
			$invoice = Entities\Customer::get( absint( $request['id'] ) );
			if ( null === $invoice ) {
				return new WP_Error( 'wpeci_rest_customer_invalid_id', __( 'Invalid customer id.', 'easy-customer-invoices' ), array( 'status' => 404 ) );
			}

			return $invoice->prepare_for_api();
		}

		public function get_customer_schema() {
			return Entities\Customer::get_api_schema();
		}

		public function check_permissions( $request ) {
			return current_user_can( 'manage_options' );
		}
	}

}
