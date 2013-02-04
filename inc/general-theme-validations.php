<?php

/**
 * Class for all static validations to be covered by regex
 * @author nofearinc
 *
 */
class General_Theme_Validations {
	public $template_validations = array();
	public $include_validations = array();
	public $common_validations = array();
	
	public function __construct() {
		
		$this->common_validations = array(
			'/<script/' => __( 'Script tags should be included on wp_enqueue_scripts or admin_enqueue_scripts instead of embedded directly', 'dx_theme_mentor' ),
			'/<link(.+)style/' => __( 'Styles should be included on wp_enqueue_scripts or admin_enqueue_scripts instead of embedded directly', 'dx_theme_mentor' ),
			'/<.id=(.*)>/' => 'id check',
		);
	}
}