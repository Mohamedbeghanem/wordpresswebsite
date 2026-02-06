<?php
namespace BitCode\FI\Triggers\Brizy;

use BitCode\FI\Core\Util\Common;
use BitCode\FI\Flow\Flow;
use BitCode\FI\Log\LogHandler;

final class BrizyController
{
    public static function info()
    {
        $plugin_path = 'brizy/brizy.php';
        return [
            'name' => 'Brizy',
            'title' => 'Brizy is the platform web creators choose to build professional WordPress websites, grow their skills, and build their business. Start for free today!',
            'slug' => $plugin_path,
            'pro' => $plugin_path,
            'type' => 'form',
            'is_active' => is_plugin_active($plugin_path),
            'activation_url' => wp_nonce_url(self_admin_url('plugins.php?action=activate&amp;plugin=' . $plugin_path . '&amp;plugin_status=all&amp;paged=1&amp;s'), 'activate-plugin_' . $plugin_path),
            'install_url' => wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_path), 'install-plugin_' . $plugin_path),
            'list' => [
                'action' => 'brizy/get',
                'method' => 'get',
            ],
            'fields' => [
                'action' => 'brizy/get/form',
                'method' => 'post',
                'data' => ['id']
            ],
        ];
    }

    public static function handle_brizy_submit($fields, $form)
    {
        if (!method_exists($form, 'getId')) {
            return $fields;
        }
        $form_id = $form->getId();
        $flows = Flow::exists('Brizy', $form_id);
        try {
            if (!$flows) {
                return $fields;
            }
            $data = [];
            $AllFields = $fields;
            foreach ($AllFields as $element) {
                if ($element->type == 'FileUpload' && !empty($element->value)) {
                    $upDir = wp_upload_dir();
                    $files = $element->value;
                    $value = [];
                    $newFileLink = Common::filePath($files);
                    $data[$element->name] = $newFileLink;
                } elseif ($element->type == 'checkbox') {
                    $value = explode(',', $element->value);
                    $data[$element->name] = $value;
                } else {
                    $data[$element->name] = $element->value;
                }
            }
            Flow::execute('Brizy', $form_id, $data, $flows);
        } catch (\Exception $e) {
            foreach ($flows as $flowData) {
                $integrationId = $flowData->id;
                $msg = $e->getMessage();

                LogHandler::save($integrationId, json_encode(['type' => 'error', 'type_name' => 'error']), 'error', json_encode($msg));
            }
        }
        return $fields;
    }

    public function getAllForms()
    {
        if (!is_plugin_active('brizy/brizy.php')) {
            wp_send_json_error(__('Brizy Pro is not installed or activated', 'bit-integrations'));
        }
        //Brizy get form list
        $posts = self::getBrizyPosts();

        $all_forms = [];
        if (!empty($posts) && is_array($posts)) {
            foreach ($posts as $post) {
                $forms = self::parseContentGetForms($post->post_content, $post->post_title);

                foreach ($forms as $form) {
                    $all_forms[] = (object)[
                        'id' => $form->uniqueId,
                        'title' => $form->title,
                        'post_id' => $post->ID,
                    ];
                }
            }
        }
        wp_send_json_success($all_forms);
    }

    public function getFormFields($data)
    {
        if (!is_plugin_active('brizy/brizy.php')) {
            wp_send_json_error(__('Brizy Pro is not installed or activated', 'bit-integrations'));
        }
        if (empty($data->id) && empty($data->postId)) {
            wp_send_json_error(__('Form doesn\'t exists', 'bit-integrations'));
        }

        $fields = self::fields($data);
        if (empty($fields)) {
            wp_send_json_error(__('Form doesn\'t exists any field', 'bit-integrations'));
        }

        $responseData['fields'] = $fields;
        $responseData['postId'] = $data->postId;
        wp_send_json_success($responseData);
    }

    public static function fields($data)
    {
        if (!isset($data->postId)) {
            return;
        }

        $postContent = get_post_field('post_content', $data->postId);
        if (empty($postContent)) {
            return;
        }

        $formFields = self::parseContentGetFormFields($postContent, $data->id);

        $fields = [];
        foreach ($formFields as $field) {
            $type = $field->field_type;
            if ($type === 'upload') {
                $type = 'file';
            }

            $fields[] = [
                'name' => $field->field_id,
                'type' => $type,
                'label' => $field->field_title,
            ];
        }
        return $fields;
    }

    public static function parseContentGetFormFields($content, $formUniqueId)
    {
        $formStart = 0;
        $formFields = [];
        $contentArray = explode('><', $content);
        foreach ($contentArray as $line) {
            $lineArray = explode(' ', $line);

            if ($lineArray[0] == 'form') {
                $regularExpressionUniqueId = '/data-form-id\s*=\s*"([^"]+)"/';
                preg_match($regularExpressionUniqueId, $line, $uniqueId);
                if ($uniqueId[1] != $formUniqueId) {
                    continue;
                }
                $formStart = 1;
            }

            if ($formStart && ($lineArray[0] == 'input' || $lineArray[0] == 'textarea' || $lineArray[0] == 'select' || $lineArray[0] == 'number' || $lineArray[0] == 'checkbox' || $lineArray[0] == 'radio' || $lineArray[0] == 'hidden' || $lineArray[0] == 'file' || $lineArray[0] == 'date' || $lineArray[0] == 'time' || $lineArray[0] == 'tel' || $lineArray[0] == 'password' || $lineArray[0] == 'url')) {
                $regularExpressionUniqueId = '/name\s*=\s*"([^"]+)"/';
                preg_match($regularExpressionUniqueId, $line, $fieldId);
                $regularExpressionUniqueId = '/type\s*=\s*"([^"]+)"/';
                preg_match($regularExpressionUniqueId, $line, $fieldType);
                $regularExpressionUniqueId = '/data-label\s*=\s*"([^"]+)"/';
                preg_match($regularExpressionUniqueId, $line, $fieldTitle);
                $formFields[] = (object)[
                    'field_id' => strtolower($fieldId[1]),
                    'field_type' => strtolower(isset($fieldType[1]) ? $fieldType[1] : 'text'),
                    'field_title' => isset($fieldTitle[1]) ? $fieldTitle[1] : $fieldId[1],
                ];
            }

            if ($lineArray[0] == '/form') {
                $formStart = 0;
            }
        }
        $uniqueArry = [];

        foreach ($formFields as $val) {
            if (!in_array($val, $uniqueArry)) {
                $uniqueArry[] = $val;
            }
        }

        return $uniqueArry;
    }

    public static function getAllFormsFromPostMeta($post)
    {
        $forms = [];
        foreach ($post as $widget) {
            foreach ($widget->elements as $elements) {
                foreach ($elements->post_content as $element) {
                    if (isset($element->widgetType) && $element->widgetType == 'form') {
                        $forms[] = $element;
                    }
                }
            }
        }
        return $forms;
    }

    private static function getBrizyPosts()
    {
        global $wpdb;

        $query = "SELECT ID, post_title, post_content FROM $wpdb->posts
        LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
        WHERE $wpdb->posts.post_status = 'publish' AND ($wpdb->posts.post_type = 'page' OR $wpdb->posts.post_type = 'post') AND $wpdb->postmeta.meta_key = 'brizy'";

        return $wpdb->get_results($query);
    }

    public static function parseContentGetForms($content, $post_title)
    {
        $forms = [];
        $number = 0;
        $contentArray = explode('><', $content);
        foreach ($contentArray as $line) {
            $lineArray = explode(' ', $line);
            if ($lineArray[0] == 'form') {
                $regularExpressionUniqueId = '/data-form-id\s*=\s*"([^"]+)"/';
                preg_match($regularExpressionUniqueId, $line, $uniqueId);

                // $regularExpressionTitle = '/title\s*=\s*"([^"]+)"/';
                // preg_match($regularExpressionTitle, $line, $title);

                $number += 1;

                if (empty($uniqueId[1])) {
                    continue;
                }
                $forms[] = (object)[
                    'uniqueId' => $uniqueId[1],
                    'title' => $post_title . '->' . $number,
                ];
            }
        }
        return $forms;
    }
}
