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

    function alm_posts_attachments_ids() {
        $args = array(
            'fields'         => 'ids',
            'posts_per_page' => -1,
        );
        $post_ids = get_posts( $args );
        $found = [];
        foreach ( $post_ids as $id ) {
            $found[] = get_post_thumbnail_id( $id );
        }
        return $found;
    }

    function alm_get_image_id( $url ) {
        $attachment_id = 0;
        $dir = wp_upload_dir();
        if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) {
            // Is URL in uploads directory?
            $file = basename( $url );
            $query_args = array(
                'post_type'   => 'attachment',
                'post_status' => 'inherit',
                'fields'      => 'ids',
                'meta_query'  => array(array(
                    'value'   => $file,
                    'compare' => 'LIKE',
                    'key'     => '_wp_attachment_metadata',
                )),
            );
            $query = new WP_Query($query_args);
            if ( $query->have_posts() ) {
                foreach ( $query->posts as $post_id ) {
                    $meta = wp_get_attachment_metadata( $post_id );
                    $original_file = basename( $meta['file'] );
                    $cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );
                    if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
                        $attachment_id = $post_id;
                        break;
                    }
                }
            }
        }
        return $attachment_id;
    }

    function alm_init() {
        if ( is_admin() ) {
            return;
        }
        function get_content(  $alm_data_generator  ) {
            return apply_filters( 'alm_output', $alm_data_generator );
        }

        ob_start( 'get_content' );
    }

    function alm_generator( $alm_data_generator ) {
        $html = str_get_html( $alm_data_generator );
        $generate_empty_alt = alm_get_option( 'only_empty_images_alt' );
        $generate_empty_title = alm_get_option( 'only_empty_images_title' );
        $ID = get_the_ID();
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
        //Check Archives
        if ( is_tax() || is_category() || is_tag() ) {
            // Get the current taxonomy term
            $term = get_queried_object();
            // Get the post types associated with this taxonomy
            $taxonomy = $term->taxonomy;
            $taxonomy_object = get_taxonomy( $taxonomy );
            if ( !empty( $taxonomy_object->object_type ) ) {
                // object_type can be an array of post types
                $type = $taxonomy_object->object_type[0];
                // Usually the main one
            }
        }
        if ( is_singular( $types ) && !is_admin() && !empty( $alm_data_generator ) ) {
            foreach ( $html->find( 'img' ) as $img ) {
                $attachments_ids = $this->alm_posts_attachments_ids();
                $attachment_id = $this->alm_get_image_id( $img->getAttribute( 'src' ) );
                $img_classes = explode( ' ', $img->getAttribute( 'class' ) );
                // Store original page ID for homepage checks
                $original_page_id = $ID;
                $extracted_post_type = $type;
                // Default to original type
                // Try to find parent <article> of the image if is archive wtih articles
                $parent_article = $img->parent();
                while ( $parent_article && $parent_article->tag !== 'article' ) {
                    $parent_article = $parent_article->parent();
                }
                if ( $parent_article ) {
                    $class_string = $parent_article->getAttribute( 'class' );
                    if ( preg_match( '/post-(\\d+)/', $class_string, $matches ) ) {
                        $extracted_id = intval( $matches[1] );
                        $extracted_post_type = get_post_field( 'post_type', $extracted_id );
                        // Update $ID for type checks, but keep original_page_id for homepage checks
                        $ID = $extracted_id;
                        // Now $ID is the ID from the parent article class like 'post-3756'
                    }
                }
                // Only check if image is featured by class
                $is_featured = in_array( 'wp-post-image', $img_classes );
                //WPML Compatibility Custom Alt
                if ( 'wpml-ls-flag' === $img->getAttribute( 'class' ) ) {
                    $next_sibling = $img->next_sibling();
                    if ( !empty( $next_sibling->innertext() ) ) {
                        $img->setAttribute( 'alt', $next_sibling->innertext() );
                    }
                }
                // Check if image already has alt/title set by alm-functions.php - skip if already set
                $has_alt = $img->hasAttribute( 'alt' ) && !empty( trim( $img->getAttribute( 'alt' ) ) );
                $has_title = $img->hasAttribute( 'title' ) && !empty( trim( $img->getAttribute( 'title' ) ) );
                //Check if image is not featured and has no alt
                // Skip if already processed by alm-functions.php (has both alt and title)
                if ( !$is_featured && $img->getAttribute( 'class' ) !== 'wpml-ls-flag' && !($has_alt && $has_title) ) {
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
                        if ( is_singular( $types ) || is_archive() || !is_front_page() && is_home() && !is_admin() && !empty( $alm_data_generator ) ) {
                            $alt = '';
                            $title = '';
                            //page images alt
                            if ( !empty( alm_get_option( 'pages_images_alt' ) ) && is_array( alm_get_option( 'pages_images_alt' ) ) ) {
                                foreach ( alm_get_option( 'pages_images_alt' ) as $option ) {
                                    if ( array_key_exists( $option, $options ) ) {
                                        $alt .= $options[$option];
                                    } else {
                                        $alt .= ' ' . $option . ' ';
                                    }
                                }
                            } elseif ( !empty( alm_get_option( 'pages_images_alt' ) ) && !is_array( alm_get_option( 'pages_images_alt' ) ) ) {
                                $alt = $options[alm_get_option( 'pages_images_alt' )];
                            }
                            //Empty alt option
                            if ( 'enabled' === $generate_empty_alt && empty( $img->getAttribute( 'alt' ) ) ) {
                                $img->setAttribute( 'alt', $alt );
                            } elseif ( 'enabled' === $generate_empty_alt && !empty( $img->getAttribute( 'alt' ) ) ) {
                                $img->setAttribute( 'alt', $img->getAttribute( 'alt' ) );
                            } else {
                                $img->setAttribute( 'alt', $alt );
                            }
                            //page images title
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
                            if ( 'enabled' === $generate_empty_title && empty( $img->getAttribute( 'title' ) ) ) {
                                $img->setAttribute( 'title', $title );
                            } elseif ( 'enabled' === $generate_empty_title && !empty( $img->getAttribute( 'title' ) ) ) {
                                $img->setAttribute( 'title', $img->getAttribute( 'title' ) );
                            } else {
                                $img->setAttribute( 'title', $title );
                            }
                        }
                        //check homepage - use original page ID, not extracted product ID
                        // Also skip if this is a product image (products should use product settings, not homepage)
                        if ( (is_home() || is_front_page()) && $extracted_post_type != 'product' ) {
                            //check if post for pages only
                            $alt = '';
                            $title = '';
                            if ( 'page' !== alm_get_option( 'show_on_front' ) && !empty( alm_get_option( 'show_on_front' ) ) ) {
                                $img->setAttribute( 'alt', $options['Site Name'] );
                                $img->setAttribute( 'title', $options['Site Name'] );
                            } else {
                                //Homepage images alt
                                if ( !empty( alm_get_option( 'home_images_alt' ) ) && is_array( alm_get_option( 'home_images_alt' ) ) ) {
                                    foreach ( alm_get_option( 'home_images_alt' ) as $option ) {
                                        if ( array_key_exists( $option, $options ) ) {
                                            $alt .= $options[$option];
                                        } else {
                                            $alt .= ' ' . $option . ' ';
                                        }
                                    }
                                } elseif ( !empty( alm_get_option( 'home_images_alt' ) ) && !is_array( alm_get_option( 'home_images_alt' ) ) ) {
                                    $alt = $options[alm_get_option( 'home_images_alt' )];
                                }
                                //Empty alt option
                                if ( 'enabled' === $generate_empty_alt && empty( $img->getAttribute( 'alt' ) ) ) {
                                    $img->setAttribute( 'alt', $alt );
                                } elseif ( 'enabled' === $generate_empty_alt && !empty( $img->getAttribute( 'alt' ) ) ) {
                                    $img->setAttribute( 'alt', $img->getAttribute( 'alt' ) );
                                } else {
                                    $img->setAttribute( 'alt', $alt );
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
                                if ( 'enabled' === $generate_empty_title && empty( $img->getAttribute( 'title' ) ) ) {
                                    $img->setAttribute( 'title', $title );
                                } elseif ( 'enabled' === $generate_empty_title && !empty( $img->getAttribute( 'title' ) ) ) {
                                    $img->setAttribute( 'title', $img->getAttribute( 'title' ) );
                                } else {
                                    $img->setAttribute( 'title', $title );
                                }
                            }
                        }
                        //check post type
                        if ( is_single( $ID ) || (is_tax() || is_category() || is_tag()) && 'post' === $type ) {
                            $alt = '';
                            $title = '';
                            //post images alt
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
                            if ( 'enabled' === $generate_empty_alt && empty( $img->getAttribute( 'alt' ) ) ) {
                                $img->setAttribute( 'alt', $alt );
                            } elseif ( 'enabled' === $generate_empty_alt && !empty( $img->getAttribute( 'alt' ) ) ) {
                                $img->setAttribute( 'alt', $img->getAttribute( 'alt' ) );
                            } else {
                                $img->setAttribute( 'alt', $alt );
                            }
                            //post images title
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
                            if ( 'enabled' === $generate_empty_title && empty( $img->getAttribute( 'title' ) ) ) {
                                $img->setAttribute( 'title', $title );
                            } elseif ( 'enabled' === $generate_empty_title && !empty( $img->getAttribute( 'title' ) ) ) {
                                $img->setAttribute( 'title', $img->getAttribute( 'title' ) );
                            } else {
                                $img->setAttribute( 'title', $title );
                            }
                        }
                    }
                }
            }
            // $html = $alm_content->saveHtml();
            // fb-edit query param to add fushion builder compatibility
            if ( wp_doing_ajax() || isset( $_GET['fb-edit'] ) && 1 === absint( $_GET['fb-edit'] ) ) {
                $html = $alm_data_generator;
            }
            return $html;
        } else {
            return $alm_data_generator;
        }
    }

}

