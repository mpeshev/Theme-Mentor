<?php
/**
 * Plugin Name: Theme Mentor
 * Description: Theme Mentor is a cousing of the Theme-Check plugin getting deeper into the code analysis. 
 * It's using different approaches to monitor for common problems regarding theme reviews from the
 * WordPress Theme Reviewers Team. It is prone to fault analysis, so use only as a reference for improving
 * your code base even further.
 * Author: nofearinc
 * 
 */

define( 'TM_PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'TM_PLUGIN_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'TM_INC_PATH', trailingslashit( TM_PLUGIN_PATH . 'inc' ) );
define( 'TM_INC_URL', trailingslashit( TM_PLUGIN_URL . 'inc' ) );


// if isset the option for complex checks, load them as well in the process of evaluation

class Theme_Mentor {
	
	private $templates = array();
	private $includes = array();
	private $theme_path = '';
	
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'theme_mentor_page' ) );
		// TODO: temporary
// 		$this->do_everything();
	}
	
	public function run_tests( ) {
		// $this->theme_path = trailingslashit( '/opt/lampp/htdocs/wpreview/wp-content/themes/tampa' ); // TODO: get theme path from options
		
		// all the heavy lifting for picking up proper files from the theme folder
		// for templates and includes, that is
		$this->iterate_theme_folder( $this->theme_path, 0 );
		
		// swap functions.php as it's include-alike
		$functions_file = $this->theme_path . 'functions.php';
		foreach( $this->templates as $index => $template ) {
			if( $template === $functions_file )
				unset( $this->templates[ $index ] );
		}
		$this->includes[] = $this->theme_path . 'functions.php';
		
		include TM_INC_PATH . 'general-theme-validations.php';
		$general_validations = new General_Theme_Validations();
		
		// iterate all templates
		foreach( $this->templates as $index => $template ) {
			// only unique theme stuff
			$template_unique_only = str_replace( $this->theme_path , '',  $template );
				
			// read the files, keep the file number as it matters, you know
			$file = file( $template , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			if( false === $file ) { continue; }
				
			// General
			foreach( $general_validations->common_validations as $pattern => $message ) {
				$this->iterate_data( $pattern, $message, $template_unique_only, $file );
			}
			
			foreach( $general_validations->template_validations as $pattern => $message ) {
				$this->iterate_data( $pattern, $message, $template_unique_only, $file );
			}
		}
		
		// iterate includes
		foreach( $this->includes as $index => $functional ) {
			// only unique theme stuff
			$functional_unique_only = str_replace( $this->theme_path , '',  $functional );
		
			// read the files, keep the file number as it matters, you know
			$file = file( $functional , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			if( false === $file ) { continue; }
		
			// General
			foreach( $general_validations->common_validations as $pattern => $message ) {
				$this->iterate_data( $pattern, $message, $functional_unique_only, $file );
			}
				
			foreach( $general_validations->include_validations as $pattern => $message ) {
				$this->iterate_data( $pattern, $message, $functional_unique_only, $file );
			}
		}
	}
	
	/**
	 * Adapt the Theme Mentor page
	 */
	public function theme_mentor_page() {
		$page = add_theme_page( 'Theme Mentor', 'Theme Mentor', 'manage_options', 'theme_mentor', array( $this, 'theme_mentor_page_cb' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'styles_theme_mentor' ) );
	}
	
	public function theme_mentor_page_cb() {
		if( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'We all know you shouldn\'t be here', 'dx_theme_mentor' ) );
		}
		
		// get stylesheet to pick the selected theme
		$stylesheet = get_stylesheet();
		// default activated theme is selected atfirst
		$selected = $stylesheet;
		// list all themes
		$themes = wp_get_themes();

		echo '<div class="wrap">';
		echo '<div id="icon-edit" class="icon32 icon32-base-template"><br></div>';
		echo '<h2>' . __( 'Theme Mentor', 'dx_theme_mentor' ) . '</h2>';
		
		// is the form submitted
		if( isset( $_POST['dx_theme'] ) ) {
			$theme_name = $_POST['dx_theme'];

			if( isset( $themes[$theme_name] ) ) {
				$theme = $themes[$theme_name];
				$this->theme_path = trailingslashit( $theme->get_template_directory() );
				
				// selected is the last submitted to $_POST
				$selected = $theme->get_stylesheet();
				
				$this->run_tests();
			}
		}
		
		do_action( 'dx_theme_mentor_before_admin_page' );

		include_once 'inc/templates/admin-template.php';
		
		do_action( 'dx_theme_mentor_after_admin_page' );
		
		echo '</div>';
	}
	
	/**
	 * Iterate theme folder and assign templates and includes
	 * @param unknown_type $folder
	 * @param unknown_type $level
	 */
	public function iterate_theme_folder( $folder, $level = 0 ) {
		// get all templates
		$folder = trailingslashit( $folder );
		$directory = dir( $folder );
		
		while ( false !== ( $entry = $directory->read() ) ) {
			// drop all empty folders, hidden folders/files and parents
			if ( ( $entry[0] == "." ) ) continue;
			
			// includes should be there
			if( is_dir( $folder . $entry ) ) {
				// iterate the next level
				$this->iterate_theme_folder( $folder . $entry, $level + 1 );
			} else {
				// read only PHP files
				if( substr( $entry , -4, 4 ) === '.php' ) {
					if( $level === 0 ) {
						// templates on level 0
						$this->templates[] = $folder . $entry;
					} else {
						// includes
						$this->includes[] = $folder . $entry;
					}
				}
			}
		}
	}
	
	/**
	 * Do the regex for the possibly dangerous snippets 
	 * 
	 * @param regex $pattern
	 * @param error message text $message
	 * @param path to file when something happened $file_path
	 * @param file to run after $file
	 */
	public function iterate_data( $pattern, $message, $file_path, $file ) {
		$lines_found = preg_grep( $pattern, $file );
		if( ! empty( $lines_found ) ) {
			foreach( $lines_found as $line => $snippet ) {
				printf( '<div class="tm_report_row"><span class="tm_message">%s</span> at file <span class="tm_file">%s</span>, line <span class="tm_line">%d</span>: <span class="tm_snippet">%s</span></div>', 
						$message, $file_path, $line, esc_html( $snippet ) );
			}
		}
	}
	
	// list all errors
	
	// lookout for the errors
	// admin panel for theme list
	
	public function styles_theme_mentor() {
		wp_enqueue_style( 'theme-mentor', TM_PLUGIN_URL . 'css/theme-mentor.css' );
	}
}

new Theme_Mentor();