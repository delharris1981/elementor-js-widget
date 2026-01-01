<?php

declare(strict_types=1);

namespace CustomElementorJSWidget\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom JS Widget
 * 
 * Allowing users to add raw JavaScript to their Elementor layouts.
 */
class Custom_JS_Widget extends Widget_Base
{

    /**
     * Get widget name.
     */
    public function get_name(): string
    {
        return 'cjs_js_widget';
    }

    /**
     * Get widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Custom JS', 'custom-js-widget');
    }

    /**
     * Get widget icon.
     */
    public function get_icon(): string
    {
        return 'eicon-code';
    }

    /**
     * Get widget categories.
     */
    public function get_categories(): array
    {
        return ['custom-widgets'];
    }

    /**
     * Register widget controls.
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('JavaScript Code', 'custom-js-widget'),
            ]
        );

        $this->add_control(
            'js_code',
            [
                'label' => esc_html__('Raw JS', 'custom-js-widget'),
                'type' => Controls_Manager::CODE,
                'language' => 'javascript',
                'rows' => 20,
                'description' => esc_html__('Enter your JavaScript code here. Do not include <script> tags.', 'custom-js-widget'),
            ]
        );

        $this->add_control(
            'placement',
            [
                'label' => esc_html__('Placement', 'custom-js-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => 'inline',
                'options' => [
                    'inline' => esc_html__('Inline', 'custom-js-widget'),
                    'header' => esc_html__('Header (wp_head)', 'custom-js-widget'),
                    'footer' => esc_html__('Footer (wp_footer)', 'custom-js-widget'),
                ],
            ]
        );

        $this->add_control(
            'use_jquery',
            [
                'label' => esc_html__('Requires jQuery?', 'custom-js-widget'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'custom-js-widget'),
                'label_off' => esc_html__('No', 'custom-js-widget'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $js_code = $settings['js_code'];

        if (empty($js_code)) {
            return;
        }

        $placement = $settings['placement'];
        $use_jquery = 'yes' === $settings['use_jquery'];

        if ('inline' === $placement) {
            if ($use_jquery) {
                wp_enqueue_script('jquery');
            }
            echo wp_get_inline_script_tag($js_code); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            \CustomElementorJSWidget\Plugin::enqueue_script($placement, $js_code, $use_jquery);

            // Editor placeholder for better UX.
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                printf(
                    '<div class="cjs-script-placeholder" style="padding: 10px; background: #f8f9fa; border: 1px dashed #ccc; text-align: center;">' .
                    '<strong>%s</strong>: %s' .
                    '</div>',
                    esc_html__('JS Placement', 'custom-js-widget'),
                    esc_html(ucfirst($placement))
                );
            }
        }
    }
}
