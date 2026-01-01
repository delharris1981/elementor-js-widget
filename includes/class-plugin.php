<?php

declare(strict_types=1);

namespace CustomElementorJSWidget;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 * 
 * Handles dependency checks and initialization.
 */
final class Plugin
{

    /**
     * Instance of this class.
     */
    private static ?self $instance = null;

    /**
     * Scripts queue for header and footer.
     * 
     * @var array<string, array<int, array{code: string, jquery: bool}>>
     */
    private static array $scripts_queue = [
        'header' => [],
        'footer' => [],
    ];

    /**
     * Private constructor for singleton pattern.
     */
    private function __construct()
    {
        $this->init_hooks();
        $this->init_updater();
    }

    /**
     * Get the singleton instance.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks(): void
    {
        add_action('plugins_loaded', [$this, 'check_dependencies']);
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('elementor/elements/categories_registered', [$this, 'register_categories']);

        // Script printing hooks.
        add_action('wp_enqueue_scripts', [$this, 'register_header_scripts_early']);
        add_action('wp_head', [$this, 'print_header_scripts']);
        add_action('wp_footer', [$this, 'print_footer_scripts']);
    }

    /**
     * Initialize GitHub Updater.
     */
    private function init_updater(): void
    {
        require_once CJSW_PLUGIN_DIR . 'includes/class-updater.php';
        // Note: Replace 'username/repo' with your actual repository.
        new Updater(CJSW_PLUGIN_FILE, 'derek/elementor-js-widget');
    }

    /**
     * Check if required dependencies are active.
     */
    public function check_dependencies(): void
    {
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_elementor']);
            return;
        }
    }

    /**
     * Display admin notice if Elementor is missing.
     */
    public function admin_notice_missing_elementor(): void
    {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor */
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'custom-js-widget'),
            '<strong>' . esc_html__('Custom JS Widget', 'custom-js-widget') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'custom-js-widget') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message);
    }

    /**
     * Register the custom widget.
     * 
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
     */
    public function register_widgets($widgets_manager): void
    {
        require_once CJSW_PLUGIN_DIR . 'includes/widgets/class-custom-js-widget.php';
        $widgets_manager->register(new Widgets\Custom_JS_Widget());
    }

    /**
     * Register custom Elementor category.
     * 
     * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
     */
    public function register_categories($elements_manager): void
    {
        $elements_manager->add_category(
            'custom-widgets',
            [
                'title' => esc_html__('Custom Widgets', 'custom-js-widget'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    /**
     * Enqueue a script for later printing.
     * 
     * @param string $placement Location (header/footer).
     * @param string $code      The JS code.
     * @param bool   $jquery    Whether jQuery is required.
     */
    public static function enqueue_script(string $placement, string $code, bool $jquery = false): void
    {
        if (!isset(self::$scripts_queue[$placement])) {
            return;
        }

        // Prevent duplicates based on hash of code and placement.
        $hash = md5($placement . $code);
        if (isset(self::$scripts_queue[$placement][$hash])) {
            return;
        }

        self::$scripts_queue[$placement][$hash] = [
            'code' => $code,
            'jquery' => $jquery,
        ];
    }

    /**
     * Early detection of header scripts by parsing Elementor data.
     */
    public function register_header_scripts_early(): void
    {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }

        // Ensure Elementor is loaded.
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }

        $document = \Elementor\Plugin::$instance->documents->get($post_id);
        if (!$document) {
            return;
        }

        $data = $document->get_elements_data();
        if (!empty($data)) {
            $this->find_widgets_recursive($data);
        }
    }

    /**
     * Recursively find our widget and enqueue header scripts.
     * 
     * @param array $elements Elementor elements data.
     */
    private function find_widgets_recursive(array $elements): void
    {
        foreach ($elements as $element) {
            if (isset($element['widgetType']) && 'cjs_js_widget' === $element['widgetType']) {
                $settings = $element['settings'] ?? [];
                $placement = $settings['placement'] ?? 'inline';

                if ('header' === $placement && !empty($settings['js_code'])) {
                    $use_jquery = 'yes' === ($settings['use_jquery'] ?? 'no');
                    self::enqueue_script('header', $settings['js_code'], $use_jquery);
                }
            }

            if (!empty($element['elements'])) {
                $this->find_widgets_recursive($element['elements']);
            }
        }
    }

    /**
     * Print scripts for wp_head.
     */
    public function print_header_scripts(): void
    {
        $this->print_scripts('header');
    }

    /**
     * Print scripts for wp_footer.
     */
    public function print_footer_scripts(): void
    {
        $this->print_scripts('footer');
    }

    /**
     * Print scripts from the queue.
     * 
     * @param string $placement Location to print from.
     */
    private function print_scripts(string $placement): void
    {
        if (empty(self::$scripts_queue[$placement])) {
            return;
        }

        foreach (self::$scripts_queue[$placement] as $script) {
            // Handle jQuery dependency if needed (basic version for now).
            if ($script['jquery']) {
                wp_enqueue_script('jquery');
            }

            echo wp_get_inline_script_tag($script['code']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
}
