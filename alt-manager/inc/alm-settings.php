<?php
// File: inc/alm-settings.php

// Helper: check API status (used by the API key field renderer)
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

// Renderers for settings fields
function render_api_key_field()
{
    $endpoint_url = 'https://api.openai.com/v1/chat/completions';
    $api_key = alm_get_option('alm_ai_api_key');

    $api_status = alm_check_api_status($endpoint_url, $api_key);

    $value = esc_attr($api_key);

    wp_nonce_field('alm_ai_api_key_update');
    echo "<div id='alm_api_key_container'>";
    echo "<span class='alm-marker-api-key' style='display:none'></span>";
    echo "<input type='text' name='alm_ai_api_key' id='alm_ai_api_key' value='{$value}' class='regular-text'>";
    echo "<div id='alm-api-status' style='margin-top:5px;'></div>";
    echo "</div>";
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

function render_ai_connector_switch_field() {
    $val = alm_get_option('alm_use_ai_connectors', '');
    if ('enabled' === $val) {
        echo '<input id="alm_use_ai_connectors" type="checkbox" name="alm_use_ai_connectors" value="enabled" checked>';
    } else {
        echo '<input id="alm_use_ai_connectors" type="checkbox" name="alm_use_ai_connectors" value="enabled">';
    }
    echo '<label for="alm_use_ai_connectors"> Enable AI Connectors (use WordPress built-in AI connectors)</label>';
}

function render_ai_connector_selector_field() {
    $selected = alm_get_option('alm_ai_connector', 'plugin');
    $use_connectors = alm_get_option('alm_use_ai_connectors', '') === 'enabled';
    $connectors = [];
    if (file_exists(__DIR__ . '/ai-connectors/index.php')) {
        require_once __DIR__ . '/ai-connectors/index.php';
        $connectors = alm_get_available_ai_connectors();
    }

    echo '<div id="alm_ai_connector_container">';
    // If connectors usage is requested, the built-in 'plugin' option should not be available
    if ($use_connectors) {
        // Filter out the built-in plugin option
        $available = array_filter($connectors, function($label, $key) {
            return $key !== 'plugin';
        }, ARRAY_FILTER_USE_BOTH);

        // Determine connected providers
        $connected_keys = [];
        foreach ($available as $k => $lbl) {
            if (function_exists('alm_is_connector_connected') && alm_is_connector_connected($k)) {
                $connected_keys[] = $k;
            }
        }

        if (empty($available) || empty($connected_keys)) {
            $connectors_url = esc_url( admin_url('options-connectors.php') );
            echo '<p><em>No AI connectors found or connected. Install and connect providers under Settings → <a href="' . $connectors_url . '" target="_blank" rel="noopener noreferrer">Connectors</a>.</em></p>';
            echo '<input type="hidden" name="alm_ai_connector" value="">';
        } else {
            // Ensure selected is a valid connected key; otherwise pick the first connected
            if (!in_array($selected, $connected_keys, true)) {
                $selected = $connected_keys[0];
            }

            echo '<select id="alm_ai_connector" name="alm_ai_connector">';
            foreach ($available as $key => $label) {
                $connected = function_exists('alm_is_connector_connected') ? alm_is_connector_connected($key) : false;
                $disabled = $connected ? '' : 'disabled';
                $label_text = $label . ($connected ? '' : ' (not connected)');
                $sel = selected($selected, $key, false);
                echo '<option value="' . esc_attr($key) . '" ' . $sel . ' ' . $disabled . '>' . esc_html($label_text) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">Choose which connected provider to use when AI Connectors are enabled.</p>';
        }
    } else {
        // When connectors are not used, allow selecting the built-in plugin option
        if (empty($connectors) || (count($connectors) === 1 && isset($connectors['plugin']))) {
            echo '<p><em>No AI connectors found. Install or register connectors under Settings → Connectors.</em></p>';
            echo '<input type="hidden" name="alm_ai_connector" value="plugin">';
        } else {
            echo '<select id="alm_ai_connector" name="alm_ai_connector">';
            foreach ($connectors as $key => $label) {
                $connected = alm_is_connector_connected($key);
                $disabled = ($key !== 'plugin' && !$connected) ? 'disabled' : '';
                $label_text = $label . ($key !== 'plugin' && !$connected ? ' (not connected)' : '');
                $sel = selected($selected, $key, false);
                echo '<option value="' . esc_attr($key) . '" ' . $sel . ' ' . $disabled . '>' . esc_html($label_text) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">Choose which connector to use when AI Connectors are enabled.</p>';
        }
    }
    echo '</div>';

    // Inline JS to toggle visibility based on the checkbox
    ?>
    <script type="text/javascript">
    (function(){
        function closestRow(el) {
            while (el && el.nodeName && el.nodeName.toLowerCase() !== 'tr') el = el.parentNode;
            return el;
        }

        function initToggle(){
            var checkbox = document.getElementById('alm_use_ai_connectors');
            var apiMarker = document.querySelector('.alm-marker-api-key');
            var apiContainer = document.getElementById('alm_api_key_container');
            var connectorContainer = document.getElementById('alm_ai_connector_container');

            var apiRow = apiMarker ? closestRow(apiMarker) : (apiContainer ? closestRow(apiContainer) : null);
            var connectorRow = connectorContainer ? closestRow(connectorContainer) : null;

            function update(){
                var enabled = checkbox && checkbox.checked;
                if (apiContainer) apiContainer.style.display = enabled ? 'none' : '';
                if (apiRow) apiRow.style.display = enabled ? 'none' : '';
                if (connectorContainer) connectorContainer.style.display = enabled ? '' : 'none';
                if (connectorRow) connectorRow.style.display = enabled ? '' : 'none';
            }

            // initial
            update();

            if (checkbox) checkbox.addEventListener('change', update);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initToggle);
        } else {
            initToggle();
        }
    })();
    </script>
    <?php
}

// Register settings and fields during admin_init only
function alm_register_settings() {
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
        register_setting('alm_ai_settings', 'alm_use_ai_connectors');
        register_setting('alm_ai_settings', 'alm_ai_connector');

        add_settings_section(
            'alm_ai_section',
            'AI API Settings',
            null,
            'alm_ai_settings_section'
        );

        add_settings_field('alm_ai_api_key', 'API Key', 'render_api_key_field', 'alm_ai_settings_section', 'alm_ai_section');
        add_settings_field('alm_use_ai_connectors', 'Use AI Connectors', 'render_ai_connector_switch_field', 'alm_ai_settings_section', 'alm_ai_section');
        add_settings_field('alm_ai_connector', 'AI Connector', 'render_ai_connector_selector_field', 'alm_ai_settings_section', 'alm_ai_section');
    }
}
add_action('admin_init', 'alm_register_settings');
