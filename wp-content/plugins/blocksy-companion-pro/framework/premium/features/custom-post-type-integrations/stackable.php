<?php

namespace Blocksy\CustomPostType\Integrations;

class StackableStorage {
	public static $is_main_script_loaded = false;
	public static $scripts_loaded = [];
}

class Stackable_Init_No_Constructor extends \Stackable_Init {
	public function __construct() {
	}
}

class Stackable extends \Blocksy\CustomPostTypeRenderer {
	public function get_content($args = []) {
		return \Blocksy\CustomPostTypeRenderer::NOT_IMPLEMENTED;
	}

	public function pre_output() {
		$post = get_post($this->id);

		if ($post) {
			$contentpost = $post->post_content;

			$contentpost = str_replace(
				'<!-- wp:post-content /-->',
				'',
				$contentpost
			);

			// Stackable will handle content and enqueue everything needed.
			if (method_exists('\Stackable_Init', 'enqueue_frontend_assets_for_content')) {
				\Stackable_Init::enqueue_frontend_assets_for_content($contentpost);
				return;
			}

			// Backward compatibility for older Stackable versions.
			$this->enqueue_frontend_assets_for_content($contentpost);
		}
	}

	public static function enqueue_frontend_assets_for_content($post_content) {
		$init = new Stackable_Init_No_Constructor();

		// If a Stackable block is present in the post content, enqueue the frontend assets.
		if ( ! StackableStorage::$is_main_script_loaded && ! is_admin() ) {
			if ( stripos( $post_content, '<!-- wp:stackable/' ) !==  false ) {
				$init->block_enqueue_frontend_assets();
				StackableStorage::$is_main_script_loaded = true;
			}
		}

		// Gather all the unique Stackable blocks and load all the block scripts once.
		// Gather all the "<!-- wp:stackable/BLOCK_NAME"
		preg_match_all('/<!-- wp:stackable\/([a-zA-Z_-]+)/', $post_content, $stackable_blocks);

		// Go through each unique block name.
		foreach ($stackable_blocks[1] as $_block_name) {
			// Clean up the block name, trailing "-" from the end since it may have "--" in the end if the post content is compressed.
			$block_name = trim($_block_name, '-');

			// Enqueue the block script once.
			if (! isset(StackableStorage::$scripts_loaded[$block_name])) {
				do_action( 'stackable/' . $block_name . '/enqueue_scripts' );
				StackableStorage::$scripts_loaded[$block_name] = true;
			}
		}

		// Check whether the current block needs to enqueue some scripts.
		// This gets called across all the blocks.
		do_action('stackable/enqueue_scripts', $post_content, null);
	}
}

