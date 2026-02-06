<?php

namespace FluentFormPro\Components\DynamicField;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use FluentForm\App\Helpers\Helper;
use FluentForm\App\Modules\Acl\Acl;
use FluentForm\App\Services\FormBuilder\BaseFieldManager;
use FluentForm\App\Services\FormBuilder\Components\Checkable;
use FluentForm\App\Services\FormBuilder\Components\Select;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;

class DynamicField extends BaseFieldManager
{

    /**
     * @var DynamicUser
     */
    private $user;

    /**
     * @var DynamicTerm
     */
    private $term;

    /**
     * @var DynamicPost
     */
    private $post;

    /**
     * @var FluentformSubmission
     */
    private $fluentform_submission;

    public function __construct()
    {
        parent::__construct(
            'dynamic_field',
            __('Dynamic Field', 'fluentformpro'),
            ['dynamic', 'populate', 'lookup'],
            'advanced'
        );
        $this->user = new DynamicUser();
        $this->term = new DynamicTerm();
        $this->post = new DynamicPost();
        $this->fluentform_submission = new FluentformSubmission();
        $this->registerFilters();
        $this->registerAjax();
        new DynamicCsv();
    }

    private function registerAjax()
    {
        add_action('wp_ajax_fluentform-get-dynamic-filter-value-options', [$this, 'getFilterValueOptions']);
        add_action('wp_ajax_fluentform-get-dynamic-filter-form-fields', [$this, 'getFormFields']);
        add_action('wp_ajax_fluentform-get-dynamic-filter-result', [$this, 'getResult']);
    }

    private function registerFilters()
    {
        add_filter('fluentform/editor_i18n', [
            $this, 'mergerI18n',
        ]);

        add_filter('fluentform/dynamic_field_re_fetch_result_and_resolve_value', [
            $this, 'reFetchResultAndResolveValue',
        ]);

        add_filter('fluentform/editor_init_element_' . $this->key, function ($element) {
            if (!isset($element['settings']['dynamic_default_value'])) {
                $element['settings']['dynamic_default_value'] = '';
            }
            return $element;
        });
    }

    public function mergerI18n($i18n)
    {
        return wp_parse_args(DynamicFieldHelper::getI18n(), $i18n);
    }

    public function getComponent()
    {
        $default = $this->fluentform_submission->getDefaultConfig();
        return array(
            'index'          => 19,
            'element'        => $this->key,
            'attributes'     => array(
                'name'     => $this->key,
                'type'     => 'radio',
                'multiple' => false,
                'value'    => '',
                'id'       => '',
                'class'    => ''
            ),
            'settings'       => array(
                'label'              => __('Dynamic Field', 'fluentformpro'),
                'help_message'       => '',
                'label_placement'    => '',
                'admin_field_label'  => '',
                'container_class'    => '',
                'dynamic_default_value'=> '',
                'field_type'         => 'select',
                'advanced_options'   => array(),
                'dynamic_config'     => [
                    'source'           => 'fluentform_submission',
                    'unique_result'  => 'yes',
                    'query_type'     => Arr::get($default, 'query_type'),
                    'basic_query'    => Arr::get($default, 'basic_query'),
                    'filters'        => Arr::get($default, 'filters'),
                    'sort_by'        => Arr::get($default, 'sort_by'),
                    'order_by'       => Arr::get($default, 'order_by'),
                    'template_value' => Arr::get($default, 'template_value'),
                    'template_label' => Arr::get($default, 'template_label'),
                    'result_limit'   => Arr::get($default, 'result_limit'),
                ],
                'dynamic_fetch'      => 'no',
                'enable_select_2'    => 'no',
                'randomize_options'  => 'no',
                'conditional_logics' => array(),
                'validation_rules'   => array(
                    'required' => array(
                        'value'          => false,
                        'global'         => true,
                        'message'        => Helper::getGlobalDefaultMessage('required'),
                        'global_message' => Helper::getGlobalDefaultMessage('required'),
                    ),
                ),
            ),
            'editor_options' => array(
                'title'      => __('Dynamic Field', 'fluentformpro'),
                'icon_class' => 'ff-edit-repeat',
                'template'   => 'dynamic_field',
            )
        );
    }


    public function getGeneralEditorElements()
    {
        return [
            'label',
            'admin_field_label',
            'label_placement',
            'validation_rules',
            'field_type',
            'dynamic_config',
            'dynamic_fetch',
            'enable_select_2',
            'randomize_options',
        ];
    }

