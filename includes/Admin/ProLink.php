<?php
/**
 * A plain text link to the Pro waiting list, on the plugin's own screens only.
 *
 * The plugin makes no request and sends no data: it only prints a link that the
 * user may click. Pure logic (URL, enabled decision, markup) lives here so it can be tested.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

defined( 'ABSPATH' ) || exit;

class ProLink {

	const WAITLIST_URL = 'https://outboxhealth.com/waitlist/';

	/**
	 * Single switch: set to false if the waiting list page is not live.
	 */
	const WAITLIST_LIVE = true;

	/**
	 * Screen id of the plugin's own admin page (hook suffix of the top-level menu page).
	 */
	const SCREEN_ID = 'toplevel_page_' . AdminPage::MENU_SLUG;

	public function register(): void {
		add_filter( 'admin_footer_text', array( $this, 'footer_text' ) );
		add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );
	}

	/**
	 * The waiting list URL with its tracking parameters.
	 */
	public static function url(): string {
		return self::WAITLIST_URL . '?' . http_build_query(
			array(
				'utm_source'   => 'plugin',
				'utm_medium'   => 'admin',
				'utm_campaign' => 'waitlist',
			),
			'',
			'&'
		);
	}

	/**
	 * Whether the link may be shown at all.
	 */
	public static function is_enabled(): bool {
		if ( ! self::WAITLIST_LIVE ) {
			return false;
		}

		/**
		 * Filters whether the plain text link to the Pro waiting list is shown.
		 *
		 * @param bool $show Default true.
		 */
		return (bool) apply_filters( 'ecehc_show_pro_link', true );
	}

	/**
	 * Whether the given screen id is the plugin's own page.
	 *
	 * @param mixed $screen_id Screen id, or anything else (null, false).
	 */
	public static function is_our_screen( $screen_id ): bool {
		return is_string( $screen_id ) && self::SCREEN_ID === $screen_id;
	}

	/**
	 * The link markup.
	 */
	public static function link_html(): string {
		return '<a href="' . esc_url( self::url() ) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr( __( 'A Pro version is being planned. Free to ignore.', 'ecom-email-health-check' ) ) . '">'
			. esc_html__( 'Follow the Pro plan', 'ecom-email-health-check' )
			. '</a>';
	}

	/**
	 * Footer text for the plugin's own page: keeps the existing text and appends the link.
	 *
	 * @param mixed $text Existing footer text.
	 * @return mixed
	 */
	public function footer_text( $text ) {
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_id = $screen ? $screen->id : null;

		return self::filter_footer( $text, $screen_id );
	}

	/**
	 * Pure part of footer_text(), testable with a screen id.
	 *
	 * @param mixed       $text      Existing footer text.
	 * @param string|null $screen_id Current screen id.
	 * @return mixed
	 */
	public static function filter_footer( $text, $screen_id ) {
		if ( ! self::is_our_screen( $screen_id ) || ! self::is_enabled() ) {
			return $text;
		}

		return ( is_string( $text ) && '' !== $text ? $text . ' | ' : '' ) . self::link_html();
	}

	/**
	 * Plugin row meta links: only on this plugin's row.
	 *
	 * @param array  $links Existing meta links.
	 * @param string $file  Plugin basename of the row.
	 * @return array
	 */
	public function row_meta( $links, $file ) {
		return self::filter_row_meta( $links, $file, defined( 'ECEHC_PLUGIN_BASE' ) ? ECEHC_PLUGIN_BASE : '' );
	}

	/**
	 * Pure part of row_meta().
	 *
	 * @param mixed  $links       Existing meta links.
	 * @param string $file        Plugin basename of the row.
	 * @param string $plugin_base This plugin's basename.
	 * @return mixed
	 */
	public static function filter_row_meta( $links, $file, $plugin_base ) {
		if ( ! is_array( $links ) || '' === $plugin_base || $plugin_base !== $file || ! self::is_enabled() ) {
			return $links;
		}

		$links[] = self::link_html();

		return $links;
	}
}
