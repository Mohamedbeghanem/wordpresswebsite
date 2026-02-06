<?php
/**
 * Conversation Controller Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFAN_Conversation
 */
class BWFAN_Conversation {
	private static $ins = null;

	/** @var BWFCRM_Contact $contact */
	public $contact = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $do_shorten_links = false;

	public $broadcast_id = 0;
	public $conversation_id = 0;
	public $template = null;
	public $template_id = 0;
	public $engagement_type = null;

	/**
	 * @param $content
	 *
	 * @return string|null
	 */
	private function prepare_email_content( $content ) {
		$has_body = stripos( $content, '<body' ) !== false;

		/** Check if body tag exists */
		if ( ! $has_body ) {
			return '<html><head></head><body><div id="body_content" >' . $content . '</div></body></html>';
		}

		$pattern     = "/<body(.*?)>(.*?)<\/body>/is";
		$replacement = '<body$1><div id="body_content" >$2</div></body>';

		return preg_replace( $pattern, $replacement, $content );
	}

	public function emogrify_email( $data ) {
		$data = $this->prepare_email_content( $data );
		ob_start();
		include BWFAN_PLUGIN_DIR . '/templates/email-styles.php';
		$css = ob_get_clean();

		if ( BWFAN_Common::supports_emogrifier() ) {
			$emogrifier_class_bwf = '\\BWF_Pelago\\Emogrifier';
			$emogrifier_class     = '\\Pelago\\Emogrifier';
			if ( ! class_exists( $emogrifier_class_bwf ) && ! class_exists( $emogrifier_class ) ) {
				include_once BWFAN_PLUGIN_DIR . '/libraries/class-emogrifier.php';
			}

			if ( class_exists( $emogrifier_class_bwf ) ) {
				$emogrifier_class = $emogrifier_class_bwf;
			}

			try {
				/** @var \BWF_Pelago\Emogrifier $emogrifier */
				$emogrifier = new $emogrifier_class( $data, $css );
				$data       = $emogrifier->emogrify();
			} catch ( Exception $e ) {
				BWFAN_Core()->logger->log( $e->getMessage(), 'send_email_emogrifier' );
			}
		} else {
			$data = '<style type="text/css">' . $css . '</style>' . $data;
		}

		return $data;
	}

	public function emogrify_email_html( $email_body ) {
		$data = $this->prepare_email_content( $email_body );
		ob_start();
		do_action( 'bwfan_output_email_style' ); // for registering the css on html type
		$css = ob_get_clean();

		if ( BWFAN_Common::supports_emogrifier() ) {
			$emogrifier_class_bwf = '\\BWF_Pelago\\Emogrifier';
			$emogrifier_class     = '\\Pelago\\Emogrifier';
			if ( ! class_exists( $emogrifier_class_bwf ) && ! class_exists( $emogrifier_class ) ) {
				include_once BWFAN_PLUGIN_DIR . '/libraries/class-emogrifier.php';
			}

			if ( class_exists( $emogrifier_class_bwf ) ) {
				$emogrifier_class = $emogrifier_class_bwf;
			}

			try {
				/** @var \BWF_Pelago\Emogrifier $emogrifier */
				$emogrifier = new $emogrifier_class( $data, $css );
				$data       = $emogrifier->emogrify();
			} catch ( Exception $e ) {
				BWFAN_Core()->logger->log( $e->getMessage(), 'send_email_emogrifier' );
			}
		} else {
			$data = '<style type="text/css">' . $css . '</style>' . $data;
		}

		return $data;
	}

	public function woocommercify_email( $email_body, $email_heading ) {
		$mailer = WC()->mailer();

		ob_start();

		if ( version_compare( WC()->version, 4.8, '>' ) ) {
			$mailer->email_header( $email_heading, '' );
		} else {
			$mailer->email_header( $email_heading );
		}

		echo $email_body; //phpcs:ignore WordPress.Security.EscapeOutput
		if ( version_compare( WC()->version, 4.8, '>' ) ) {
			$mailer->email_footer( $email_heading );
		} else {
			$mailer->email_footer();
		}
		$email_body            = ob_get_clean();
		$email_abstract_object = new WC_Email();

		return apply_filters( 'woocommerce_mail_content', $email_abstract_object->style_inline( wptexturize( $email_body ) ) );
	}

	public function apply_template_by_type( $email_body, $type = 'rich', $heading = '' ) {
		switch ( $type ) {
			case 'rich':
				return $this->emogrify_email( $email_body );
			case 'wc':
				if ( class_exists( 'WooCommerce' ) ) {
					return $this->woocommercify_email( $email_body, $heading );
				}
			case 'html':
				return $this->emogrify_email_html( $email_body );
			case 'editor':
				return $this->emogrify_email_editor( $email_body );
			case 'block':
				return $this->block_email_editor( $email_body );
		}

		return $email_body;
	}

	/**
	 * Append Tracking Pixel, PreHeader to email body
	 *
	 * @param $body
	 * @param $pre_header
	 * @param $pixel_id
	 *
	 * @return mixed
	 */
	public function append_to_email_body( $body, $pre_header, $pixel_id ) {
		$pre_header = ! empty( $pre_header ) ? str_replace( "$", "\\$", $pre_header ) : '';
		$pre_header = ! empty( $pre_header ) ? '<span style="display:none;font-size:1px;color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">' . $pre_header . '</span>' : '';
		$pixel      = ! empty( $pixel_id ) ? $this->get_email_pixel_html( $pixel_id ) : '';

		/** it will add the space after the pre-header to not show the email body content */
		if ( ! empty( $pre_header ) ) {
			$pre_header .= '<div style="display: none; max-height: 0; overflow: hidden;">' . str_repeat( '&#847;&zwnj;&nbsp;', apply_filters( 'bwfan_email_pre_header_space', 100 ) ) . '</div>'; // Adding 70 instances to create enough hidden space
		}

		if ( strpos( $body, '</body>' ) ) {
			$pattern       = '/<body(.*?)>(.*?)<\/body>/is';
			$replacement   = '<body$1>' . $pre_header . $pixel . '$2</body>';
			$appended_body = preg_replace( $pattern, $replacement, $body );
		} else {
			$appended_body = $body . $pixel;
		}

		return $appended_body;
	}

