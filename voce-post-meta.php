<?php
/*
Plugin Name: Voce Meta API
Plugin URI: http://plugins.voceconnect.com
Description: A brief description of the Plugin.
Version: The Plugin's Version Number, e.g.: 1.0
Author: Name Of The Plugin Author
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

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
	var $fields;

	/**
	 *
	 * ID of the group
	 * @var string
	 */
	var $id;

	/**
	 *
	 * Title of group
	 * @var string
	 */
	var $title;

	/**
	 *
	 * Descriptive text to display at the top of the metabox
	 * @var string
	 */
	var $description;

	/**
	 *
	 * Required capability to edit this group
	 * @var string
	 */
	var $capability;

	/**
	 *
	 * Context used for the metabox
	 * @var string
	 */
	var $context;

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

		add_action ( 'add_meta_boxes', array ($this, '_add_metabox' ) );
		add_action ( 'save_post', array($this, 'update_group' ), 10, 2 );
	}

	public function _add_metabox($post_type) {

		if (post_type_supports ( $post_type, $this->id ) && current_user_can($this->capability) ) {
			add_meta_box ( $this->id, $this->title, array ($this, '_display_group' ), $post_type, $this->context, $this->priority );
		}
	}

	public function _display_group($post) {
		if ($this->description) {
			echo '<p>', esc_html ( $this->description ), '</p>';
		}
		foreach ( $this->fields as $field ) {
			$field->display_field ($post->ID);
		}
		wp_nonce_field("update_{$this->id}", "{$this->id}_nonce");
	}

	function add_field($type, $id, $label, $args = array()) {

		if (! isset ( $this->fields [$id] )) {
			if (class_exists($type)) {
				$this->fields [$id] = new $type ( $id, $label, $this, $args );
			}
		}

		return $this->fields [$id];
	}

	function verify_nonce() {

		if (isset($_REQUEST["{$this->id}_nonce"])) {
			return wp_verify_nonce($_REQUEST["{$this->id}_nonce"], "update_{$this->id}");
		}

		return false;
	}

	function update_group($post_id, $post) {

		if (wp_is_post_autosave($post) || wp_is_post_revision($post) || !$this->verify_nonce()) {
			return $post_id;
		}

		foreach ($this->fields as $field) {
			$field->update_field($post_id);
		}

	}

}

class Voce_Meta_Field {

	var $group;
	var $id;
	var $label;
	var $callback;

	function __construct($id, $label, $group, $args = array()) {
		$this->group = $group;
		$this->id = $id;
		$this->label = $label;

		$defaults = array(
			'callback' => 'voce_text_field_display',
		);
		$r = wp_parse_args($args, $defaults);

		$this->callback = $r['callback'];
	}

	function get_value($post_id) {
		return get_post_meta($post_id, "{$this->group->id}_{$this->id}", true);
	}

	function update_field($post_id) {
		$old_value = $this->get_value($post_id);
		$new_value = isset($_POST[$this->id]) ? $_POST[$this->id] : '';
		update_post_meta($post_id, "{$this->group->id}_{$this->id}", $new_value);
	}

	function display_field($post_id) {
		$value = $this->get_value($post_id);
		call_user_func($this->callback, $this->id, $this->label, $value);
	}

}

function voce_text_field_display($id, $label, $value) {
	?>
	<label><?php echo esc_html($label); ?></label>
	<input type="text" name="<?php echo $id; ?>" value="<?php echo esc_attr($value); ?>" />
	<?php
}