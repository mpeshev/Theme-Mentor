<?php

/**
 * Interface for the complex executions
 * 
 * Reads all files during their iteration and gathers data
 * 
 * @author nofearinc
 *
 */
interface Theme_Mentor_Executor {
		/**
		 * Reading the file and collecting data in an array
		 * @param unknown_type $file
		 */
		public function crawl( $file );
		
		/**
		 * Aggregating the data if needed, like stats, some array management, etc
		 * @param unknown_type $file
		 */
		public function execute( $file );
		
		/**
		 * Describe to the regular human being what's going on, if anything
		 */
		public function get_description();
}