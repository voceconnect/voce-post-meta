<?php

class Voce_Meta_API {
	private static $instance;
	
	private $groups;
	
	public static function GetInstance() {
		
		if (! isset ( self::$instance )) {
			self::$instance = new Voce_Meta_API ();
		}
		
		return self::$instance;
	}
	
	private function __construct() {
		$this->groups = array();
	}
	
	public function add_group($id, $title, $args = array()) {
		
		if (! isset ( $this->groups [$id] )) {
			$this->groups [$id] = new Voce_Meta_Group ( $id, $title, $args );
		}
		
		return $this->groups [$id];
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
		$defaults = array ('description' => '', 'capability' => '', 'context' => 'normal', 'priority' => 'default' );
		$r = wp_parse_args ( $args, $defaults );
		
		$this->fields = array ();
		$this->id = $id;
		$this->title = $title;
		$this->description = $r ['description'];
		$this->capability = $r ['capability'];
		$this->context = $r ['context'];
		$this->priority = $r ['priority'];
		
		add_action ( 'add_meta_boxes', array ($this, 'add_metabox' ) );
	}
	
	public function _add_metabox($post_type) {
		if (post_type_supports ( $post_type, $this->id )) {
			add_meta_box ( $this->id, $this->title, array ($this, '_display_group' ), $post_type, $this->context, $this->priority );
		}
	}
	
	public function _display_group() {
		if ($this->description) {
			echo '<p>', esc_html ( $this->description ), '</p>';
		}
		foreach ( $this->fields as $field ) {
			$field->display_field ();
		}
	}
	
	function add_field($type, $id, $label, $args = array()) {
		
		if (! isset ( $this->fields [$id] )) {
			if ($type instanceof Voce_Meta_Field) {
				$this->fields [$id] = new $type ( $id, $label, $args );
			}
		}
		
		return $this->fields [$id];
	}
}

class Voce_Meta_Field {
	var $group;
	var $id;
	var $label;
	var $callback;

}