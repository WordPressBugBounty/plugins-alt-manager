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

    function alm_build_value( $setting_key, $options ) {
        $configured = alm_get_option( $setting_key );
        if ( empty( $configured ) ) {
            return '';
        }
        if ( !is_array( $configured ) ) {
            return ( isset( $options[$configured] ) ? $options[$configured] : '' );
        }
        $value = '';
        foreach ( $configured as $option ) {
            if ( array_key_exists( $option, $options ) ) {
                $value .= $options[$option];
            } else {
                $value .= ' ' . $option . ' ';
            }
        }
        return $value;
    }

    function alm_set_img_attribute(
        $img,
        $attribute,
        $value,
        $only_empty_setting
    ) {
        $current_value = $img->getAttribute( $attribute );
        if ( 'enabled' === $only_empty_setting ) {
            if ( empty( $current_value ) ) {
                $img->setAttribute( $attribute, esc_attr( $value ) );
            } else {
                $img->setAttribute( $attribute, esc_attr( $current_value ) );
            }
            return;
        }
        $img->setAttribute( $attribute, esc_attr( $value ) );
    }

    function alm_resolve_image_context( $img, $fallback_id, $fallback_type ) {
        $resolved_id = absint( $fallback_id );
        $resolved_type = $fallback_type;
        $source = 'base';
        $matched_tag = '';
        $matched_class = '';
        $node = $img;
        while ( $node ) {
            $class_string = (string) $node->getAttribute( 'class' );
            $id_string = (string) $node->getAttribute( 'id' );
            $data_post_id = absint( $node->getAttribute( 'data-post-id' ) );
            if ( $data_post_id > 0 && get_post_status( $data_post_id ) ) {
                $resolved_id = $data_post_id;
                $resolved_type = get_post_field( 'post_type', $resolved_id );
                $source = 'data-post-id';
                $matched_tag = $node->tag;
                $matched_class = $class_string;
                break;
            }
            if ( preg_match( '/(?:^|\\s)(?:post|postid)-(\\d+)(?:\\s|$)/', $class_string, $matches ) || preg_match( '/^post-(\\d+)$/', $id_string, $matches ) ) {
                $resolved_id = absint( $matches[1] );
                $resolved_type = get_post_field( 'post_type', $resolved_id );
                $source = 'post-class';
                $matched_tag = $node->tag;
                $matched_class = $class_string;
                break;
            }
            if ( 'a' === $node->tag ) {
                $linked_post_id = url_to_postid( (string) $node->getAttribute( 'href' ) );
                if ( $linked_post_id > 0 ) {
                    $resolved_id = absint( $linked_post_id );
                    $resolved_type = get_post_field( 'post_type', $resolved_id );
                    $source = 'link';
                    $matched_tag = $node->tag;
                    $matched_class = $class_string;
                    break;
                }
            }
            $node = $node->parent();
        }
        return [
            'id'     => $resolved_id,
            'type'   => $resolved_type,
            'source' => $source,
            'tag'    => $matched_tag,
            'class'  => $matched_class,
        ];
    }

    function alm_apply_context_attributes(
        $img,
        $context,
        $options,
        $generate_empty_alt,
        $generate_empty_title
    ) {
        $context_map = [
            'page'    => [
                'alt'   => 'pages_images_alt',
                'title' => 'pages_images_title',
            ],
            'home'    => [
                'alt'   => 'home_images_alt',
                'title' => 'home_images_title',
            ],
            'post'    => [
                'alt'   => 'post_images_alt',
                'title' => 'post_images_title',
            ],
            'product' => [
                'alt'   => 'product_images_alt',
                'title' => 'product_images_title',
            ],
            'cpt'     => [
                'alt'   => 'cpt_images_alt',
                'title' => 'cpt_images_title',
            ],
        ];
        if ( !isset( $context_map[$context] ) ) {
            return;
        }
        if ( 'home' === $context && 'page' !== alm_get_option( 'show_on_front' ) && !empty( alm_get_option( 'show_on_front' ) ) ) {
            $home_value = $options['Site Name'];
            $this->alm_set_img_attribute(
                $img,
                'alt',
                $home_value,
                $generate_empty_alt
            );
            $this->alm_set_img_attribute(
                $img,
                'title',
                $home_value,
                $generate_empty_title
            );
            return;
        }
        $alt = $this->alm_build_value( $context_map[$context]['alt'], $options );
        $title = $this->alm_build_value( $context_map[$context]['title'], $options );
        $this->alm_set_img_attribute(
            $img,
            'alt',
            $alt,
            $generate_empty_alt
        );
        $this->alm_set_img_attribute(
            $img,
            'title',
            $title,
            $generate_empty_title
        );
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
        $page_id = get_queried_object_id();
        if ( empty( $page_id ) ) {
            $page_id = get_the_ID();
        }
        $page_id = absint( $page_id );
        $page_type = ( $page_id ? get_post_field( 'post_type', $page_id ) : '' );
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
                $page_type = $taxonomy_object->object_type[0];
                // Usually the main one
            }
        }
        //Removed is_singular($types) to support elemntor template integeration
        if ( !is_admin() && !empty( $alm_data_generator ) ) {
            foreach ( $html->find( 'img' ) as $img ) {
                $attachment_id = $this->alm_get_image_id( $img->getAttribute( 'src' ) );
                $img_classes = explode( ' ', $img->getAttribute( 'class' ) );
                $resolved_context = $this->alm_resolve_image_context( $img, $page_id, $page_type );
                $resolved_id = $resolved_context['id'];
                $resolved_type = $resolved_context['type'];
                $is_loop_item = $resolved_id > 0 && $resolved_id !== $page_id;
                // Only check if image is featured by class
                $is_featured = in_array( 'wp-post-image', $img_classes );
                //WPML Compatibility Custom Alt
                if ( 'wpml-ls-flag' === $img->getAttribute( 'class' ) ) {
                    $next_sibling = $img->next_sibling();
                    if ( !empty( $next_sibling->innertext() ) ) {
                        $img->setAttribute( 'alt', esc_attr( $next_sibling->innertext() ) );
                    }
                }
                // Check if image already has alt/title set by alm-functions.php - skip if already set
                $has_alt = $img->hasAttribute( 'alt' ) && !empty( trim( $img->getAttribute( 'alt' ) ) ) && 'enabled' === $generate_empty_alt;
                $has_title = $img->hasAttribute( 'title' ) && !empty( trim( $img->getAttribute( 'title' ) ) ) && 'enabled' === $generate_empty_title;
                //Check if image is not featured and has no alt
                // Skip if already processed by alm-functions.php (has both alt and title)
                if ( !$is_featured && $img->getAttribute( 'class' ) !== 'wpml-ls-flag' && !($has_alt && $has_title) ) {
                    // options
                    $options = [
                        'Site Name'        => sanitize_text_field( get_bloginfo( 'name' ) ),
                        'Site Description' => sanitize_text_field( get_bloginfo( 'description' ) ),
                        'Page Title'       => sanitize_text_field( get_the_title( $resolved_id ) ),
                        'Post Title'       => sanitize_text_field( get_post_field( 'post_title', $resolved_id ) ),
                        'Product Title'    => sanitize_text_field( get_post_field( 'post_title', $resolved_id ) ),
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
                        $alt = $options['Site Name'] . ' | ' . $options['Site Description'];
                        $title = $options['Site Name'] . ' | ' . $options['Site Description'];
                        $img->setAttribute( 'alt', esc_attr( $alt ) );
                        $img->setAttribute( 'title', esc_attr( $title ) );
                    }
                    if ( !$logo_checker ) {
                        $context = '';
                        if ( $is_loop_item ) {
                            if ( 'product' === $resolved_type ) {
                                $context = 'product';
                            } elseif ( 'post' === $resolved_type ) {
                                $context = 'post';
                            } elseif ( 'page' === $resolved_type ) {
                                $context = 'page';
                            } elseif ( !empty( $resolved_type ) && !in_array( $resolved_type, [
                                'product',
                                'post',
                                'page',
                                'attachment'
                            ], true ) ) {
                                $context = 'cpt';
                            }
                        } else {
                            if ( is_singular( 'page' ) || !is_front_page() && is_home() && !is_admin() && !empty( $alm_data_generator ) && 'page' === $resolved_type ) {
                                $context = 'page';
                            } elseif ( (is_home() || is_front_page()) && 'product' !== $resolved_type ) {
                                $context = 'home';
                            } elseif ( 'post' === $resolved_type && (is_single( $page_id ) || is_singular( 'post' )) ) {
                                $context = 'post';
                            } elseif ( am_fs()->is__premium_only() && 'product' === $resolved_type ) {
                                $context = 'product';
                            } elseif ( am_fs()->is__premium_only() && !empty( $resolved_type ) && !in_array( $resolved_type, [
                                'product',
                                'post',
                                'page',
                                'attachment'
                            ], true ) ) {
                                $context = 'cpt';
                            }
                        }
                        if ( !empty( $context ) ) {
                            $this->alm_apply_context_attributes(
                                $img,
                                $context,
                                $options,
                                $generate_empty_alt,
                                $generate_empty_title
                            );
                        }
                        $alm_debug = current_user_can( 'manage_options' ) && isset( $_GET['alm_debug'] );
                        if ( $alm_debug ) {
                            $img->setAttribute( 'data-alm-page-id', (string) $page_id );
                            $img->setAttribute( 'data-alm-resolved-id', (string) $resolved_id );
                            $img->setAttribute( 'data-alm-resolved-type', (string) $resolved_type );
                            $img->setAttribute( 'data-alm-context', (string) $context );
                            $img->setAttribute( 'data-alm-source', $resolved_context['source'] );
                            $img->setAttribute( 'data-alm-parent-tag', $resolved_context['tag'] );
                            $img->setAttribute( 'data-alm-parent-class', sanitize_text_field( $resolved_context['class'] ) );
                            $img->setAttribute( 'data-alm-post-title', sanitize_text_field( get_the_title( $resolved_id ) ) );
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
function alm_enqueue_frontend_script() {
    if ( is_admin() ) {
        return;
    }
    if ( is_home() || is_archive() || is_tax() || is_category() || is_tag() || is_author() || is_search() ) {
        return;
    }
    $ID = get_queried_object_id();
    if ( empty( $ID ) ) {
        $ID = get_the_ID();
    }
    $type = get_post_field( 'post_type', $ID );
    $context = '';
    if ( is_front_page() ) {
        $context = 'home';
    } elseif ( is_single() && 'post' === $type ) {
        $context = 'post';
    } elseif ( is_page() && 'page' === $type ) {
        $context = 'page';
    }
    // Early return if context is not set (not a supported type)
    if ( empty( $context ) ) {
        return;
    }
    $replacements = [
        'Site Name'        => sanitize_text_field( get_bloginfo( 'name' ) ),
        'Site Description' => sanitize_text_field( get_bloginfo( 'description' ) ),
        'Page Title'       => sanitize_text_field( get_the_title( $ID ) ),
        'Post Title'       => sanitize_text_field( get_post_field( 'post_title', $ID ) ),
        'Product Title'    => sanitize_text_field( get_post_field( 'post_title', $ID ) ),
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
    $alt_output = esc_attr( $alt_final );
    $title_output = esc_attr( $title_final );
    // Enqueue script properly
    wp_enqueue_script(
        'alm-frontend',
        plugins_url( '/assets/js/alm-frontend.js', dirname( __FILE__, 2 ) . '/alt-manager.php' ),
        array(),
        filemtime( plugin_dir_path( __FILE__ ) . '../assets/js/alm-frontend.js' ),
        // Dynamic versioning
        true
    );
    // Localize script with dynamic values
    wp_localize_script( 'alm-frontend', 'almAltManager', array(
        'altText'   => $alt_output,
        'titleText' => $title_output,
        'context'   => $context,
    ) );
}

add_action( 'wp_enqueue_scripts', 'alm_enqueue_frontend_script', 20 );