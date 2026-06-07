<?php
if (!defined('ABSPATH')) {
    exit;
}

interface AI_Connector_Interface {
    /**
     * Generate alt and title for an image URL
     * @param string $image_url
     * @return array|WP_Error ['alt' => '', 'title' => '', 'error' => '...'] or WP_Error
     */
    public function generate_alt_title($image_url);
}