	public function get_email_pixel_html( $pixel_id ) {
		$pixel_url = add_query_arg( array(
			'bwfan-track-action' => 'open',
			'bwfan-track-id'     => $pixel_id,
		), home_url() );

		return '<img src="' . $pixel_url . '" height="1" width="1" alt="" style="height:0;display:inherit"/>';
	}

	public function append_to_email_body_links( $body, $utm_data, $track_id, $uid, $mode = 'email' ) {
		$url_args = self::transform_utm_data( $utm_data );

		/** SMS & WhatsApp */
		if ( 'email' !== $mode ) {

			$global_settings        = BWFAN_Common::get_global_settings();
			$disable_click_tracking = isset( $global_settings['bwfan_disable_sms_tracking'] ) && ! empty( $global_settings['bwfan_disable_sms_tracking'] ) ? $global_settings['bwfan_disable_sms_tracking'] : 0;

			/** return body without adding tracking detail in url if disabled and mode not email */
			if ( $disable_click_tracking || true === apply_filters( 'bwfan_skip_broadcast_tracking_url', false, $body, $mode, $track_id ) ) {
				return $body;
			}

			$link_regex = BWFAN_Common::get_regex_pattern( 3 );

			return preg_replace_callback( $link_regex, function ( $matches ) use ( $url_args, $track_id, $uid ) {
				$this->do_shorten_links = true;

				return $this->modify_message_body_urls( $matches[0], $url_args, $track_id, $uid );
			}, $body );

		}

		/** Email */
		$href_regex = BWFAN_Common::get_regex_pattern();

		return preg_replace_callback( $href_regex, function ( $matches ) use ( $url_args, $track_id, $uid ) {
			$url = $this->modify_message_body_urls( $matches[1], $url_args, $track_id, $uid );

			return str_replace( $matches[1], $url, $matches[0] );
		}, $body );
	}

	/**
	 * Transform UTM data to new format
	 * utm prefixes was missing in the old format
	 *
	 * @param $utm_data
	 *
	 * @return array
	 */
	public static function transform_utm_data( $utm_data ) {
		if ( empty( $utm_data ) || ! is_array( $utm_data ) ) {
			return array();
		}

		$utm_data = array(
			'utm_source'   => $utm_data['utm_source'] ?? $utm_data['source'] ?? '',
			'utm_medium'   => $utm_data['utm_medium'] ?? $utm_data['medium'] ?? '',
			'utm_campaign' => $utm_data['utm_campaign'] ?? $utm_data['name'] ?? '',
			'utm_term'     => $utm_data['utm_term'] ?? $utm_data['term'] ?? '',
			'utm_content'  => $utm_data['utm_content'] ?? $utm_data['content'] ?? '',
		);

		return array_filter( $utm_data, function ( $value ) {
			return ! empty( trim( $value ) );
		} );
	}

	/**
	 * append track id, uid in url
	 *
	 * @param $string
	 * @param $utm_data
	 * @param $track_id
	 * @param $uid
	 *
	 * @return array|string|string[]|null
	 */
	public function modify_message_body_urls( $string, $utm_data, $track_id, $uid ) {
		if ( empty( $string ) ) {
			return $string;
		}

		/** Check url needs to exclude from click track */
		if ( BWFAN_Common::is_exclude_url( $string ) ) {
			return $string;
		}

		$url_args = ! is_array( $utm_data ) ? array() : $utm_data;
		$url_args = apply_filters( 'bwfan_modify_url_args', $url_args, $this->contact );

		/** Exclude click tracking for unsubscribe link */
		if ( false === strpos( $string, 'bwfan-action=unsubscribe' ) && false === strpos( $string, 'bwfan-action=view_in_browser' ) ) {
			$l_hash = $this->get_link_hash( $string );
			$string = $this->convert_link_to_track_link( $string, $track_id, $uid, $l_hash );
		}

		//* Add UTM data to the URL */
		$url = add_query_arg( $url_args, $string );

		/** Add track id if view email browser link */
		if ( false !== strpos( $url, 'bwfan-action=view_in_browser' ) ) {
			$url = add_query_arg( array(
				'bwfan-ehash' => $track_id
			), $url );
		}
		$url = ! empty( $url ) ? $url : $string;

		if ( false === $this->do_shorten_links ) {
			return $url;
		}

		/**
		 * Checking any shortener service provider is available or not
		 * if not then return the same url.
		 */
		if ( method_exists( 'BWFAN_Common', 'get_default_shortener_provider' ) && empty( BWFAN_Common::get_default_shortener_provider() ) ) {
			return $url;
		}

		/** Reset the shorten links flag */
		$this->do_shorten_links = false;

		/** Do Shorten via the selected Shortening Service */
		if ( class_exists( 'BWFAN_Connectors_Common' ) && method_exists( BWFAN_Connectors_Common::class, 'get_shorten_url' ) ) {
			$shorten_url = BWFAN_Connectors_Common::get_shorten_url( $url );
			/**If url not shorten then hold broadcast for 5 minutes */
			$this->hold_broadcast_for_shorten_url( $shorten_url, $url );

			return $shorten_url;
		}

		/** If no service available try to use default method (Bitly) */
		return do_shortcode( '[bwfan_bitly_shorten]' . $url . '[/bwfan_bitly_shorten]' );
	}

