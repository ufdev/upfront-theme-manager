<?php 
/*
Plugin Name: Reset Upfront Themes
Plugin URI: http://premium.wpmudev.org
Description: Reset upfront child themes and parent theme.
Version: 1.0.0
Author: Ashok (Incsub)
Author URI: http://premium.wpmudev.org/
License: GNU General Public License (Version 2 - GPLv2)
*/
if ( ! defined( 'ABSPATH' ) ) wp_die( __( 'Sorry Cowboy! Find another place to have fun!', 'rut' ) );
if( ! class_exists( 'RESET_UF' ) ) {
    /**
     * Class RESET_UF
     */
    class RESET_UF{
        
        /**
         * Singleton Instance of this class
         *
         * @since 1.0.0
         * @access private
         * @var OBJECT of RESET_UF class
         */
        private static $_instance;
        
        
        /**
         * List of upfront child themes
         */
        private $_uf_themes;
        
        
        /**
         * List of all themes
         */
        private $_all_themes;
        
        
        /**
         * List of available themes
         */
        private $_themes;
        
        
        /**
         * Class constructor
         */
        public function __construct() {
            $this->_uf_themes = array( 'uf-fixer', 'uf-panino', 'uf-scribe', 'uf-spirit' );
            $this->_all_themes = array_keys( wp_get_themes() );
            $this->_themes = array_intersect( $this->_uf_themes, $this->_all_themes );
            
            add_action( 'admin_menu', array( &$this, 'reset_uf_settings' ) );
            add_action( 'admin_footer', array( &$this, 'alert_user_on_reset' ) );
            add_action( 'admin_action_reset_uf_themes_cb', array( &$this, 'reset_uf_themes_cb' ) );
        }
        
        
        /**
         * Initializes the RESET_UF class
         *
         * Checks for an existing RESET_UF() instance
	 * and if there is none, creates an instance.
	 *
	 * @since 1.0.0
	 * @access public
	 */
        public static function get_instance() {
            
            if ( ! self::$_instance instanceof RESET_UF ) {
                self::$_instance = new RESET_UF();
            }
            
            return self::$_instance;
            
        }
        
        
        /**
         * Menu Settings Page
         */
        public function reset_uf_settings() {
            add_options_page( __( 'Reset Upfront Themes', 'rut' ), __( 'Reset Upfront', 'rut' ), 'manage_options', 'reset-upfront', array( &$this, 'reset_upfront_cb' ) );
        }
        
        
        /**
         * Menu settings page content
         */
        public function reset_upfront_cb() {
            ?>
            <div class="wrap">
                <h2><?php _e( 'Reset Upfront Themes', 'rut' ) ?></h2>
                <?php if( isset( $_REQUEST['msg'] ) ) { ?>
                <div class="updated"><p><strong><?php echo urldecode( $_REQUEST['msg'] ) ?></strong></p></div>
                <?php } ?>
                <div id="poststuff">
                    <div id="post-body">
                        <div id="post-body-content">
                            <div class="postbox">
                                <h3 class="hndle"><?php _e( 'General Settings', 'rut' ) ?></h3>
                                <div class="inside">
                                    <div class="rut-content">
                                        <form action="<?php echo admin_url( 'admin.php?action=reset_uf_themes_cb' ) ?>" method="post">
                                            <?php wp_nonce_field( 'reset_uf_settings', 'reset_uf_settings_nonce' ); ?>
                                            <table cellpadding="5" cellspacing="5" width="100%">
                                                <tr>
                                                    <td valign="top" width="300">
                                                        <strong><?php _e( 'Select Upfront Child Themes to Reset', 'rut' ) ?></strong><br>
                                                        <em><?php _e( 'Selected themes will be reset', 'rut' ) ?></em>
                                                    </td>
                                                    <td valign="top">
                                                        <?php foreach( $this->_themes as $theme ) { ?>
                                                        <label><input type="checkbox" name="uf_theme[]" value="<?php echo $theme ?>"> <?php echo ucfirst( substr( $theme, 3 ) ); ?></label>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong><?php _e( 'Reset Upfront Parent Theme', 'rot' ) ?></strong></td>
                                                    <td><label><input type="checkbox" name="uf_theme[]" value="uf-upfront"> Upfront</label></td>
                                                </tr>
                                            </table>
                                            <p>
                                                <input type="submit" name="uf_reset" id="uf_reset" value="<?php _e( 'RESET THEMES', 'rot' ) ?>" class="button button-primary">
                                            </p>
                                        </form>
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
         * Process reset operation
         */
        public function reset_uf_themes_cb() {
            if ( ! isset( $_POST['reset_uf_settings_nonce'] ) ) {
                return;
            }
            
            if ( ! wp_verify_nonce( $_POST['reset_uf_settings_nonce'], 'reset_uf_settings' ) ) {
                return;
            }
            
            global $wpdb;
            $themes = $_POST['uf_theme'];
            foreach( $themes as $theme ){
                $key = substr( $theme, 3 );
                $wpdb->query( "delete from {$wpdb->prefix}options where option_name like '%{$key}%'" );
            }
            
            wp_redirect( admin_url( 'options-general.php?page=reset-upfront&msg=' . urlencode( __( 'Themes reset done!', 'rut' ) ) ) );
            
        }
        
        
        /**
         * Alert users on Reset
         */
        public function alert_user_on_reset() {
            ?>
            <script type="text/javascript">
            jQuery(function($) {
                $('#uf_reset').click(function() {
                    var con = confirm( "<?php _e( 'Are you sure want to reset? This can\'t be undone!', 'rut' ); ?>" );
                    if( ! con ){
                        return false;
                    }
                    return true;
                });
            });
            </script>
            <?php
        }
        
    }
    
    function RESET_UF_init() {
	return RESET_UF::get_instance();
    }
    
    add_action( 'plugins_loaded', 'reset_uf_init_load' );
    function reset_uf_init_load() {
        RESET_UF_init();
    }
}
