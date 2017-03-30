<?php
/**
 * Plugin Name: My Custom Functions
 * Plugin URI: https://github.com/ArthurGareginyan/my-custom-functions
 * Description: Easily and safely add your custome functions (PHP code) directly out of your WordPress Dashboard without need of an external editor.
 * Author: Arthur Gareginyan
 * Author URI: http://www.arthurgareginyan.com
 * Version: 3.5
 * License: GPL3
 * Text Domain: my-custom-functions
 * Domain Path: /languages/
 *
 * Copyright 2014-2016 Arthur Gareginyan (email : arthurgareginyan@gmail.com)
 *
 * This file is part of "My Custom Functions".
 *
 * "My Custom Functions" is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * "My Custom Functions" is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with "My Custom Functions".  If not, see <http://www.gnu.org/licenses/>.
 *
 */


/**
 * Prevent Direct Access
 *
 * @since 0.1
 */
defined('ABSPATH') or die("Restricted access!");

/**
 * Define constants
 *
 * @since 3.5
 */
defined('MCFUNC_DIR') or define('MCFUNC_DIR', dirname(plugin_basename(__FILE__)));
defined('MCFUNC_BASE') or define('MCFUNC_BASE', plugin_basename(__FILE__));
defined('MCFUNC_URL') or define('MCFUNC_URL', plugin_dir_url(__FILE__));
defined('MCFUNC_PATH') or define('MCFUNC_PATH', plugin_dir_path(__FILE__));
defined('MCFUNC_TEXT') or define('MCFUNC_TEXT', 'my-custom-functions');
defined('MCFUNC_VERSION') or define('MCFUNC_VERSION', '3.5');

/**
 * Register text domain
 *
 * @since 2.2
 */
function MCFunctions_textdomain() {
    load_plugin_textdomain( MCFUNC_TEXT, false, MCFUNC_DIR . '/languages/' );
}
add_action( 'init', 'MCFunctions_textdomain' );

/**
 * Print direct link to Custom Functions admin page
 *
 * Fetches array of links generated by WP Plugin admin page ( Deactivate | Edit )
 * and inserts a link to the Custom Functions admin page
 *
 * @since  2.2
 * @param  array $links Array of links generated by WP in Plugin Admin page.
 * @return array        Array of links to be output on Plugin Admin page.
 */
function MCFunctions_settings_link( $links ) {
    $settings_page = '<a href="' . admin_url( 'themes.php?page=my-custom-functions.php' ) .'">' . __( 'Settings', MCFUNC_TEXT ) . '</a>';
    array_unshift( $links, $settings_page );
    return $links;
}
add_filter( 'plugin_action_links_'.MCFUNC_BASE, 'MCFunctions_settings_link' );

/**
 * Register "Custom Functions" submenu in "Appearance" Admin Menu
 *
 * @since 2.2
 */
function MCFunctions_register_submenu_page() {
    add_theme_page( __( 'My Custom Functions', MCFUNC_TEXT ), __( 'Custom Functions', MCFUNC_TEXT ), 'edit_theme_options', basename( __FILE__ ), 'MCFunctions_render_submenu_page' );
}
add_action( 'admin_menu', 'MCFunctions_register_submenu_page' );

/**
 * Attach Settings Page
 *
 * @since 3.0
 */
require_once( MCFUNC_PATH . 'inc/php/settings_page.php' );

/**
 * Register settings
 *
 * @since 2.0
 */
function MCFunctions_register_settings() {
    register_setting( 'anarcho_cfunctions_settings_group', 'anarcho_cfunctions_settings' );
    register_setting( 'anarcho_cfunctions_settings_group', 'anarcho_cfunctions_error' );
}
add_action( 'admin_init', 'MCFunctions_register_settings' );

/**
 * Load scripts and style sheet for settings page
 *
 * @since 3.1
 */
