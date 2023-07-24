<?php
/**
 *Plugin Name: Disciple.Tools - PII Obliterator
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-pii-obliterator
 * Description: Small utility to obscure all names, phone numbers, addresses, email addresses in the database. This is only intended for local development databased. Do not use this on live databases. Highly destructive.
 * Version:  0.3.2
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-pii-obliterator
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.5
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( !defined( 'ABSPATH' ) ){
    exit;
} // Exit if accessed directly
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

add_action( 'after_setup_theme', function (){
    // must be in admin area
    if ( !is_admin() ){
        return false;
    }

    $required_dt_theme_version = '0.28.0';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;
    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = class_exists( "Disciple_Tools" );
    if ( $is_theme_dt && version_compare( $version, $required_dt_theme_version, "<" ) ){
        add_action( 'admin_notices', function (){
            ?>
            <div class="notice notice-error notice-pii_obliterator is-dismissible" data-notice="pii_obliterator">
                Disciple
                Tools Theme not active or not latest version for PII Obliterator plugin.
            </div><?php
        } );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }
    /*
     * Don't load the plugin on every rest request. Only those with the 'sample' namespace
     */
    $is_rest = dt_is_rest();
    if ( !$is_rest ){
        return PII_Obliterator::instance();
    }
    return false;
} );


/**
 * Class PII_Obliterator
 */
class PII_Obliterator{

    public $token = 'pii_obliterator';
    public $title = 'PII Obliterator';
    public $permissions = 'manage_dt';

    /**  Singleton */
    private static $_instance = null;