    public function getAdvancedEditorElements()
    {
        return [
            'dynamic_default_value',
            'help_message',
            'container_class',
            'class',
            'name',
            'conditional_logics',
        ];
    }

    public function generalEditorElement()
    {
        return [
            'field_type'        => [
                'template'  => 'radioButton',
                'label'     => __('Field Type', 'fluentformpro'),
                'help_text' => __('Choose the type of field to be used for this dynamic field.', 'fluentformpro'),
                'options'   => [
                    [
                        'value' => 'radio',
                        'label' => __('Radio', 'fluentformpro'),
                    ],
                    [
                        'value' => 'checkbox',
                        'label' => __('Checkbox', 'fluentformpro'),
                    ],
                    [
                        'value' => 'select',
                        'label' => __('Select', 'fluentformpro'),
                    ],
                    [
                        'value' => 'multi_select',
                        'label' => __('Multi-Select', 'fluentformpro'),
                    ],
                ],
            ],
            'dynamic_config'    => [
                'template'        => 'dynamicFilter',
                'label'           => __('Populate Dynamically', 'fluentformpro'),
                'help_text'       => __('Populate values option dynamically based on the selected source. Customize behavior by specifying field types, filters, sorting, ordering, and mapping.', 'fluentformpro'),
                'sources'         => $this->getSources(),
                'columns'         => $this->getFilterColumns(),
                'numeric_columns' => DynamicFieldHelper::numericColumns(),
                'date_columns'    => DynamicFieldHelper::dateColumns(),
                'operators'       => DynamicFieldHelper::getOperators(),
                'order'           => [
                    'ASC'  => __('Ascending', 'fluentformpro'),
                    'DESC' => __('Descending', 'fluentformpro'),
                ]
            ],
            'dynamic_fetch'   => [
                'template'   => 'inputYesNoCheckBox',
                'label'      => __('Dynamic Retrieval', 'fluentformpro'),
                'help_text'  => __('When checked, result are dynamically fetched based on filters during rendering. If unchecked, the current valid value remain unchanged.', 'fluentformpro'),
            ],
            'enable_select_2'   => [
                'template'   => 'inputYesNoCheckBox',
                'label'      => __('Enable Searchable Smart Options', 'fluentformpro'),
                'help_text'  => __('If you enable this then options will be searchable by select2 js library', 'fluentformpro'),
                'dependency' => [
                    'depends_on' => 'settings/field_type',
                    'operator'   => '==',
                    'value'      => 'select',
                ],
            ],
            'randomize_options' => [
                'template'   => 'inputYesNoCheckBox',
                'label'      => __('Shuffle the available options', 'fluentformpro'),
                'help_text'  => __('If you enable this then the options will be shuffled', 'fluentformpro'),
            ],
        ];
    }

    /**
     * Get sources with their corresponding labels.
     *
     * @return array Sources with labels.
     */
    private function getSources()
    {
        $sources = [
            'post'                  => __('Post', 'fluentformpro'),
            'term'                  => __('Taxonomy Term', 'fluentformpro'),
            'user'                  => __('User', 'fluentformpro'),
            'fluentform_submission' => __('Fluent Forms Submission', 'fluentformpro'),
        ];
        return apply_filters('fluentform/dynamic_field_sources', $sources);
    }

