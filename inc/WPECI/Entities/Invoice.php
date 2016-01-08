<?php
/**
 * @package WPECI
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPECI\Entities;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

use WPOO\Post as Post;

if ( ! class_exists( 'WPECI\Entities\Invoice' ) ) {

	final class Invoice extends Post {
		protected static function get_item( $id = null ) {
			$post = get_post( $id );
			if ( is_object( $post ) && is_a( $post, 'WP_Post' ) && 'eci_invoice' === $post->post_type ) {
				return $post;
			}

			return null;
		}

		protected function __construct( $item ) {
			parent::__construct( $item );
		}
	}

}
