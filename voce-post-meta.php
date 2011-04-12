<?php

class Voce_Meta_API {
	private static $instance;
	
	private $groups;
	
	public static function GetInstance() {
		
		if (! isset(self::$instance)) {
			self::$instance = new Voce_Meta_API();
		}
		
		return self::$instance;
	}
	
	private function __construct() {
		$this->groups = array();
	}
	
	public function add_group($id, $title, $args = array()) {
		
		if (! isset($this->groups[$id])) {
			$this->groups[$id] = new Voce_Meta_Group($id, $title, $args);
		}
		
		return $this->groups[$id];
	}

}

class Voce_Meta_Group {
	/**
	 *
	 * Associative array of fields belonging to this group
	 * @var array
	 */
	private $fields;
	
	/**
	 *
	 * ID of the group
	 * @var string
	 */
	private $id;
	
	/**
	 *
	 * Title of group
	 * @var string
	 */
	private $title;
	
	/**
	 *
	 * Descriptive text to display at the top of the metabox
	 * @var string
	 */
	private $description;
	
	/**
	 *
	 * Required capability to edit this group
	 * @var string
	 */
	private $capability;
	
	/**
	 *
	 * Context used for the metabox
	 * @var string
	 */
	private $context;
	
	/**
	 *
	 * priority for the metabox
	 * @var string
	 */
	private $priority;
	
	public function __construct($id, $title, $args) {
		$defaults = array('description' => '', 'capability' => '', 'context' => 'normal', 'priority' => 'default');
		$r = wp_parse_args($args, $defaults);
		
		$this->fields = array();
		$this->id = $id;
		$this->title = $title;
		$this->description = $r['description'];
		$this->capability = $r['capability'];
		$this->context = $r['context'];
		$this->priority = $r['priority'];
		
		add_action('add_meta_boxes', array($this, 'add_metabox'));
		add_action('save_post', array($this, 'update_group'), 10, 2);
	}
	
	public function _add_metabox($post_type) {
		
		if (post_type_supports($post_type, $this->id)) {
			add_meta_box($this->id, $this->title, array($this, '_display_group'), $post_type, $this->context, $this->priority);
		}
	}
	
	public function _display_group() {
		if ($this->description) {
			echo '<p>', esc_html($this->description), '</p>';
		}
		foreach ( $this->fields as $field ) {
			$field->display_field();
		}
		wp_nonce_field("update_{$this->id}", "{$this->id}_nonce");
	}
	
	/**
	 * 
	 * Creates, adds, and returns a new field for this group
	 * @param string $type
	 * @param string $id
	 * @param string $label
	 * @param array $args
	 */
	public function add_field($type, $id, $label, $args = array()) {
		
		if (! isset($this->fields[$id])) {
			if (class_exists($type) && in_array('iVoce_Meta_Field', class_implements($type))) {
				$this->fields[$id] = new $type($id, $label, $args);
			}
		}
		
		return $this->fields[$id];
	}
	
	private function verify_nonce() {
		
		if (isset($_REQUEST["{$this->id}_nonce"])) {
			return wp_verify_nonce($_REQUEST["{$this->id}_nonce"], "update_{$this->id}");
		}
		
		return false;
	}
	
	public function update_group($post_id, $post) {
		
		if (wp_is_post_autosave($post) || wp_is_post_revision($post) || ! $this->verify_nonce()) {
			return $post_id;
		}
		
		foreach ( $this->fields as $field ) {
			$field->update_field();
		}
	
	}
}

interface iVoce_Meta_Field {
	
}

class Voce_Meta_Field implements iVoce_Meta_Field {
	private $group;
	private $id;
	private $label;
	private $display_callbacks;
	private $sanitize_callbacks;
	private $capability;
	
	private $args;
	
	
	public function __construct($group, $id, $label, $args) {
		$this->group = $group;
		$this->title = $title;
		$this->id = $id;
		
		$defaults = array(
			'capability' => $this->group->capability, 
			'default_value' => '', 
			'display_callbacks' => '',
			'sanitize_callbacks' => array('vs_santize_text'),
			'description' => ''
		);
		
		$args = wp_parse_args($args, $defaults);
		
		$this->default_value = $args['default_value'];
		$this->capability = $args['capability'];
		$this->args = $args;
	}

}