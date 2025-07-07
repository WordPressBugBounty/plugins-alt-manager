<?php

// Register both menus, same callback
add_action( 'admin_menu', function () {
    add_options_page(
        'Alt Manager Settings',
        'Alt Manager',
        'manage_options',
        'alt-manager',
        'alm_settings_admin'
    );
} );
add_action( 'network_admin_menu', function () {
    add_submenu_page(
        'settings.php',
        'Alt Manager Network Settings',
        'Alt Manager',
        'manage_network_options',
        'alt-manager',
        'alm_settings_admin'
    );
} );
function alm_settings_admin() {
    if ( is_network_admin() && isset( $_POST['alm_ai_api_key'] ) ) {
        if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'alm_ai_api_key_update' ) ) {
            alm_update_option( 'alm_ai_api_key', sanitize_text_field( $_POST['alm_ai_api_key'] ) );
        }
    }
    // Handle saving in network admin
    if ( is_network_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['alm_network_settings_nonce'] ) ) {
        if ( wp_verify_nonce( $_POST['alm_network_settings_nonce'], 'alm_network_settings_save' ) ) {
            // Save each option
            alm_update_option( 'only_empty_images_alt', ( isset( $_POST['only_empty_images_alt'] ) ? 'enabled' : '' ) );
            alm_update_option( 'only_empty_images_title', ( isset( $_POST['only_empty_images_title'] ) ? 'enabled' : '' ) );
            alm_update_option( 'home_images_alt', ( isset( $_POST['home_images_alt'] ) ? (array) $_POST['home_images_alt'] : [] ) );
            alm_update_option( 'home_images_title', ( isset( $_POST['home_images_title'] ) ? (array) $_POST['home_images_title'] : [] ) );
            alm_update_option( 'pages_images_alt', ( isset( $_POST['pages_images_alt'] ) ? (array) $_POST['pages_images_alt'] : [] ) );
            alm_update_option( 'pages_images_title', ( isset( $_POST['pages_images_title'] ) ? (array) $_POST['pages_images_title'] : [] ) );
            alm_update_option( 'post_images_alt', ( isset( $_POST['post_images_alt'] ) ? (array) $_POST['post_images_alt'] : [] ) );
            alm_update_option( 'post_images_title', ( isset( $_POST['post_images_title'] ) ? (array) $_POST['post_images_title'] : [] ) );
            echo '<div class="updated notice is-dismissible"><p>' . __( 'Settings saved.', 'alt-manager' ) . '</p></div>';
        } else {
            echo '<div class="error notice is-dismissible"><p>' . __( 'Security check failed.', 'alt-manager' ) . '</p></div>';
        }
    }
    $alm_home_options = array(
        'Page Title'        => [
            'value' => 'Page Title',
            'text'  => __( 'Page Title', 'alt-manager' ),
        ],
        'Site Name'         => [
            'value' => 'Site Name',
            'text'  => __( 'Site Name', 'alt-manager' ),
        ],
        'Site Description'  => [
            'value' => 'Site Description',
            'text'  => __( 'Site Description', 'alt-manager' ),
        ],
        'Image Alt'         => [
            'value' => 'Image Alt',
            'text'  => __( 'Image Alt', 'alt-manager' ),
        ],
        'Image Name'        => [
            'value' => 'Image Name',
            'text'  => __( 'Image Name', 'alt-manager' ),
        ],
        'Image Caption'     => [
            'value' => 'Image Caption',
            'text'  => __( 'Image Caption', 'alt-manager' ),
        ],
        'Image Description' => [
            'value' => 'Image Description',
            'text'  => __( 'Image Description', 'alt-manager' ),
        ],
        '&'                 => [
            'value' => '&',
            'text'  => '&',
        ],
        '|'                 => [
            'value' => '|',
            'text'  => '|',
        ],
        '-'                 => [
            'value' => '-',
            'text'  => '-',
        ],
        '_'                 => [
            'value' => '_',
            'text'  => '_',
        ],
    );
    $alm_pages_options = array(
        'Page Title'        => [
            'value' => 'Page Title',
            'text'  => __( 'Page Title', 'alt-manager' ),
        ],
        'Site Name'         => [
            'value' => 'Site Name',
            'text'  => __( 'Site Name', 'alt-manager' ),
        ],
        'Site Description'  => [
            'value' => 'Site Description',
            'text'  => __( 'Site Description', 'alt-manager' ),
        ],
        'Image Alt'         => [
            'value' => 'Image Alt',
            'text'  => __( 'Image Alt', 'alt-manager' ),
        ],
        'Image Name'        => [
            'value' => 'Image Name',
            'text'  => __( 'Image Name', 'alt-manager' ),
        ],
        'Image Caption'     => [
            'value' => 'Image Caption',
            'text'  => __( 'Image Caption', 'alt-manager' ),
        ],
        'Image Description' => [
            'value' => 'Image Description',
            'text'  => __( 'Image Description', 'alt-manager' ),
        ],
        '&'                 => [
            'value' => '&',
            'text'  => '&',
        ],
        '|'                 => [
            'value' => '|',
            'text'  => '|',
        ],
        '-'                 => [
            'value' => '-',
            'text'  => '-',
        ],
        '_'                 => [
            'value' => '_',
            'text'  => '_',
        ],
    );
    $alm_post_options = array(
        'Post Title'        => [
            'value' => 'Post Title',
            'text'  => __( 'Post Title', 'alt-manager' ),
        ],
        'Site Name'         => [
            'value' => 'Site Name',
            'text'  => __( 'Site Name', 'alt-manager' ),
        ],
        'Site Description'  => [
            'value' => 'Site Description',
            'text'  => __( 'Site Description', 'alt-manager' ),
        ],
        'Image Alt'         => [
            'value' => 'Image Alt',
            'text'  => __( 'Image Alt', 'alt-manager' ),
        ],
        'Image Name'        => [
            'value' => 'Image Name',
            'text'  => __( 'Image Name', 'alt-manager' ),
        ],
        'Image Caption'     => [
            'value' => 'Image Caption',
            'text'  => __( 'Image Caption', 'alt-manager' ),
        ],
        'Image Description' => [
            'value' => 'Image Description',
            'text'  => __( 'Image Description', 'alt-manager' ),
        ],
        '&'                 => [
            'value' => '&',
            'text'  => '&',
        ],
        '|'                 => [
            'value' => '|',
            'text'  => '|',
        ],
        '-'                 => [
            'value' => '-',
            'text'  => '-',
        ],
        '_'                 => [
            'value' => '_',
            'text'  => '_',
        ],
    );
    if ( am_fs()->is_not_paying() ) {
        echo '<div class="notice notice-success is-dismissible" style="text-align: center;">';
        echo '<strong><span style="display: block;margin: 0.5em 0.5em 0 0;clear: both;color: #0f8377;font-size: 1vw;">' . __( 'Get Alt Manager Premium Features', 'alt-manager' ) . '</span></strong>';
        echo '<strong><span style="display: block; margin: 0.5em; clear: both;">';
        echo '<a href="' . am_fs()->get_upgrade_url() . '" style="color: #15375f;">' . __( 'Upgrade Now!', 'alt-manager' ) . '</a>';
        echo '</span></strong>';
        echo '</div>';
    }
    $active_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : '' );
    // var_dump($active_tab);
    ?>
    <div class="wrap fs-section">
        <h2 class="nav-tab-wrapper">
            <a href="?page=alt-manager" class="home <?php 
    echo ( $active_tab == '' ? 'nav-tab-active' : '' );
    ?> nav-tab">Settings</a>
            <a href="?page=alt-manager&tab=ai_settings" class="<?php 
    echo ( $active_tab == 'ai_settings' ? 'nav-tab-active' : '' );
    ?> nav-tab">AI Settings</a>
            <a href="?page=alt-manager&tab=ai_generator" class="<?php 
    echo ( $active_tab == 'ai_generator' ? 'nav-tab-active' : '' );
    ?> nav-tab">AI Generator</a>
        </h2>
        <?php 
    if ( $active_tab == '' ) {
        ?>
        <h1 class="alm-heading"><span class="dashicons dashicons-images-alt2"></span>
            <?php 
        _e( 'Alt Manager Settings', 'alt-manager' );
        // print_r($alm_home_options);
        // var_dump(settings_fields('alm_settings'));
        ?></h1>


        <form method="post" action="<?php 
        echo ( is_network_admin() ? '' : 'options.php' );
        ?>">
            <?php 
        //  var_dump(alm_get_option('post_images_alt'));
        if ( is_network_admin() ) {
            wp_nonce_field( 'alm_network_settings_save', 'alm_network_settings_nonce' );
        } else {
            settings_fields( 'alm_settings' );
            do_settings_sections( 'alm_settings' );
        }
        ?>

            <table class="form-table">
                <tr>
                    <th scope="row" ><strong><?php 
        _e( 'Generate Only Empty Alt', 'alt-manager' );
        ?></strong></th>   
                    <td >
                        <?php 
        if ( alm_get_option( 'only_empty_images_alt' ) == 'enabled' ) {
            ?>
                            <input id="empty_status" type="checkbox" name="only_empty_images_alt" value="enabled" checked>

                        <?php 
        } else {
            ?>
                            <input id="empty_status" type="checkbox" name="only_empty_images_alt" value="enabled">

                        <?php 
        }
        ?>
                    </td>

                </tr>
                <tr>
                    <th scope="row"><strong><?php 
        _e( 'Generate Only Empty Title', 'alt-manager' );
        ?></strong></th>
                    <td colspan="2">
                        <?php 
        if ( alm_get_option( 'only_empty_images_title' ) == 'enabled' ) {
            ?>
                            <input id="empty_status" type="checkbox" name="only_empty_images_title" value="enabled" checked>

                        <?php 
        } else {
            ?>
                            <input id="empty_status" type="checkbox" name="only_empty_images_title" value="enabled">

                        <?php 
        }
        ?>
                    </td>

                </tr>
                <tr valign="bottom">
                    <th colspan="2">
                        <h3><?php 
        _e( 'Homepage Images Settings', 'alt-manager' );
        ?></h3>
                    </th>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
        _e( 'Home Images Alt', 'alt-manager' );
        ?></th>
                    <td>
                        <select name="home_images_alt[]" class="home-images-alt" multiple="multiple">

                            <?php 
        // var_dump(alm_get_option('home_images_alt'));
        if ( !empty( alm_get_option( 'home_images_alt' ) ) && is_array( alm_get_option( 'home_images_alt' ) ) ) {
            foreach ( alm_get_option( 'home_images_alt' ) as $option ) {
                echo '<option value="' . esc_attr( $alm_home_options[$option]['value'] ) . '" selected="selected">' . $alm_home_options[$option]['text'] . '</option>';
            }
        } elseif ( !empty( alm_get_option( 'home_images_alt' ) ) && !is_array( alm_get_option( 'home_images_alt' ) ) ) {
            echo '<option value="' . esc_attr( $alm_home_options[alm_get_option( 'home_images_alt' )]['value'] ) . '" selected="selected">' . $alm_home_options[alm_get_option( 'home_images_alt' )]['text'] . '</option>';
        }
        foreach ( $alm_home_options as $option ) {
            if ( is_array( alm_get_option( 'home_images_alt' ) ) && !in_array( $option['value'], alm_get_option( 'home_images_alt' ) ) ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            } elseif ( !is_array( alm_get_option( 'home_images_alt' ) ) && alm_get_option( 'home_images_alt' ) !== $option['value'] ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            }
        }
        ?>

                        </select>
                    </td>

                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
        _e( 'Home Images Title', 'alt-manager' );
        ?></th>
                    <td>
                        <select name="home_images_title[]" class="home-images-title" multiple="multiple">
                            <?php 
        if ( !empty( alm_get_option( 'home_images_title' ) ) && is_array( alm_get_option( 'home_images_title' ) ) ) {
            foreach ( alm_get_option( 'home_images_title' ) as $option ) {
                echo '<option value="' . esc_attr( $alm_home_options[$option]['value'] ) . '" selected="selected">' . $alm_home_options[$option]['text'] . '</option>';
            }
        } elseif ( !empty( alm_get_option( 'home_images_title' ) ) && !is_array( alm_get_option( 'home_images_title' ) ) ) {
            echo '<option value="' . esc_attr( $alm_home_options[alm_get_option( 'home_images_title' )]['value'] ) . '" selected="selected">' . $alm_home_options[alm_get_option( 'home_images_title' )]['text'] . '</option>';
        }
        foreach ( $alm_home_options as $option ) {
            if ( is_array( alm_get_option( 'home_images_title' ) ) && !in_array( $option['value'], alm_get_option( 'home_images_title' ) ) ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            } elseif ( !is_array( alm_get_option( 'home_images_title' ) ) && alm_get_option( 'home_images_title' ) !== $option['value'] ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            }
        }
        // echo  '<option value="' . esc_attr( $alm_home_options[alm_get_option( 'home_images_title' )]['value'] ) . '" selected="selected">' . $alm_home_options[alm_get_option( 'home_images_title' )]['text'] . '</option>' ;
        // if ( !empty(alm_get_option( 'home_images_title' )) && is_array( alm_get_option( 'home_images_title' ) ) ) {
        //     foreach ( $alm_home_options as $option ) {
        //         if ( !in_array( $option['value'], alm_get_option( 'home_images_title' ) ) ) {
        //             echo  '<option value="' . esc_attr( $option['value'] ) . '">' . $option['text'] . '</option>' ;
        //         }
        //     }
        //     } elseif ( !empty(alm_get_option( 'home_images_title' )) && !is_array( alm_get_option( 'home_images_title' ) ) ) {
        //         foreach ( $alm_home_options as $option ) {
        //             if ( $option['value'] !==   alm_get_option( 'home_images_title' )) {
        //                 echo  '<option value="' . esc_attr( $option['value'] ) . '">' . $option['text'] . '</option>' ;
        //             }
        //         }
        //     }
        ?>

                        </select>
                    </td>

                </tr>
                <tr>
                    <td colspan="2">
                        <p><strong><?php 
        _e( 'Note: ', 'alt-manager' );
        ?></strong><?php 
        _e( 'If homepage is set to Your latest posts alt and title will be site name by default.', 'alt-manager' );
        ?> </p>
                    </td>
                </tr>
                <tr valign="bottom">
                    <th colspan="2">
                        <h3><?php 
        _e( 'Pages Images Settings', 'alt-manager' );
        ?></h3>
                    </th>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
        _e( 'Pages Images Alt', 'alt-manager' );
        ?></th>
                    <td>
                        <select name="pages_images_alt[]" class="pages-images-alt" multiple="multiple">
                            <?php 
        if ( !empty( alm_get_option( 'pages_images_alt' ) ) && is_array( alm_get_option( 'pages_images_alt' ) ) ) {
            foreach ( alm_get_option( 'pages_images_alt' ) as $option ) {
                echo '<option value="' . esc_attr( $alm_pages_options[$option]['value'] ) . '" selected="selected">' . $alm_pages_options[$option]['text'] . '</option>';
            }
        } elseif ( !empty( alm_get_option( 'pages_images_alt' ) ) && !is_array( alm_get_option( 'pages_images_alt' ) ) ) {
            echo '<option value="' . esc_attr( $alm_pages_options[alm_get_option( 'pages_images_alt' )]['value'] ) . '" selected="selected">' . $alm_pages_options[alm_get_option( 'pages_images_alt' )]['text'] . '</option>';
        }
        foreach ( $alm_pages_options as $option ) {
            if ( is_array( alm_get_option( 'pages_images_alt' ) ) && !in_array( $option['value'], alm_get_option( 'pages_images_alt' ) ) ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            } elseif ( !is_array( alm_get_option( 'pages_images_alt' ) ) && alm_get_option( 'pages_images_alt' ) !== $option['value'] ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            }
        }
        ?>

                        </select>
                    </td>

                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
        _e( 'Pages Images Title', 'alt-manager' );
        ?></th>
                    <td>
                        <select name="pages_images_title[]" class="pages-images-title" multiple="multiple">
                            <?php 
        if ( !empty( alm_get_option( 'pages_images_title' ) ) && is_array( alm_get_option( 'pages_images_title' ) ) ) {
            foreach ( alm_get_option( 'pages_images_title' ) as $option ) {
                echo '<option value="' . esc_attr( $alm_pages_options[$option]['value'] ) . '" selected="selected">' . $alm_pages_options[$option]['text'] . '</option>';
            }
        } elseif ( !empty( alm_get_option( 'pages_images_title' ) ) && !is_array( alm_get_option( 'pages_images_title' ) ) ) {
            echo '<option value="' . esc_attr( $alm_pages_options[alm_get_option( 'pages_images_title' )]['value'] ) . '" selected="selected">' . $alm_pages_options[alm_get_option( 'pages_images_title' )]['text'] . '</option>';
        }
        foreach ( $alm_pages_options as $option ) {
            if ( is_array( alm_get_option( 'pages_images_title' ) ) && !in_array( $option['value'], alm_get_option( 'pages_images_title' ) ) ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            } elseif ( !is_array( alm_get_option( 'pages_images_title' ) ) && alm_get_option( 'pages_images_title' ) !== $option['value'] ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            }
        }
        ?>
                        </select>
                    </td>
                </tr>
                <tr valign="bottom">
                    <th colspan="2">
                        <h3><?php 
        _e( 'Posts Images Settings', 'alt-manager' );
        ?></h3>
                    </th>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
        _e( 'Posts Images Alt', 'alt-manager' );
        ?></th>
                    <td>
                        <select name="post_images_alt[]" class="post-images-alt" multiple="multiple">
                            <?php 
        if ( !empty( alm_get_option( 'post_images_alt' ) ) && is_array( alm_get_option( 'post_images_alt' ) ) ) {
            foreach ( alm_get_option( 'post_images_alt' ) as $option ) {
                echo '<option value="' . esc_attr( $alm_post_options[$option]['value'] ) . '" selected="selected">' . $alm_post_options[$option]['text'] . '</option>';
            }
        } elseif ( !empty( alm_get_option( 'post_images_alt' ) ) && !is_array( alm_get_option( 'post_images_alt' ) ) ) {
            echo '<option value="' . esc_attr( $alm_post_options[alm_get_option( 'post_images_alt' )]['value'] ) . '" selected="selected">' . $alm_post_options[alm_get_option( 'post_images_alt' )]['text'] . '</option>';
        }
        foreach ( $alm_post_options as $option ) {
            if ( is_array( alm_get_option( 'post_images_alt' ) ) && !in_array( $option['value'], alm_get_option( 'post_images_alt' ) ) ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            } elseif ( !is_array( alm_get_option( 'post_images_alt' ) ) && alm_get_option( 'post_images_alt' ) !== $option['value'] ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            }
        }
        ?>

                        </select>
                    </td>

                </tr>
                <tr valign="top">
                    <th scope="row"><?php 
        _e( 'Posts Images Title', 'alt-manager' );
        ?></th>
                    <td>
                        <select name="post_images_title[]" class="post-images-title" multiple="multiple">
                            <?php 
        if ( !empty( alm_get_option( 'post_images_title' ) ) && is_array( alm_get_option( 'post_images_title' ) ) ) {
            foreach ( alm_get_option( 'post_images_title' ) as $option ) {
                echo '<option value="' . esc_attr( $alm_post_options[$option]['value'] ) . '" selected="selected">' . $alm_post_options[$option]['text'] . '</option>';
            }
        } elseif ( !empty( alm_get_option( 'post_images_title' ) ) && !is_array( alm_get_option( 'post_images_title' ) ) ) {
            echo '<option value="' . esc_attr( $alm_post_options[alm_get_option( 'post_images_title' )]['value'] ) . '" selected="selected">' . $alm_post_options[alm_get_option( 'post_images_title' )]['text'] . '</option>';
        }
        foreach ( $alm_post_options as $option ) {
            if ( is_array( alm_get_option( 'post_images_title' ) ) && !in_array( $option['value'], alm_get_option( 'post_images_title' ) ) ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            } elseif ( !is_array( alm_get_option( 'post_images_title' ) ) && alm_get_option( 'post_images_title' ) !== $option['value'] ) {
                echo '<option value="' . esc_attr( $option['value'] ) . '" >' . $option['text'] . '</option>';
            }
        }
        ?>

                        </select>
                    </td>
                </tr>
                <?php 
        ?>
                    <tr valign="bottom">
                        <th colspan="2">
                            <h3><?php 
        _e( 'Products Images Settings', 'alt-manager' );
        ?></h3>
                        </th>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php 
        _e( 'Products Images Alt', 'alt-manager' );
        ?></th>
                        <td>
                            <select name="product_images_alt[]" class="product-images-alt" multiple="multiple" disabled>
                                <option><?php 
        _e( 'Available in Premium', 'alt-manager' );
        ?></option>
                            </select>
                            <p><a href="<?php 
        echo am_fs()->get_upgrade_url();
        ?>"><?php 
        _e( 'Upgrade to Premium', 'alt-manager' );
        ?></a></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php 
        _e( 'Products Images Title', 'alt-manager' );
        ?></th>
                        <td>
                            <select name="product_images_title[]" class="product-images-title" multiple="multiple" disabled>
                                <option><?php 
        _e( 'Available in Premium', 'alt-manager' );
        ?></option>
                            </select>
                            <p><a href="<?php 
        echo am_fs()->get_upgrade_url();
        ?>"><?php 
        _e( 'Upgrade to Premium', 'alt-manager' );
        ?></a></p>
                        </td>
                    </tr>

                    <tr valign="bottom">
                        <th colspan="2">
                            <h3><?php 
        _e( 'Custom Post Type Images Settings', 'alt-manager' );
        ?></h3>
                        </th>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php 
        _e( 'Custom Post Type Images Alt', 'alt-manager' );
        ?></th>
                        <td>
                            <select name="cpt_images_alt[]" class="cpt-images-alt" multiple="multiple" disabled>
                                <option><?php 
        _e( 'Available in Premium', 'alt-manager' );
        ?></option>
                            </select>
                            <p><a href="<?php 
        echo am_fs()->get_upgrade_url();
        ?>"><?php 
        _e( 'Upgrade to Premium', 'alt-manager' );
        ?></a></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php 
        _e( 'Custom Post Type Images Title', 'alt-manager' );
        ?></th>
                        <td>
                            <select name="cpt_images_title[]" class="cpt-images-title" multiple="multiple" disabled>
                                <option><?php 
        _e( 'Available in Premium', 'alt-manager' );
        ?></option>
                            </select>
                            <p><a href="<?php 
        echo am_fs()->get_upgrade_url();
        ?>"><?php 
        _e( 'Upgrade to Premium', 'alt-manager' );
        ?></a></p>
                        </td>
                    </tr>
                <?php 
        ?>
            </table>
            <?php 
        submit_button( 'Save Changes', 'primary', 'submit' );
        ?>
        </form>
        <form method="post" action="<?php 
        echo ( is_network_admin() ? '' : 'options-general.php?page=alt-manager' );
        ?>">
            <?php 
        wp_nonce_field( 'alm_reset_nonce', 'reset_nonce' );
        submit_button( 'Reset To Defaults', 'primary', 'reset' );
        ?>
        </form>
        <?php 
    } elseif ( $active_tab == 'ai_settings' ) {
        ?>
                <div class="notice notice-warning">
                    <p><?php 
        _e( 'AI Settings are available for premium users only.', 'alt-manager' );
        ?> <a href="<?php 
        echo am_fs()->get_upgrade_url();
        ?>"><?php 
        _e( 'Upgrade Now!', 'alt-manager' );
        ?></a></p>
                </div>
            <?php 
    } elseif ( $active_tab == 'ai_generator' ) {
        ?>
                <div class="notice notice-warning">
                <p><?php 
        _e( 'AI Generation are available for premium users only.', 'alt-manager' );
        ?> <a href="<?php 
        echo am_fs()->get_upgrade_url();
        ?>"><?php 
        _e( 'Upgrade Now!', 'alt-manager' );
        ?></a></p>
                </div>
            <?php 
    }
    ?>
    </div>
<?php 
}
