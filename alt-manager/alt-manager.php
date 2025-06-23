<?php

/**
 * @package ALM
 * @author WPSAAD
 * @link https://wpsaad.com
 * @since 1.0.0
 */
/**
 * Plugin Name: Image Alt Text Manager
 * plugin URI: https://wpsaad.com/alt-manager-wordpress-image-alt-text-plugin/
 * Description:Automatically bulk change images alt text to dynamic alt tags values related to content or media and also generate empty values for both alt and title tags.
 * Version: 1.7.1
 * Author: WPSAAD
 * Author URI: https://wpsaad.com
 * License: GPLv2 or later
 * Text Domain: alt-manager
 * Domain Path: /languages
 */
defined( 'ABSPATH' ) or die;
if ( function_exists( 'am_fs' ) ) {
    am_fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'am_fs' ) ) {
        // Create a helper function for easy SDK access.
        function am_fs() {
            global $am_fs;
            if ( !isset( $am_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $am_fs = fs_dynamic_init( array(
                    'id'             => '5548',
                    'slug'           => 'alt-manager',
                    'type'           => 'plugin',
                    'navigation'     => 'tabs',
                    'public_key'     => 'pk_07c4f76da780308f88546ce3da78a',
                    'is_premium'     => false,
                    'premium_suffix' => 'premium plan',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                        'slug'   => 'alt-manager',
                        'parent' => array(
                            'slug' => 'options-general.php',
                        ),
                    ),
                    'is_live'        => true,
                ) );
            }
            return $am_fs;
        }

        // Init Freemius.
        am_fs();
        // Signal that SDK was initiated.
        do_action( 'am_fs_loaded' );
    }
    //add style
    add_action( 'admin_enqueue_scripts', 'alm_style' );
    function alm_style() {
        wp_enqueue_script( 'switcher-script', plugins_url( '/assets/js/jquery.switcher.min.js', __FILE__ ) );
        wp_enqueue_style( 'switcher-style', plugins_url( '/assets/css/switcher.css', __FILE__ ) );
        wp_enqueue_script( 'select2-script', plugins_url( '/assets/js/select2.min.js', __FILE__ ) );
        wp_enqueue_style( 'select2-style', plugins_url( '/assets/css/select2.min.css', __FILE__ ) );
        wp_enqueue_script( 'alm-admin-script', plugins_url( '/assets/js/alm-admin.js', __FILE__ ) );
        wp_enqueue_style( 'alm-admin-style', plugins_url( '/assets/css/alm-admin-styles.css', __FILE__ ) );
        if ( function_exists( 'am_fs' ) && am_fs()->is__premium_only() ) {
            //ai api check and ajax
            wp_enqueue_script(
                'alm-progress',
                plugins_url( '/assets/js/alm-progress.js', __FILE__ ),
                ['jquery'],
                '1.0',
                true
            );
            wp_localize_script( 'alm-progress', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
            // Pass the AJAX URL to the script
        }
        if ( is_rtl() ) {
            wp_enqueue_style( 'alm-admin-style-rtl', plugins_url( '/assets/css/alm-admin-styles-rtl.css', __FILE__ ) );
        }
        // wp_enqueue_script('jquery-ui-sortable');
    }

    //load plugin required files
    add_action( 'init', 'alm_load' );
    function alm_load() {
        // Add this helper at the top of your file
        function alm_get_option(  $option, $default = false  ) {
            if ( is_multisite() && is_network_admin() ) {
                return get_site_option( $option, $default );
            }
            return get_option( $option, $default );
        }

        require_once plugin_dir_path( __FILE__ ) . 'inc/alm-functions.php';
        require_once plugin_dir_path( __FILE__ ) . 'inc/alm-empty-generator.php';
        if ( user_can( get_current_user_id(), 'manage_options' ) ) {
            include_once plugin_dir_path( __FILE__ ) . 'inc/alm-admin.php';
        }
        if ( !function_exists( 'file_get_html' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'inc/simple_html_dom.php';
        }
    }

    //Generate activaition class
    if ( !class_exists( 'almActivate' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'inc/alm-activate.php';
    }
    //Activation Hook
    register_activation_hook( __FILE__, array('almActivate', 'activate') );
    //Activation & Reset
    add_action( 'admin_init', 'admin_page_functions' );
    function admin_page_functions() {
        //Reset Action
        if ( user_can( get_current_user_id(), 'manage_options' ) && isset( $_REQUEST['reset'] ) && wp_verify_nonce( $_POST['reset_nonce'], 'alm_reset_nonce' ) ) {
            $activate_reset = new almActivate();
            $activate_reset->reset();
        }
        if ( user_can( get_current_user_id(), 'manage_options' ) ) {
            include_once plugin_dir_path( __FILE__ ) . 'inc/alm-settings.php';
            if ( function_exists( 'am_fs' ) && am_fs()->is__premium_only() ) {
                //AI Generator Action
                include_once plugin_dir_path( __FILE__ ) . 'inc/ai-generator__premium_only.php';
            }
        }
    }

}