	public function hold_broadcast_for_shorten_url( $shorten_url, $url ) {
		if ( $shorten_url !== $url ) {
			return;
		}

		BWFAN_Model_Broadcast::set_delay( $this->broadcast_id, 2 );

		/** Delete current engagement */
		BWFAN_Model_Engagement_Tracking::delete( $this->conversation_id );

		if ( ! class_exists( 'BWFCRM_Broadcast_Processing' ) ) {
			return;
		}
		$broadcast_processing                                 = BWFCRM_Broadcast_Processing::get_instance();
		$broadcast_processing->stop_broadcast_for_shorten_url = true;
		$broadcast_processing->stop_broadcast_required        = true;
	}

	public function convert_link_to_track_link( $url, $track_id, $uid, $l_hash = '' ) {
		$args = [
			'bwfan-uid'      => $uid,
			'bwfan-track-id' => $track_id,
		];

		if ( false === apply_filters( 'bwfan_is_link_trigger_url', false, $url ) ) {
			$args['bwfan-track-action'] = 'click';
			if ( ! empty( $l_hash ) ) {
				$args['l_hash'] = $l_hash;
			}
			$args['bwfan-link'] = urlencode( html_entity_decode( $url ) );
			$url                = home_url();
		} else if ( bwfan_is_autonami_pro_active() && ! is_null( $this->contact ) ) {
			/** Add the Auth Hash */
			$auth_hash = BWFCRM_Core()->link_trigger_handler->get_auth_hash( $this->contact, $url );
			if ( false !== $auth_hash ) {
				$args['bwfan-auth'] = $auth_hash;
			}
		}

		return add_query_arg( $args, $url );
	}

	public function get_or_create_template( $type, $subject, $body, $return = 'id', $data = array() ) {
		$template_id = BWFAN_Email_Conversations::check_already_exists_template( $subject, $body, $type, $data );
		if ( 'id' === $return ) {
			return $template_id;
		}

		return BWFAN_Model_Templates::get( absint( $template_id ) );
	}

	/**
	 * @param BWFCRM_Contact $contact
	 * @param int $campaign_id
	 * @param int $template_id
	 * @param int $author_id
	 * @param int $conversation_mode
	 * @param false $is_single
	 * @param array $template
	 * @param int $single_type
	 * @param int $status conversation status. Default BWFAN_Email_Conversations::$STATUS_DRAFT
	 *
	 * @return array|WP_Error
	 */
	public function create_campaign_conversation( $contact, $campaign_id, $template_id, $author_id, $conversation_mode = 1, $is_single = false, $template = array(), $single_type = 0, $status = 1 ) {
		$contact = ( $contact instanceof BWFCRM_Contact ) ? $contact : new BWFCRM_Contact( $contact );
		if ( ! $contact->is_contact_exists() ) {
			return BWFAN_Common::crm_error( __( 'Unable to find contact', 'wp-marketing-automations' ) );
		}

		$to          = BWFAN_Email_Conversations::$MODE_SMS === intval( $conversation_mode ) ? $contact->contact->get_contact_no() : $contact->contact->get_email();
		$hash_code   = md5( microtime( true ) . $to );
		$type        = ( true === $is_single ? ( BWFAN_Email_Conversations::$MODE_SMS === absint( $conversation_mode ) ? BWFAN_Email_Conversations::$TYPE_SMS : BWFAN_Email_Conversations::$TYPE_EMAIL ) : BWFAN_Email_Conversations::$TYPE_CAMPAIGN );
		$type        = ( true === $is_single && ! empty( $single_type ) ? $single_type : $type );
		$create_time = current_time( 'mysql', 1 );
		$insert_data = array(
			'cid'           => $contact->get_id(),
			'hash_code'     => $hash_code,
			'created_at'    => $create_time,
			'updated_at'    => $create_time,
			'mode'          => $conversation_mode,
			'send_to'       => $to,
			'type'          => $type,
			'open'          => 0,
			'click'         => 0,
			'oid'           => empty( $campaign_id ) ? 0 : absint( $campaign_id ),
			'author_id'     => absint( $author_id ),
			'tid'           => $template_id,
			'o_interaction' => '',
			'c_interaction' => '',
			'c_status'      => $status,
		);

		BWFAN_Model_Engagement_Tracking::insert_ignore( $insert_data, [
			'%d',// cid
			'%s',// hash_code
			'%s',// created_at
			'%s',// updated_at
			'%d',// mode
			'%s',// send_to
			'%d',// type
			'%d',// open
			'%d',// click
			'%d',// oid
			'%d',// author_id
			'%d',// tid
			'%s',// o_interaction
			'%s',// c_interaction
			'%d',// c_status
		] );
		$conversation_id = BWFAN_Model_Engagement_Tracking::insert_id();

		if ( empty( $template ) && ! empty( $template_id ) ) {
			$template = BWFAN_Model_Templates::get( absint( $template_id ) );
		}

		if ( false === $is_single && ( empty( $template ) || ! is_array( $template ) || ! isset( $template['template'] ) || empty( $template['template'] ) ) ) {
			/* translators: 1: Template ID */
			return BWFAN_Common::crm_error( sprintf( __( 'Unable to find campaign\'s template of ID: %1$d', 'wp-marketing-automations' ), $template_id ), array( 'conversation_id' => $conversation_id ) );
		}

		$subject       = ( false === $is_single && BWFAN_Email_Conversations::$MODE_EMAIL === absint( $conversation_mode ) ) ? $template['subject'] : '';
		$template_meta = isset( $template['data'] ) && ! empty( $template['data'] ) ? (array) json_decode( $template['data'] ) : [];
		$template_type = isset( $template['type'] ) ? $template['type'] : '';

		$template   = ( false === $is_single ) ? $template['template'] : '';
		$template   = BWFAN_Common::correct_shortcode_string( $template, $template_type );
		$merge_tags = BWFAN_Core()->conversations->get_email_merge_tags( $template, $template_type );
		if ( ! empty( $subject ) ) {
			$subject_merge_tags = BWFAN_Core()->conversations->get_email_merge_tags( $subject, $template_type );
			$merge_tags         = array_replace( $merge_tags, $subject_merge_tags );
		}

		if ( ! empty( $merge_tags ) && is_array( $merge_tags ) ) {
			BWFAN_Model_Engagement_Trackingmeta::insert( array(
				'eid'        => absint( $conversation_id ),
				'meta_key'   => 'merge_tags',
				'meta_value' => wp_json_encode( $merge_tags ),
			) );
		}

		return array(
			'conversation_id' => absint( $conversation_id ),
			'template'        => $template,
			'template_data'   => $template_meta,
			'subject'         => $subject,
			'merge_tags'      => $merge_tags,
			'hash_code'       => $hash_code,
		);
	}

