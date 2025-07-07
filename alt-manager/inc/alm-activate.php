<?php

class almActivate {
    public static function activate() {
        $home_images_alt = alm_get_option( 'home_images_alt' );
        $home_images_title = alm_get_option( 'home_images_title' );
        $pages_images_alt = alm_get_option( 'pages_images_alt' );
        $pages_images_title = alm_get_option( 'pages_images_title' );
        $post_images_alt = alm_get_option( 'post_images_alt' );
        $post_images_title = alm_get_option( 'post_images_title' );
        // Basic Fields default values
        if ( !$home_images_alt && !$home_images_title && !$pages_images_alt && !$pages_images_title && !$post_images_alt && !$post_images_title ) {
            alm_update_option( 'home_images_alt', ['Site Name', '|', 'Page Title'] );
            alm_update_option( 'home_images_title', ['Site Name', '|', 'Page Title'] );
            alm_update_option( 'pages_images_alt', ['Site Name', '|', 'Page Title'] );
            alm_update_option( 'pages_images_title', ['Site Name', '|', 'Page Title'] );
            alm_update_option( 'post_images_alt', ['Site Name', '|', 'Post Title'] );
            alm_update_option( 'post_images_title', ['Site Name', '|', 'Post Title'] );
            alm_update_option( 'product_images_alt', ['Site Name', '|', 'Product Title'] );
            alm_update_option( 'product_images_title', ['Site Name', '|', 'Product Title'] );
            alm_update_option( 'cpt_images_alt', ['Site Name', '|', 'Post Title'] );
            alm_update_option( 'cpt_images_title', ['Site Name', '|', 'Post Title'] );
        }
    }

    public static function reset() {
        alm_update_option( 'home_images_alt', ['Site Name', '|', 'Page Title'] );
        alm_update_option( 'home_images_title', ['Site Name', '|', 'Page Title'] );
        alm_update_option( 'pages_images_alt', ['Site Name', '|', 'Page Title'] );
        alm_update_option( 'pages_images_title', ['Site Name', '|', 'Page Title'] );
        alm_update_option( 'post_images_alt', ['Site Name', '|', 'Post Title'] );
        alm_update_option( 'post_images_title', ['Site Name', '|', 'Post Title'] );
    }

}
