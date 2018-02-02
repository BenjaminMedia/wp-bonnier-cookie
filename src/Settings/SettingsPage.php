<?php

namespace Bonnier\WP\Cookie\Settings;

class SettingsPage
{
    const SETTINGS_KEY = 'wp_cookie_settings';
    const SETTINGS_GROUP = 'wp_cookie_settings_group';
    const SETTINGS_SECTION = 'wp_cookie_settings_section';
    const SETTINGS_PAGE = 'wp_cookie_settings_page';
    const Settings_PAGE_NAME = 'Bonnier Cookie';
    const Settings_PAGE_TITLE = 'WP Bonnier Cookie settings:';
    const NOTICE_PREFIX = 'WP Bonnier Cookie:';

    private $settingsFields = [
        'cookie_name' => [
            'type' => 'text',
            'name' => 'Cookie name',
        ],
        'cookie_script_id' => [
            'type' => 'text',
            'name' => 'Cookie script id (data-cbid)',
        ],
        'cookie_declaration' => [
            'type' => 'text',
            'name' => 'Link to declaration',
        ],
    ];

    private $settingsValues;

    public function __construct()
    {
        $this->settingsValues = get_option(self::SETTINGS_KEY);
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function get_cookie_host()
    {
        return $this->get_setting_value('host_url') ?: '';
    }

    public function print_error($error)
    {
        $out = "<div class='error settings-error notice is-dismissible'>";
        $out .= "<strong>" . self::NOTICE_PREFIX . "</strong><p>$error</p>";
        $out .= "</div>";
        print $out;
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            self::Settings_PAGE_NAME,
            'manage_options',
            self::SETTINGS_PAGE,
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields(self::SETTINGS_GROUP);
                do_settings_sections(self::SETTINGS_PAGE);
                submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function register_settings()
    {
        if ($this->languages_is_enabled()) {
            $this->enable_language_fields();
        }

        register_setting(
            self::SETTINGS_GROUP, // Option group
            self::SETTINGS_KEY, // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            self::SETTINGS_SECTION, // ID
            self::Settings_PAGE_TITLE, // Title
            array($this, 'print_section_info'), // Callback
            self::SETTINGS_PAGE // Page
        );

        foreach ($this->settingsFields as $settingsKey => $settingField) {
            add_settings_field(
                $settingsKey, // ID
                $settingField['name'], // Title
                array($this, $settingsKey), // Callback
                self::SETTINGS_PAGE, // Page
                self::SETTINGS_SECTION // Section
            );
        }
    }

    public function languages_is_enabled()
    {
        return function_exists('Pll') && PLL()->model->get_languages_list();
    }

    /**
     * Get the current language by looking at the current HTTP_HOST
     *
     * @return null|PLL_Language
     */
    public function get_current_language()
    {
        if ($this->languages_is_enabled()) {
            return PLL()->model->get_language(pll_current_language());
        }
        return null;
    }

    public function get_languages()
    {
        if ($this->languages_is_enabled()) {
            return PLL()->model->get_languages_list();
        }
        return false;
    }

    /**
     * Returns the language code from locale ie. 'da_DK' becomes 'da'
     *
     * @param $locale
     * @return string
     */
    public static function locale_to_lang_code($locale)
    {
        return substr($locale, 0, 2);
    }

    public function get_localized_setting_key($settingKey, $locale = null)
    {
        if ($locale) {
            return $this->locale_to_lang_code($locale) . '_' . $settingKey;
        }

        return $settingKey;
    }

    private function enable_language_fields()
    {
        $languageEnabledFields = [];

        foreach ($this->get_languages() as $language) {
            foreach ($this->settingsFields as $fieldKey => $settingsField) {
                $localeFieldKey = $this->get_localized_setting_key($fieldKey, $language->locale);
                $languageEnabledFields[$localeFieldKey] = $settingsField;
                $languageEnabledFields[$localeFieldKey]['name'] .= ' ' . $language->locale;
                $languageEnabledFields[$localeFieldKey]['locale'] = $language->locale;
            }
        }

        $this->settingsFields = $languageEnabledFields;
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array
     */
    public function sanitize($input)
    {
        $sanitizedInput = [];

        foreach ($this->settingsFields as $fieldKey => $settingsField) {
            if (isset($input[$fieldKey])) {
                if ($settingsField['type'] === 'checkbox') {
                    $sanitizedInput[$fieldKey] = absint($input[$fieldKey]);
                }
                if ($settingsField['type'] === 'text' || $settingsField['type'] === 'select') {
                    $sanitizedInput[$fieldKey] = sanitize_text_field($input[$fieldKey]);
                }
                if ($settingsField['type'] === 'callback') {
                    $sanitizedInput[$fieldKey] =call_user_func_array($settingsField['sanitize_callback'], [$input[$fieldKey]]);
                }
            }
        }

        return $sanitizedInput;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /**
     * Catch callbacks for creating setting fields
     * @param string $function
     * @param array $arguments
     * @return bool
     */
    public function __call($function, $arguments)
    {
        if (!isset($this->settingsFields[$function])) {
            return false;
        }

        $field = $this->settingsFields[$function];
        $this->create_settings_field($field, $function);

        return true;
    }

    public function get_setting_value($settingKey, $locale = null)
    {
        if (!$this->settingsValues) {
            $this->settingsValues = get_option(self::SETTINGS_KEY);
        }

        $settingKey = $this->get_localized_setting_key($settingKey, $locale);

        if (isset($this->settingsValues[$settingKey]) && !empty($this->settingsValues[$settingKey])) {
            return $this->settingsValues[$settingKey];
        }
        return false;
    }

    private function get_select_field_options($field)
    {
        if (isset($field['options_callback'])) {
            $options = $this->{$field['options_callback']}($field['locale']);
            if ($options) {
                return $options;
            }
        }

        return [];
    }

    private function create_settings_field($field, $fieldKey)
    {
        $fieldName = self::SETTINGS_KEY . "[$fieldKey]";
        $fieldOutput = false;

        if ($field['type'] === 'text') {
            $fieldValue = isset($this->settingsValues[$fieldKey]) ? esc_attr($this->settingsValues[$fieldKey]) : '';
            $fieldOutput = "<input type='text' name='$fieldName' value='$fieldValue' class='regular-text' />";
        }
        if ($field['type'] === 'checkbox') {
            $checked = isset($this->settingsValues[$fieldKey]) && $this->settingsValues[$fieldKey] ? 'checked' : '';
            $fieldOutput = "<input type='hidden' value='0' name='$fieldName'>";
            $fieldOutput .= "<input type='checkbox' value='1' name='$fieldName' $checked />";
        }
        if ($field['type'] === 'select') {
            $fieldValue = isset($this->settingsValues[$fieldKey]) ? $this->settingsValues[$fieldKey] : '';
            $fieldOutput = "<select name='$fieldName'>";
            $options = $this->get_select_field_options($field);
            foreach ($options as $option) {
                $selected = ($option['system_key'] === $fieldValue) ? 'selected' : '';
                $fieldOutput .= "<option value='" . $option['system_key'] . "' $selected >" . $option['system_key'] . "</option>";
            }
            $fieldOutput .= "</select>";
        }
        if ($field['type'] === 'callback') {
            $fieldValue = isset($this->settingsValues[$fieldKey]) ? $this->settingsValues[$fieldKey] : [];

            call_user_func_array($field['callback'], [$fieldName, $fieldValue]);
        }

        if ($fieldOutput) {
            print $fieldOutput;
        }
    }
}
