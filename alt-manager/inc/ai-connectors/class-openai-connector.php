<?php
if (!defined('ABSPATH')) {
    exit;
}

class OpenAI_Connector implements AI_Connector_Interface {
    private $api_key;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function generate_alt_title($image_url) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', 'OpenAI API key is missing');
        }

        $api_url = 'https://api.openai.com/v1/chat/completions';

        $body = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Describe this image for alt text and title in a short sentence. Return the alt text and title as JSON with keys "alt" and "title".'
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $image_url
                            ]
                        ]
                    ]
                ]
            ],
            'max_tokens' => 100
        ];

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('request_failed', $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($data['choices'][0]['message']['content'])) {
            $err = isset($data['error']['message']) ? $data['error']['message'] : 'Invalid API response';
            return new WP_Error('invalid_response', $err);
        }

        $content = $data['choices'][0]['message']['content'];
        $json = json_decode($content, true);

        if (is_array($json) && isset($json['alt']) && isset($json['title'])) {
            return [
                'alt' => sanitize_text_field($json['alt']),
                'title' => sanitize_text_field($json['title']),
            ];
        }

        // fallback: use content as both alt and title
        return [
            'alt' => sanitize_text_field($content),
            'title' => sanitize_text_field($content),
        ];
    }
}
