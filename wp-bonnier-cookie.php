<?php
/**
 * Plugin Name: WP Bonnier Cookie
 * Plugin URI: http://bonnierpublications.com
 * Description: Bonnier Cookie Plugin
 * Version: 1.0.0
 * Author: Mohamed Salem Lamiri
 * Author URI: http://bonnierpublications.com
 */

namespace Bonnier\WP\Cookie;

use Bonnier\WP\Cookie\Settings\SettingsPage;
use Bonnier\WP\Cookie\Assets\Scripts;

defined('ABSPATH') or die('No script kiddies please!');


spl_autoload_register(function ($className) {
    if (strpos($className, __NAMESPACE__) !== false) {
        $classPath = str_replace( // Replace namespace with directory separator
            "\\",
            DIRECTORY_SEPARATOR,
            str_replace( // Replace namespace with path to class dir
                __NAMESPACE__,
                __DIR__ . DIRECTORY_SEPARATOR . WpBonnierCookie::CLASS_DIR,
                $className
            )
        );
        require_once($classPath . '.php');
    }
});

require_once(__DIR__ . '/includes/vendor/autoload.php');

class WpBonnierCookie
{
    const TEXT_DOMAIN = 'wp-bonnier-cookie';

    const CLASS_DIR = 'src';

    private static $instance;

    public $settings;

    public $file;

    public $basename;

    public $plugin_dir;

    public $plugin_url;

    /**
     * WpBonnierCookie constructor.
     */
    private function __construct()
    {
        $this->file = __FILE__;
        $this->basename = plugin_basename($this->file);
        $this->plugin_dir = plugin_dir_path($this->file);
        $this->plugin_url = plugin_dir_url($this->file);

        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname($this->basename.'/languages'));

        $this->settings = new SettingsPage();
        $this->scripts = new Scripts();

    }

    private function bootstrap()
    {
        $this->scripts->bootstrap($this->settings);
    }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
            global $wp_bonnier_cache;
            $wp_bonnier_cache = self::$instance;
            self::$instance->bootstrap();

            do_action('wp_bonnier_cookie_loaded');
        }

        return self::$instance;
    }
}

function instance()
{
    return WpBonnierCookie::instance();
}

add_action('plugins_loaded', __NAMESPACE__.'\instance', 0);