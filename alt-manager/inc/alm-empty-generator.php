<?php

class alm_dom_generator {
    function __construct() {
        add_action( 'template_redirect', [$this, 'alm_init'], PHP_INT_MAX );
        add_filter(
            'alm_output',
            [$this, 'alm_generator'],
            PHP_INT_MAX,
            1
        );
        // add_filter( 'the_content', [$this, 'alm_generator'], PHP_INT_MAX );
        // add_filter( 'woocommerce_single_product_image_thumbnail_html', [$this, 'alm_generator'], 0 );
        // add_filter( 'post_thumbnail_html', [$this, 'alm_generator'], PHP_INT_MAX );
    }

    // retrieves the attachment ID from the file URL
    function alm_is_featured( $url, $option = '' ) {
        // Check if the URL is empty
        if ( empty( $url ) ) {
            return false;
        }
        $dir = wp_upload_dir();
        switch ( $option ) {
            case 'ID':
                // Attempt to get attachment ID from URL
                $attachment_id = ( function_exists( 'attachment_url_to_postid' ) ? attachment_url_to_postid( $url ) : url_to_postid( $url ) );
                if ( !$attachment_id ) {
                    // Remove size from filename (e.g., -150x150)
                    $url = preg_replace( '/-\\d+x\\d+(?=\\.(jpg|jpeg|png|gif)$)/i', '', $url );
                    $attachment_id = attachment_url_to_postid( $url );
                }
                return ( $attachment_id ? $attachment_id : false );
            default:
                // Check if URL is a post thumbnail
                global $post;
                if ( $post ) {
                    $thumbnail_id = get_post_thumbnail_id( $post->ID );
                    $thumbnail_url = wp_get_attachment_url( $thumbnail_id );
                    if ( $thumbnail_url === $url ) {
                        return true;
                    }
                }
                return false;
        }
        return false;
    }

    function alm_init() {
        if ( !is_singular( array('post', 'page', 'product') ) ) {
            return;
        }
        function get_content(  $alm_data_generator  ) {
            return apply_filters( 'alm_output', $alm_data_generator );
        }

        ob_start( 'get_content' );
    }

