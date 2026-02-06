<?php

class BWFAN_Split_Path_Traversal_Controller extends BWFAN_Base_Step_Controller {

	public $current_step_type = null;
	public $current_node_id = 0;
	public $links = null;
	public $steps = null;
	public $db_links = null;
	public $step_iteration = null;
	public $get_nodes = false;
	public $formatted_links = [];
	public $path_target_nodes = [];

	/**
	 * Set steps in the traverser
	 *
	 * @param array $db_steps
	 *
	 * @return void
	 */
	public function set_steps( $db_steps ) {
		if ( ! is_array( $db_steps ) || empty( $db_steps ) ) {
			return;
		}

		foreach ( $db_steps as $step ) {
			$this->steps[ $step['id'] ] = $step;
		}

		if ( ! isset( $this->steps[ $this->current_node_id ] ) ) {
			return;
		}

		$this->current_step_type = $this->steps[ $this->current_node_id ]['type'];
		$this->step_id           = $this->steps[ $this->current_node_id ]['stepId'];
	}


	/**
	 * Get Step branches
	 *
	 * @param $step_id
	 *
	 * @return array
	 */
	public function get_all_branches_of_step( $step_id ) {
		$iteration_array = empty( $this->step_iteration ) ? BWFAN_Model_Automationmeta::get_meta( $this->automation_id, 'step_iteration_array' ) : $this->step_iteration;
		if ( ! isset( $iteration_array[ $step_id ] ) ) {
			return [];
		}
		$branches = [];
		foreach ( $iteration_array[ $step_id ] as $data ) {
			if ( ! isset( $data['next'] ) ) {
				continue;
			}
			$branches[] = $data['next'];
		}

		return $branches;
	}

	/**
	 * Get split/conditional step's all node & step ids
	 *
	 * @param $node_id
	 *
	 * @return array
	 */
	public function get_steps_all_node( $node_id ) {
		$this->formatted_links = $this->get_formatted_links();
		$node_id               = empty( $node_id ) ? $this->current_node_id : $node_id;
		if ( ! isset( $this->steps[ $node_id ] ) ) {
			return [];
		}
		$step_id  = $this->steps[ $node_id ]['stepId'];
		$type     = $this->steps[ $node_id ]['type'];
		$branches = $this->get_all_branches_of_step( $step_id );
		if ( empty( $branches ) ) {
			return [];
		}
		$split_steps         = [];
		$first_branch        = '';
		$first_path_node_ids = $first_path_sids = $last_path_sids = $merger_points = [];
		foreach ( $branches as $index => $branch ) {
			$branch_name = str_replace( $node_id . '-path-', 'p-', $branch );
			/** Assign first branch name */
			if ( empty( $first_branch ) ) {
				$first_branch = $branch_name;
			}
			$this->path_target_nodes = [];

			/** Fetch node ids and step ids  */
			$path_nodes = $this->get_connected_nodes( $branch );

			/** Assign first path to get first path's steps */
			if ( empty( $first_path_node_ids ) ) {
				$first_path_node_ids = $path_nodes['node_ids'];
				$first_path_sids     = $path_nodes['step_ids'];
				if ( 'split' === $type ) {
					$split_steps[ $branch_name ] = $path_nodes['step_ids'];
				}
				continue;
			}

			$merger_points = array_values( array_unique( array_intersect( $path_nodes['node_ids'], $first_path_node_ids ) ) );

			if ( 'split' === $type ) {
				$split_steps[ $branch_name ] = array_diff( $path_nodes['step_ids'], array_intersect( $path_nodes['step_ids'], $first_path_sids ) );
			}
			$last_path_sids = $path_nodes['step_ids'];
		}

		if ( 'split' === $type ) {
			$split_steps[ $first_branch ] = array_diff( $split_steps[ $first_branch ], array_intersect( $split_steps[ $first_branch ], $last_path_sids ) );
		}

		return [
			'merger_points' => isset( $merger_points[0] ) ? $merger_points[0] : 0,
			'split_steps'   => $split_steps
		];
	}

	/**
	 * Get connected nodes
	 *
	 * @param $element
	 *
	 * @return array
	 */
	public function get_connected_nodes( $element ) {
		$node_ids = [];
		$step_ids = [];
		if ( ! empty( $this->formatted_links[ $element ] ) && ! in_array( $element, $this->path_target_nodes, true ) ) {
			foreach ( $this->formatted_links[ $element ] as $targetElem ) {
				/** For merger points we need node ids */
				$node_ids[]    = isset( $this->steps[ $targetElem['target'] ]['id'] ) ? $this->steps[ $targetElem['target'] ]['id'] : '';
				$step_ids[]    = isset( $this->steps[ $targetElem['target'] ]['stepId'] ) ? $this->steps[ $targetElem['target'] ]['stepId'] : $this->steps[ $targetElem['target'] ]['id'];
				$subTargetElem = $this->get_connected_nodes( $targetElem['target'] );
				$res_node_ids  = $subTargetElem['node_ids'];

				if ( count( $res_node_ids ) > 0 ) {
					$node_ids = array_merge( $node_ids, $res_node_ids );
					$step_ids = array_merge( $step_ids, $subTargetElem['step_ids'] );
				}
			}
		}
		$this->path_target_nodes = $node_ids;

		return [
			'node_ids' => array_unique( $node_ids ),
			'step_ids' => array_unique( $step_ids ),
		];
	}

	/**
	 * Get formatted links
	 *
	 * @param $db_links
	 *
	 * @return array
	 */
	public function get_formatted_links() {
		$links = empty( $this->db_links ) ? BWFAN_Model_Automationmeta::get_meta( $this->automation_id, 'links' ) : $this->db_links;
		if ( empty( $links ) ) {
			return [];
		}
		$data = [];
		foreach ( $links as $link ) {
			if ( isset( $link['source'] ) && isset( $link['target'] ) && isset( $mapArr[ $link['source'] ] ) ) {
				$data[ $link['source'] ][] = [
					'target' => $link['target'],
				];
				continue;
			}
			if ( isset( $link['target'] ) ) {
				$data[ $link['source'] ][] = [
					'target' => $link['target'],
				];
			}
		}

		return $data;
	}


}
