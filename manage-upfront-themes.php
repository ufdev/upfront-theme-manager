<?php
/*
Plugin Name: Upfront Theme Manager
Plugin URI:  http://premium.wpmudev.org
Description: Reset, Export and Import Upfront themes.
Version:     1.1.2
Author:      Ashok, Philipp (Incsub)
Author URI:  http://premium.wpmudev.org/
Textdomain:  utm
License:     GNU General Public License (Version 2 - GPLv2)
*/

if ( ! defined( 'ABSPATH' ) ) {
	// Cannot use wp_die() here because WP is not loaded...
	die( 'Sorry Cowboy! Find another place to have fun!' );
}

if ( ! class_exists( 'Theme_Manager_UF' ) ) {
	define( 'RUT_LANG', 'utm' );

	/**
	 * Class Theme_Manager_UF
	 */
	class Theme_Manager_UF {

		/**
		 * List of installed themes.
		 */
		private $_themes;


		/**
		 * Initializes the Theme_Manager_UF class
		 *
		 * Checks for an existing Theme_Manager_UF() instance
		 * and if there is none, creates an instance.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public static function get_instance() {
			static $Inst = null;

			if ( null === $Inst ) {
				$Inst = new Theme_Manager_UF();
			}

			return $Inst;
		}


		/**
		 * Class constructor is private to enforce the Singleton pattern.
		 */
		private function __construct() {
			$this->find_themes();

			add_action( 'admin_menu', array( $this, 'reset_uf_settings' ) );
			add_action( 'admin_footer', array( $this, 'alert_user_on_reset' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'admin_action_reset_uf_themes_cb', array( $this, 'reset_uf_themes_cb' ) );
			add_action( 'admin_action_export_upfront', array( $this, 'export_upfront_cb' ) );
			add_action( 'admin_action_import_upfront', array( $this, 'import_upfront_cb' ) );
		}


		/**
		 * Populates the variable $this->_themes with a list of all installed
		 * Upfront themes.
		 */
		protected function find_themes() {
			$known_uf_themes = array(
				'uf-fixer',
				'uf-panino',
				'uf-scribe',
				'uf-spirit',
                'uf-parrot',
                'uf-luke-and-sara'
			);
			$all_wp_themes = array_keys( wp_get_themes() );
			$this->_themes = array_intersect( $known_uf_themes, $all_wp_themes );
		}

		/**
		 * Menu Settings Page
		 */
		public function reset_uf_settings() {
			add_management_page(
				__( 'Manage Upfront Themes', RUT_LANG ),
				__( 'Upfront Themes', RUT_LANG ),
				'manage_options',
				'manage-upfront',
				array( $this, 'reset_upfront_cb' )
			);
		}


		/**
		 * Menu settings page content
		 */
		public function reset_upfront_cb() {
			?>
			<div class="wrap">
				<h2><?php _e( 'Upfront Theme Manager', RUT_LANG ); ?></h2>
				<div id="poststuff">
					<div id="post-body">
						<div id="post-body-content">

							<div class="postbox">
								<h3 class="hndle"><?php _e( 'General Settings', RUT_LANG ); ?></h3>
								<div class="inside">
									<div class="rut-content">
									<?php $this->form_reset(); ?>
									</div>
								</div>
							</div>

							<div class="postbox">
								<h3 class="hndle"><?php _e( 'Export Data', RUT_LANG ) ?></h3>
								<div class="inside">
									<div class="rut-content">
									<?php $this->form_export(); ?>
									</div>
								</div>
							</div>

							<div class="postbox">
								<h3 class="hndle"><?php _e( 'Import Data', RUT_LANG ) ?></h3>
								<div class="inside">
									<div class="rut-content">
									<?php $this->form_import(); ?>
									</div>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Displays the update message, if a message is available.
		 */
		public function admin_notices() {
			if ( empty( $_REQUEST['msg'] ) ) {
				return;
			}

			$msg = base64_decode( $_REQUEST['msg'] );
			if ( $msg ) {
				?>
				<div id="message" class="updated notice notice-success is-dismissible">
					<p><?php echo wp_kses_post( $msg ); ?></p>
					<button type="button" class="notice-dismiss"></button>
				</div>
				<?php
			}
		}

		/**
		 * Redirect the user to the plugin page and displays the given message.
		 */
		protected function show_message( $message ) {
			// We use base64 encoding to make it more difficult to modify the
			// message. Users try all kind of funny things like injecting
			// javascript, etc...
			$msg = base64_encode( $message );
			$url = admin_url( 'tools.php?page=manage-upfront' );
			$url = add_query_arg( 'msg', $msg, $url );
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Output the Reset Form
		 */
		protected function form_reset() {
			$action = admin_url( 'admin.php?action=reset_uf_themes_cb' );
			?>
			<form action="<?php echo $action; ?>" method="post">
				<?php wp_nonce_field( 'reset_uf_settings' ); ?>
				<input type="hidden" name="task" value="reset" />
				<table cellpadding="5" cellspacing="5" width="100%">
					<tr>
						<td valign="top" width="300">
							<strong><?php _e( 'Select Upfront Child Themes to Reset', RUT_LANG ) ?></strong><br>
							<em><?php _e( 'Selected themes will be reset', RUT_LANG ); ?></em>
						</td>
						<td valign="top">
							<?php foreach ( $this->_themes as $theme ) { ?>
							<label>
								<input type="checkbox" name="uf_theme[]" value="<?php echo esc_attr( $theme ); ?>">
								<?php echo ucfirst( substr( $theme, 3 ) ); ?>
							</label><br />
							<?php } ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php _e( 'Reset Upfront Parent Theme', RUT_LANG ) ?></strong></td>
						<td><label><input type="checkbox" name="uf_theme[]" value="uf-upfront"> Upfront</label></td>
					</tr>
				</table>
				<p>
					<button name="uf_reset" id="uf_reset" class="button button-primary">
					<?php _e( 'RESET THEMES', RUT_LANG ) ?>
					</button>
				</p>
			</form>
			<?php
		}


		/**
		 * Output the Export Form
		 */
		protected function form_export() {
			$action = admin_url( 'admin.php?action=export_upfront' );
			?>
			<form action="<?php echo $action; ?>" method="post">
				<?php wp_nonce_field( 'export_uf_settings' ); ?>
				<input type="hidden" name="task" value="export" />
				<table cellpadding="5" cellspacing="5" width="100%">
					<tr>
						<td valign="top" width="200">
							<b><?php _e( 'Theme to export:', RUT_LANG ); ?></b>
						</td>
						<td>
							<?php foreach ( $this->_themes as $theme ) { ?>
							<?php $name = substr( $theme, 3 ); ?>
							<label>
								<input type="radio" name="uf_exp_name" value="<?php echo esc_attr( $name ); ?>">
								<?php echo ucfirst( $name ); ?>
							</label><br />
							<?php } ?>
						</td>
					</tr>
					<tr>
						<td valign="top" width="200">
							<b><?php _e( 'Replace Domain:', RUT_LANG ); ?></b>
						</td>
						<td valign="top">
							<?php
							printf(
								__( 'The export will flag that the Domain "%s" should be replaced with the domain name of the import site', RUT_LANG ),
								'<b>' . $this->get_domain() . '</b>'
							);
							?>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<button class="button button-primary">
							<?php _e( 'Export', RUT_LANG ); ?>
							</button>
						</td>
					</tr>
				</table>
			</form>
			<?php
		}


		/**
		 * Output the Import Form
		 */
		protected function form_import() {
			$action = admin_url( 'admin.php?action=import_upfront&noheader=true' );
			?>
			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'import_uf_settings' ); ?>
				<input type="hidden" name="task" value="import" />
				<table cellpadding="5" cellspacing="5" width="100%">
					<tr>
						<td valign="top" width="200">
							<b><?php _e( 'Upload the csv:', RUT_LANG ); ?></b>
						</td>
						<td>
							<input type="file" name="uf_import_name">
						</td>
					</tr>
					<tr>
						<td valign="top" width="200">
							<b><?php _e( 'Replace Domain:', RUT_LANG ); ?></b>
						</td>
						<td valign="top">
							<?php
							printf(
								__( 'The original domain in the import file will be replaced with the current domain "%s"', RUT_LANG ),
								'<b>' . $this->get_domain() . '</b>'
							);
							?>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<button class="button button-primary">
							<?php _e( 'Import', RUT_LANG ); ?>
							</button>
						</td>
					</tr>
				</table>
			</form>
			<?php
		}

		/**
		 * Validates the nonce. We need the fields '_wpnonce' and 'task' to
		 * do this.
		 */
		protected function validate_nonce() {
			if ( empty( $_POST['_wpnonce'] ) || empty( $_POST['task'] ) ) {
				return false;
			}

			$action = $_POST['task'] . '_uf_settings';
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], $action ) ) {
				return false;
			}

			return true;
		}


		/**
		 * Process reset operation
		 */
		public function reset_uf_themes_cb() {
			global $wpdb;

			if ( ! $this->validate_nonce() ) { return; }

			$themes = $_POST['uf_theme'];
			foreach ( $themes as $theme ) {
				$key = substr( $theme, 3 );
				$sql = "
				DELETE FROM
				{$wpdb->options}
				WHERE option_name LIKE '%{$key}%';
				";
				$wpdb->query( $sql );
			}

			$this->show_message( __( 'Themes reset done!', RUT_LANG ) );
		}


		/**
		 * Export theme
		 */
		public function export_upfront_cb() {
			global $wpdb;

			if ( ! $this->validate_nonce() ) { return; }

			$key = $_POST['uf_exp_name'];
			$sql = "
			SELECT option_name, option_value
			FROM {$wpdb->options}
			WHERE option_name LIKE '%{$key}%';
			";
			$settings = $wpdb->get_results( $sql, ARRAY_A );

			$export = array();
			$export['theme'] = $key;
			$export['domain'] = $this->get_domain();
			$export['settings'] = $settings;

			$content = json_encode( $export );

			header( 'Content-type: text/json' );
			header( 'Content-disposition: attachment; filename=' . $key . '.json' );
			header( 'Content-Type: application/json' );
			header( 'Content-Length: ' . strlen( $content ) );
			echo $content;

			// We're done, stop WordPress from processing the page any further.
			exit;
		}

		/**
		 * Import Theme
		 */
		public function import_upfront_cb() {
			global $wpdb;

			if ( ! $this->validate_nonce() ) { return; }

			if ( ! is_uploaded_file( $_FILES['uf_import_name']['tmp_name'] ) ) {
				return;
			}

			$array = array();

			$upload = $_FILES['uf_import_name'];
			$string = file_get_contents( $upload['tmp_name'] );
			$json = json_decode( $string, true );

			$theme = $json['theme'];
			$old_domain = $json['domain'];
			$new_domain = $this->get_domain();
			$settings = $json['settings'];

			$sql = "
			DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '%{$theme}%'
			";
			$wpdb->query( $sql );

			foreach ( $settings as $item ) {
				// Replace the doman name before importing the option.
				$value = str_replace(
					$old_domain,
					$new_domain,
					$item['option_value']
				);

				// Using a custom SQL here, because update_option will
				// serialize/escape some values (i.e. the Upfront Styles value).
				$sql = "
					INSERT INTO {$wpdb->options}
					(option_name, option_value)
					VALUES
					(%s, %s)
				";
				$sql = $wpdb->prepare( $sql, $item['option_name'], $value );
				$wpdb->query( $sql );
			}

			$this->show_message(
				sprintf(
					__( '%s theme imported successfully!', RUT_LANG ),
					ucfirst( $theme )
				)
			);
		}


		/**
		 * Alert users on Reset
		 */
		public function alert_user_on_reset() {
			?>
			<script type="text/javascript">
			jQuery(function($) {
				$('#uf_reset').click(function() {
					var con = confirm( "<?php
						_e( 'Are you sure want to reset? This can\'t be undone!', RUT_LANG );
					?>" );
					return con;
				});
			});
			</script>
			<?php
		}

		/**
		 * Returns the current WP domain (the URL without the protocol prefix).
		 */
		protected function get_domain() {
			$url = site_url();
			$domain = substr( $url, strpos( $url, '://' ) + 3 );
			return $domain;
		}

	};
	// End of class Theme_Manager_UF

	add_action( 'plugins_loaded', 'reset_uf_init_load' );
	function reset_uf_init_load() {
		Theme_Manager_UF::get_instance();
	}
}
