<?php
/**
 * Main plugin class: wires all hooks.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck;

use CodeSir\EmailHealthCheck\Admin\AdminPage;
use CodeSir\EmailHealthCheck\Admin\TestEmailHandler;

defined( 'ABSPATH' ) || exit;

class Plugin {

	/** @var Plugin|null */
	private static $instance = null;

	/** @var bool */
	private $initialized = false;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		( new Activator() )->register();
		( new AdminPage() )->register();
		( new TestEmailHandler() )->register();

		add_filter( 'plugin_action_links_' . ECEHC_PLUGIN_BASE, array( $this, 'add_plugin_links' ) );
	}

	/**
	 * Add custom links to the plugin list.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_plugin_links( $links ): array {
		$health_check_link = '<a href="' . esc_url( admin_url( 'admin.php?page=' . AdminPage::MENU_SLUG ) ) . '">' . __( 'Health Check', 'ecom-email-health-check' ) . '</a>';

		array_unshift( $links, $health_check_link );

		return $links;
	}
}
