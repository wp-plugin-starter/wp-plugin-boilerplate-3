<?php

namespace nicomartin\PluginBoilerplate;

class Settings
{

    public static $key = 'PREFIX-settings';
    public $registered_settings = [];

    public function __construct()
    {
    }

    public function run()
    {
        add_action('init', [$this, 'addFilterSettings']);
        add_action('rest_api_init', [$this, 'registerRoute']);
    }

    public function addFilterSettings()
    {
        $this->registered_settings = apply_filters('PREFIX_register_settings', $this->registered_settings);
    }

    public function registerRoute()
    {
        register_rest_route(PREFIX_get_instance()->api_namespace, 'settings', [
            'methods'  => 'POST',
            'callback' => [$this, 'apiUpdateSetting'],
        ]);

        register_rest_route(PREFIX_get_instance()->api_namespace, 'settings', [
            'methods'  => 'GET',
            'callback' => [$this, 'apiGetSettings'],
        ]);
    }

    public function apiUpdateSetting($req)
    {
        $settings = $req->get_params();
        $errors   = [];

        foreach ($settings as $key => $value) {
            $validate = $this->validateSetting($key, $value);
            if (is_wp_error($validate)) {
                $errors[$key] = $validate->get_error_message();
            }
        }

        if (count($errors) !== 0) {
            $message = '<p>' . __('Validation Failed', 'PREFIX') . '</p>';
            $message .= '<ul>';
            foreach ($errors as $error) {
                $message .= '<li>' . $error . '</li>';
            }
            $message .= '</ul>';

            return new \WP_Error('validation_failed', $message, [
                'status' => 400,
                'data'   => $errors,
            ]);
        }

        $options = $this->getSettings();

        update_option(self::$key, array_merge($options, $settings));

        return $this->getSettings();
    }

    public function apiGetSettings()
    {
        if (Helpers::checkAuth()) {
            return new \WP_Error('unauthorized', 'Unauthorized', [
                'status' => 401,
            ]);
        }

        $return              = [];
        $valid_settings_keys = array_keys($this->registered_settings);
        foreach ($valid_settings_keys as $key) {
            $return[$key] = $this->getSingleSetting($key, $this->getSettings());
        }

        return $return;
    }

    public function regsterSettings($key, $default_value, $validation)
    {
        $this->registered_settings[$key] = [
            'default'  => $default_value,
            'validate' => $validation ? function ($value) use ($validation) {
                return $validation($value);
            } : null,
        ];
    }

    public function getSettings($keys_to_return = [])
    {
        $saved_options = get_option(self::$key, []);

        if (count($keys_to_return) === 0) {
            $keys_to_return = array_keys($this->registered_settings);
        }

        $settings_to_return = [];
        foreach ($keys_to_return as $settings_key) {
            $settings_to_return[$settings_key] = array_key_exists(
                $settings_key,
                $saved_options
            ) ? $saved_options[$settings_key] : $this->registered_settings[$settings_key]['default'];
        }

        return $settings_to_return;
    }

    public function getSingleSetting($key, $all_settings = null)
    {
        if ( ! array_key_exists($key, $this->registered_settings)) {
            return null;
        }

        if (null === $all_settings) {
            $all_settings = $this->getSettings([$key]);
        }

        return $all_settings[$key];
    }

    public function validateSetting($key, $value)
    {
        if ( ! array_key_exists($key, $this->registered_settings)) {
            return new \WP_Error('invalid_setting', sprintf(__('Invalid Settings key "%s"', 'PREFIX'), $key));
        }

        $validate = $this->registered_settings[$key]['validate'] ?
            $this->registered_settings[$key]['validate']($value) :
            '';
        if ('' !== $validate) {
            return new \WP_Error('invalid_setting_value', sprintf(__('%s: %s', 'PREFIX'), $key, $validate));
        }

        return true;
    }
}