    public static function instance(){
        if ( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct(){

        if ( is_admin() ){
            add_action( "admin_menu", [ $this, "register_menu" ] );

            // Check for plugin updates
            if ( !class_exists( 'PucFactory' ) ){
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }
            $hosted_json = "https://raw.githubusercontent.com/DiscipleTools/disciple-tools-pii-obliterator/master/version-control.json";
            if ( class_exists( 'PucFactory' ) ){
                PucFactory::buildUpdateChecker(
                    $hosted_json,
                    __FILE__,
                    'disciple-tools-pii-obliterator'
                );
            }
        }


    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu(){
        add_submenu_page( 'dt_extensions', $this->title, $this->title, $this->permissions, $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple.Tools Theme fully loads.
     */
    public function extensions_menu(){
    }

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content(){

        if ( !current_user_can( $this->permissions ) ){ // manage dt is a permission that is specific to Disciple.Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        ?>
        <div class="wrap">
            <h2><?php echo esc_html( $this->title ) ?></h2>
            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <!-- Main Column -->

                            <?php $this->main_column(); ?>

                            <!-- End Main Column -->
                        </div><!-- end post-body-content -->
                        <div id="postbox-container-1" class="postbox-container">
                            <!-- Right Column -->

                            <?php $this->right_column(); ?>

                            <!-- End Right Column -->
                        </div><!-- postbox-container 1 -->
                        <div id="postbox-container-2" class="postbox-container">
                        </div><!-- postbox-container 2 -->
                    </div><!-- post-body meta box container -->
                </div><!--poststuff end -->
            </div><!-- wrap end -->
        </div><!-- End wrap -->

        <?php
    }

    public function main_column(){
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th><p style="max-width:450px">This process obliterates all personally identifiable information in this
                        system. It is completely irreversible. Ths action was designed to help developers
                        who are working on their local machines with security sensitive databases, so that they can
                        obliterate the pii sensitive info, but continue to develop
                        on the system. If this is not what you are doing, please consider stopping now. :)</p>
                    <p><a class="button"
                          href="<?php echo esc_url( trailingslashit( admin_url() ) ) ?>admin.php?page=pii_obliterator&obliterate=true">Obliterate
                            Away!</a></p>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php
            /* Start Loop */
            if ( isset( $_GET['obliterate'] ) && !isset( $_GET['step'] ) ){
                ?>
                <tr>
                    <td><img src="<?php echo esc_url( get_theme_file_uri() ) ?>/spinner.svg" width="30px"
                             alt="spinner"/></td>
                </tr>
                <script type="text/javascript">
                    function nextpage() {
                        location.href = "<?php echo esc_html( admin_url() ) ?>admin.php?page=<?php echo esc_attr( $this->token )  ?>&obliterate=true&step=1&nonce=<?php echo esc_html( wp_create_nonce( 'obliterate' . get_current_user_id() ) ) ?>";
                    }

                    setTimeout("nextpage()", 1500);
                </script>
                <?php
            }

            /* CONTACTS */
            if ( isset( $_GET['obliterate'] ) && $_GET['step'] === '1' && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'obliterate' . get_current_user_id() ) ){
                global $wpdb;
                $results = $wpdb->get_results( "SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'contacts' ", ARRAY_A );
                $alphabet = [ 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' ];
                foreach ( $results as $row ){
                    $hash_first = substr( strtolower( preg_replace( '/[0-9_\/]+/', '', base64_encode( hash( 'sha256', $row['post_title'] ) ) ) ), 0, rand( 3, 8 ) );
                    $hash_second = substr( strtolower( preg_replace( '/[0-9_\/]+/', '', base64_encode( hash( 'sha256', $row['post_title'] ) ) ) ), 0, rand( 3, 8 ) );
                    $name = $alphabet[rand( 0, 25 )] . $hash_first . ' ' . $alphabet[rand( 0, 25 )] . $hash_second;
                    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_title = %s WHERE ID = %d; ", $name, $row['ID'] ) );
                }
                ?>
                <tr>
                    <td>Contacts Obliterated</td>
                </tr>
                <tr>
                    <td><img src="<?php echo esc_url( get_theme_file_uri() ) ?>/spinner.svg" width="30px"
                             alt="spinner"/></td>
                </tr>
                <script type="text/javascript">
                    function nextpage() {
                        location.href = "<?php echo esc_html( admin_url() ) ?>admin.php?page=<?php echo esc_attr( $this->token )  ?>&obliterate=true&step=2&nonce=<?php echo esc_html( wp_create_nonce( 'obliterate' . get_current_user_id() ) ) ?>";
                    }

                    setTimeout("nextpage()", 1500);
                </script>
                <?php
            }

            /* GROUPS */
            if ( isset( $_GET['obliterate'] ) && $_GET['step'] === '2' && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'obliterate' . get_current_user_id() ) ){
                global $wpdb;
                $results = $wpdb->get_results( "SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'groups' ", ARRAY_A );
                $alphabet = [ 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' ];
                foreach ( $results as $row ){
                    $hash_first = substr( strtolower( preg_replace( '/[0-9_\/]+/', '', base64_encode( hash( 'sha256', $row['post_title'] ) ) ) ), 0, rand( 4, 8 ) );
                    $name = $alphabet[rand( 0, 25 )] . $hash_first;
                    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_title = %s WHERE ID = %d; ", $name, $row['ID'] ) );
                }
                ?>
                <tr>
                    <td>Contacts Obliterated</td>
                </tr>
                <tr>
                    <td>Groups Obliterated</td>
                </tr>
                <tr>
                    <td><img src="<?php echo esc_url( get_theme_file_uri() ) ?>/spinner.svg" width="30px"
                             alt="spinner"/></td>
                </tr>
                <script type="text/javascript">
                    function nextpage() {
                        location.href = "<?php echo esc_html( admin_url() ) ?>admin.php?page=<?php echo esc_attr( $this->token )  ?>&obliterate=true&step=3&nonce=<?php echo esc_html( wp_create_nonce( 'obliterate' . get_current_user_id() ) ) ?>";
                    }

                    setTimeout("nextpage()", 1500);
                </script>
                <?php
            }

            /* DISPLAY NAME */
            if ( isset( $_GET['obliterate'] ) && $_GET['step'] === '3' && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'obliterate' . get_current_user_id() ) ){
                global $wpdb;
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT ID, display_name FROM $wpdb->users WHERE ID != %d", get_current_user_id() ), ARRAY_A );
                $alphabet = [ 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' ];
                foreach ( $results as $row ){
                    $hash_first = substr( strtolower( preg_replace( '/[0-9_\/]+/', '', base64_encode( hash( 'sha256', $row['display_name'] ) ) ) ), 0, rand( 4, 8 ) );
                    $name = $alphabet[rand( 0, 25 )] . $hash_first;
                    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET display_name = %s WHERE ID = %d; ", $name, $row['ID'] ) );
                }
                ?>
                <tr>
                    <td>Contacts Obliterated</td>
                </tr>
                <tr>
                    <td>Groups Obliterated</td>
                </tr>
                <tr>
                    <td>User Display Names Obliterated</td>
                </tr>
                <tr>
                    <td><img src="<?php echo esc_url( get_theme_file_uri() ) ?>/spinner.svg" width="30px"
                             alt="spinner"/></td>
                </tr>
                <script type="text/javascript">
                    function nextpage() {
                        location.href = "<?php echo esc_html( admin_url() ) ?>admin.php?page=<?php echo esc_attr( $this->token )  ?>&obliterate=true&step=4&nonce=<?php echo esc_html( wp_create_nonce( 'obliterate' . get_current_user_id() ) ) ?>";
                    }

                    setTimeout("nextpage()", 1500);
                </script>
                <?php
            }

            /* USER EMAIL */
            if ( isset( $_GET['obliterate'] ) && $_GET['step'] === '4' && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'obliterate' . get_current_user_id() ) ){
                global $wpdb;
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT ID, user_email FROM $wpdb->users WHERE ID != %d", get_current_user_id() ), ARRAY_A );
                $alphabet = [ 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' ];
                foreach ( $results as $row ){
                    $hash_first = substr( strtolower( preg_replace( '/[0-9_\/]+/', '', base64_encode( hash( 'sha256', $row['ID'] ) ) ) ), 0, rand( 4, 8 ) );
                    $name = $alphabet[rand( 0, 25 )] . $hash_first . '@local.email.com';
                    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->users SET user_email = %s WHERE ID = %d; ", $name, $row['ID'] ) );
                }
                ?>
                <tr>
                    <td>Contacts Obliterated</td>
                </tr>
                <tr>
                    <td>Groups Obliterated</td>
                </tr>
                <tr>
                    <td>User Display Names Obliterated</td>
                </tr>
                <tr>
                    <td>User Emails Obliterated</td>
                </tr>
                <tr>
                    <td><img src="<?php echo esc_url( get_theme_file_uri() ) ?>/spinner.svg" width="30px"
                             alt="spinner"/></td>
                </tr>
                <script type="text/javascript">
                    function nextpage() {
                        location.href = "<?php echo esc_html( admin_url() ) ?>admin.php?page=<?php echo esc_attr( $this->token )  ?>&obliterate=true&step=5&nonce=<?php echo esc_html( wp_create_nonce( 'obliterate' . get_current_user_id() ) ) ?>";
                    }

                    setTimeout("nextpage()", 1500);
                </script>
                <?php
            }

            /* Contact Phone Numbers */
            if ( isset( $_GET['obliterate'] ) && $_GET['step'] === '5' && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'obliterate' . get_current_user_id() ) ){
                global $wpdb;
                $results = $wpdb->get_results( "SELECT pm.meta_id FROM $wpdb->posts as p JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id WHERE p.post_type = 'contacts' AND pm.meta_key LIKE 'contact_phone%' AND pm.meta_key NOT LIKE '%details' ", ARRAY_A );
                foreach ( $results as $row ){
                    $phone = rand( 100, 999 ) . '-' . rand( 100, 999 ) . '-' . rand( 1000, 9999 );
                    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d; ", $phone, $row['meta_id'] ) );
                }
                ?>
                <tr>
                    <td>Contacts Obliterated</td>
                </tr>
                <tr>
                    <td>Groups Obliterated</td>
                </tr>
                <tr>
                    <td>User Display Names Obliterated</td>
                </tr>
                <tr>
                    <td>User Emails Obliterated</td>
                </tr>
                <tr>
                    <td>Contact Phone Numbers Obliterated</td>
                </tr>
                <tr>
                    <td><img src="<?php echo esc_url( get_theme_file_uri() ) ?>/spinner.svg" width="30px"
                             alt="spinner"/></td>
                </tr>
                <script type="text/javascript">
                    function nextpage() {
                        location.href = "<?php echo esc_html( admin_url() ) ?>admin.php?page=<?php echo esc_attr( $this->token )  ?>&obliterate=true&step=6&nonce=<?php echo esc_html( wp_create_nonce( 'obliterate' . get_current_user_id() ) ) ?>";
                    }

                    setTimeout("nextpage()", 1500);
                </script>
                <?php
            }

            /* Addresses */
            if ( isset( $_GET['obliterate'] ) && $_GET['step'] === '6' && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'obliterate' . get_current_user_id() ) ){
                global $wpdb;
                $results = $wpdb->get_results( "SELECT pm.meta_id FROM $wpdb->posts as p JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id WHERE pm.meta_key LIKE 'contact_address%' AND pm.meta_key NOT LIKE '%details' ", ARRAY_A );
                foreach ( $results as $row ){
                    $address = "Fake Address, City, State, Zip";
                    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d; ", $address, $row['meta_id'] ) );
                }
                ?>
                <tr>
                    <td>Contacts Obliterated</td>
                </tr>
                <tr>
                    <td>Groups Obliterated</td>
                </tr>
                <tr>
                    <td>User Display Names Obliterated</td>
                </tr>
                <tr>
                    <td>User Emails Obliterated</td>
                </tr>
                <tr>
                    <td>Contact Phone Numbers Obliterated</td>
                </tr>
                <tr>
                    <td>Contact and Group Addresses Obliterated</td>
                </tr>
                <tr>
                    <td><img src="<?php echo esc_url( get_theme_file_uri() ) ?>/spinner.svg" width="30px"
                             alt="spinner"/></td>
                </tr>
                <script type="text/javascript">
                    function nextpage() {
                        location.href = "<?php echo esc_html( admin_url() ) ?>admin.php?page=<?php echo esc_attr( $this->token )  ?>&obliterate=true&step=7&nonce=<?php echo esc_html( wp_create_nonce( 'obliterate' . get_current_user_id() ) ) ?>";
                    }

                    setTimeout("nextpage()", 1500);
                </script>
                <?php
            }

            /* Contact Emails */
            if ( isset( $_GET['obliterate'] ) && $_GET['step'] === '7' && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'obliterate' . get_current_user_id() ) ){
                global $wpdb;
                $results = $wpdb->get_results( "SELECT pm.meta_id FROM $wpdb->posts as p JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id WHERE pm.meta_key LIKE 'contact_email%' AND pm.meta_key NOT LIKE '%details' ", ARRAY_A );
                $alphabet = [ 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' ];
                foreach ( $results as $row ){
                    $hash_first = substr( strtolower( preg_replace( '/[0-9_\/]+/', '', base64_encode( hash( 'sha256', $row['meta_id'] ) ) ) ), 0, rand( 4, 7 ) );
                    $name = strtolower( $alphabet[rand( 0, 25 )] ) . $hash_first . '@local.email.com';
                    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d; ", $name, $row['meta_id'] ) );
                }
                ?>
                <tr>
                    <td>Contacts Obliterated</td>
                </tr>
                <tr>
                    <td>Groups Obliterated</td>
                </tr>
                <tr>
                    <td>User Display Names Obliterated</td>
                </tr>
                <tr>
                    <td>User Emails Obliterated</td>
                </tr>
                <tr>
                    <td>Contact Phone Numbers Obliterated</td>
                </tr>
                <tr>
                    <td>Contact and Group Addresses Obliterated</td>
                </tr>
                <tr>
                    <td>Contact Emails Obliterated</td>
                </tr>
                <script type="text/javascript">
                    function nextpage() {
                        location.href = "<?php echo esc_html( admin_url() ) ?>admin.php?page=<?php echo esc_attr( $this->token )  ?>&obliterate=true&step=8&nonce=<?php echo esc_html( wp_create_nonce( 'obliterate' . get_current_user_id() ) ) ?>";
                    }

                    setTimeout("nextpage()", 1500);
                </script>
                <?php
            }

            /* Delete Activity */
            if ( isset( $_GET['obliterate'] ) && $_GET['step'] === '8' && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'obliterate' . get_current_user_id() ) ){
                global $wpdb;
                $results = $wpdb->query( "DELETE FROM $wpdb->dt_activity_log WHERE object_type = 'contacts' AND action = 'field_update' AND ( object_subtype LIKE 'contact%' OR object_subtype = 'assigned_to'); " );
                $results = $wpdb->query( "DELETE FROM $wpdb->comments WHERE comment_content LIKE '%@%';" );
                ?>
                <tr>
                    <td>Contacts Obliterated</td>
                </tr>
                <tr>
                    <td>Groups Obliterated</td>
                </tr>
                <tr>
                    <td>User Display Names Obliterated</td>
                </tr>
                <tr>
                    <td>User Emails Obliterated</td>
                </tr>
                <tr>
                    <td>Contact Phone Numbers Obliterated</td>
                </tr>
                <tr>
                    <td>Contact and Group Addresses Obliterated</td>
                </tr>
                <tr>
                    <td>Contact Emails Obliterated</td>
                </tr>
                <tr>
                    <td>Activity & Notifications Obliterated</td>
                </tr>
                <tr>
                    <td>Finished!</td>
                </tr>
                <?php
            }
            ?>

            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column(){
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>WARNING!!</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    This plugin has one purpose and once you click go it is done. This plugin is intended to
                    destructively wipe and replace all Personally Identifiable Information. It leaves a few traces in
                    the database of original names, but all visible locations are replaced with hash strings.<br><br>
                    The primary use for this is for developers working on their local machine with copies of sites with
                    sensitive data.
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public static function activation(){

    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public static function deactivation(){

    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @return string
     * @since  0.1
     * @access public
     */
    public function __toString(){
        return $this->token;
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public function __clone(){
        _doing_it_wrong( __FUNCTION__, esc_html( 'Whoah, partner!' ), '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public function __wakeup(){
        _doing_it_wrong( __FUNCTION__, esc_html( 'Whoah, partner!' ), '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     *
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ){
        // @codingStandardsIgnoreLine
        _doing_it_wrong( __FUNCTION__, esc_html( 'Whoah, partner!' ), '0.1' );
        unset( $method, $args );
        return null;
    }
}

// Register activation hook.
register_activation_hook( __FILE__, [ 'PII_Obliterator', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'PII_Obliterator', 'deactivation' ] );