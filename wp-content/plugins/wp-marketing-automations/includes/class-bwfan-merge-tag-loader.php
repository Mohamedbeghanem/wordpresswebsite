<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

#[AllowDynamicProperties]
class BWFAN_Merge_Tag_Loader {
	public static $included_merge_tags = array();
	/** @var array */
	protected static $data = array(
		'is_preview' => false,
	);
	/** @var array */
	private static $merge_tags_list;
	private static $instance = null;
	private static $localize_tags_with_source = [];
	private static $localize_tags = [];
	/** @var bool */
	public static $merge_tag_loaded = false;
	private static $merge_tag_group = [];

	private static $_registered_entity = array(
		'active' => array(),
	);

	private static $localize_tags_with_group = [];

	private $_form_merge_tags = [
		'contact_confirmation_link'
	];

	private $_fields_merge_tags = [
		/** Contact Columns */
		'email'         => 'contact_email',
		'f_name'        => 'contact_first_name',
		'l_name'        => 'contact_last_name',
		'contact_no'    => 'contact_phone',
		'timezone'      => 'contact_timezone',
		'creation_date' => 'contact_creation_date',
		'status'        => 'contact_marketing_status',
		'address'       => 'contact_address',
		'country'       => 'contact_country',
		'state'         => 'contact_state',

		/** Contact Fields */
		'company'       => 'contact_company',
		'dob'           => 'contact_dob',
		'gender'        => 'contact_gender',
		'address-1'     => 'contact_address_1',
		'address-2'     => 'contact_address_2',
		'city'          => 'contact_city',
		'postcode'      => 'contact_postcode',
	];

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'is_automation_ajax_call' ) );
	}

	/**
	 * Check if the current request is an automation ajax call
	 */
	public function is_automation_ajax_call() {
		if ( ! wp_doing_ajax() || empty( $_REQUEST['action'] ) || strpos( $_REQUEST['action'], 'bwfan_get_automation_' ) === false ) {
			return;
		}

		$this->include_merge_tag_files();
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_Merge_Tag_Loader|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function include_merge_tag_files() {
		$this->load_merge_tags();
		$this->register_classes();
	}

	/**
	 * Function to get all the data set
	 *
	 * @param string $key
	 *
	 * @return array|mixed
	 */
	public static function get_data( $key = '' ) {
		if ( empty( $key ) ) {
			return self::$data;
		}
		if ( isset( self::$data[ $key ] ) ) {
			return self::$data[ $key ];
		}

		return array();
	}

	/**
	 * Set merge tag data
	 *
	 * @param $data
	 */
	public static function set_data( $data ) {
		if ( ! is_array( $data ) ) {
			return;
		}
		if ( ! self::$merge_tag_loaded ) {
			self::$instance->include_merge_tag_files();
		}
		foreach ( $data as $key => $value ) {
			self::$data[ $key ] = $value;
		}
	}

	/**
	 * Reset the data
	 */
	public static function reset_data() {
		self::$data = array(
			'is_preview' => false,
		);
	}

	/**
	 * Include all the Merge Tags's files
	 */
	public static function load_merge_tags() {

		if ( self::$merge_tag_loaded ) {
			return;
		}

		$integration_dir = __DIR__ . '/merge_tags';
		foreach ( glob( $integration_dir . '/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			require_once( $_field_filename );
		}
		do_action( 'bwfan_merge_tags_loaded' );
		self::$merge_tag_loaded = true;

	}

	/**
	 * Register the merge tag when the merge tag file is included
	 *
	 * @param $shortName
	 * @param $class
	 * @param $overrides
	 * @param $group_label
	 *
	 * @return void
	 */
	public static function register( $shortName, $class, $overrides = null, $group_label = '' ) {
		if ( ! class_exists( $class ) || ! method_exists( $class, 'get_instance' ) ) {
			return;
		}

		/**
		 * @var $instance BWFAN_Merge_Tag
		 */
		$instance                                               = $class::get_instance();
		$slug                                                   = $instance->get_slug();
		self::$localize_tags_with_source[ $shortName ][ $slug ] = $instance->get_localize_data();
		self::$localize_tags[ $slug ]                           = self::$localize_tags_with_source[ $shortName ][ $slug ];
		self::$_registered_entity[ $shortName ][ $slug ]        = $instance;
		if ( ! empty( $group_label ) && ! isset( self::$merge_tag_group[ $shortName ] ) ) {
			self::$merge_tag_group[ $shortName ] = $group_label;
		}

		if ( true === $instance->is_crm_broadcast() ) {
			$group_label                                             = ! empty( $group_label ) ? $group_label : 'General';
			self::$localize_tags_with_group[ $group_label ][ $slug ] = $instance;
		}
	}

	/**
	 * Return all the actions with group and their integrations
	 *
	 * @return array
	 */
	public static function get_all_merge_tags() {
		if ( ! self::$merge_tag_loaded ) {
			self::$instance->include_merge_tag_files();
		}

		return self::$included_merge_tags;
	}

	/**
	 * Registers every integration as a system integration
	 */
	public function register_classes() {
		$load_classes = self::get_registered_merge_tags();

		if ( is_array( $load_classes ) && count( $load_classes ) > 0 ) {
			self::$included_merge_tags = $load_classes;
		}
	}

	/**
	 * Return the registered integrations
	 *$instance->get_localize_data()
	 * @return mixed
	 */
	public static function get_registered_merge_tags() {
		if ( ! self::$merge_tag_loaded ) {
			self::$instance->include_merge_tag_files();
		}

		return self::$_registered_entity;
	}

	/**
	 * Return associated merge tag localize data(Parent child based)
	 * @return array
	 */
	public function get_localize_tags_with_source() {
		if ( ! self::$merge_tag_loaded ) {
			self::$instance->include_merge_tag_files();
		}

		return self::$localize_tags_with_source;
	}

	/**
	 * Return merge tag localize data
	 * @return array
	 */
	public function get_localize_tags() {
		return self::$localize_tags_with_source;
	}

	/**
	 * Returns merge tag group array
	 *
	 * @return array
	 */
	public function get_merge_tag_groups() {
		return self::$merge_tag_group;
	}


	/**
	 * Get crm broadcast merge tags
	 *
	 * @param $form_tags
	 *
	 * @return array
	 */
	public function get_localize_tags_with_group( $form_tags = false, $extra_group = [] ) {
		if ( ! self::$merge_tag_loaded ) {
			self::$instance->include_merge_tag_files();
		}
		$group_localize_tags = self::$localize_tags_with_group;

		foreach ( $group_localize_tags as $key => $value ) {
			foreach ( $value as $k => $v ) {
				if ( false === $form_tags && in_array( $k, $this->_form_merge_tags ) ) {
					unset( $group_localize_tags[ $key ][ $k ] );
				}
			}
		}

		if ( empty( $extra_group ) ) {
			return $group_localize_tags;
		}

		foreach ( $extra_group as $group_key ) {
			if ( ! isset( self::$included_merge_tags[ $group_key ] ) ) {
				continue;
			}
			$group_localize_tags[ self::$merge_tag_group[ $group_key ] ] = self::$included_merge_tags[ $group_key ];
		}

		return $group_localize_tags;
	}

	public function get_field_tag( $field_slug ) {
		if ( empty( $field_slug ) ) {
			return '';
		}

		if ( isset( $this->_fields_merge_tags[ $field_slug ] ) ) {
			return "{{{$this->_fields_merge_tags[ $field_slug ]}}}";
		}

		return "{{contact_field key=\"{$field_slug}\"}}";
	}

}

if ( class_exists( 'BWFAN_Merge_Tag_Loader' ) ) {
	BWFAN_Core::register( 'merge_tags', 'BWFAN_Merge_Tag_Loader' );

}