	public function prepare_email_body( $conversation_id, $contact_id, $hash_code = '', $template_type = 'rich', $template = '', $pre_header = '', $utm_data = array(), $broadcast_id = 0 ) {
		if ( empty( $conversation_id ) ) {
			return BWFAN_Common::crm_error( __( 'Conversation not found', 'wp-marketing-automations' ) );
		}

		if ( empty( $hash_code ) ) {
			$con       = BWFAN_Model_Templates::get( absint( $conversation_id ) );
			$hash_code = $con['hash_code'];
		}

		if ( empty( $hash_code ) ) {
			/* translators: 1: Conversation ID */
			return BWFAN_Common::crm_error( sprintf( __( 'No tracking enabled on this conversation: %1$d', 'wp-marketing-automations' ), $conversation_id ) );
		}

		if ( empty( $template ) ) {
			$conversation = BWFAN_Model_Engagement_Tracking::get( $conversation_id );
			if ( empty( $conversation ) || ! isset( $conversation['tid'] ) ) {
				return BWFAN_Common::crm_error( __( 'Conversation not found', 'wp-marketing-automations' ) );
			}

			$template_id = absint( $conversation['tid'] );
			if ( empty( $template_id ) ) {
				return BWFAN_Common::crm_error( __( 'Template not found', 'wp-marketing-automations' ) );
			}

			$template       = BWFAN_Model_Templates::get( absint( $template_id ) );
			$this->template = $template;
			$template       = $template['template'];
		}
		$template = BWFAN_Common::correct_shortcode_string( $template, $template_type );
		$uid      = '';
		if ( ! empty( $contact_id ) ) {
			if ( ! empty( $broadcast_id ) ) {
				BWFAN_Merge_Tag_Loader::set_data( array(
					'broadcast_id' => absint( $broadcast_id ),
				) );
			}
			BWFAN_Merge_Tag_Loader::set_data( array(
				'contact_id'   => $contact_id,
				'contact_mode' => 1
			) );
			$template = BWFAN_Common::decode_merge_tags( html_entity_decode( $template ) );
			// $template = $this->fit_merge_tags_into_template( $template, $merge_tags );

			$contact = new BWFCRM_Contact( $contact_id );
			if ( $contact->is_contact_exists() ) {
				$uid           = $contact->contact->get_uid();
				$this->contact = $contact;
			}
		}
		$this->broadcast_id = $broadcast_id;
		$template           = BWFAN_Common::bwfan_correct_protocol_url( $template );
		$pre_header         = BWFAN_Common::decode_merge_tags( $pre_header );
		$template           = $this->apply_template_by_type( $template, $template_type, $pre_header );
		$template           = $this->append_to_email_body_links( $template, $utm_data, $hash_code, $uid );

		return $this->append_to_email_body( $template, $pre_header, $hash_code );
	}

	public function prepare_email_subject( $subject, $contact_id ) {
		BWFAN_Merge_Tag_Loader::set_data( array(
			'contact_id'   => $contact_id,
			'contact_mode' => 1
		) );

		return BWFAN_Common::decode_merge_tags( $subject );
	}

	public function prepare_sms_body( $conversation_id, $contact_id, $hash_code = '', $template = '', $utm_data = array(), $broadcast_id = 0 ) {
		if ( empty( $conversation_id ) ) {
			return BWFAN_Common::crm_error( __( 'Conversation not found', 'wp-marketing-automations' ) );
		}

		$this->conversation_id = $conversation_id;
		$this->broadcast_id    = $broadcast_id;

		if ( empty( $hash_code ) ) {
			$con       = BWFAN_Model_Templates::get( absint( $conversation_id ) );
			$hash_code = $con['hash_code'];
		}

		if ( empty( $hash_code ) ) {
			/* translators: 1: Conversation ID */
			return BWFAN_Common::crm_error( sprintf( __( 'No tracking enabled on this conversation: %1$d', 'wp-marketing-automations' ), $conversation_id ) );
		}

		if ( empty( $template ) ) {
			$template_id = BWFAN_Model_Engagement_Trackingmeta::get_meta( $conversation_id, 'template_id' );
			if ( empty( $template_id ) ) {
				return BWFAN_Common::crm_error( __( 'Template not found', 'wp-marketing-automations' ) );
			}

			$template = BWFAN_Model_Templates::get( intval( $template_id ) );
			$template = $template['template'];
		}

		$uid = '';
		if ( ! empty( $contact_id ) ) {
			if ( ! empty( $broadcast_id ) ) {
				BWFAN_Merge_Tag_Loader::set_data( array(
					'broadcast_id' => intval( $broadcast_id ),
				) );
			}
			BWFAN_Merge_Tag_Loader::set_data( array(
				'contact_id'   => intval( $contact_id ),
				'contact_mode' => 2
			) );
			$template = BWFAN_Common::decode_merge_tags( $template );
			// $template = $this->fit_merge_tags_into_template( $template, $merge_tags );

			$contact = new BWFCRM_Contact( $contact_id );
			if ( $contact->is_contact_exists() ) {
				$uid           = $contact->contact->get_uid();
				$this->contact = $contact;
			}
		}

		return $this->append_to_email_body_links( $template, $utm_data, $hash_code, $uid, 'sms' );
	}

