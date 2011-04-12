<?php

class Voce_Meta_API {
	private static $instance;

	var $groups;

	public static function GetInstance() {

		if (!isset(self::$instance)) {
			self::$instance = new Voce_Settings_API();
		}

		return self::$instance;
	}

	function add_group($id, $title, $args = array()) {

		if (!isset($this->groups[$id])) {
			$this->groups[$id] = new Voce_Meta_Group($id, $title, $args);
		}

		return $this->groups[$id];
	}

}

class Voce_Meta_Group {
	var $fields;
	var $id;
	var $title;
	var $description;
	var $capability;
	var $context;
	var $priority;

	function __construct($id, $title, $args) {
		$defaults = array(
			'description' => '',
			'capability' => '',
			'context' => 'normal',
			'priority' => 'default',
		);
		$r = wp_parse_args($args, $defaults);

		$this->fields = array();
		$this->id = $id;
		$this->title = $title;
		$this->description = $r['description'];
		$this->capability = $r['capability'];
		$this->context = $r['context'];
		$this->priority = $r['priority'];

		add_action('add_meta_boxes', array($this, 'add_metabox'));
	}

	function add_metabox($post_type) {
		if (post_type_supports($post_type, $this->id)) {
			add_meta_box($this->id, $$this->title, array($this, 'display_group'), $post_type, $this->context, $this->priority);
		}
	}

	function display_group() {
		if ($this->description) {
			echo '<p>', esc_html($this->description), '</p>';
		}
		foreach ($this->fields as $field) {
			$field->display_field();
		}
	}

	function add_field($id, $label, $args = array()) {

		if (!isset($this->fields[$id])) {
			$this->fields[$id] = new Voce_Meta_Field($id, $label, $args);
		}

		return $this->fields[$id];
	}
}

class Voce_Meta_Field {
	var $group;
	var $id;
	var $label;
	var $callback;

}