<?php

class BWFAN_Mail_Footer extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'mail_footer';
		$this->tag_description = __( 'Mail Footer', 'wp-marketing-automations' );
		add_shortcode( 'bwfan_mail_footer', array( $this, 'parse_shortcode' ) );
		$this->support_fallback = false;
		$this->is_crm_broadcast = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Parse the merge tag and return its value.
	 *
	 * @param $attr
	 *
	 * @return mixed|string|void
	 */
	public function parse_shortcode( $attr ) {
		$global_settings = BWFAN_Common::get_global_settings();
		if ( ! isset( $global_settings['bwfan_email_footer_setting'] ) ) {
			return '';
		}

		$footer = BWFAN_Common::decode_merge_tags( $global_settings['bwfan_email_footer_setting'] );

		return $this->parse_shortcode_output( $footer, $attr );
	}

}

/**
 * Register this merge tag to a group.
 */
BWFAN_Merge_Tag_Loader::register( 'bwfan_default', 'BWFAN_Mail_Footer', null, __( 'General', 'wp-marketing-automations' ) );
