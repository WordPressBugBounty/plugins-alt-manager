<?php

/**
 * Alt Manager Functions
 *
 * @package ALM
 * @author WPSAAD
 * @since 1.0.0
 */
if ( !function_exists( 'alm_image_attributes' ) ) {
    //Alm change alt and title hook
    add_filter(
        'wp_get_attachment_image_attributes',
        'alm_image_attributes',
        PHP_INT_MAX,
        2
    );
    /**
     * Modify image attributes (alt and title) based on page/post/product type
     *
     * @param array   $attr       Image attributes.
     * @param WP_Post $attachment Attachment post object.
     * @return array Modified attributes.
     */
    function alm_image_attributes(  $attr, $attachment  ) {
        // Get post parent
        // $parent = get_post_field('post_parent', $attachment->ID);
        $parent = wp_get_post_parent_id( $attachment->ID );
        // If no parent or more than one parent, use current page ID as its context
        if ( $parent == 0 ) {
            $parent = get_the_ID();
        }
        $page_id = get_queried_object_id();
        if ( empty( $page_id ) ) {
            $page_id = get_the_ID();
        }
        $context_id = ( !empty( $parent ) ? absint( $parent ) : absint( $page_id ) );
        // Get post type
        $type = get_post_field( 'post_type', $context_id );
        // options
        $options = [
            'Site Name'        => sanitize_text_field( get_bloginfo( 'name' ) ),
            'Site Description' => sanitize_text_field( get_bloginfo( 'description' ) ),
            'Page Title'       => sanitize_text_field( get_the_title( $context_id ) ),
            'Post Title'       => sanitize_text_field( get_post_field( 'post_title', $context_id ) ),
            'Product Title'    => sanitize_text_field( get_post_field( 'post_title', $context_id ) ),
        ];
        //wp image attachment data
        if ( wp_attachment_is_image( $attachment->ID ) ) {
            $options['Image Alt'] = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
            $options['Image Name'] = get_the_title( $attachment->ID );
            $options['Image Caption'] = $attachment->post_excerpt;
            $options['Image Description'] = $attachment->post_content;
        }
        $generate_empty_alt = alm_get_option( 'only_empty_images_alt' );
        $generate_empty_title = alm_get_option( 'only_empty_images_title' );
        /*Check If its logo*/
        $logo_checker = false;
        if ( isset( $attr['class'] ) && strpos( $attr['class'], 'logo' ) !== false ) {
            $logo_checker = true;
        }
        /*Set logo Image Alt and Title*/
        if ( $logo_checker ) {
            $alt = $options['Site Name'] . ' | ' . $options['Site Description'];
            $title = $options['Site Name'] . ' | ' . $options['Site Description'];
            $attr['alt'] = esc_attr( $alt );
            $attr['title'] = esc_attr( $title );
        }
        if ( !$logo_checker ) {
            //check page type
            if ( empty( $parent ) && is_page( $page_id ) ) {
                $alt = '';
                $title = '';
                //Page Images Alt
                if ( !empty( alm_get_option( 'pages_images_alt' ) ) && is_array( alm_get_option( 'pages_images_alt' ) ) ) {
                    foreach ( alm_get_option( 'pages_images_alt' ) as $option ) {
                        if ( array_key_exists( $option, $options ) ) {
                            $alt .= $options[$option];
                        } else {
                            $alt .= $option;
                        }
                    }
                } elseif ( !empty( alm_get_option( 'pages_images_alt' ) ) && !is_array( alm_get_option( 'pages_images_alt' ) ) ) {
                    $alt = $options[alm_get_option( 'pages_images_alt' )];
                }
                //Empty alt option
                if ( 'enabled' === $generate_empty_alt && empty( $attr['alt'] ) ) {
                    $attr['alt'] = esc_attr( $alt );
                } elseif ( 'enabled' === $generate_empty_alt && !empty( $attr['alt'] ) ) {
                    $attr['alt'] = esc_attr( $attr['alt'] );
                } else {
                    $attr['alt'] = esc_attr( $alt );
                }
                //Page images title
                if ( !empty( alm_get_option( 'pages_images_title' ) ) && is_array( alm_get_option( 'pages_images_title' ) ) ) {
                    foreach ( alm_get_option( 'pages_images_title' ) as $option ) {
                        if ( array_key_exists( $option, $options ) ) {
                            $title .= $options[$option];
                        } else {
                            $title .= ' ' . $option . ' ';
                        }
                    }
                } elseif ( !empty( alm_get_option( 'pages_images_title' ) ) && !is_array( alm_get_option( 'pages_images_title' ) ) ) {
                    $title = $options[alm_get_option( 'pages_images_title' )];
                }
                //Empty title option
                if ( 'enabled' === $generate_empty_title && empty( get_the_title( $attachment->ID ) ) ) {
                    $attr['title'] = esc_attr( $title );
                } elseif ( 'enabled' === $generate_empty_title && !empty( get_the_title( $attachment->ID ) ) ) {
                    $attr['title'] = esc_attr( get_the_title( $attachment->ID ) );
                } else {
                    $attr['title'] = esc_attr( $title );
                }
            }
            //check homepage
            if ( empty( $parent ) && is_front_page() ) {
                $alt = '';
                $title = '';
                //Homepage Images Alt
                if ( !empty( alm_get_option( 'home_images_alt' ) ) && is_array( alm_get_option( 'home_images_alt' ) ) ) {
                    foreach ( alm_get_option( 'home_images_alt' ) as $option ) {
                        if ( array_key_exists( $option, $options ) ) {
                            $alt .= $options[$option];
                        } else {
                            $alt .= $option;
                        }
                    }
                } elseif ( !empty( alm_get_option( 'home_images_alt' ) ) && !is_array( alm_get_option( 'home_images_alt' ) ) ) {
                    $alt = $options[alm_get_option( 'home_images_alt' )];
                }
                if ( 'enabled' === $generate_empty_alt && empty( $attr['alt'] ) ) {
                    $attr['alt'] = esc_attr( $alt );
                } elseif ( 'enabled' === $generate_empty_alt && !empty( $attr['alt'] ) ) {
                    $attr['alt'] = esc_attr( $attr['alt'] );
                } else {
                    $attr['alt'] = esc_attr( $alt );
                }
                //Homepage images title
                if ( !empty( alm_get_option( 'home_images_title' ) ) && is_array( alm_get_option( 'home_images_title' ) ) ) {
                    foreach ( alm_get_option( 'home_images_title' ) as $option ) {
                        if ( array_key_exists( $option, $options ) ) {
                            $title .= $options[$option];
                        } else {
                            $title .= ' ' . $option . ' ';
                        }
                    }
                } elseif ( !empty( alm_get_option( 'home_images_title' ) ) && !is_array( alm_get_option( 'home_images_title' ) ) ) {
                    $title = $options[alm_get_option( 'home_images_title' )];
                }
                //Empty title option
                if ( 'enabled' === $generate_empty_title && empty( get_the_title( $attachment->ID ) ) ) {
                    $attr['title'] = esc_attr( $title );
                } elseif ( 'enabled' === $generate_empty_title && !empty( get_the_title( $attachment->ID ) ) ) {
                    $attr['title'] = esc_attr( get_the_title( $attachment->ID ) );
                } else {
                    $attr['title'] = esc_attr( $title );
                }
            }
            //check post type
            if ( 'post' === $type && (!empty( $parent ) || is_single( $page_id ) || is_singular( 'post' )) ) {
                $alt = '';
                $title = '';
                //Posts Images Alt
                if ( !empty( alm_get_option( 'post_images_alt' ) ) && is_array( alm_get_option( 'post_images_alt' ) ) ) {
                    foreach ( alm_get_option( 'post_images_alt' ) as $option ) {
                        if ( array_key_exists( $option, $options ) ) {
                            $alt .= $options[$option];
                        } else {
                            $alt .= ' ' . $option . ' ';
                        }
                    }
                } elseif ( !empty( alm_get_option( 'post_images_alt' ) ) && !is_array( alm_get_option( 'post_images_alt' ) ) ) {
                    $alt = $options[alm_get_option( 'post_images_alt' )];
                }
                //Empty alt option
                if ( 'enabled' === $generate_empty_alt && empty( $attr['alt'] ) ) {
                    $attr['alt'] = esc_attr( $alt );
                } elseif ( 'enabled' === $generate_empty_alt && !empty( $attr['alt'] ) ) {
                    $attr['alt'] = esc_attr( $attr['alt'] );
                } else {
                    $attr['alt'] = esc_attr( $alt );
                }
                //Posts images title
                if ( !empty( alm_get_option( 'post_images_title' ) ) && is_array( alm_get_option( 'post_images_title' ) ) ) {
                    foreach ( alm_get_option( 'post_images_title' ) as $option ) {
                        if ( array_key_exists( $option, $options ) ) {
                            $title .= $options[$option];
                        } else {
                            $title .= ' ' . $option . ' ';
                        }
                    }
                } elseif ( !empty( alm_get_option( 'post_images_title' ) ) && !is_array( alm_get_option( 'post_images_title' ) ) ) {
                    $title = $options[alm_get_option( 'post_images_title' )];
                }
                //Empty title option
                if ( 'enabled' === $generate_empty_title && empty( get_the_title( $attachment->ID ) ) ) {
                    $attr['title'] = esc_attr( $title );
                } elseif ( 'enabled' === $generate_empty_title && !empty( get_the_title( $attachment->ID ) ) ) {
                    $attr['title'] = esc_attr( get_the_title( $attachment->ID ) );
                } else {
                    $attr['title'] = esc_attr( $title );
                }
            }
        }
        return $attr;
    }

}