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

        $this->start_controls_section(
            'section_execution',
            [
                'label' => esc_html__('Execution Settings', 'custom-js-widget'),
            ]
        );

        $this->add_control(
            'trigger',
            [
                'label' => esc_html__('Execution Trigger', 'custom-js-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => 'immediate',
                'options' => [
                    'immediate' => esc_html__('Immediate', 'custom-js-widget'),
                    'elementor_init' => esc_html__('Elementor Frontend Init', 'custom-js-widget'),
                    'popup_show' => esc_html__('On Popup Show', 'custom-js-widget'),
                    'custom_event' => esc_html__('Custom jQuery Event', 'custom-js-widget'),
                ],
                'description' => esc_html__('When should this script execute?', 'custom-js-widget'),
            ]
        );

        $this->add_control(
            'custom_event_name',
            [
                'label' => esc_html__('Event Name', 'custom-js-widget'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'my_custom_event',
                'condition' => [
                    'trigger' => 'custom_event',
                ],
            ]
        );

        $this->add_control(
            'popup_id_filter',
            [
                'label' => esc_html__('Filter by Popup ID', 'custom-js-widget'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Optional post ID of the popup. Leave empty for all popups.', 'custom-js-widget'),
                'condition' => [
                    'trigger' => 'popup_show',
                ],
            ]
        );

        $this->add_control(
            'restrict_to_popup',
            [
                'label' => esc_html__('Restrict to Popup Context?', 'custom-js-widget'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'custom-js-widget'),
                'label_off' => esc_html__('No', 'custom-js-widget'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => esc_html__('Only runs if the widget is physically placed inside an Elementor Popup.', 'custom-js-widget'),
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
        $trigger = $settings['trigger'] ?? 'immediate';
        $restrict_to_popup = 'yes' === ($settings['restrict_to_popup'] ?? 'no');
        $widget_id = $this->get_id();

        // 1. Handle Context Restriction (Popup check)
        if ($restrict_to_popup) {
            $js_code = "if (jQuery('.elementor-element-{$widget_id}').closest('.elementor-location-popup').length > 0) {\n{$js_code}\n}";
        }

        // 2. Handle Triggers
        switch ($trigger) {
            case 'elementor_init':
                $js_code = "jQuery(window).on('elementor/frontend/init', function() {\n{$js_code}\n});";
                break;

            case 'popup_show':
                $popup_id = !empty($settings['popup_id_filter']) ? trim($settings['popup_id_filter']) : '';
                $condition = !empty($popup_id) ? "if (id == '{$popup_id}') {\n" : "";
                $end_condition = !empty($popup_id) ? "\n}" : "";

                $js_code = "jQuery(document).on('elementor/popup/show', function(event, id, instance) {\n{$condition}{$js_code}{$end_condition}\n});";
                break;

            case 'custom_event':
                $event_name = !empty($settings['custom_event_name']) ? trim($settings['custom_event_name']) : 'custom_js_event';
                $js_code = "jQuery(document).on('{$event_name}', function() {\n{$js_code}\n});";
                break;
        }

        // 3. Output the script
        if ('inline' === $placement) {
            if ($use_jquery || 'immediate' !== $trigger) {
                wp_enqueue_script('jquery');
            }
            echo wp_get_inline_script_tag($js_code); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            \CustomElementorJSWidget\Plugin::enqueue_script($placement, $js_code, $use_jquery);

            // Editor placeholder for better UX.
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                $trigger_label = $settings['trigger'] ?? 'immediate';
                printf(
                    '<div class="cjs-script-placeholder" style="padding: 10px; background: #f8f9fa; border: 1px dashed #ccc; text-align: center; margin-bottom: 10px;">' .
                    '<strong>%s</strong>: %s | <strong>%s</strong>: %s' .
                    '</div>',
                    esc_html__('Placement', 'custom-js-widget'),
                    esc_html(ucfirst($placement)),
                    esc_html__('Trigger', 'custom-js-widget'),
                    esc_html(ucfirst(str_replace('_', ' ', $trigger_label)))
                );
            }
        }
    }
}