function MCFunctions_load_scripts($hook) {

    // Return if the page is not a settings page of this plugin
    if ( 'appearance_page_my-custom-functions' != $hook ) {
        return;
    }

    // Style sheet
    wp_enqueue_style( 'MCFunctions-admin-css', MCFUNC_URL . 'inc/css/admin.css' );

    // JavaScript
    wp_enqueue_script( 'MCFunctions-admin-js', MCFUNC_URL . 'inc/js/admin.js', array(), false, true );

    // CodeMirror
    wp_enqueue_style( 'MCFunctions-codemirror-css', MCFUNC_URL . 'inc/lib/codemirror/codemirror.css' );
    wp_enqueue_script( 'MCFunctions-codemirror-js', MCFUNC_URL . 'inc/lib/codemirror/codemirror-compressed.js' );
    wp_enqueue_script( 'MCFunctions-codemirror-active-line', MCFUNC_URL . 'inc/lib/codemirror/addons/active-line.js' );

}
add_action( 'admin_enqueue_scripts', 'MCFunctions_load_scripts' );

/**
 * Prepare the user entered code for execution
 *
 * @since 2.4
 */
function MCFunctions_prepare($content) {

    // Cleaning
    $content = trim( $content );
    $content = ltrim( $content, '<?php' );
    $content = rtrim( $content, '?>' );

    // Return prepared code
    return $content;
}

/**
 * Check the user entered code for duplicate names of functions
 *
 * @since 2.5.1
 */
function MCFunctions_duplicates($content) {

    // Find names of user entered functions and check for duplicates
    preg_match_all('/function[\s\n]+(\S+)[\s\n]*\(/i', $content, $user_func_names);
    $user_func_a = count( $user_func_names[1] );
    $user_func_b = count( array_unique( $user_func_names[1] ) );

    // Find all names of declared user functions and mutch with names of user entered functions
    $declared_func = get_defined_functions();
    $declared_func_user = array_intersect( $user_func_names[1], $declared_func['user'] );
    $declared_func_internal = array_intersect( $user_func_names[1], $declared_func['internal'] );

    // Update error status
    if ( $user_func_a != $user_func_b OR count( $declared_func_user ) != 0 OR count( $declared_func_internal ) != 0 ) {
        update_option( 'anarcho_cfunctions_error', '1' );   // ERROR
        $error_status = '1';
    } else {
        update_option( 'anarcho_cfunctions_error', '0' );   // RESET ERROR VALUE
        $error_status = '0';
    }

    // Return error status
    return $error_status;
}

/**
 * Execute the user entered code
 *
 * @since 3.2
 */
function MCFunctions_exec() {

    // If STOP file exist...
    if ( file_exists( MCFUNC_PATH . 'STOP' ) ) {
        return;   // EXIT
    }

    // Read data from DB
    $options = get_option( 'anarcho_cfunctions_settings' );
    $content = isset( $options['anarcho_cfunctions-content'] ) && !empty( $options['anarcho_cfunctions-content'] ) ? $options['anarcho_cfunctions-content'] : ' ';
    $enable = isset( $options['enable'] ) && !empty( $options['enable'] ) ? $options['enable'] : ' ';

    // If the user entered code is disabled...
    if ( $enable == 'on') {
        return;   // EXIT
    }

    // Prepare the user entered functions by calling the "prepare" function
    $content = MCFunctions_prepare($content);

    // If content is empty...
    if ( empty($content) OR $content == ' ' ) {
        return;   // EXIT
    }

    // If the duplicates functions finded...
    $duplicates = MCFunctions_duplicates($content);
    if ( $duplicates != 0 ) {
        return;   // EXIT
    }

    // Parsing and execute by Eval
    if( false === @eval( $content ) ) {
        update_option( 'anarcho_cfunctions_error', '1' );   // ERROR
        return;   // EXIT
    } else {
        update_option( 'anarcho_cfunctions_error', '0' );   // RESET ERROR VALUE
    }
}
MCFunctions_exec();

/**
 * Delete options on uninstall
 *
 * @since 0.1
 */
function MCFunctions_uninstall() {
    delete_option( 'anarcho_cfunctions_settings' );
    delete_option( 'anarcho_cfunctions_error' );
}
register_uninstall_hook( __FILE__, 'MCFunctions_uninstall' );

?>