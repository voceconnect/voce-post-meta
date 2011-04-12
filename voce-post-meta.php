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
	var $priority;

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
		
		add_action('add_meta_boxes', array($this, '_add_metabox'));
		add_action('save_post', array($this, 'update_group'), 10, 2);

	}
	
	public function _add_metabox($post_type) {

		if (post_type_supports ( $post_type, $this->id ) && current_user_can($this->capability) ) {
			add_meta_box ( $this->id, $this->title, array ($this, '_display_group' ), $post_type, $this->context, $this->priority );
		}
	}

	public function _display_group($post) {
		if ($this->description) {
			echo '<p>', esc_html($this->description), '</p>';
		}
		foreach ( $this->fields as $field ) {
			$field->display_field ($post->ID);
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
				$this->fields[$id] = new $type($this, $id, $label, $args);
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


		foreach ($this->fields as $field) {
			$field->update_field($post_id);
		}
	
	}

}


interface iVoce_Meta_Field {
	
}

class Voce_Meta_Field implements iVoce_Meta_Field {

	var $group;
	var $id;
	var $label;
	var $display_callbacks;
	var $sanitize_callbacks;
	var $capability;
	var $args;
	
	public function __construct($group, $id, $label, $args = array()) {
		$this->group = $group;
		$this->label = $label;
		$this->id = $id;
		
		$defaults = array(
			'capability' => $this->group->capability, 
			'default_value' => '', 
			'display_callbacks' => array('voce_text_field_display'),
			'sanitize_callbacks' => array(),
			'description' => ''
		);
		
		$args = wp_parse_args($args, $defaults);
		
		$this->default_value = $args['default_value'];
		$this->capability = $args['capability'];
		$this->args = $args;
	}

	function get_value($post_id) {
		return get_post_meta($post_id, "{$this->group->id}_{$this->id}", true);
	}

	function update_field($post_id) {
		$old_value = $this->get_value($post_id);
		$new_value = isset($_POST[$this->id]) ? $_POST[$this->id] : '';
		foreach ($this->args['sanitize_callbacks'] as $callback) {
			$new_value = call_user_func($callback, $this, $old_value, $new_value, $post_id);
		}
		update_post_meta($post_id, "{$this->group->id}_{$this->id}", $new_value);
	}

	function display_field($post_id) {
		$value = $this->get_value($post_id);
		foreach ($this->args['display_callbacks'] as $callback) {
			call_user_func($callback, $this, $value, $post_id);
		}
	}

}

// stuff to test below

function voce_text_field_display($field, $value, $post_id) {
	?>
	<p>
		<label><?php echo esc_html($field->label); ?></label>
		<input type="text" name="<?php echo $field->id; ?>" value="<?php echo esc_attr($value); ?>" />
	</p>
	<?php
}

function voce_numeric_value($field, $old, $new, $post_id) {
	if (is_numeric($new)) {
		return $new;
	}
	return 0;
}

Voce_Meta_API::GetInstance()
	->add_group('basic', 'Basic Options', array(
		'description' => 'Just some basic options.',
		'capability' => 'manage_options',
	))
		->add_field('Voce_Meta_Field', 'first_name', 'First Name')->group
		->add_field('Voce_Meta_Field', 'last_name', 'Last Name')->group
		->add_field('Voce_Meta_Field', 'age', 'Your Age', array('sanitize_callbacks'=>array('voce_numeric_value')));
add_post_type_support('post', 'basic');