    function alm_generator( $alm_data_generator ) {
        $html = str_get_html( $alm_data_generator );
        $generate_empty_alt = get_option( 'only_empty_images_alt' );
        $generate_empty_title = get_option( 'only_empty_images_title' );
        // var_dump($alm_data_generator);
        $ID = get_the_ID();
        // // Get post type
        $type = get_post_field( 'post_type', $ID );
        $post_types = get_post_types();
        $types = [];
        foreach ( $post_types as $t => $value ) {
            $types[] = $value;
        }
        $woo_checker = '';
        $classes = get_body_class();
        if ( !empty( $classes ) ) {
            if ( in_array( 'woocommerce-page', $classes ) ) {
                $woo_checker = true;
            } else {
                $woo_checker = false;
            }
        }
        if ( is_singular( $types ) && !is_admin() && !empty( $alm_data_generator ) ) {
            // print_r($alm_data_generator);
            foreach ( $html->find( 'img' ) as $img ) {
                $is_image_featured = $this->alm_is_featured( $img->getAttribute( 'src' ) );
                //WPML Compatibility Custom Alt
                if ( $img->getAttribute( 'class' ) == 'wpml-ls-flag' ) {
                    $next_sibling = $img->next_sibling();
                    if ( !empty( $next_sibling->innertext() ) ) {
                        $img->setAttribute( 'alt', $next_sibling->innertext() );
                    }
                }
                if ( $is_image_featured && $img->getAttribute( 'class' ) !== 'wpml-ls-flag' || empty( $img->getAttribute( 'alt' ) ) ) {
                    $attachment_id = $this->alm_is_featured( $img->getAttribute( 'src' ), 'ID' );
                    // $parent = get_post_field('post_parent', $attachment_id);
                    // options
                    $options = [
                        'Site Name'        => get_bloginfo( 'name' ),
                        'Site Description' => get_bloginfo( 'description' ),
                        'Page Title'       => get_the_title( $ID ),
                        'Post Title'       => get_post_field( 'post_title', $ID ),
                        'Product Title'    => get_post_field( 'post_title', $ID ),
                    ];
                    //wp image attachment data
                    if ( wp_attachment_is_image( $attachment_id ) ) {
                        $options['Image Alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                        $options['Image Name'] = get_the_title( $attachment_id );
                        $options['Image Caption'] = wp_get_attachment_caption( $attachment_id );
                        $options['Image Description'] = get_the_content( $attachment_id );
                    }
                    /*Check If its logo*/
                    $logo_checker = '';
                    $image_classes = explode( ' ', $img->getAttribute( 'class' ) );
                    foreach ( $image_classes as $image_class ) {
                        if ( strpos( $image_class, 'logo' ) !== false ) {
                            $logo_checker = true;
                        }
                    }
                    /*Set logo Image Alt and Title*/
                    if ( $logo_checker ) {
                        $alt = $options['Site Name'];
                        $title = $options['Site Name'];
                        $img->setAttribute( 'alt', $alt );
                        $img->setAttribute( 'title', $title );
                    }
                    if ( !$logo_checker ) {
                        //check page type
                        if ( is_page( $ID ) && !is_home( $ID ) && !is_front_page( $ID ) ) {
                            $alt = '';
                            $title = '';
                            //page images alt
                            if ( !empty( get_option( 'pages_images_alt' ) ) && is_array( get_option( 'pages_images_alt' ) ) ) {
                                foreach ( get_option( 'pages_images_alt' ) as $option ) {
                                    if ( array_key_exists( $option, $options ) ) {
                                        $alt .= $options[$option];
                                    } else {
                                        $alt .= ' ' . $option . ' ';
                                    }
                                }
                            } elseif ( !empty( get_option( 'pages_images_alt' ) ) && !is_array( get_option( 'pages_images_alt' ) ) ) {
                                $alt = $options[get_option( 'pages_images_alt' )];
                            }
                            //Empty alt option
                            if ( $generate_empty_alt == 'enabled' && empty( $img->getAttribute( 'alt' ) ) ) {
                                $img->setAttribute( 'alt', $alt );
                            } elseif ( $generate_empty_alt == 'enabled' && !empty( $img->getAttribute( 'alt' ) ) ) {
                                $img->setAttribute( 'alt', $img->getAttribute( 'alt' ) );
                            } else {
                                $img->setAttribute( 'alt', $alt );
                            }
                            //page images title
                            if ( !empty( get_option( 'pages_images_title' ) ) && is_array( get_option( 'pages_images_title' ) ) ) {
                                foreach ( get_option( 'pages_images_title' ) as $option ) {
                                    if ( array_key_exists( $option, $options ) ) {
                                        $title .= $options[$option];
                                    } else {
                                        $title .= ' ' . $option . ' ';
                                    }
                                }
                            } elseif ( !empty( get_option( 'pages_images_title' ) ) && !is_array( get_option( 'pages_images_title' ) ) ) {
                                $title = $options[get_option( 'pages_images_title' )];
                            }
                            //Empty title option
                            if ( $generate_empty_title == 'enabled' && empty( get_the_title( $attachment_id ) ) ) {
                                $img->setAttribute( 'title', $title );
                            } elseif ( $generate_empty_title == 'enabled' && !empty( get_the_title( $attachment_id ) ) ) {
                                $img->setAttribute( 'title', get_the_title( $attachment_id ) );
                            } else {
                                $img->setAttribute( 'title', $title );
                            }
                        }
                        //check homepage
                        if ( is_home( $ID ) || is_front_page( $ID ) ) {
                            $alt = '';
                            $title = '';
                            if ( get_option( 'show_on_front' ) != 'page' && !empty( get_option( 'show_on_front' ) ) ) {
                                $img->setAttribute( 'alt', $options['Site Name'] );
                                $img->setAttribute( 'title', $options['Site Name'] );
                            } else {
                                //Homepage images alt
                                if ( !empty( get_option( 'home_images_alt' ) ) && is_array( get_option( 'home_images_alt' ) ) ) {
                                    foreach ( get_option( 'home_images_alt' ) as $option ) {
                                        if ( array_key_exists( $option, $options ) ) {
                                            $alt .= $options[$option];
                                        } else {
                                            $alt .= ' ' . $option . ' ';
                                        }
                                    }
                                } elseif ( !empty( get_option( 'home_images_alt' ) ) && !is_array( get_option( 'home_images_alt' ) ) ) {
                                    $alt = $options[get_option( 'home_images_alt' )];
                                }
                                //Empty alt option
                                if ( $generate_empty_alt == 'enabled' && empty( $img->getAttribute( 'alt' ) ) ) {
                                    $img->setAttribute( 'alt', $alt );
                                } elseif ( $generate_empty_alt == 'enabled' && !empty( $img->getAttribute( 'alt' ) ) ) {
                                    $img->setAttribute( 'alt', $img->getAttribute( 'alt' ) );
                                } else {
                                    $img->setAttribute( 'alt', $alt );
                                }
                                //Homepage images title
                                if ( !empty( get_option( 'home_images_title' ) ) && is_array( get_option( 'home_images_title' ) ) ) {
                                    foreach ( get_option( 'home_images_title' ) as $option ) {
                                        if ( array_key_exists( $option, $options ) ) {
                                            $title .= $options[$option];
                                        } else {
                                            $title .= ' ' . $option . ' ';
                                        }
                                    }
                                } elseif ( !empty( get_option( 'home_images_title' ) ) && !is_array( get_option( 'home_images_title' ) ) ) {
                                    $title = $options[get_option( 'home_images_title' )];
                                }
                                //Empty title option
                                if ( $generate_empty_title == 'enabled' && empty( get_the_title( $attachment_id ) ) ) {
                                    $img->setAttribute( 'title', $title );
                                } elseif ( $generate_empty_title == 'enabled' && !empty( get_the_title( $attachment_id ) ) ) {
                                    $img->setAttribute( 'title', get_the_title( $attachment_id ) );
                                } else {
                                    $img->setAttribute( 'title', $title );
                                }
                            }
                        }
                        //check post type
                        if ( is_single( $ID ) && $type == 'post' ) {
                            $alt = '';
                            $title = '';
                            //post images alt
                            if ( !empty( get_option( 'post_images_alt' ) ) && is_array( get_option( 'post_images_alt' ) ) ) {
                                foreach ( get_option( 'post_images_alt' ) as $option ) {
                                    if ( array_key_exists( $option, $options ) ) {
                                        $alt .= $options[$option];
                                    } else {
                                        $alt .= ' ' . $option . ' ';
                                    }
                                }
                            } elseif ( !empty( get_option( 'post_images_alt' ) ) && !is_array( get_option( 'post_images_alt' ) ) ) {
                                $alt = $options[get_option( 'post_images_alt' )];
                            }
                            //Empty alt option
                            if ( $generate_empty_alt == 'enabled' && empty( $img->getAttribute( 'alt' ) ) ) {
                                $img->setAttribute( 'alt', $alt );
                            } elseif ( $generate_empty_alt == 'enabled' && !empty( $img->getAttribute( 'alt' ) ) ) {
                                $img->setAttribute( 'alt', $img->getAttribute( 'alt' ) );
                            } else {
                                $img->setAttribute( 'alt', $alt );
                            }
                            //post images title
                            if ( !empty( get_option( 'post_images_title' ) ) && is_array( get_option( 'post_images_title' ) ) ) {
                                foreach ( get_option( 'post_images_title' ) as $option ) {
                                    if ( array_key_exists( $option, $options ) ) {
                                        $title .= $options[$option];
                                    } else {
                                        $title .= ' ' . $option . ' ';
                                    }
                                }
                            } elseif ( !empty( get_option( 'post_images_title' ) ) && !is_array( get_option( 'post_images_title' ) ) ) {
                                $title = $options[get_option( 'post_images_title' )];
                            }
                            //Empty title option
                            if ( $generate_empty_title == 'enabled' && empty( get_the_title( $attachment_id ) ) ) {
                                $img->setAttribute( 'title', $title );
                            } elseif ( $generate_empty_title == 'enabled' && !empty( get_the_title( $attachment_id ) ) ) {
                                $img->setAttribute( 'title', get_the_title( $attachment_id ) );
                            } else {
                                $img->setAttribute( 'title', $title );
                            }
                        }
                    }
                }
            }
            // $html = $alm_content->saveHtml();
            if ( wp_doing_ajax() ) {
                $html = $alm_data_generator;
            }
            return $html;
        } else {
            return $alm_data_generator;
        }
    }

}

$init = new alm_dom_generator();