    /**
     * Get filter value options based on the specified source.
     *
     * @return void Sends the options as JSON response.
     */
    public function getFilterValueOptions()
    {
        try {
            Acl::verify('fluentform_forms_manager');
            $source = sanitize_text_field($this->app->request->get('source'));
            if (Arr::exists($this->getSources(), $source)) {
                if (isset($this->{$source}) && $this->{$source} instanceof DynamicBase) {
                    $options = $this->{$source}->getValueOptions();
                    $defaultConfig = $this->{$source}->getDefaultConfig();
                } else {
                    $options = apply_filters('fluentform/dynamic_field_filter_value_options' . $source, []);
                    $defaultConfig = apply_filters('fluentform/dynamic_field_filter_default_config' . $source, []);
                }
                $options = apply_filters('fluentform/dynamic_field_filter_value_options', $options, $source);
                wp_send_json_success([
                    'options'        => $options,
                    'default_config' => $defaultConfig,
                ], 200);
            }
            throw new \Exception(__('Invalid Field Config', 'fluentformpro'));
        } catch (\Exception|\Error $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }


    /**
     * Get form fields.
     *
     * @return void Sends the options as JSON response.
     */
    public function getFormFields()
    {
        try {
            Acl::verify('fluentform_forms_manager');
            $formId = intval($this->app->request->get('form_id'));
            wp_send_json_success([
                'options'   => $this->fluentform_submission->getFormFields($formId),
            ], 200);
        } catch (\Exception|\Error $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Get the result based on the provided configuration.
     *
     * @return void Sends the result as JSON response.
     */
    public function getResult()
    {
        try {
            Acl::verify('fluentform_forms_manager');
            $config = Arr::get($_REQUEST, 'config', []);
            $source = Arr::get($config, 'source');
            if (!$config || !$source) {
                throw new \Exception(__('Invalid Field Config', 'fluentformpro'));
            }
            $result = $this->getFieldResult($config, $source);
            wp_send_json_success($result, 200);
        } catch (\Exception|\Error $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Retrieve the result data for a specific source based on the provided configuration.
     *
     * @param array $config The configuration to populate.
     * @param string $source The source of to populate.
     * @return array The result data for the specified source.
     * @throws \Exception If the field configuration is invalid.
     */
    private function getFieldResult($config, $source)
    {
        if (Arr::exists($this->getSources(), $source)) {
            if (isset($this->{$source}) && $this->{$source} instanceof DynamicBase) {
                $this->{$source}->setConfig($config);
                return $this->{$source}->getResult();
            } else {
                return apply_filters('fluentform/dynamic_field_filter_get_result' . $source, [], $config);
            }
        }
        throw new \Exception(__('Invalid Field Config', 'fluentformpro'));
    }

    /**
     * Get the filter columns for all sources of dynamic fields.
     *
     * @return array An associative array containing filter columns for each dynamic field source.
     */
    private function getFilterColumns()
    {
        $fields = [
            'post'                  => $this->post->getSupportedColumns(),
            'term'                  => $this->term->getSupportedColumns(),
            'user'                  => $this->user->getSupportedColumns(),
            'fluentform_submission' => $this->fluentform_submission->getSupportedColumns(),
        ];
        return apply_filters("fluentform/dynamic_field_filter_fields", $fields);
    }

    public function advancedEditorElement()
    {
        return [];
    }

    /**
     * Refetches the result and resolves the value for a dynamic field.
     *
     * @param array $field The field data.
     * @return array The modified field data.
     */
    public function reFetchResultAndResolveValue($field)
    {
        // By default, reference to the original field data
        $fieldRef = &$field;

        // Check if settings exist directly or within 'raw' key
        if (!$settings = Arr::get($field, 'settings')) {
            if (!$settings = Arr::get($field, 'raw.settings')) {
                return $field;
            }
            // If 'settings' not found directly, reference to 'raw' key
            $fieldRef = &$field['raw'];
        }

        // Check if dynamic fetching is enabled
        if ('yes' == Arr::get($settings, 'dynamic_fetch')) {
            $config = Arr::get($settings, 'dynamic_config');
            $source = Arr::get($config, 'source');
            try {
                // Retrieve valid options based on dynamic configuration
                $validOptions = Arr::get($this->getFieldResult($config, $source), 'valid_options');
                if (is_array($validOptions) && count($validOptions) > 0) {
                    $fieldRef['settings']['advanced_options'] = $validOptions;
                    if ($defaultValue = Arr::get($fieldRef, 'attributes.value')) {
                        // Remove default value if not found in valid options
                        $validOptionsValues = array_column($validOptions, 'value');
                        if (is_array($defaultValue)) {
                            foreach ($defaultValue as $index => $value) {
                                if (!in_array($value, $validOptionsValues)) {
                                    Arr::forget($defaultValue, $index);
                                }
                            }
                            $fieldRef['attributes']['value'] = $defaultValue;
                        } else {
                            if (!in_array($defaultValue, $validOptionsValues)) {
                                $fieldRef['attributes']['value'] = '';
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                //..
            }
        }
        return $field;
    }


    public function render($data, $form)
    {
        $fieldType = Arr::get($data, 'settings.field_type', 'select');
        if ('yes' == Arr::get($data, 'settings.dynamic_fetch')) {
            $data = $this->reFetchResultAndResolveValue($data);
        }
        if (in_array($fieldType, ['checkbox', 'radio'])) {
            (new Checkable)->compile($data, $form);
        } elseif (in_array($fieldType, ['select', 'multi_select'])) {
            (new Select)->compile($data, $form);
        }
    }
}
