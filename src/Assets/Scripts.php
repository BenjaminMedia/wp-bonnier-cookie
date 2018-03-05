<?php

namespace Bonnier\WP\Cookie\Assets;

use Bonnier\WP\Cookie\Settings\SettingsPage;


class Scripts
{
    /**
     * @var static SettingsPage
     */
    private static $settings;

    /**
     * @var string
     */
    private $locale;

    public function bootstrap(SettingsPage $settings)
    {
        self::$settings = $settings;

        add_action('wp_head', [$this, 'add_cookie_to_header']);
    }

    public function add_cookie_to_header()
    {
        $this->locale = get_locale();

        $this->add_styles();
        //Html will be added to the body
        $this->add_html();
        $this->add_scripts();
    }

    public function add_styles()
    {
        $styles =
            '
              <style>
                .afubar-top {
                  background: #F0F0F0;
                  border-bottom: 1px solid #E7E7E7;
                  font-size: 11px;
                  line-height: 14px;
                  padding: .3em 10px;
                  min-height: 22px;
                }
                .afubar-top ul {
                  height: auto;
                  margin: 0;
                  padding: 0;
                  list-style: none;
                  text-align: left;
                }
                .afubar-top ul li {
                  padding-right: 10px;
                  display: inline;
                }
                .afubar-top a {
                  color: #555555;
                }
                .afubar-top img {
                  margin: -3px 3px 0;
                }
            
                .pull-right {
                  float: right !important;
                }
             </style>
            ';

        echo $styles;
    }

    public function add_html()
    {
        $cookieName = $this::$settings->get_setting_value('cookie_name', $this->locale);
        $cookieDeclaration = $this::$settings->get_setting_value('cookie_declaration', $this->locale);

        if ($cookieName && $cookieDeclaration) {
            $html = '
            <div class="afubar-top" style="position: relative;">
                <ul id="afubar_options" class="pull-right">
                    <li>
                    <a href="' . $cookieDeclaration . '" id="cookie" target="_blank" rel="nofollow">
                        <img src="https://s3-eu-west-1.amazonaws.com/white-album/images/cookie-triangle-small.png">
                        ' . $cookieName . '
                    </a>
                    </li>
                </ul>
            </div>
            ';

            echo $html;
        }
    }

    public function add_scripts()
    {
        $lang = SettingsPage::locale_to_lang_code($this->locale);
        $cookieScriptId = $this::$settings->get_setting_value('cookie_script_id', $this->locale);

        if ($cookieScriptId && $lang) {
            $scripts = '<script id="CookieConsent" data-culture="' . $lang . '" src="https://policy.cookieinformation.com/uc.js" 
              data-cbid="' . $cookieScriptId . '" type="text/javascript"></script>';

            echo $scripts;
        }
    }

}