	public function fit_merge_tags_into_template( $template, $merge_tags ) {
		foreach ( $merge_tags as $merge_tag => $value ) {
			$template = str_replace( $merge_tag, $value, $template );
		}

		return $template;
	}

	public function fail_the_conversation( $conversation_id, $error_message ) {
		if ( empty( $conversation_id ) ) {
			return false;
		}

		BWFAN_Model_Engagement_Tracking::update( array(
			'updated_at' => current_time( 'mysql', 1 ),
			'c_status'   => BWFAN_Email_Conversations::$STATUS_ERROR,
		), array(
			'ID' => absint( $conversation_id ),
		) );

		if ( ! empty( $error_message ) ) {
			BWFAN_Model_Engagement_Trackingmeta::insert( array(
				'meta_key'   => 'error_msg',
				'meta_value' => $error_message,
				'eid'        => absint( $conversation_id ),
			) );
		}

		return true;
	}

	public function update_conversation_status( $conversation_id, $status = 1 ) {
		if ( empty( $conversation_id ) ) {
			return false;
		}

		BWFAN_Model_Engagement_Tracking::update( array(
			'updated_at' => current_time( 'mysql', 1 ),
			'c_status'   => $status,
		), array(
			'ID' => absint( $conversation_id ),
		) );

		return true;
	}

	public function prepare_notification_data( $conversation_id, $contact_id, $hash_code = '', $template = '', $utm_data = array(), $broadcast_id = 0 ) {
		if ( empty( $conversation_id ) ) {
			return BWFAN_Common::crm_error( __( 'Conversation not found', 'wp-marketing-automations' ) );
		}

		$this->conversation_id = $conversation_id;
		if ( empty( $hash_code ) ) {
			$con       = BWFAN_Model_Templates::get( absint( $conversation_id ) );
			$hash_code = $con['hash_code'];
		}

		if ( empty( $hash_code ) ) {
			/* translators: 1: Conversation ID */
			return BWFAN_Common::crm_error( sprintf( __( 'No tracking enabled on this conversation: %1$d', 'wp-marketing-automations' ), $conversation_id ) );
		}

		if ( empty( $template ) ) {
			$template_id = BWFAN_Model_Engagement_Trackingmeta::get_meta( $conversation_id, 'template_id' );
			if ( empty( $template_id ) ) {
				return BWFAN_Common::crm_error( __( 'Template not found', 'wp-marketing-automations' ) );
			}

			$template = BWFAN_Model_Templates::get( intval( $template_id ) );
			$template = $template['template'];
		}

		BWFAN_Merge_Tag_Loader::set_data( array(
			'contact_id'   => intval( $contact_id ),
			'contact_mode' => 2
		) );
		$this->broadcast_id = $broadcast_id;
		$template           = BWFAN_Common::decode_merge_tags( $template );
		$uid                = '';
		if ( ! empty( $contact_id ) ) {
			if ( ! empty( $broadcast_id ) ) {
				BWFAN_Merge_Tag_Loader::set_data( array(
					'broadcast_id' => intval( $broadcast_id ),
				) );
			}

			$contact = new BWFCRM_Contact( $contact_id );
			if ( $contact->is_contact_exists() ) {
				$uid           = $contact->contact->get_uid();
				$this->contact = $contact;
			}
		}

		return $this->append_to_email_body_links( $template, $utm_data, $hash_code, $uid, 'push-notification' );
	}

	public function is_twilio_connected() {
		if ( ! class_exists( 'WFCO_Autonami_Connectors_Core' ) || ! class_exists( 'WFCO_Load_Connectors' ) ) {
			return false;
		}

		global $wpdb;
		$twilio_connector = $wpdb->get_results( 'SELECT * from ' . $wpdb->prefix . 'wfco_connectors where slug = \'bwfco_twilio\'' ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return ! empty( $twilio_connector ) ? 1 : 0;
	}

	public function get_twilio_settings() {
		if ( ! class_exists( 'WFCO_Autonami_Connectors_Core' ) || ! class_exists( 'WFCO_Load_Connectors' ) ) {
			return false;
		}

		global $wpdb;
		$twilio_connector = $wpdb->get_results( 'SELECT cm.* from ' . $wpdb->prefix . 'wfco_connectors as c JOIN ' . $wpdb->prefix . 'wfco_connectormeta as cm WHERE c.ID = cm.connector_id AND c.slug = \'bwfco_twilio\'', ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $twilio_connector ) ) {
			return false;
		}

		$settings = array();
		foreach ( $twilio_connector as $item ) {
			$settings[ $item['meta_key'] ] = $item['meta_value'];
		}

		return $settings;
	}

