<?php
/*
Plugin Name:        Log cleaner for Kadence Security
Plugin URI:         https://wordpress.org/plugins/log-cleaner-for-ithemes-security/
Description:        Delete Kadence Security (formerly Solid Security / iThemes Security) logs from the database.
Version:            1.5.1
Requires at least:  6.0
Requires PHP:       7.4
Requires Plugins:   better-wp-security
Author:             Rocket Apps
Author URI:         https://rocketapps.com.au/
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:        log-cleaner-for-ithemes-security
Domain Path:        /languages/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LCIS_VERSION', '1.5.1' );
define( 'LCIS_SLUG', 'log-cleaner-for-ithemes-security' );

// Load translations.
function lcis_load_textdomain() {
    load_plugin_textdomain( 'log-cleaner-for-ithemes-security', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'lcis_load_textdomain' );

// Capability required to view the page and delete logs.
function lcis_capability() {
    return is_multisite() ? 'manage_network_options' : 'manage_options';
}

// Tables managed by this plugin. Kadence Security uses network-wide tables on multisite.
function lcis_tables() {
    global $wpdb;

    return array(
        'logs'      => array( 'table' => $wpdb->base_prefix . 'itsec_logs',                'label' => __( 'Security logs', 'log-cleaner-for-ithemes-security' ) ),
        'dashboard' => array( 'table' => $wpdb->base_prefix . 'itsec_dashboard_events',    'label' => __( 'Dashboard logs', 'log-cleaner-for-ithemes-security' ) ),
        'dash_lock' => array( 'table' => $wpdb->base_prefix . 'itsec_dashboard_lockouts',  'label' => __( 'Dashboard lockouts', 'log-cleaner-for-ithemes-security' ) ),
        'lockouts'  => array( 'table' => $wpdb->base_prefix . 'itsec_lockouts',            'label' => __( 'Lockouts', 'log-cleaner-for-ithemes-security' ) ),
        'temp'      => array( 'table' => $wpdb->base_prefix . 'itsec_temp',                'label' => __( 'Temps', 'log-cleaner-for-ithemes-security' ) ),
        'geo'       => array( 'table' => $wpdb->base_prefix . 'itsec_geolocation_cache',   'label' => __( 'Geolocation cache', 'log-cleaner-for-ithemes-security' ) ),
        'ds'        => array( 'table' => $wpdb->base_prefix . 'itsec_distributed_storage', 'label' => __( 'Distributed Storage', 'log-cleaner-for-ithemes-security' ) ),
    );
}

function lcis_table_exists( $table ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
}

// Row count, or 0 if the table doesn't exist.
function lcis_row_count( $table ) {
    global $wpdb;

    if ( ! lcis_table_exists( $table ) ) {
        return 0;
    }

    return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Disk space (data + indexes) in bytes, keyed by table name.
function lcis_table_sizes( $table_names ) {
    global $wpdb;

    // MySQL 8 caches these stats for up to 24 hours by default.
    if ( $wpdb->get_var( "SHOW VARIABLES LIKE 'information_schema_stats_expiry'" ) ) {
        $wpdb->query( 'SET SESSION information_schema_stats_expiry = 0' );
    }

    $placeholders = implode( ', ', array_fill( 0, count( $table_names ), '%s' ) );
    $results      = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT TABLE_NAME AS name, DATA_LENGTH + INDEX_LENGTH AS bytes FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ( $placeholders )",
            $table_names
        )
    );

    $sizes = array();
    foreach ( (array) $results as $row ) {
        $sizes[ $row->name ] = (int) $row->bytes;
    }

    return $sizes;
}

// Menu icon as a base64 SVG data URI, sized by WordPress as a 20px background image.
function lcis_menu_icon() {
    $svg = file_get_contents( plugin_dir_path( __FILE__ ) . 'images/shield.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
    return $svg ? 'data:image/svg+xml;base64,' . base64_encode( $svg ) : 'dashicons-shield'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

// Admin menu.
function lcis_register_menu() {
    global $lcis_hook;

    if ( is_multisite() ) {
        $lcis_hook = add_menu_page(
            __( 'Kadence Security Log Cleaner', 'log-cleaner-for-ithemes-security' ),
            __( 'Kadence Security Log Cleaner', 'log-cleaner-for-ithemes-security' ),
            lcis_capability(),
            LCIS_SLUG,
            'lcis_render_page',
            lcis_menu_icon()
        );
    } else {
        $lcis_hook = add_management_page(
            __( 'Kadence Security Log Cleaner', 'log-cleaner-for-ithemes-security' ),
            __( 'Kadence Security Log Cleaner', 'log-cleaner-for-ithemes-security' ),
            lcis_capability(),
            LCIS_SLUG,
            'lcis_render_page'
        );
    }
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'lcis_register_menu' );

// Assets, only on the plugin page.
function lcis_admin_assets( $hook ) {
    global $lcis_hook;

    if ( empty( $lcis_hook ) || $hook !== $lcis_hook ) {
        return;
    }

    wp_enqueue_style( 'log-cleaner-style-admin', plugins_url( 'css/log-cleaner.css', __FILE__ ), array(), LCIS_VERSION );

    wp_enqueue_script( 'jquery' );
    $js = <<<'JS'
        jQuery(function($) {
            var $wrap  = $('.log-cleaner');
            var $items = $wrap.find('.other-items');

            $wrap.find('#lcis-all').on('change', function() {
                $items.prop('checked', this.checked).closest('tr').toggleClass('active', this.checked);
                $(this).closest('tr').toggleClass('active', this.checked);
            });

            $items.on('change', function() {
                $(this).closest('tr').toggleClass('active', this.checked);
                var allChecked = $items.length === $items.filter(':checked').length;
                $wrap.find('#lcis-all').prop('checked', allChecked).closest('tr').toggleClass('active', allChecked);
            });

            $('#lcis-submit').on('click', function() {
                return confirm($(this).data('confirm'));
            });
        });
JS;
    wp_add_inline_script( 'jquery', $js );
}
add_action( 'admin_enqueue_scripts', 'lcis_admin_assets' );

// Handle the delete request. Returns 'deleted', 'none' or null.
function lcis_handle_delete( $tables ) {
    global $wpdb;

    if ( ! isset( $_POST['lcis_submit'] ) ) {
        return null;
    }

    if ( ! current_user_can( lcis_capability() ) ) {
        wp_die( esc_html__( 'You do not have permission to do that.', 'log-cleaner-for-ithemes-security' ) );
    }

    check_admin_referer( 'lcis-delete-logs', 'lcis_nonce' );

    $selected = isset( $_POST['lcis_tables'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['lcis_tables'] ) ) : array();

    if ( isset( $_POST['lcis_all'] ) ) {
        $selected = array_keys( $tables );
    }

    $selected = array_intersect( $selected, array_keys( $tables ) );

    if ( empty( $selected ) ) {
        return 'none';
    }

    foreach ( $selected as $key ) {
        $table = $tables[ $key ]['table'];
        if ( lcis_table_exists( $table ) ) {
            $wpdb->query( "TRUNCATE TABLE `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
    }

    return 'deleted';
}

// Admin page.
function lcis_render_page() {

    if ( ! current_user_can( lcis_capability() ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'log-cleaner-for-ithemes-security' ) );
    }

    $tables = lcis_tables();
    $result = lcis_handle_delete( $tables );

    $counts = array();
    foreach ( $tables as $key => $info ) {
        $counts[ $key ] = lcis_row_count( $info['table'] );
    }
    $total = array_sum( $counts );

    $sizes      = lcis_table_sizes( wp_list_pluck( $tables, 'table' ) );
    $total_size = 0;
    foreach ( array_filter( $counts ) as $key => $count ) {
        $total_size += isset( $sizes[ $tables[ $key ]['table'] ] ) ? $sizes[ $tables[ $key ]['table'] ] : 0;
    }

    // Only list tables that have rows.
    $rows = array_filter( $counts );

    $log_link = is_multisite() ? network_admin_url( 'admin.php?page=itsec-logs' ) : admin_url( 'admin.php?page=itsec-logs' );
    ?>

    <div class="wrap kadence-security-log-cleaner">

        <h1><?php esc_html_e( 'Log cleaner for Kadence Security', 'log-cleaner-for-ithemes-security' ); ?></h1>

        <?php if ( 'deleted' === $result ) { ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'The selected logs have been deleted.', 'log-cleaner-for-ithemes-security' ); ?></p>
            </div>
        <?php } elseif ( 'none' === $result ) { ?>
            <div class="notice notice-error is-dismissible">
                <p><?php esc_html_e( 'You need to select at least one item to delete.', 'log-cleaner-for-ithemes-security' ); ?></p>
            </div>
        <?php } ?>

        <?php if ( $total > 0 ) { ?>

            <p class="delete-logs-notice">
                <?php
                printf(
                    wp_kses(
                        /* translators: %s: URL of the Kadence Security logs page. */
                        __( "Note: Continuing here is irreversible. You absolutely can not undo this action. If in doubt, <a href='%s'>view the logs first</a> or backup your database.", 'log-cleaner-for-ithemes-security' ),
                        array( 'a' => array( 'href' => array() ) )
                    ),
                    esc_url( $log_link )
                );
                ?>
            </p>

            <form action="" method="post">

                <div class="log-cleaner boxy">

                    <p><strong><?php esc_html_e( 'Clear data from the following tables:', 'log-cleaner-for-ithemes-security' ); ?></strong></p>

                    <table>
                        <tbody>
                            <tr class="all-items">
                                <td><input type="checkbox" name="lcis_all" id="lcis-all" value="1" /></td>
                                <td><label for="lcis-all"><?php esc_html_e( 'All', 'log-cleaner-for-ithemes-security' ); ?></label></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <?php foreach ( $rows as $key => $count ) { ?>
                            <tr>
                                <td><input type="checkbox" name="lcis_tables[]" id="lcis-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>" class="other-items" /></td>
                                <td><label for="lcis-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $tables[ $key ]['label'] ); ?></label></td>
                                <td><?php echo esc_html( number_format_i18n( $count ) ); ?> <?php esc_html_e( 'rows', 'log-cleaner-for-ithemes-security' ); ?></td>
                                <td class="size"><?php echo esc_html( size_format( isset( $sizes[ $tables[ $key ]['table'] ] ) ? $sizes[ $tables[ $key ]['table'] ] : 0, 1 ) ); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr class="total">
                                <td></td>
                                <td><?php esc_html_e( 'Total:', 'log-cleaner-for-ithemes-security' ); ?></td>
                                <td><?php echo esc_html( number_format_i18n( $total ) ); ?> <?php esc_html_e( 'rows', 'log-cleaner-for-ithemes-security' ); ?></td>
                                <td class="size"><?php echo esc_html( size_format( $total_size, 1 ) ); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php wp_nonce_field( 'lcis-delete-logs', 'lcis_nonce' ); ?>
                <input type="submit" name="lcis_submit" id="lcis-submit" class="button button-primary" value="<?php esc_attr_e( 'Clear logs', 'log-cleaner-for-ithemes-security' ); ?>" data-confirm="<?php esc_attr_e( 'This is your last chance. Are you sure?', 'log-cleaner-for-ithemes-security' ); ?>" />

            </form>

        <?php } else { ?>
            <p class="all-clear">&#10004; <?php esc_html_e( 'All logs are clear.', 'log-cleaner-for-ithemes-security' ); ?></p>
        <?php } ?>

    </div>

<?php }