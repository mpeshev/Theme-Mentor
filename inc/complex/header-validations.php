<?php

class Header_Validations implements Theme_Mentor_Executor {
private $wp_head_found = false;
	
	// random defaults - need to compare further
	private $wp_head_line = -1;
	private $header_close_tag_line = -1;
	
	private $file = array();
	
	private $error_message = '';
	
	public function crawl( $filename, $file ) { 
		if( false !== strpos( $filename, 'header.php') ) {
			$this->file = $file;
			
			// do the header twist
			$lines_found = preg_grep( '/wp_head\(\)/', $file );
			
			// wp_head found
			// TODO: we need to abstract these 5-liners with 1-liner helper function.
			if( ! empty( $lines_found ) ) {
				$this->wp_head_found = true;
				foreach( $lines_found as $line => $snippet ) {
					$this->wp_head_line = $line;				
				}
			}
			
			// lookup for closing body tag
			if( $this->wp_head_found ) {
				$lines_found = preg_grep( '/<\/head>/', $file );
				
				if( ! empty( $lines_found ) ) {
					foreach( $lines_found as $line => $snippet ) {
						$this->head_close_tag_line = $line;
					}
				}
			}
		} 
	}
	
	/**
	 * Aggregating the data if needed, like stats, some array management, etc
	*/
	public function execute( ) {
		if( -1 != $this->wp_head_line && -1 != $this->head_close_tag_line ) {
			$diff = $this->head_close_tag_line - $this->wp_head_line;
			if( $diff < 0 || $diff > 1 ) {
				// edge case for closing PHP tag between wp_footer and closing body
				if( $diff > 2 ||
						empty( $this->file ) || 
						! isset( $this->file[$this->head_close_tag_line - 1] ) ||
						false === strpos( $this->file[$this->head_close_tag_line - 1], '?>' ) ) {
					$this->error_message = __( 'wp_head call should be right before the closing head tag.', 'dx_theme_mentor' );
				} 
			}
		} else {
			$this->error_message = __( 'No wp_head or closing head tag found', 'dx_theme_mentor' );
		}
	}
	
	/**
	 * Describe to the regular human being what's going on, if anything
	*/
	public function get_description() {
		if( empty( $this->error_message ) ) {
			return '';
		}
		
		return sprintf( '<div class="tm_report_row"><span class="tm_message">%s</span> at file <span class="tm_file">%s</span>, line <span class="tm_line">%d</span></div>',
			$this->error_message, 'header.php', $this->header_close_tag_line );
	}
	
	
}

Theme_Mentor::$validations[] = new Header_Validations();