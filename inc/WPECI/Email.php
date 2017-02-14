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

if ( ! class_exists( 'WPECI\Email' ) ) {

	final class Email {
		private $to = '';

		private $subject = '';

		private $message = '';

		private $attachments = array();

		private $from = '';

		private $from_name = '';

		private $header_image = '';

		private $background_color = '#e5e5e5';

		private $highlight_color = '#0073aa';

		private $footer = '';

		public function __construct( $to, $subject, $message, $args = array() ) {
			$this->to          = $to;
			$this->subject     = $subject;
			$this->message     = $message;

			foreach ( $args as $key => $value ) {
				if ( isset( $this->$key ) ) {
					$this->$key = $value;
				}
			}
		}

		public function send() {
			$message = $this->wrap_message();

			add_filter( 'wp_mail_content_type', array( $this, 'content_type' ) );
			if ( ! empty( $this->from ) ) {
				add_filter( 'wp_mail_from', array( $this, 'from' ) );
			}
			if ( ! empty( $this->from_name ) ) {
				add_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );
			}

			$result = wp_mail( $this->to, $this->subject, $message, '', $this->attachments );

			if ( ! empty( $this->from_name ) ) {
				remove_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );
			}
			if ( ! empty( $this->from ) ) {
				remove_filter( 'wp_mail_from', array( $this, 'from' ) );
			}
			remove_filter( 'wp_mail_content_type', array( $this, 'content_type' ) );

			return $result;
		}

		public function content_type() {
			return 'text/html';
		}

		public function from() {
			return $this->from;
		}

		public function from_name() {
			return $this->from_name;
		}

		private function wrap_message() {
			$template = App::get_path( 'templates/email.php' );

			$data = array(
				'background_color' => $this->background_color,
				'styles'           => '',
				'title'            => $this->subject,
				'headline'         => $this->subject,
				'header_image'     => $this->header_image,
				'main_content'     => $this->message,
				'footer_content'   => $this->footer,
			);

			if ( $this->highlight_color ) {
				$data['styles'] = 'a { text-decoration: none !important; } a:hover, a:focus { text-decoration: underline !important; }';
				$data['styles'] .= ' a, a:hover, a:focus { color: ' . $this->highlight_color . ' !important; }';
			}

			ob_start();
			require $template;
			$output = ob_get_clean();

			return $output;
		}
	}

}
