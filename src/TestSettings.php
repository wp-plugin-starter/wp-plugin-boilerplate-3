<?php

namespace nicomartin\PluginBoilerplate;

class TestSettings
{

    public function __construct()
    {
    }

    public function run()
    {
        add_filter('PREFIX_register_settings', [$this, 'registerSettings']);
    }

    public function registerSettings($settings)
    {
        $settings['myString'] = [
            'default'  => 'default value',
            'validate' => null,
        ];

        $settings['myStringArea'] = [
            'default'  => '',
            'validate' => null,
        ];

        $settings['mySelectValue'] = [
            'default'  => '',
            'validate' => function ($value) {
                if ($value !== 'green') {
                    return 'muss Green sein';
                }

                return '';
            },
        ];

        $settings['myCheckox'] = [
            'default'  => true,
            'validate' => null,
        ];

        $settings['myRadio'] = [
            'default'  => '',
            'validate' => null,
        ];

        $settings['myImages'] = [
            'default'  => '',
            'validate' => null,
        ];

        $settings['myEmail'] = [
            'default'  => 'test@nico.dev',
            'validate' => null,
        ];

        return $settings;
    }
}
