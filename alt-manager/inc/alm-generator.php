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

    // Retrieves the attachment ID from the file URL
    function alm_get_image_id( $url ) {
        $attachment_id = 0;
        $dir = wp_upload_dir();
        if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) {
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
        if ( !is_singular( array('post', 'page', 'product') ) ) {
            return;
        }
        function get_content(  $alm_data_generator  ) {
            return apply_filters( 'alm_output', $alm_data_generator );
        }

        ob_start( 'get_content' );
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

    function alm_generator( $alm_data_generator ) {
        // Use DOMDocument instead of simple_html_dom
        $dom = new DOMDocument();
        @$dom->loadHTML( $alm_data_generator, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        $generate_empty_alt = get_option( 'only_empty_images_alt' );
        $generate_empty_title = get_option( 'only_empty_images_title' );
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
        if ( is_singular( $types ) && !is_admin() && !empty( $alm_data_generator ) ) {
            $images = $dom->getElementsByTagName( 'img' );
            foreach ( $images as $img ) {
                $src = $img->getAttribute( 'src' );
                $attachment_id = $this->alm_get_image_id( $src );
                $attachments_ids = $this->alm_posts_attachments_ids();
                // WPML Compatibility Custom Alt
                if ( $img->getAttribute( 'class' ) == 'wpml-ls-flag' ) {
                    $next_sibling = $img->nextSibling;
                    if ( $next_sibling && !empty( trim( $next_sibling->textContent ) ) ) {
                        $img->setAttribute( 'alt', trim( $next_sibling->textContent ) );
                    }
                }
                if ( !in_array( $attachment_id, $attachments_ids ) && $img->getAttribute( 'class' ) !== 'wpml-ls-flag' || empty( $img->getAttribute( 'alt' ) ) ) {
                    $attachment_id = $this->alm_get_image_id( $src );
                    $parent = get_post_field( 'post_parent', $attachment_id );
                    $options = [
                        'Site Name'        => get_bloginfo( 'name' ),
                        'Site Description' => get_bloginfo( 'description' ),
                        'Page Title'       => get_the_title( $ID ),
                        'Post Title'       => get_post_field( 'post_title', $ID ),
                        'Product Title'    => get_post_field( 'post_title', $ID ),
                    ];
                    if ( wp_attachment_is_image( $attachment_id ) ) {
                        $options['Image Alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                        $options['Image Name'] = get_the_title( $attachment_id );
                        $options['Image Caption'] = wp_get_attachment_caption( $attachment_id );
                        $options['Image Description'] = get_the_content( $attachment_id );
                    }
                    $logo_checker = false;
                    $image_classes = explode( ' ', $img->getAttribute( 'class' ) );
                    foreach ( $image_classes as $image_class ) {
                        if ( strpos( $image_class, 'logo' ) !== false ) {
                            $logo_checker = true;
                        }
                    }
                    if ( $logo_checker ) {
                        $alt = $options['Site Name'];
                        $title = $options['Site Name'];
                        $img->setAttribute( 'alt', $alt );
                        $img->setAttribute( 'title', $title );
                    } else {
                        // Page type checks and alt/title generation
                        $alt = '';
                        $title = '';
                        if ( is_page( $ID ) && !is_home( $ID ) && !is_front_page( $ID ) ) {
                            $alt = $this->generate_image_attribute(
                                $options,
                                'pages_images_alt',
                                $generate_empty_alt,
                                $img->getAttribute( 'alt' )
                            );
                            $title = $this->generate_image_attribute(
                                $options,
                                'pages_images_title',
                                $generate_empty_title,
                                $img->getAttribute( 'title' )
                            );
                        }
                        if ( is_home( $ID ) || is_front_page( $ID ) ) {
                            $alt = $this->generate_image_attribute(
                                $options,
                                'home_images_alt',
                                $generate_empty_alt,
                                $img->getAttribute( 'alt' )
                            );
                            $title = $this->generate_image_attribute(
                                $options,
                                'home_images_title',
                                $generate_empty_title,
                                $img->getAttribute( 'title' )
                            );
                        }
                        if ( is_single( $ID ) && $type == 'post' ) {
                            $alt = $this->generate_image_attribute(
                                $options,
                                'post_images_alt',
                                $generate_empty_alt,
                                $img->getAttribute( 'alt' )
                            );
                            $title = $this->generate_image_attribute(
                                $options,
                                'post_images_title',
                                $generate_empty_title,
                                $img->getAttribute( 'title' )
                            );
                        }
                        if ( am_fs()->is__premium_only() && $type == 'product' ) {
                            $alt = $this->generate_image_attribute(
                                $options,
                                'product_images_alt',
                                $generate_empty_alt,
                                $img->getAttribute( 'alt' )
                            );
                            $title = $this->generate_image_attribute(
                                $options,
                                'product_images_title',
                                $generate_empty_title,
                                $img->getAttribute( 'title' )
                            );
                        }
                        $img->setAttribute( 'alt', $alt );
                        $img->setAttribute( 'title', $title );
                    }
                }
            }
            if ( wp_doing_ajax() ) {
                return $alm_data_generator;
            } else {
                return $dom->saveHTML();
            }
        } else {
            return $alm_data_generator;
        }
    }

    private function generate_image_attribute(
        $options,
        $setting,
        $generate_empty,
        $current_value
    ) {
        $value = '';
        $option = get_option( $setting );
        if ( is_array( $option ) ) {
            foreach ( $option as $opt ) {
                $value .= ( isset( $options[$opt] ) ? $options[$opt] : " {$opt} " );
            }
        } else {
            $value = ( isset( $options[$option] ) ? $options[$option] : '' );
        }
        if ( $generate_empty === 'enabled' && empty( $current_value ) ) {
            return $value;
        }
        return ( $current_value ?: $value );
    }

}
