<?php
// Loader for AI connectors
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/interface-ai-connector.php';
require_once __DIR__ . '/class-openai-connector.php';

/**
 * Return available connectors (id => label). Uses WP Connectors API when available.
 * @return array
 */
function alm_get_available_ai_connectors() {
    $connectors = [];

    if (function_exists('wp_get_connectors')) {
        $all = wp_get_connectors();
        foreach ($all as $id => $data) {
            // only AI provider types are relevant, but allow any registered connector
            $connectors[$id] = isset($data['name']) ? $data['name'] : $id;
        }
    }

    // always include plugin built-in option
    $connectors = array_merge(['plugin' => 'Plugin AI (built-in)'], $connectors);

    return apply_filters('alm_available_ai_connectors', $connectors);
}

/**
 * Check if a connector is 'connected' by checking API key presence (env/const/db)
 * @param string $id
 * @return bool
 */
function alm_is_connector_connected($id) {
    if (empty($id) || $id === 'plugin') {
        return false;
    }


    // Try to use the connector metadata if available
    if (function_exists('wp_get_connector')) {
        $meta = wp_get_connector($id);
        if ($meta && isset($meta['authentication']) && isset($meta['authentication']['method']) && $meta['authentication']['method'] === 'api_key') {
            // determine setting name
            $setting_name = isset($meta['authentication']['setting_name']) ? $meta['authentication']['setting_name'] : 'connectors_ai_' . $id . '_api_key';

            // 1) env var: connectors may specify env_var_name or constant_name
            if (isset($meta['authentication']['env_var_name']) && getenv($meta['authentication']['env_var_name'])) {
                return true;
            }
            if (isset($meta['authentication']['constant_name']) && defined($meta['authentication']['constant_name'])) {
                return true;
            }

            // 3) database
            $val = get_option($setting_name);
            if (!empty($val)) {
                return true;
            }

            return false;
        }
    }

    // Fallback: try conventional setting name
    $fallback = get_option('connectors_ai_' . $id . '_api_key');
    return !empty($fallback);
}

/**
 * Instantiate a connector by key (only implements OpenAI as example)
 * @param string|null $key
 * @return AI_Connector_Interface|false
 */
function alm_get_ai_connector_instance($key = null) {
    if (!$key) {
        $key = alm_get_option('alm_ai_connector', 'plugin');
    }

    if ($key === 'plugin') {
        return false; // 'plugin' means use existing plugin AI path
    }

    if ($key === 'openai' && class_exists('OpenAI_Connector')) {
        // prefer connectors stored key if available
        $api_key = '';
        if (function_exists('wp_get_connector')) {
            $meta = wp_get_connector('openai');
            if ($meta && isset($meta['authentication']['setting_name'])) {
                $api_key = get_option($meta['authentication']['setting_name']);
            }
        }
        if (empty($api_key)) {
            $api_key = alm_get_option('alm_ai_api_key');
        }
        return new OpenAI_Connector($api_key);
    }

    return false;
}

/**
 * Wrapper used by the generator to call the active connector
 * @param string $image_url
 * @return array|WP_Error ['alt'=>'','title'=> '', 'error'=>...] or WP_Error
 */
function alm_generate_ai_alt_title_via_connector($image_url) {
    $connector_key = alm_get_option('alm_ai_connector', 'plugin');

    // If user explicitly selected plugin or no connector configured
    if (empty($connector_key) || $connector_key === 'plugin') {
        return ['alt' => '', 'title' => '', 'error' => 'No external connector selected'];
    }

    // If connector appears not connected, return structured error
    if (!alm_is_connector_connected($connector_key)) {
        $msg = 'Connector ' . $connector_key . ' is not connected or missing credentials';
        error_log('[alm AI ERROR] ' . $msg);
        return ['alt' => '', 'title' => '', 'error' => $msg];
    }

    $instance = alm_get_ai_connector_instance($connector_key);
    if (!$instance) {
        $msg = 'No AI connector implementation available for: ' . $connector_key;
        error_log('[alm AI ERROR] ' . $msg);
        return ['alt' => '', 'title' => '', 'error' => $msg];
    }

    $result = $instance->generate_alt_title($image_url);
    if (is_wp_error($result)) {
        $msg = $result->get_error_message();
        error_log('[alm AI ERROR] ' . $msg);
        return ['alt' => '', 'title' => '', 'error' => $msg];
    }

    // Normalize result to expected array shape
    $alt = '';
    $title = '';
    if (is_array($result)) {
        if (isset($result['alt'])) $alt = sanitize_text_field($result['alt']);
        if (isset($result['title'])) $title = sanitize_text_field($result['title']);
        if (isset($result['error']) && !empty($result['error'])) {
            // propagate error message
            error_log('[alm AI ERROR] ' . $result['error']);
            return ['alt' => $alt, 'title' => $title, 'error' => $result['error']];
        }
        return ['alt' => $alt, 'title' => $title];
    }

    // Fallback
    $msg = 'Unexpected connector response format';
    error_log('[alm AI ERROR] ' . $msg);
    return ['alt' => '', 'title' => '', 'error' => $msg];
}
