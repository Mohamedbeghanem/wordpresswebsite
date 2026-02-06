<?php

namespace BitCode\FI\Triggers\Elementor;

use BitCode\FI\Flow\Flow;

final class ElementorController
{
    public static function info()
    {
        $plugin_path = self::pluginActive('get_name');
        return [
            'name' => 'Elementor',
            'title' => 'Elementor is the platform web creators choose to build professional WordPress websites, grow their skills, and build their business. Start for free today!',
            'slug' => $plugin_path,
            'pro' => $plugin_path,
            'type' => 'form',
            'is_active' => is_plugin_active($plugin_path),
            'activation_url' => wp_nonce_url(self_admin_url('plugins.php?action=activate&amp;plugin=' . $plugin_path . '&amp;plugin_status=all&amp;paged=1&amp;s'), 'activate-plugin_' . $plugin_path),
            'install_url' => wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_path), 'install-plugin_' . $plugin_path),
            'list' => [
                'action' => 'elementor/get',
                'method' => 'get',
            ],
            'fields' => [
                'action' => 'elementor/get/form',
                'method' => 'post',
                'data' => ['id']
            ],
        ];
    }

    public static function pluginActive($option = null)
    {
        if (is_plugin_active('elementor-pro/elementor-pro.php') || is_plugin_active('elementor/elementor.php')) {
            return true;
        }
        return false;
    }

    public function getAllForms()
    {
        if (!self::pluginActive()) {
            wp_send_json_error(__('Elementor Pro is not installed or activated', 'bit-integrations'));
        }

        $forms = ElementorHelper::all_forms();

        foreach ($forms as $form) {
            $all_forms[] = (object)[
                'id' => $form['id'],
                'title' => $form['title']
            ];
        }
        wp_send_json_success($all_forms);
    }

    public function getFormFields($data)
    {
        if (!self::pluginActive()) {
            wp_send_json_error(__('Elementor Pro is not installed or activated', 'bit-integrations'));
        }
        if (empty($data->id) && empty($data->postId)) {
            wp_send_json_error(__('Form doesn\'t exists', 'bit-integrations'));
        }

        $fields = ElementorHelper::all_fields($data->id);
        if (empty($fields)) {
            wp_send_json_error(__('Form doesn\'t exists any field', 'bit-integrations'));
        }

        $responseData['fields'] = $fields;
        $responseData['postId'] = $data->postId;
        wp_send_json_success($responseData);
    }

    public static function handle_elementor_submit($record)
    {
        $form_id = $record->get_form_settings('id');

        $flows = Flow::exists('Elementor', $form_id);
        if (!$flows) {
            return;
        }

        $data = [];
        $fields = $record->get('fields');
        foreach ($fields as $field) {
            $data[$field['id']] = $field['raw_value'];
        }

        Flow::execute('Elementor', $form_id, $data, $flows);
    }

}