	public function get_conversation_template_array( $template_id ) {
		if ( ! class_exists( 'BWFAN_Model_Templates' ) || empty( $template_id ) ) {
			return array();
		}

		$template = BWFAN_Model_Templates::get( absint( $template_id ) );
		if ( empty( $template ) || ! isset( $template['ID'] ) ) {
			return array();
		}

		return array(
			'id'      => absint( $template['ID'] ),
			'subject' => $template['subject'],
			'body'    => $template['template'],
		);
	}

	public function send_test_email( $args ) {
		if ( empty( $args ) || ! is_email( $args['email'] ) || empty( $args['body'] ) ) {
			return false;
		}
		$contact = new BWFCRM_Contact( $args['email'] );

		if ( $contact->is_contact_exists() ) {
			BWFAN_Merge_Tag_Loader::set_data( array(
				'contact_id' => intval( $contact->get_id() ),
			) );
		}

		/** Email Subject */
		BWFAN_Merge_Tag_Loader::set_data( array( 'is_preview' => true ) );
		$subject = BWFAN_Common::decode_merge_tags( $args['subject'] );

		/** Email Body */
		$body = BWFAN_Common::decode_merge_tags( $args['body'] );
		$body = BWFAN_Common::bwfan_correct_protocol_url( $body );
		$body = BWFAN_Core()->conversation->apply_template_by_type( $body, $args['type'], $subject );

		/** Email Preheader */
		$preheader = isset( $args['preheader'] ) ? $args['preheader'] : '';
		$body      = ! empty( $preheader ) ? BWFAN_Core()->conversation->append_to_email_body( $body, $preheader, '' ) : $body;

		/** Append UTM parameters */
		$body = class_exists( 'BWFAN_PRO_Common' ) ? BWFAN_PRO_Common::add_test_broadcast_utm_params( $body, $args ) : $body;

		$global_email_settings = BWFAN_Common::get_global_settings();

		/** Email Headers */
		$reply_to_email = isset( $args['reply_to'] ) ? $args['reply_to'] : $global_email_settings['bwfan_email_reply_to'];
		$from_email     = isset( $args['senders_email'] ) ? $args['senders_email'] : $global_email_settings['bwfan_email_from'];
		$from_name      = isset( $args['senders_name'] ) ? $args['senders_name'] : $global_email_settings['bwfan_email_from_name'];

		/** Setup Headers */
		$header   = array();
		$header[] = 'MIME-Version: 1.0';
		if ( ! empty( $from_email ) && ! empty( $from_name ) ) {
			$header[] = 'From: ' . $from_name . ' <' . $from_email . '>';
		}
		if ( ! empty( $reply_to_email ) ) {
			$header[] = 'Reply-To:  ' . $reply_to_email;
		}

		/** Set unsubscribe link in header */
		$unsubscribe_link = class_exists( 'BWFAN_PRO_Common' ) ? BWFAN_PRO_Common::get_unsubscribe_link( [ 'uid' => $contact->contact->get_uid() ] ) : '';
		if ( ! empty( $unsubscribe_link ) ) {
			$header[] = "List-Unsubscribe: <$unsubscribe_link>";
			$header[] = "List-Unsubscribe-Post: List-Unsubscribe=One-Click";
		}

		$header[] = 'Content-type:text/html;charset=UTF-8';

		/** Removed wp mail filters */
		BWFAN_Common::bwf_remove_filter_before_wp_mail();

		$header = apply_filters( 'bwfan_email_headers', $header );

		$subject = __( 'TEST: ', 'wp-marketing-automations' ) . $subject;

		return wp_mail( $args['email'], $subject, $body, $header );
	}

	public static function get_stats_total( $after, $before ) {
		return BWFAN_Core()->conversations->get_stats_total( $after, $before );
	}

	/**
	 * @return mixed
	 */
	public static function get_popular_emails() {
		$popular_emails['popular_emails'] = BWFAN_Model_Engagement_Tracking::get_popular_emails();

		return $popular_emails;
	}

	public function send_test_sms( $args ) {
		$sms_body = BWFAN_Common::decode_merge_tags( $args['sms_body'] );
		$sms_body = preg_replace_callback( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', function ( $matches ) {
			return do_shortcode( '[bwfan_bitly_shorten]' . $matches[0] . '[/bwfan_bitly_shorten]' );
		}, $sms_body );

		$to = $args['phone'];

		$sms_settings = BWFAN_Core()->conversation->get_twilio_settings();
		$url          = 'https://api.twilio.com/2010-04-01/Accounts/' . $sms_settings['account_sid'] . '/Messages.json';
		$headers      = array(
			'Content-Type'  => 'application/x-www-form-urlencoded',
			'Authorization' => 'Basic ' . base64_encode( $sms_settings['account_sid'] . ':' . $sms_settings['auth_token'] ),
		);

		$req_params = array(
			'Body' => $sms_body,
			'From' => $sms_settings['twilio_no'],
			'To'   => $to,
		);

		$response      = wp_remote_post( $url, array(
			'timeout'     => 45,
			'httpversion' => '1.0',
			'blocking'    => true,
			'body'        => $req_params,
			'headers'     => $headers,
		) );
		$response_body = array();
		if ( ! is_wp_error( $response ) ) {
			$response_body = isset( $response['body'] ) && ! empty( $response['body'] ) ? json_decode( $response['body'], true ) : $response_body;
		}

		if ( is_array( $response ) && 201 === $response['response']['code'] && empty( $response_body['error_message'] ) ) {
			return true;
		}

		$message = __( 'SMS could not be sent. ', 'wp-marketing-automations' );

