<?php
/**
 * Main plugin class: wires all hooks.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck;

use CodeSir\EmailHealthCheck\Admin\AdminPage;
use CodeSir\EmailHealthCheck\Admin\ChecklistHandler;
use CodeSir\EmailHealthCheck\Admin\DashboardWidget;
use CodeSir\EmailHealthCheck\Admin\LogSettingsHandler;
use CodeSir\EmailHealthCheck\Admin\RecheckHandler;
use CodeSir\EmailHealthCheck\Admin\ReportLoader;
use CodeSir\EmailHealthCheck\Admin\ReviewPromptHandler;
use CodeSir\EmailHealthCheck\Admin\SiteHealth;
use CodeSir\EmailHealthCheck\Admin\TestEmailHandler;
use CodeSir\EmailHealthCheck\Log\EmailLogger;
use CodeSir\EmailHealthCheck\Support\SmtpDetector;

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
		( new RecheckHandler() )->register();
		( new ReportLoader() )->register();
		( new SiteHealth() )->register();
		( new DashboardWidget() )->register();
		( new LogSettingsHandler() )->register();
		( new ReviewPromptHandler() )->register();
		( new ChecklistHandler() )->register();
		SmtpDetector::register();
		( new EmailLogger() )->register();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'ehc', Cli\Command::class );
		}

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
