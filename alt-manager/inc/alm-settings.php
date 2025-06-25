<?php
// File: includes/alm-settings.php

register_setting('alm_settings', 'only_empty_images_alt');
register_setting('alm_settings', 'only_empty_images_title');

register_setting('alm_settings', 'home_images_alt');
register_setting('alm_settings', 'home_images_title');
register_setting('alm_settings', 'pages_images_alt');
register_setting('alm_settings', 'pages_images_title');
register_setting('alm_settings', 'post_images_alt');
register_setting('alm_settings', 'post_images_title');

if (function_exists('am_fs') && am_fs()->is__premium_only()) {
    register_setting('alm_settings', 'product_images_alt');
    register_setting('alm_settings', 'product_images_title');
    register_setting('alm_settings', 'cpt_images_alt');
    register_setting('alm_settings', 'cpt_images_title');

    register_setting('alm_ai_settings', 'alm_ai_api_key');

add_settings_section(
    'alm_ai_section',
    'AI API Settings',
    null,
    'alm_ai_settings_section'
);

function alm_check_api_status($url, $key) {
    if (empty($url)) {
        return [
            'success' => false,
            'message' => 'API endpoint URL is missing.'
        ];
    }
    if (empty($key)) {
        return [
            'success' => false,
            'message' => 'API key is missing. Please enter your API key.'
        ];
    }

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ["role" => "user", "content" => "Say hello"]
            ]
        ]),
        'timeout' => 10
    ]);

    if (is_wp_error($response)) {
        return [
            'success' => false,
            'message' => $response->get_error_message()
        ];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['choices'])) {
        return [
            'success' => false,
            'message' => isset($body['error']['message']) ? $body['error']['message'] : 'Unexpected response'
        ];
    }

    return [
        'success' => true,
        'message' => 'API Connected'
    ];
}

$error_message = '';

function render_api_key_field()
{
    $endpoint_url = 'https://api.openai.com/v1/chat/completions';
    $api_key = alm_get_option('alm_ai_api_key');

    $api_status = alm_check_api_status($endpoint_url, $api_key);

    $value = esc_attr($api_key);

    wp_nonce_field('alm_ai_api_key_update');

    echo "<input type='text' name='alm_ai_api_key' id='alm_ai_api_key' value='{$value}' class='regular-text'>";
    echo "<div id='alm-api-status' style='margin-top:5px;'></div>";
    ?>
    <div id="alm_api_status" style="font-weight: bold; color: <?php echo $api_status['success'] ? 'green' : 'red'; ?>">
        <?php
        echo $api_status['success']
            ? '✅ ' . esc_html($api_status['message'])
            : '❌ ' . esc_html($api_status['message']);
        ?>
    </div>
    <?php
}
add_settings_field('alm_ai_api_key', 'API Key', 'render_api_key_field', 'alm_ai_settings_section', 'alm_ai_section');


}