		if ( isset( $response['body']['errors'] ) && isset( $response['body']['errors'][0] ) && isset( $response['body']['errors'][0]['message'] ) ) {
			$message = $response['body']['errors'][0]['message'];
		} elseif ( isset( $response['body']['message'] ) ) {
			$message = $response['body']['message'];
		} elseif ( isset( $response['body']['error_message'] ) ) {
			$message = $response['body']['error_message'];
		} elseif ( isset( $response['bwfan_response'] ) && ! empty( $response['bwfan_response'] ) ) {
			$message = $response['bwfan_response'];
		} elseif ( is_array( $response['body'] ) && isset( $response['body'][0] ) && is_string( $response['body'][0] ) ) {
			$message = $message . $response['body'][0];
		}

		return $message;
	}

	/**
	 * Get WhatsApp Plugin Availability
	 *
	 * @return bool
	 */
	public function is_whatsapp_service_available() {
		if ( class_exists( 'BWFCO_Wabot' ) || class_exists( 'BWFCO_Waapi' ) ) {
			return true;
		}

		return apply_filters( 'bwfan_is_whatsapp_service_available', false );
	}

	/**
	 * Get WhatsApp Services
	 *
	 * @return array
	 */
	public function get_whatsapp_services() {
		$services = array();
		if ( class_exists( 'BWFCO_Wabot' ) && BWFAN_Core()->connectors->is_connected( 'bwfco_wabot' ) ) {
			$services[] = array(
				'value' => 'bwfco_wabot',
				'label' => 'Wabot',
			);
		}
		if ( class_exists( 'BWFCO_Waapi' ) && BWFAN_Core()->connectors->is_connected( 'bwfco_waapi' ) ) {
			$services[] = array(
				'value' => 'bwfco_waapi',
				'label' => 'Waapi',
			);
		}

		return apply_filters( 'bwfan_whatsapp_services', $services );
	}

	/**
	 * Send test WhatsApp message
	 *
	 * @param $phone
	 * @param $messages
	 * @param $utm
	 *
	 * @return array
	 */
	public function send_whatsapp_message( $phone, $messages, $utm = [] ) {
		$response         = [
			'status'  => false,
			'message' => ''
		];
		$whatsapp_service = self::get_whatsapp_services();

		if ( empty( $whatsapp_service ) ) {
			return [
				'status'  => false,
				'message' => __( 'No WhatsApp service Found', 'wp-marketing-automations' )
			];
		}
		$service = '';
		if ( count( $whatsapp_service ) > 1 ) {
			$global_settings = BWFAN_Common::get_global_settings();
			if ( isset( $global_settings['bwfan_primary_whats_app_service'] ) && ! empty( $global_settings['bwfan_primary_whats_app_service'] ) ) {
				if ( BWFAN_Core()->connectors->is_connected( $global_settings['bwfan_primary_whats_app_service'] ) ) {
					$service = $global_settings['bwfan_primary_whats_app_service'];
				}
			}
		}

		if ( empty( $service ) ) {
			$service = $whatsapp_service[0]['value'];
		}

		switch ( $service ) {
			case 'bwfco_wabot':
				$response = BWFCO_Wabot::send_message_via_broadcast( $phone, $messages, $utm );
				break;
			case 'bwfco_waapi':
				$response = BWFCO_Waapi::send_message_via_broadcast( $phone, $messages, $utm );
				break;
			case 'bwfco_whatsapp':
				$response = BWFCO_Whatsapp::send_single_message( $phone, $messages, $utm );
				break;
		}

		return $response;
	}

	/**
	 *  Save the time of last sent engagement to contact
	 *
	 * @param $data
	 */

	public static function save_last_sent_engagement( $data ) {
		if ( ! is_array( $data ) || empty( $data ) ) {
			return;
		}

		$cid_or_email = isset( $data['cid'] ) ? absint( $data['cid'] ) : 0;

		if ( 0 === $cid_or_email ) {
			/** Get contact id from engagement */
			$engagement = BWFAN_Model_Engagement_Tracking::get( $data['conversation_id'] );
			if ( ! is_array( $engagement ) || empty( $engagement ) || 0 === absint( $engagement['cid'] ) ) {
				return;
			}
			$cid_or_email = $engagement['cid'];
		}

		if ( 0 === $cid_or_email ) {
			$cid_or_email = isset( $data['email'] ) ? absint( $data['email'] ) : '';
		}

		$contact = new BWFCRM_Contact( $cid_or_email );
		if ( ! $contact->is_contact_exists() ) {
			return;
		}

		$contact->set_field_by_slug( 'last-sent', current_time( 'mysql' ) );
		$contact->save_fields();
	}

	public function include_email_merge_tags_templates() {
		$products = [];
		$cart     = false;
		$data     = false;

		ob_start();
		include_once BWFAN_PLUGIN_DIR . '/templates/cart-table.php';
		include_once BWFAN_PLUGIN_DIR . '/templates/order-table.php';
		include_once BWFAN_PLUGIN_DIR . '/templates/product-grid-2-col.php';
		include_once BWFAN_PLUGIN_DIR . '/templates/product-grid-3-col.php';
		include_once BWFAN_PLUGIN_DIR . '/templates/product-rows.php';
		include_once BWFAN_PLUGIN_DIR . '/templates/review-rows.php';

		$buffer = ob_get_clean();
		unset( $buffer );
	}

	/** added css for email editor using emogrify */
	public function emogrify_email_editor( $data ) {
		$data = $this->prepare_email_content( $data );
		ob_start();
		include BWFAN_PLUGIN_DIR . '/templates/email-editor-styles.php';
		$css = ob_get_clean();

		if ( BWFAN_Common::supports_emogrifier() ) {
			$emogrifier_class_bwf = '\\BWF_Pelago\\Emogrifier';
			$emogrifier_class     = '\\Pelago\\Emogrifier';
			if ( ! class_exists( $emogrifier_class_bwf ) && ! class_exists( $emogrifier_class ) ) {
				include_once BWFAN_PLUGIN_DIR . '/libraries/class-emogrifier.php';
			}

			if ( class_exists( $emogrifier_class_bwf ) ) {
				$emogrifier_class = $emogrifier_class_bwf;
			}

			try {
				/** @var \BWF_Pelago\Emogrifier $emogrifier */
				$emogrifier = new $emogrifier_class( $data, $css );
				$data       = $emogrifier->emogrify();
			} catch ( Exception $e ) {
				BWFAN_Core()->logger->log( $e->getMessage(), 'send_email_emogrifier' );
			}
		} else {
			$data = '<style type="text/css">' . $css . '</style>' . $data;
		}

		return apply_filters( 'bwfan_modify_block_html_body', $data );

	}

	/** added css for email block editor */
	public function block_email_editor( $data ) {
		if ( ! bwfan_is_autonami_pro_active() ) {
			return '';
		}
		include_once BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-block-editor.php';
		if ( function_exists( 'bwfan_modify_block_html_body' ) ) {
			$data = bwfan_modify_block_html_body( $data );
		}
		$data       = do_shortcode( $data );
		$global_val = class_exists( 'BWFCRM_Block_Editor' ) ? BWFCRM_Block_Editor::$global_settings_var : [];
		if ( ! empty( $global_val ) ) {
			$global_val_k = array_keys( $global_val );
			$global_val_v = array_values( $global_val );
			$data         = str_replace( $global_val_k, $global_val_v, $data );
		}

		return $this->emogrify_email_editor( $data );
	}

	/**
	 * Get link hash
	 *
	 * @param $url
	 * @param $data
	 *
	 * @return mixed|string
	 */
	public function get_link_hash( $url, $data = [] ) {
		$url = BWFAN_Common::bwfan_correct_protocol_url( $url );
		if ( empty( $this->template ) ) {
			return BWFAN_Core()->conversation->maybe_insert_link( $url, $data );
		}
		$template_data = isset( $this->template['data'] ) && ! empty( $this->template['data'] ) ? json_decode( $this->template['data'], true ) : [];
		$links         = isset( $template_data['links'] ) && ! empty( $template_data['links'] ) ? $template_data['links'] : [];

		BWFAN_Common::log_test_data( $links, 'fk-store-link', true );
		BWFAN_Common::log_test_data( $url, 'fk-store-link', true );
		if ( ! empty( $links ) && isset( $links[ $url ] ) ) {
			BWFAN_Common::log_test_data( 'link already found in the DB', 'fk-store-link', true );

			return $links[ $url ];
		}

		$link_hash              = $this->maybe_insert_link( $url, $data );
		$links[ $url ]          = $link_hash;
		$template_data['links'] = $links;
		BWFAN_Model_Templates::bwfan_update_template( $this->template['ID'], [
			'data' => wp_json_encode( $template_data ),
		] );

		return $link_hash;
	}

	/**
	 * Save link
	 *
	 * @return string
	 */
	public function maybe_insert_link( $link, $data ) {
		if ( false === wp_http_validate_url( $link ) ) {
			return '';
		}
		$type = $data['type'] ?? 0;
		$type = empty( $type ) ? $this->engagement_type : $type;
		$type = empty( $type ) ? 2 : $type;

		$oid = $data['oid'] ?? 0;
		$tid = ! empty( $data['template_id'] ) ? intval( $data['template_id'] ) : ( ! empty( $this->template_id ) ? $this->template_id : 0 );
		$oid = empty( $oid ) ? $data['automation_id'] ?? $this->broadcast_id : $oid;
		$sid = $data['step_id'] ?? 0;

		$cleaned_url = $this->get_cleaned_url( $link );
		$l_hash      = BWFAN_Model_Links::check_if_link_exists( $link, [
			'clean_url' => $cleaned_url,
			'oid'       => $oid,
			'tid'       => $tid,
			'type'      => $type,
			'sid'       => $sid,
		] );
		if ( ! empty( $l_hash ) ) {
			return $l_hash;
		}
		$link_hash = md5( $cleaned_url . $oid . $tid . $type . $sid );

		$data = [
			'url'        => $link,
			'l_hash'     => $link_hash,
			'created_at' => current_time( 'mysql', 1 ),
			'clean_url'  => $cleaned_url,
			'oid'        => $oid,
			'tid'        => $tid,
			'sid'        => $sid,
			'type'       => $type
		];
		BWFAN_Model_Links::insert( $data );

		return $link_hash;
	}

	/**
	 * Get clean url
	 *
	 * @param $link
	 *
	 * @return mixed|string
	 */
	public function get_cleaned_url( $link ) {
		$parsed_url = parse_url( $link );
		$url_host   = $parsed_url['host'] ?? '';
		$url_path   = $parsed_url['path'] ?? '';
		$port       = $parsed_url['port'] ?? '';
		$url_host   = ! empty( $port ) ? $url_host . ':' . $port : $url_host;

		/** If link has contained link trigger */
		if ( isset( $parsed_url['query'] ) && false !== strpos( $parsed_url['query'], 'bwfan-link-trigger=' ) ) {
			return $url_host . '/?' . $parsed_url['query'];
		}

		/** if only slash in path */
		$url_path = '/' === $url_path ? '' : $url_path;

		return ! empty( $url_host ) ? $url_host . $url_path : $link;
	}

}

if ( class_exists( 'BWFAN_Conversation' ) ) {
	BWFAN_Core::register( 'conversation', 'BWFAN_Conversation' );
}