$init = new alm_dom_generator();
add_action( 'wp_footer', function () {
    if ( is_admin() ) {
        return;
    }
    $ID = get_the_ID();
    $type = get_post_field( 'post_type', $ID );
    $context = '';
    if ( is_front_page() || is_home() ) {
        $context = 'home';
    } elseif ( is_single() && 'post' === $type ) {
        $context = 'post';
    } elseif ( 'page' === $type ) {
        $context = 'page';
    }
    // Early return if context is not set (not a supported type)
    if ( empty( $context ) ) {
        return;
    }
    $replacements = [
        'Site Name'        => get_bloginfo( 'name' ),
        'Site Description' => get_bloginfo( 'description' ),
        'Page Title'       => get_the_title( $ID ),
        'Post Title'       => get_post_field( 'post_title', $ID ),
        'Product Title'    => get_post_field( 'post_title', $ID ),
    ];
    $alt_keys = alm_get_option( "{$context}_images_alt" );
    $title_keys = alm_get_option( "{$context}_images_title" );
    $alt_keys = ( is_array( $alt_keys ) ? $alt_keys : (( !empty( $alt_keys ) ? [$alt_keys] : [] )) );
    $title_keys = ( is_array( $title_keys ) ? $title_keys : (( !empty( $title_keys ) ? [$title_keys] : [] )) );
    $alt_final = '';
    foreach ( $alt_keys as $key ) {
        $alt_final .= ( isset( $replacements[$key] ) ? $replacements[$key] : $key );
    }
    $title_final = '';
    foreach ( $title_keys as $key ) {
        $title_final .= ( isset( $replacements[$key] ) ? $replacements[$key] : $key );
    }
    // Prevent injection if both are blank
    if ( empty( $alt_final ) && empty( $title_final ) ) {
        return;
    }
    // Decode for raw readable characters
    $alt_output = htmlspecialchars_decode( $alt_final, ENT_QUOTES );
    $title_output = htmlspecialchars_decode( $title_final, ENT_QUOTES );
    // Enqueue script properly
    wp_enqueue_script(
        'alm-frontend',
        plugins_url( '/assets/js/alm-frontend.js', dirname( dirname( __FILE__ ) ) . '/alt-manager.php' ),
        array(),
        '1.0.0',
        true
    );
    // Localize script with dynamic values
    wp_localize_script( 'alm-frontend', 'almAltManager', array(
        'altText'   => $alt_output,
        'titleText' => $title_output,
    ) );
}, PHP_INT_MAX );