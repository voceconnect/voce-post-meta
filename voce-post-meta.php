<?php

if ( ! class_exists( 'Voce_Meta_API' ) ):
/*
  Plugin Name: Voce Post Meta
  Description: Allow easily adding meta fields to post types
  Version: 2.0.0-alpha
  Author: prettyboymp, kevinlangleyjr, jeffstieler, markparolisi, banderon
  License: GPLv2 or later
 */


/**
 * Class Voce_Meta_API
 *
 * Main API class
 */
class Voce_Meta_API {

	public $groups;
	public $type_mapping;
	public $label_allowed_html;
	public $description_allowed_html;

	private static $instance;

	/**
	 * Get singleton
	 *
	 * @return Voce_Meta_API
	 */
	public static function GetInstance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Voce_Meta_API();
		}

		return self::$instance;
	}

	/**
	 * @constructor
	 */
	private function __construct() {
		$this->groups = array();

		/* v2.0.0: move/rename default display/sanitization functions into default class */
		$mapping = array();
		$mapping['fieldset'] = array(
			'class' => 'Voce_Meta_Fieldset',
			'args'  => array(
				'display_callbacks'       => array( array( 'Voce_Meta_Fieldset', 'display_fieldset' ) ),
				'clone_template_callback' => array( 'Voce_Meta_Fieldset', 'display_fieldset_template' ),
			),
		);
		$mapping['text'] = array(
			'class' => 'Voce_Meta_Field_Text',
			'args'  => array(
				'display_callbacks' => array( array( 'Voce_Meta_Field_Text', 'display_text' ) ),
			),
		);
		$mapping['numeric'] = array(
			'class' => 'Voce_Meta_Field_Text',
			'args'  => array(
				'display_callbacks'  => array( array( 'Voce_Meta_Field_Text', 'display_text' ) ),
				'sanitize_callbacks' => array( array( 'Voce_Meta_Field_Text', 'sanitize_numeric' ) ),
			),
		);
		$mapping['textarea'] = array(
			'class' => 'Voce_Meta_Field_Textarea',
			'args'  => array(
				'display_callbacks' => array( array( 'Voce_Meta_Field_Textarea', 'display_textarea' ) ),
			),
		);
		$mapping['hidden'] = array(
			'class' => 'Voce_Meta_Field_Hidden',
			'args'  => array(
				'display_callbacks' => array( array( 'Voce_Meta_Field_Hidden', 'display_hidden' ) ),
			)
		);
		$mapping['dropdown'] = array(
			'class' => 'Voce_Meta_Field_Dropdown',
			'args'  => array(
				'display_callbacks'  => array( array( 'Voce_Meta_Field_Dropdown', 'display_dropdown' ) ),
				'sanitize_callbacks' => array( array( 'Voce_Meta_Field_Dropdown', 'sanitize_dropdown' ) ),
			),
		);
		$mapping['checkbox'] = array(
			'class' => 'Voce_Meta_Field_Checkbox',
			'args'  => array(
				'display_callbacks'       => array( array( 'Voce_Meta_Field_Checkbox', 'display_checkbox' ) ),
				'clone_template_callback' => array( 'Voce_Meta_Field_Checkbox', 'display_checkbox_template' ),
				'clone_js_callback'       => array( 'Voce_Meta_Field_Checkbox', 'render_clone_checkbox_js' ),
				'clone_js'                => 'vpm_clone_checkbox',
			),
		);
		$mapping['radio'] = array(
			'class' => 'Voce_Meta_Field_Radio',
			'args'  => array(
				'display_callbacks'       => array( array( 'Voce_Meta_Field_Radio', 'display_radio' ) ),
				'clone_template_callback' => array( 'Voce_Meta_Field_Radio', 'display_radio_template' ),
				'clone_js_callback'       => array( 'Voce_Meta_Field_Radio', 'render_clone_radio_js' ),
				'clone_js'                => 'vpm_clone_radio',
			),
		);
		$mapping['wp_editor'] = array(
			'class' => 'Voce_Meta_Field_WP_Editor',
			'args'  => array(
				'display_callbacks'       => array( array( 'Voce_Meta_Field_WP_Editor', 'display_wp_editor' ) ),
				'sanitize_callbacks'      => array( array( 'Voce_Meta_Field_WP_Editor', 'sanitize_wp_editor' ) ),
				'clone_template_callback' => array( 'Voce_Meta_Field_WP_Editor', 'display_wp_editor_template' ),
				'clone_js_callback'       => array( 'Voce_Meta_Field_WP_Editor', 'render_clone_wp_editor_js' ),
				'clone_js'                => 'vpm_clone_wp_editor',
				'wp_editor_args'          => array(
					'textarea_rows' => 10
				),
			)
		);

		/* v2.0.0: add 'voce_' to filter to bring it in line with other plugin filters */
		$this->type_mapping = apply_filters( 'voce_meta_type_mapping', $mapping );

		$allowed_html = array(
			'a'      => array(
				'href'  => array(),
				'title' => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'code'   => array(),
			'pre'    => array(),
		);

		$this->label_allowed_html = apply_filters( 'voce_meta_label_allowed_html', $allowed_html );
		$this->description_allowed_html = apply_filters( 'voce_meta_description_allowed_html', $allowed_html );

		add_action( 'admin_print_styles-post.php', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_print_styles-post-new.php', array( __CLASS__, 'enqueue_scripts' ) );
	}

	public static function enqueue_scripts() {
		wp_enqueue_script( 'voce-post-meta', plugins_url( '/js/voce-post-meta.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable' ) );
		wp_enqueue_style( 'voce-post-meta', plugins_url( '/css/voce-post-meta.css', __FILE__ ) );
	}

	/**
	 *
	 * @param string $id
	 * @param string $title
	 * @param array $args
	 *
	 * @return Voce_Meta_Group
	 */
	public function add_group( $id, $title, $args = array() ) {
		if ( !isset( $this->groups[ $id ] ) ) {
			$this->groups[ $id ] = new Voce_Meta_Group( $id, $title, $args );
		}

		return $this->groups[ $id ];
	}

	/**
	 * @method get_meta_value
	 *
	 * @param integer $post_id
	 * @param string $group
	 * @param string $field
	 *
	 * @return boolean
	 */
	public function get_meta_value( $post_id, $group, $field, $single = true ) {
		if ( is_array( $group ) && 2 == count( $group ) ) {
			$fieldset = end( $group );
			$group = reset( $group );
		}
		if ( isset( $this->groups[ $group ] ) ) {
			if ( empty( $fieldset ) && isset( $this->groups[ $group ]->fields[ $field ] ) ) {
				$values = $this->groups[ $group ]->fields[ $field ]->get_values( $post_id );
				if ( empty( $this->groups[ $group ]->fields[ $field ]->multiple ) && $single ) {
					return reset( $values );
				}

				return $values;
			}
			if ( ! empty( $this->groups[ $group ]->fields[ $fieldset ]->fields[ $field ] ) ) {
				$values = $this->groups[ $group ]->fields[ $fieldset ]->fields[ $field ]->get_values( $post_id );
				if ( empty( $this->groups[ $group ]->fields[ $fieldset ]->fields[ $field ]->multiple ) && $single ) {
					return reset( $values );
				}

				return $values;
			}
		}

		return false;
	}

	/**
	 * @method get_meta_count
	 *
	 * @param integer $post_id
	 * @param string $group
	 * @param string $field
	 *
	 * @return integer
	 */
	public function get_meta_count( $post_id, $group, $field ) {
		if ( isset( $this->groups[ $group ] ) && isset( $this->groups[ $group ]->fields[ $field ] ) ) {
			return $this->groups[ $group ]->fields[ $field ]->get_count( $post_id );
		}

		return 0;
	}

}


/**
 * Class Voce_Meta_Group
 *
 * Handle meta groups
 */
class Voce_Meta_Group {

	/**
	 * Associative array of fields belonging to this group
	 *
	 * @var array
	 */
	public $fields;

	/**
	 * ID of the group
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Title of group
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Descriptive text to display at the top of the metabox
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Required capability to edit this group
	 *
	 * @var string
	 */
	public $capability;

	/**
	 * Context used for the metabox
	 *
	 * @var string
	 */
	public $context;

	/**
	 * Priority for the metabox
	 *
	 * @var string
	 */
	public $priority;

	/**
	 * @param interger id
	 * @param string title
	 * @param array args
	 *
	 * @constructor
	 */
	public function __construct( $id, $title, $args ) {
		$defaults = array(
			'description' => '',
			'capability'  => 'edit_posts',
			'context'     => 'normal',
			'priority'    => 'default'
		);
		$r = wp_parse_args( $args, $defaults );

		$this->fields = array();
		$this->id = $id;
		$this->title = $title;
		$this->description = $r['description'];
		$this->capability = $r['capability'];
		$this->context = $r['context'];
		$this->priority = $r['priority'];

		add_action( 'add_meta_boxes', array( $this, '_add_metabox' ) );
		add_action( 'save_post', array( $this, 'update_group' ), 10, 2 );
	}

	/**
	 * @method _add_metabox
	 *
	 * @param string $post_type
	 */
	public function _add_metabox( $post_type ) {
		if ( post_type_supports( $post_type, $this->id ) && current_user_can( $this->capability ) ) {
			add_meta_box( $this->id, $this->title, array( $this, '_display_group' ), $post_type, $this->context, $this->priority );
		}
	}

	/**
	 * @method _display_group
	 *
	 * @param type $post
	 */
	public function _display_group( $post ) {
		echo ( ! empty( $this->description ) ? '<p class="description">' . wp_kses( $this->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</p>' : '' );
		foreach ( $this->fields as $field ) {
			$field->display_field( $post->ID );
		}
		wp_nonce_field( "update_{$this->id}", "{$this->id}_nonce" );
	}

	/**
	 * @method __call
	 *
	 * @param string $name
	 * @param array $func_args
	 *
	 * @return iVoce_Meta_Field|null New field or null
	 */
	public function __call( $name, $func_args ) {
		if ( strpos( $name, 'add_field_' ) === 0 ) {
			$type = substr( $name, 10 );
			$api = Voce_Meta_API::GetInstance();

			if ( ! empty( $api->type_mapping[ $type ] ) ) {
				$mapping = $api->type_mapping[ $type ];
				$field_args = isset( $func_args[2] ) ? $func_args[2] : array();
				$field_args = wp_parse_args( $field_args, $mapping['args'] );
				return $this->add_field( $type, $mapping['class'], $func_args[0], $func_args[1], $field_args );
			}

			return null;
		}
	}

	/**
	 * Creates, adds, and returns a new field for this group
	 *
	 * @param string $type
	 * @param string $type_class
	 * @param string $id
	 * @param string $label
	 * @param array $args
	 *
	 * @return iVoce_Meta_Field
	 */
	public function add_field( $type, $type_class, $id, $label, $args = array() ) {
		if ( ! isset( $this->fields[ $id ] ) ) {
			if ( class_exists( $type_class ) && in_array( 'iVoce_Meta_Field', class_implements( $type_class ) ) ) {
				$this->fields[ $id ] = new $type_class( $type, $this, $id, $label, $args );
			}
		}

		return $this->fields[ $id ];
	}

	/**
	 * Deletes a field in this group
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function remove_field( $id ) {
		if ( isset( $this->fields[ $id ] ) ){
			unset( $this->fields[ $id ] );
			return true;
		}

		return false;
	}

	/**
	 * @method verfiy_nonce
	 *
	 * @return boolean
	 */
	private function verify_nonce() {
		if ( isset( $_REQUEST[ "{$this->id}_nonce" ] ) ) {
			return wp_verify_nonce( $_REQUEST[ "{$this->id}_nonce" ], "update_{$this->id}" );
		}

		return false;
	}

	/**
	 * @method update_group
	 *
	 * @param type $post_id
	 * @param type $post
	 *
	 * @return type
	 */
	public function update_group( $post_id, $post ) {
		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return;
		}
		if ( ! post_type_supports( $post->post_type, $this->id ) || ! $this->verify_nonce() ) {
			return;
		}

		foreach ( $this->fields as $field ) {
			$field->update_field( $post_id );
		}
	}

}


/**
 * Interface iVoce_Meta_Field
 *
 * Interface for custom fields
 */
interface iVoce_Meta_Field {

	public function __construct( $type, $group, $id, $label, $args = array() );

	public function update_field( $post_id );

	public function display_field( $post_id );
}


/**
 * Class Voce_Meta_Fieldset
 *
 * Handle meta fieldsets
 */
class Voce_Meta_Fieldset implements iVoce_Meta_Field {

	public $type;
	public $group;
	public $id;
	public $label;
	public $post_type;

	public $fields;
	public $capability;
	public $display_callbacks;
	public $description;

	public $multiple;
	public $add_button_callback;
	public $delete_button_callback;
	public $clone_template_callback;
	public $clone_js;
	public $clone_js_callback;
	public $sortable;

	public $index = 0;

	/**
	 * @method init
	 */
	public static function init() {
		add_filter( 'wp_ajax_vpm_add_fieldset', function() {
			// TODO: add nonce
			$post_id = $_POST['post_id'];
			$group_id = $_POST['group_id'];
			$fieldset_id = $_POST['field_id'];
			$index = (int)$_POST['index'];

			$api = Voce_Meta_API::GetInstance();
			if ( empty( $api->groups[ $group_id ]->fields[ $fieldset_id ] ) ) {
				exit;
			}
			if ( $index < 2 ) {
				exit;
			}

			$fieldset = $api->groups[ $group_id ]->fields[ $fieldset_id ];

			$fieldset->index = $index;
			foreach ( $fieldset->display_callbacks as $callback ) {
				if ( is_callable( $callback ) ) {
					foreach ( $fieldset->fields as $field ) {
						$field->display_field( $post_id );
					}
				}
			}

			exit;
		} );
	}

	/**
	 * @constructor
	 *
	 * @param interger id
	 * @param string title
	 * @param array args
	 *
	 * @constructor
	 */
	public function __construct( $type, $group, $id, $label, $args = array() ) {
		$this->type = $type;
		$this->group = $group;
		$this->id = $id;
		$this->label = $label;
		$this->post_type = get_post_type( $id );
		$this->fields = array();

		$this->args = $args;

		$defaults = array(
			'capability'              => $this->group->capability,
			'display_callbacks'       => array(),
			'description'             => '',

			'multiple'                => false,
			'add_button_callback'     => array( __CLASS__, 'render_add_button' ),
			'delete_button_callback'  => array( __CLASS__, 'render_delete_button' ),
			'clone_template_callback' => '',
			'clone_js'                => 'vpm_clone_field',
			'clone_js_callback'       => array( __CLASS__, 'render_clone_field_js' ),
			'sortable'                => false,
		);
		$args = wp_parse_args( $args, $defaults );

		$this->capability = $args['capability'];
		$this->display_callbacks = $args['display_callbacks'];
		$this->description = $args['description'];

		$this->multiple = ( intval( $args['multiple'] ) >= 2 ? intval( $args['multiple'] ) : (bool)$args['multiple'] );
		$this->add_button_callback = $args['add_button_callback'];
		$this->delete_button_callback = $args['delete_button_callback'];
		$this->clone_template_callback = ( is_callable( $args['clone_template_callback'] ) ? $args['clone_template_callback'] : reset( $this->display_callbacks ) );
		$this->clone_js = $args['clone_js'];
		$this->clone_js_callback = $args['clone_js_callback'];
		$this->sortable = (bool)$args['sortable'];

	}

	/**
	 * Output HTML from application callback or user defined callback
	 *
	 * @param integer $post_id
	 */
	public function display_field( $post_id ) {
		$mapping = $this->get_mapping_data( $post_id );
		if ( $this->multiple && is_callable( $this->clone_template_callback ) && is_callable( $this->add_button_callback ) && is_callable( $this->delete_button_callback ) ) {
			call_user_func( $this->clone_template_callback, $this, 0 );
			if ( is_callable( $this->clone_js_callback ) ) {
				call_user_func( $this->clone_js_callback, $this, $post_id );
			}

		}

		if ( ! count( array_filter( $mapping ) ) ) {
			$mapping = array( 1 => '' );
		}
		foreach ( $mapping as $index => $meta ) {
			$this->index = $index;
			foreach ( $this->display_callbacks as $callback ) {
				if ( is_callable( $callback ) ) {
					if ( $this->multiple ) {
						call_user_func( $this->delete_button_callback, $this, $post_id );
					}
					call_user_func( $callback, $this, $post_id );
				}
			}
		}
		$this->index++;
		if ( $this->multiple ) {
			call_user_func( $this->add_button_callback, $this, $post_id );
		}
		?>
		<input type="hidden" name="<?php echo $this->get_index_count_name(); ?>" value="<?php echo $this->index - 1; ?>">
		<?php
	}

	/**
	 * Render fieldset
	 *
	 * @param integer $post_id
	 */
	public static function display_fieldset( $fieldset, $post_id ) {
		printf( '<fieldset id="%s" class="vpm_fieldset %s" data-multiple_index="%d" data-multiple="%d">',
			esc_attr( $fieldset->get_wrapper_id( $fieldset->index ) ),
			esc_attr( $fieldset->get_wrapper_id() ),
			$fieldset->index,
			$fieldset->multiple );
		echo $fieldset->get_legend();
		echo ( ! empty( $fieldset->description ) ? '<p class="description">' . wp_kses( $fieldset->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</p>' : '' );
		echo '<div class="vpm_fieldset_fields">';
		foreach ( $fieldset->fields as $field ) {
			$field->display_field( $post_id );
		}
		echo '</div>';
		echo '</fieldset>';
	}

	/**
	 * Render fieldset
	 *
	 * @param integer $post_id
	 */
	public static function display_fieldset_template( $fieldset, $post_id ) {
		printf( '<fieldset id="%s" class="vpm_fieldset %s hidden" data-multiple="%d">',
			esc_attr( $fieldset->get_wrapper_id() ),
			esc_attr( $fieldset->get_wrapper_id() ),
			$fieldset->multiple );
		echo $fieldset->get_legend();
		echo ( ! empty( $fieldset->description ) ? '<p class="description">' . wp_kses( $fieldset->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</p>' : '' );
		echo '<div class="vpm_fieldset_fields"></div>';
		echo '</fieldset>';
	}

	/**
	 * Returns post meta values
	 *
	 * @param integer $post_id
	 *
	 * @return array Returns array of values if multiple, single value in array otherwise. Returns default in array if no values at all.
	 */
	public function get_values( $post_id ) {
		$mapping = get_post_meta( $post_id, $this->get_mapping_key() );

		if ( empty( $values ) ) {
			return array( $this->default_value );
		}

		return $values;
	}

	/**
	 * Returns number of saved values for fieldset
	 *
	 * @param integer $post_id
	 *
	 * @return integer Returns number of values saved for meta
	 */
	public function get_count( $post_id ) {
		$mapping = get_post_meta( $post_id, $this->get_mapping_key() );

		return count( $mapping );
	}

	/**
	 * Returns key for keeping count of submitted fieldsets
	 *
	 * @return string Returns the key for index counter
	 */
	public function get_index_count_name() {
		return $this->get_fieldset_id() . '--index';
	}

	/**
	 * Returns key for mapping array meta
	 *
	 * @return string Returns the key used for saving the mapping array
	 */
	public function get_mapping_key() {
		return "{$this->group->id}__{$this->id}__mapping";
	}

	/**
	 * Returns mapping array for fieldset
	 *
	 * @return array Returns the mapping array for the fields in the fieldset
	 */
	public function get_mapping_data( $post_id ){
		return (array)get_post_meta( $post_id, $this->get_mapping_key(), true );
	}

	/**
	 * Returns fieldset id attribute
	 *
	 * @return string Returns the HTML id of the fieldset
	 */
	public function get_fieldset_id( $index = '' ){
		return "{$this->group->id}__{$this->id}" . ( $index ? "-{$index}" : '' );
	}

	/**
	 * Returns field wrapper id
	 *
	 * @return string Returns id used on the form element wrapper
	 */
	public function get_wrapper_id( $index = '' ) {
		return "vpm_fieldset-" . $this->get_fieldset_id( $index );
	}

	/**
	 * @method get_legend
	 *
	 * @return string
	 */
	public function get_legend() {
		return ( empty( $this->label ) ? '' : sprintf( '<legend>%s:</legend>', wp_kses( $this->label, Voce_Meta_API::GetInstance()->label_allowed_html ) ) );
	}

	/**
	 * @method __call
	 *
	 * @param string $name
	 * @param array $func_args
	 *
	 * @return iVoce_Meta_Field|null New field or null
	 */
	public function __call( $name, $func_args ) {
		if ( strpos( $name, 'add_field_' ) === 0 ) {
			$type = substr( $name, 10 );
			$api = Voce_Meta_API::GetInstance();

			if ( ! empty( $api->type_mapping[ $type ] ) ) {
				$mapping = $api->type_mapping[ $type ];
				$field_args = isset( $func_args[2] ) ? $func_args[2] : array();
				$field_args = wp_parse_args( $field_args, $mapping['args'] );
				return $this->add_field( $type, $mapping['class'], $func_args[0], $func_args[1], $field_args );
			}

			return null;
		}
	}

	/**
	 * Creates, adds, and returns a new field for this fieldset
	 *
	 * @param string $type
	 * @param string $type_class
	 * @param string $id
	 * @param string $label
	 * @param array $args
	 *
	 * @return iVoce_Meta_Field
	 */
	public function add_field( $type, $type_class, $id, $label, $args = array() ) {
		if ( ! isset( $this->fields[ $id ] ) ) {
			if ( class_exists( $type_class ) && in_array( 'iVoce_Meta_Field', class_implements( $type_class ) ) ) {
				$args['multiple'] = false;
				$this->fields[ $id ] = new $type_class( $type, array( $this->group, $this ), $id, $label, $args );
			}
		}

		return $this->fields[ $id ];
	}

	/**
	 * Deletes a field in this group
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function remove_field( $id ) {
		if ( isset( $this->fields[ $id ] ) ){
			unset( $this->fields[ $id ] );
			return true;
		}

		return false;
	}

	/**
	 * Update post meta
	 *
	 * @param type $post_id
	 */
	public function update_field( $post_id ) {
		$current_data = $this->get_mapping_data( $post_id );
		foreach ( $current_data as $field_data ) {
			if ( is_array( $field_data ) && count( $field_data ) ) {
				foreach ( $field_data as $meta_ids ) {
					foreach ( $meta_ids as $meta_id ) {
						delete_metadata_by_mid( 'post', $meta_id );
					}
				}
			}
		}

		$index_counter = ( ! empty( $_POST[ $this->get_index_count_name() ] ) ? $_POST[ $this->get_index_count_name() ] : 1 );
		$field_updates = array();
		for ( $index = 1; $index <= $index_counter; $index++ ) {
			if ( empty( $_POST[ $this->get_fieldset_id() . "-{$index}" ] ) ) {
				continue;
			}
			$this->index = $index;
			foreach ( $this->fields as $key => $field ) {
				$field_updates[ $index ][ $field->id ] = $field->update_field( $post_id );
			}
		}

		update_post_meta( $post_id, $this->get_mapping_key( $post_id ), $field_updates );
	}

	/**
	 * @method render_add_button
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_add_button( $field, $post_id ) {
		$class = ( intval( $field->multiple ) >= 2 && intval( $field->multiple ) <= $field->get_count( $post_id ) ? 'disabled' : '' );
		printf( '<span data-wrapper="%s" data-group="%s" data-field="%s" data-clone_js="%s" data-multiple_index="%d" data-multiple_max="%d" class="vpm_multiple-add %s dashicons dashicons-plus-alt">Add</span>',
			esc_attr( $field->get_wrapper_id() ),
			esc_attr( $field->group->id ),
			esc_attr( $field->id ),
			esc_attr( $field->clone_js ),
			$field->index,
			$field->multiple,
			esc_attr( $class )
		);
	}

	/**
	 * @method render_delete_button
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_delete_button( $field, $post_id ) {
		printf( '<span data-wrapper="%s" data-multiple_index="%d" class="vpm_multiple-delete dashicons dashicons-trash"></span>',
			esc_attr( $field->get_wrapper_id() ),
			$field->index
		);
	}

	/**
	 * @method render_clone_field_js
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_clone_field_js( $field, $post_id ) {
		?>
		<script type="application/javascript">
			if (typeof window.vpm_clone_field === 'undefined') {
				window.vpm_clone_field = function(addButton) {
					var $addButton = jQuery(addButton),
						wrapperId = $addButton.data('wrapper'),
						template = jQuery('#'+wrapperId).clone(),
						multiple_index = +$addButton.data('multiple_index'),
						del_button;

					$addButton.attr('data-multiple_index', multiple_index + 1).data('multiple_index', multiple_index + 1);

					template.attr('id', wrapperId+'-'+multiple_index)
						.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index)
						.removeClass('hidden');

					del_button = jQuery('.vpm_multiple-delete').first().clone()
						.attr('data-wrapper', wrapperId).data('wrapper', wrapperId)
						.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index);

					$addButton.before(del_button).before(template);

					jQuery.ajax({
						dataType: 'html',
						async: false,
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'vpm_add_fieldset',
							post_id: <?php echo esc_js( $post_id ); ?>,
							group_id: $addButton.data('group'),
							field_id: $addButton.data('field'),
							index: multiple_index
						},
						success: function (resp) {
							if (resp) {
								var index_counter = jQuery('[name="'+$addButton.data('group')+'__'+$addButton.data('field')+'--index"]');
								jQuery('#'+wrapperId+'-'+multiple_index+' .vpm_fieldset_fields').html(resp);
								index_counter.val(+index_counter.val()+1);
							}
						}
					});
				};
			}
		</script>
		<?php
	}

}

Voce_Meta_Fieldset::init();

/**
 * Class Voce_Meta_Field
 *
 * Class for handling basic field types
 */
class Voce_Meta_Field implements iVoce_Meta_Field {

	public $type;
	public $group;
	public $fieldset;
	public $id;
	public $label;
	public $post_type;

	public $capability;
	public $default_value;
	public $display_callbacks;
	public $sanitize_callbacks;
	public $description;

	public $multiple;
	public $add_button_callback;
	public $delete_button_callback;
	public $clone_template_callback;
	public $clone_js;
	public $clone_js_callback;
	public $sortable;

	public $args;

	public $index = '';

	/**
	 * @constructor
	 *
	 * @param string $type
	 * @param string $group
	 * @param integer $id
	 * @param string $label
	 * @param array $args
	 */
	public function __construct( $type, $group, $id, $label, $args = array() ) {
		$this->type = $type;
		$this->id = $id;
		$this->label = $label;
		$this->post_type = get_post_type( $id );

		$fieldset = null;
		if ( is_array( $group ) && 2 == count( $group ) ) {
			$fieldset = end( $group );
			$group = reset( $group );
		}
		$this->group = $group;
		$this->fieldset = $fieldset;

		$this->args = $args;

		$defaults = array(
			'capability'                  => $this->group->capability,
			'default_value'               => '',
			'display_callbacks'           => array(),
			'sanitize_callbacks'          => array(),
			'description'                 => '',

			'multiple'                    => false,
			'add_button_callback'         => array( __CLASS__, 'render_add_button' ),
			'delete_button_callback'      => array( __CLASS__, 'render_delete_button' ),
			'clone_template_callback'     => '',
			'clone_js'                    => 'vpm_clone_field',
			'clone_js_callback'           => array( __CLASS__, 'render_clone_field_js' ),
			'sortable'                    => false,
		);
		$args = wp_parse_args( $args, $defaults );

		$this->capability = $args['capability'];
		$this->default_value = $args['default_value'];
		$this->display_callbacks = $args['display_callbacks'];
		$this->sanitize_callbacks = $args['sanitize_callbacks'];
		$this->description = $args['description'];

		$this->multiple = ( intval( $args['multiple'] ) >= 2 ? intval( $args['multiple'] ) : (bool)$args['multiple'] );
		$this->add_button_callback = $args['add_button_callback'];
		$this->delete_button_callback = $args['delete_button_callback'];
		$this->clone_template_callback = ( is_callable( $args['clone_template_callback'] ) ? $args['clone_template_callback'] : reset( $this->display_callbacks ) );
		$this->clone_js = $args['clone_js'];
		$this->clone_js_callback = $args['clone_js_callback'];
		$this->sortable = (bool)$args['sortable'];
	}

	/**
	 * Returns post meta values
	 *
	 * @param integer $post_id
	 *
	 * @return array Returns array of values if multiple, single value in array otherwise. Returns default in array if no values at all.
	 */
	public function get_values( $post_id ) {
		$values = get_post_meta( $post_id, $this->get_meta_key() );
		if ( empty( $values ) ) {
			return array( $this->default_value );
		}

		return $values;
	}

	/**
	 * Returns number of saved values for post meta
	 *
	 * @param integer $post_id
	 *
	 * @return integer Returns number of values saved for meta
	 */
	public function get_count( $post_id ) {
		$values = get_post_meta( $post_id, $this->get_meta_key() );

		return count( $values );
	}

	/**
	 * Returns field name group for the form
	 *
	 * @return string Returns name group for form element.
	 */
	public function get_name_group(){
		$group_id = $this->group->id;
		if ( $this->fieldset ) {
			$index = (int)$this->fieldset->index;
			$group_id = "{$this->group->id}__{$this->fieldset->id}-{$index}";
		}

		return $group_id;
	}

	/**
	 * Returns field name for the form
	 *
	 * @return string Returns name for form element.
	 */
	public function get_name( $index = '' ){
		return $this->get_name_group() . "[{$this->id}][{$index}]";
	}

	/**
	 * Returns meta key the values are saved under
	 *
	 * @return string Returns meta key for saving/retrieving meta
	 */
	public function get_meta_key(){
		$group_id = $this->group->id;
		if ( $this->fieldset ) {
			$group_id = "{$this->group->id}__{$this->fieldset->id}-{$this->fieldset->index}_";
		}

		return "{$group_id}_{$this->id}";
	}

	/**
	 * Returns field unique input id
	 *
	 * @return string Returns input id used on the form element
	 */
	public function get_input_id( $index = '' ){
		$group_id = $this->group->id;
		if ( $this->fieldset ) {
			$fieldset_index = (int)$this->fieldset->index;
			$group_id = "{$this->group->id}__{$this->fieldset->id}-{$fieldset_index}_";
		}

		return "{$group_id}_{$this->id}" . ( $index ? "-{$index}" : '' );
	}

	/**
	 * Returns field wrapper id
	 *
	 * @return string Returns id used on the form element wrapper
	 */
	public function get_wrapper_id( $index = '' ){
		return "vpm_field-" . $this->get_input_id( $index );
	}

	/**
	 * @method get_label
	 *
	 * @param iVoce_Meta_Field $field
	 *
	 * @return string
	 */
	public static function get_label( $field ) {
		return ( empty( $field->label ) ? '' : sprintf( '<label for="%s">%s:</label>', esc_attr( $field->get_input_id( $field->index ) ), wp_kses( $field->label, Voce_Meta_API::GetInstance()->label_allowed_html ) ) );
	}

	/**
	 * Output HTML from application callback or user defined callback
	 *
	 * @param integer $post_id
	 */
	public function display_field( $post_id ) {
		$values = $this->get_values( $post_id );
		if ( $this->multiple && is_callable( $this->clone_template_callback ) && is_callable( $this->add_button_callback ) && is_callable( $this->delete_button_callback ) ) {
			call_user_func( $this->clone_template_callback, $this, '', $post_id );
			if ( is_callable( $this->clone_js_callback ) ) {
				call_user_func( $this->clone_js_callback, $this, $post_id );
			}
			$this->index = 1;
		}

		foreach ( $values as $value ) {
			if ( intval( $this->index ) ) {
				call_user_func( $this->delete_button_callback, $this, $post_id );
			}
			foreach ( $this->display_callbacks as $callback ) {
				if ( is_callable( $callback ) ) {
					call_user_func( $callback, $this, $value, $post_id );
				}
			}
			if ( intval( $this->index ) ) {
				$this->index++;
			}
		}
		if ( intval( $this->index ) ) {
			call_user_func( $this->add_button_callback, $this, $post_id );
		}
	}

	/**
	 * Update post meta
	 *
	 * @param type $post_id
	 */
	public function update_field( $post_id ) {
		$group_id = $this->get_name_group();
		$old_values = $this->get_values( $post_id );
		$new_values = array();

		if ( isset( $_POST[ $group_id ][ $this->id ] ) ) {
			$new_values = $_POST[ $group_id ][ $this->id ];
		}

		if ( $this->multiple ) {
			// remove initial value due to template
			array_shift( $new_values );
		}
		foreach ( $this->sanitize_callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				foreach ( $new_values as $k => $value ) {
					$new_values[ $k ] = call_user_func( $callback, $this, @$old_values[ $k ], $value, $post_id );
				}
			}
		}

		$this->delete_existing_meta( $post_id );

		$field_id = $this->get_meta_key();
		$meta_ids = array();
		foreach ( $new_values as $value ) {
			if ( $value || $value != $this->default_value ) {
				$meta_ids[] = add_post_meta( $post_id, $field_id, $value );
			}
		}
		return $meta_ids;
	}

	/**
	 * @method delete_existing_meta
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public function delete_existing_meta( $post_id ) {
		delete_post_meta( $post_id, $this->get_meta_key() );
	}

	/**
	 * @method render_add_button
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_add_button( $field, $post_id ) {
		$class = ( intval( $field->multiple ) >= 2 && intval( $field->multiple ) <= $field->get_count( $post_id ) ? 'disabled' : '' );
		printf( '<span data-wrapper="%s" data-id="%s" data-clone_js="%s" data-multiple_index="%d" data-multiple_max="%d" class="vpm_multiple-add %s dashicons dashicons-plus-alt">Add</span>',
			esc_attr( $field->get_wrapper_id() ),
			esc_attr( $field->get_input_id() ),
			esc_attr( $field->clone_js ),
			$field->index,
			$field->multiple,
			esc_attr( $class )
		);
	}

	/**
	 * @method render_delete_button
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_delete_button( $field, $post_id ) {
		printf( '<span data-wrapper="%s" data-multiple_index="%d" class="vpm_multiple-delete dashicons dashicons-trash"></span>',
			esc_attr( $field->get_wrapper_id() ),
			$field->index
		);
	}

	/**
	 * @method render_clone_field_js
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_clone_field_js( $field, $post_id ) {
		?>
		<script type="application/javascript">
			if (typeof window.vpm_clone_field === 'undefined') {
				window.vpm_clone_field = function(addButton) {
					var $addButton = jQuery(addButton),
						wrapperId = $addButton.data('wrapper'),
						elId = $addButton.data('id'),
						template = jQuery('#' + wrapperId).clone(),
						$elements = template.add(template.find('*')),
						multiple_index = +$addButton.data('multiple_index'),
						del_button;

					$addButton.attr('data-multiple_index', multiple_index + 1).data('multiple_index', multiple_index + 1);

					jQuery.each($elements, function() {
						var $el = jQuery(this);
						jQuery.each($el.get(0).attributes, function() {
							if (this.value == elId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
							if (this.value == wrapperId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
						});
					});

					template.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index)
						.removeClass('hidden');

					del_button = jQuery('.vpm_multiple-delete').first().clone()
						.attr('data-wrapper', wrapperId).data('wrapper', wrapperId)
						.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index);

					$addButton.before(del_button).before(template);
				};
			}
		</script>
		<?php
	}

}


class Voce_Meta_Field_Text extends Voce_Meta_Field {

	/**
	 * @method display_text
	 *
	 * @param iVoce_Meta_Field $field
	 * @param mixed $value
	 * @param integer $post_id
	 */
	public static function display_text( $field, $value, $post_id ) {
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="vpm_field vpm_field_text <?php echo esc_attr( $field->get_wrapper_id() ); ?>" data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label( $field ); ?>
			<input class="widefat" type="text" id="<?php echo esc_attr( $field->get_input_id( $field->index ) ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>" value="<?php echo esc_attr( $value ); ?>"  />
			<?php echo ! empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}

	/**
	 * @method sanitize_numeric
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $old_value
	 * @param type $new_value
	 * @param integer $post_id
	 *
	 * @return integer
	 */
	public static function sanitize_numeric( $field, $old_value, $new_value, $post_id ) {
		if ( is_numeric( $new_value ) ) {
			return $new_value;
		}
		return 0;
	}
}


class Voce_Meta_Field_Textarea extends Voce_Meta_Field {

	/**
	 * @method display_textarea
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_textarea( $field, $current_value, $post_id ) {
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="vpm_field vpm_field_textarea <?php echo esc_attr( $field->get_wrapper_id() ); ?>" data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label( $field ); ?>
			<textarea class="widefat" id="<?php echo esc_attr( $field->get_input_id( $field->index ) ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>"><?php echo esc_attr( $current_value ); ?></textarea>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}
}


class Voce_Meta_Field_Dropdown extends Voce_Meta_Field {

	/**
	 * @method display_dropdown
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_dropdown( $field, $current_value, $post_id ) {
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="vpm_field vpm_field_dropdown <?php echo esc_attr( $field->get_wrapper_id() ); ?>" data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label( $field ); ?>
			<select id="<?php echo esc_attr( $field->get_input_id( $field->index ) ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>">
				<?php foreach ($field->args['options'] as $key => $value): ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_value, $key ); ?>><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}

	/**
	 * @method sanitize_dropdown
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $old_value
	 * @param type $new_value
	 * @param integer $post_id
	 *
	 * @return string|boolean
	 */
	public static function sanitize_dropdown( $field, $old_value, $new_value, $post_id ) {
		if ( isset( $field->args['options'] ) && ! empty( $field->args['options'] ) ){
			$value = sanitize_text_field( $new_value );
			if ( in_array( $value, array_keys( $field->args['options'] ) ) ) {
				return $value;
			}
		}

		return false;
	}
}


class Voce_Meta_Field_Hidden extends Voce_Meta_Field {

	public $value_callback;

	/**
	 * @constructor
	 *
	 * @param string $type
	 * @param string|array $group
	 * @param integer $id
	 * @param string $label
	 * @param array $args
	 */
	public function __construct( $type, $group, $id, $label, $args = array() ) {
		parent::__construct( $type, $group, $id, $label, $args );

		$this->multiple = false;

		$this->value_callback = ( ! empty( $this->args['value_callback'] ) ? $this->args['value_callback'] : array( __CLASS__, 'get_default_value' ) );
	}

	public function get_default_value() {
		return $this->default_value;
	}

	/**
	 * Returns post meta values
	 *
	 * @param integer $post_id
	 *
	 * @return array Returns array of values if multiple, single value in array otherwise. Returns default in array if no values at all.
	 */
	public function get_values( $post_id ) {
		$values = get_post_meta( $post_id, $this->get_input_id() );
		if ( empty( $values ) ) {
			return array( call_user_func( $this->value_callback, $this, reset( $values ), $post_id ) );
		}

		return $values;
	}

	/**
	 * Output HTML from application callback or user defined callback
	 *
	 * @param type $post_id
	 */
	public function display_field( $post_id ) {
		$value = call_user_func( $this->value_callback, $this, reset( $this->get_values( $post_id ) ), $post_id );

		foreach ( $this->display_callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				call_user_func( $callback, $this, $value, $post_id );
			}
		}
	}

	/**
	 * @method display_hidden
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_hidden( $field, $value, $post_id ) {
		?>
		<input class="hidden" type="hidden" id="<?php echo esc_attr( $field->get_input_id() ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>" value="<?php echo esc_attr( $value ); ?>"  />
		<?php
	}
}


class Voce_Meta_Field_Checkbox extends Voce_Meta_Field {

	public $item_format;

	/**
	 * @constructor
	 *
	 * @param string $type
	 * @param string|array $group
	 * @param integer $id
	 * @param string $label
	 * @param array $args
	 */
	public function __construct( $type, $group, $id, $label, $args = array() ) {
		parent::__construct( $type, $group, $id, $label, $args );

		$this->multiple = false;

		$this->item_format = ( ! empty( $this->args['item_format'] ) && in_array( $this->args['item_format'], array( 'inline', 'block' ) ) ? $this->args['item_format'] : 'block' );
	}

	/**
	 * Update post meta
	 *
	 * @param type $post_id
	 */
	public function update_field( $post_id ) {
		$group_id = ( $this->fieldset ? "{$this->group->id}__{$this->fieldset->id}" : $this->group->id );
		$old_values = $this->get_values( $post_id );
		$new_values = array();

		if ( isset( $_POST[ $group_id ][ $this->id ] ) ) {
			$new_values = $_POST[ $group_id ][ $this->id ];
		}

		foreach ( $this->sanitize_callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				foreach ( $new_values as $k => $value ) {
					$new_values[ $k ] = call_user_func( $callback, $this, @$old_values[ $k ], $value, $post_id );
				}
			}
		}

		$this->delete_existing_meta( $post_id );
		if ( empty( $new_values ) ) {
			return;
		}

		$field_id = $this->get_meta_key();
		$new_ids = array();
		foreach ( $new_values as $value ) {
			$new_ids[] = add_post_meta( $post_id, $field_id, $value );
		}
		return $new_ids;

	}

	/**
	 * Output HTML from application callback or user defined callback
	 *
	 * @param type $post_id
	 */
	public function display_field( $post_id ) {
		$values = $this->get_values( $post_id );
		foreach ( $this->display_callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				call_user_func( $callback, $this, $values, $post_id );
			}
		}
	}

	/**
	 * Returns post meta values
	 *
	 * @param integer $post_id
	 *
	 * @return array Returns array of values if multiple, single value in array otherwise. Returns default in array if no values at all.
	 */
	public function get_values( $post_id ) {
		$values = get_post_meta( $post_id, $this->get_input_id() );
		if ( empty( $values ) ) {
			return array( $this->default_value );
		}

		// Backwards compatibility with older versions of VPM
		if ( is_array( reset( $values ) ) ) {
			$updated_values = array();
			foreach ( reset( $values ) as $key => $val ) {
				$updated_values[] = $key;
			}
			$values = $updated_values;
		}

		return $values;
	}

	/**
	 * @method render_clone_checkbox_js
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_clone_checkbox_js( $field, $post_id ) {
		?>
		<script type="application/javascript">
			if (typeof window.vpm_clone_checkbox === 'undefined') {
				window.vpm_clone_checkbox = function(addButton) {
					var $addButton = jQuery(addButton),
						wrapperId = $addButton.data('wrapper'),
						elId = $addButton.data('id'),
						template = jQuery('#' + wrapperId).clone(),
						$elements = template.add(template.find('*')),
						multiple_index = +$addButton.data('multiple_index'),
						del_button;

					$addButton.attr('data-multiple_index', multiple_index + 1).data('multiple_index', multiple_index + 1);

					jQuery.each($elements, function() {
						var $el = jQuery(this),
							index_position,
							new_position;

						if ($el.attr('type') == 'checkbox') {
							jQuery.each(['name','id'], function(k, field) {
								index_position = $el.attr(field).indexOf('VPM_CLONE_INDEX');
								if ( index_position > 0 ) {
									new_val = [$el.attr(field).slice(0, index_position), multiple_index, $el.attr(field).slice(index_position + 15)].join('');
									$el.attr(field, new_val);
								}
							});
						}
						jQuery.each($el.get(0).attributes, function() {
							if (this.value == elId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
							if (this.value == wrapperId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
						});

					});

					template.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index)
						.removeClass('hidden');

					del_button = jQuery('.vpm_multiple-delete').first().clone()
						.attr('data-wrapper', wrapperId).data('wrapper', wrapperId)
						.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index);

					$addButton.before(del_button).before(template);
				};
			}
		</script>
		<?php
	}

	/**
	 * @method display_checkbox
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_checkbox( $field, $current_value, $post_id ) {
		$val = ( ! empty( $field->args['value'] ) ? $field->args['value'] : 'on' );
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="vpm_field vpm_field_checkbox <?php echo esc_attr( $field->get_wrapper_id() ); ?> " data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label( $field ); ?>
			<?php if ( empty( $field->args['options'] ) ): ?>
				<?php $checked = checked( in_array( $val, $current_value ), true, false ); ?>
				<input type="checkbox" id="<?php echo esc_attr( $field->get_input_id( $field->index ) ); ?>" name="<?php echo esc_attr( $field->get_name() ) ?>" value="<?php echo esc_attr( $val ) ?>" <?php echo $checked; ?> />
			<?php else: ?>
				<?php foreach ($field->args['options'] as $key => $value): ?>
					<?php $checked = checked( in_array( $key, $current_value ) , true, false ); ?>
					<label style="display:<?php echo $field->item_format; ?>" class="voce-meta-checkbox">
						<input type="checkbox" id="<?php echo esc_attr( $field->get_input_id( $field->index ) . '_' . $key ); ?>" name="<?php echo esc_attr( $field->get_name( $field->index ) ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php echo $checked; ?> /><?php echo wp_kses( $value, Voce_Meta_API::GetInstance()->label_allowed_html ); ?>
					</label>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}

	/**
	 * @method display_checkbox_template
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_checkbox_template( $field, $current_value, $post_id ) {
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id() ); ?>" class="vpm_field vpm_field_checkbox <?php echo esc_attr( $field->get_wrapper_id() ); ?> ">
			<?php echo self::get_label( $field ); ?>
			<?php if ( empty( $field->args['options'] ) ): ?>
				<input type="checkbox" id="<?php echo esc_attr( $field->get_input_id() ); ?>" name="<?php echo esc_attr( $field->get_name() ) ?>" />
			<?php else: ?>
				<?php foreach ($field->args['options'] as $key => $value): ?>
					<label style="display:<?php echo $field->item_format; ?>" class="voce-meta-checkbox">
						<input type="checkbox" id="<?php echo esc_attr( $field->get_input_id( 'VPM_CLONE_INDEX' ) . '_' . $key ); ?>" name="<?php echo esc_attr( $field->get_name( 'VPM_CLONE_INDEX' ) . '[' . $key . ']') ?>" /><?php echo wp_kses( $value, Voce_Meta_API::GetInstance()->label_allowed_html ); ?>
					</label>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}
}


class Voce_Meta_Field_Radio extends Voce_Meta_Field {

	public $item_format;

	/**
	 * @constructor
	 *
	 * @param string $type
	 * @param string|array $group
	 * @param integer $id
	 * @param string $label
	 * @param array $args
	 */
	public function __construct( $type, $group, $id, $label, $args = array() ) {
		parent::__construct( $type, $group, $id, $label, $args );

		$this->multiple = false;

		$this->item_format = ( ! empty( $this->args['item_format'] ) && in_array( $this->args['item_format'], array( 'inline', 'block' ) ) ? $this->args['item_format'] : 'block' );
	}

	/**
	 * @method render_clone_radio_js
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_clone_radio_js( $field, $post_id ) {
		?>
		<script type="application/javascript">
			if (typeof window.vpm_clone_radio === 'undefined') {
				window.vpm_clone_radio = function(addButton) {
					var $addButton = jQuery(addButton),
						wrapperId = $addButton.data('wrapper'),
						elId = $addButton.data('id'),
						template = jQuery('#' + wrapperId).clone(),
						$elements = template.add(template.find('*')),
						multiple_index = +$addButton.data('multiple_index'),
						del_button;

					$addButton.attr('data-multiple_index', multiple_index + 1).data('multiple_index', multiple_index + 1);

					jQuery.each($elements, function() {
						var $el = jQuery(this),
							index_position,
							new_position;

						if ($el.attr('type') == 'radio') {
							jQuery.each(['name','id'], function(k, field) {
								index_position = $el.attr(field).indexOf('VPM_CLONE_INDEX');
								if ( index_position > 0 ) {
									new_val = [$el.attr(field).slice(0, index_position), multiple_index, $el.attr(field).slice(index_position + 15)].join('');
									$el.attr(field, new_val);
								}
							});
						}
						jQuery.each($el.get(0).attributes, function() {
							if (this.value == elId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
							if (this.value == wrapperId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
						});

					});

					template.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index)
						.removeClass('hidden');

					del_button = jQuery('.vpm_multiple-delete').first().clone()
						.attr('data-wrapper', wrapperId).data('wrapper', wrapperId)
						.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index);

					$addButton.before(del_button).before(template);
				};
			}
		</script>
		<?php
	}

	/**
	 * @method display_radio
	 *
	 * @param iVoce_Meta_Field $field
	 * @param mixed $current_value
	 * @param integer $post_id
	 */
	public static function display_radio( $field, $current_value, $post_id ) {
		$item_format = ! empty( $field->args['item_format'] ) && in_array( $field->args['item_format'], array( 'inline', 'block' ) ) ? $field->args['item_format'] : 'block';
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="vpm_field vpm_field_radio <?php echo esc_attr( $field->get_wrapper_id() ); ?>" data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label( $field ); ?>
			<?php foreach ($field->args['options'] as $key => $value): ?>
				<label style="display:<?php echo $item_format; ?>" class="voce-meta-radio">
					<input type="radio" id="<?php echo esc_attr( $field->get_input_id( $field->index ) . '_' . $key ); ?>" name="<?php echo esc_attr( $field->get_name( $field->index ) ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current_value, $key ); ?> /><?php echo wp_kses( $value, Voce_Meta_API::GetInstance()->label_allowed_html ); ?>
				</label>
			<?php endforeach; ?>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}

	/**
	 * @method display_radio_template
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_radio_template( $field, $current_value, $post_id ) {
		$item_format = ! empty( $field->args['item_format'] ) && in_array( $field->args['item_format'], array( 'inline', 'block' ) ) ? $field->args['item_format'] : 'block';
		?>
		<input type="hidden" name="<?php echo esc_attr( $field->get_name() ); ?>" />
		<p id="<?php echo esc_attr( $field->get_wrapper_id() ); ?>" class="vpm_field vpm_field_radio <?php echo esc_attr( $field->get_wrapper_id() ); ?>">
			<?php echo self::get_label( $field ); ?>
			<?php foreach ($field->args['options'] as $key => $value): ?>
				<label style="display:<?php echo $item_format; ?>" class="voce-meta-radio">
					<input type="radio" id="<?php echo esc_attr( $field->get_input_id( 'VPM_CLONE_INDEX' ) . '_' . $key ); ?>" name="<?php echo esc_attr( $field->get_name( 'VPM_CLONE_INDEX' ) ); ?>" value="<?php echo esc_attr( $key ); ?>" /><?php echo wp_kses( $value, Voce_Meta_API::GetInstance()->label_allowed_html ); ?>
				</label>
			<?php endforeach; ?>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}
}


class Voce_Meta_Field_WP_Editor extends Voce_Meta_Field {

	/**
	 * @method init
	 */
	public static function init() {
		add_filter( 'wp_ajax_vpm_add_wp_editor', function() {
			$field_id = $_POST['field_id'];
			$args = $_POST['field_args'];

			wp_editor( '', $field_id, $args );
			exit;
		} );
	}

	/**
	 * Returns field unique input id
	 *
	 * @return string Returns input id used on the form element
	 */
	public function get_input_id( $index = '' ){
		$group_id = $this->group->id;
		if ( $this->fieldset ) {
			$index = (int)$this->fieldset->index;
			$group_id = "{$this->group->id}__{$this->fieldset->id}-{$index}_";
		}

		return "{$group_id}_{$this->id}" . ( $index ? "_{$index}" : '' );
	}

	/**
	 * @method render_clone_wp_editor_js
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_clone_wp_editor_js( $field, $post_id ) {
		?>
		<script type="application/javascript">
			if (typeof window.vpm_clone_wp_editor === 'undefined') {
				window.vpm_clone_wp_editor = function(addButton) {
					var $addButton = jQuery(addButton),
						wrapperId = $addButton.data('wrapper'),
						elId = $addButton.data('id'),
						template = jQuery('#' + wrapperId).clone(),
						$elements = template.add(template.find('*')),
						multiple_index = +$addButton.data('multiple_index'),
						del_button;

					$addButton.attr('data-multiple_index', multiple_index + 1).data('multiple_index', multiple_index + 1);

					jQuery.each($elements, function() {
						var $el = jQuery(this);

						jQuery.each($el.get(0).attributes, function() {
							if (this.value == elId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
							if (this.value == wrapperId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
						});
					});

					template.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index)
						.removeClass('hidden');

					del_button = jQuery('.vpm_multiple-delete').first().clone()
						.attr('data-wrapper', wrapperId).data('wrapper', wrapperId)
						.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index);

					$addButton.before(del_button).before(template);

					jQuery.ajax({
						dataType: 'html',
						async: false,
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'vpm_add_wp_editor',
							field_id: elId + '_' + multiple_index,
							field_args: vpm_clone_wp_editor_args[elId]
						},
						success: function (resp) {
							if (resp) {
								jQuery('#'+wrapperId+'-'+multiple_index+' .wp-editor-wrapper').html(resp);
							}

							// init tinymce
							quicktags({id : elId + '_' + multiple_index});
							tinymce.execCommand( 'mceAddEditor', false, elId + '_' + multiple_index );
						}
					});
				};
			}
		</script>
		<?php
	}

	/**
	 * @method display_wp_editor
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_wp_editor( $field, $current_value, $post_id ) {
		$field->args['wp_editor_args']['textarea_name'] = $field->get_name();
		?>
		<div id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="vpm_field vpm_field_wp_editor <?php echo esc_attr( $field->get_wrapper_id() ); ?>" data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label($field); ?>
			<div class="wp-editor-wrapper">
				<?php wp_editor( $current_value, $field->get_input_id( $field->index ), $field->args['wp_editor_args'] ); ?>
			</div>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</div>
		<?php
	}

	/**
	 * @method display_wp_editor_template
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_wp_editor_template( $field, $current_value, $post_id ) {
		$field->args['wp_editor_args']['textarea_name'] = $field->get_name();
		?>
		<input type="hidden" name="<?php echo esc_attr( $field->get_name() ); ?>" />
		<div id="<?php echo esc_attr( $field->get_wrapper_id() ); ?>" class="vpm_field vpm_field_wp_editor <?php echo esc_attr( $field->get_wrapper_id() ); ?>">
			<?php echo self::get_label($field); ?>
			<div class="wp-editor-wrapper"></div>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</div>
		<script type="application/javascript">
			var vpm_clone_wp_editor_args = vpm_clone_wp_editor_args || [];

			vpm_clone_wp_editor_args['<?php echo esc_js( $field->get_input_id() ); ?>'] = <?php echo json_encode( $field->args['wp_editor_args'] ); ?>
		</script>
		<?php
	}

	/**
	 * @method sanitize_wp_editor
	 *
	 * @param string $field
	 * @param type $old_value
	 * @param type $new_value
	 * @param integer $post_id
	 *
	 * @return mixed
	 */
	public static function sanitize_wp_editor( $field, $old_value, $new_value, $post_id ) {
		return wp_kses( $new_value, wp_kses_allowed_html( 'post' ) );
	}
}

Voce_Meta_Field_WP_Editor::init();


/**
 * @method add_metadata_group
 *
 * @param string $id
 * @param string $title
 * @param array $args
 *
 * @return Voce_Meta_Group
 */
function add_metadata_group( $id, $title, $args = array() ) {
	return Voce_Meta_API::GetInstance()->add_group( $id, $title, $args );
}

/**
 * @method add_metadata_field
 *
 * @param string|array $group
 * @param integer $id
 * @param string $label
 * @param string $type
 * @param array $args
 *
 * @return boolean
 */
function add_metadata_field( $group, $id, $label, $type = 'text', $args = array() ) {
	$api = Voce_Meta_API::GetInstance();
	if ( is_array( $group ) && 2 == count( $group ) ) {
		$fieldset = end( $group );
		$group = reset( $group );
	}
	if ( isset( $api->groups[ $group ] ) ) {
		$func = "add_field_{$type}";
		if ( empty( $fieldset ) ) {
			return $api->groups[ $group ]->$func( $id, $label, $args );
		}
		if ( ! empty( $api->groups[ $group ]->fields[ $fieldset ] ) ) {
			return $api->groups[ $group ]->fields[ $fieldset ]->$func( $id, $label, $args );
		}
	}

	return false;
}

/**
 * @method remove_metadata_field
 *
 * @param string|array $group
 * @param integer $id
 *
 * @return boolean
 */
function remove_metadata_field( $group, $id ) {
	$api = Voce_Meta_API::GetInstance();
	if ( is_array( $group ) && 2 == count( $group ) ) {
		$fieldset = end( $group );
		$group = reset( $group );
	}
	if ( isset( $api->groups[ $group ] ) ) {
		if ( empty( $fieldset ) ) {
			return $api->groups[ $group ]->remove_field( $id );
		}
		if ( ! empty( $api->groups[ $group ]->fields[ $fieldset ] ) ) {
			return $api->groups[ $group ]->fields[ $fieldset ]->remove_field( $id );
		}
	}

	return false;
}

/**
 * @method get_vpm_value
 *
 * @param string|array $group
 * @param string $field
 * @param integer $post_id
 */
function get_vpm_value( $group, $field, $post_id = false, $single = true ){
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	return Voce_Meta_API::GetInstance()->get_meta_value( $post_id, $group, $field, $single );
}

endif;
