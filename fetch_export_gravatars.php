<?php
/*
Plugin Name: Gravatar Badge Creation
Plugin URI: https://github.com/devenbansod/Camptix---Automatic-Gravatar-Fetch-and-Export
Description: Automate Gravatar Fetching for Badge Creation in InDesign - an Addon for CampTix 
Version: 0.1
Author: Deven Bansod
Author URI: http://www.facebook.com/bansoddeven
License: GPL2
*/

define( 'FETCH_GRAVATARS_PLUGIN_URL' ,  plugin_dir_path( __FILE__ ) );


/**
 * Automate Gravatar Fetching and Export Selected Columns Addon for CampTix 
 */
class CampTix_Addon_Gravatar_Fetch extends CampTix_Addon {


	// Stores all the Columns available to export 
	public $columns_available = array();
	
	/**
	 * Runs during camptix_init, see CampTix_Addon
	 */
	function camptix_init() {
		global $camptix;
		add_filter( 'camptix_menu_tools_tabs', array( $this, 'menu_tools_fetch_gravatars' ) );
		add_filter( 'camptix_default_addons', array( $this, 'add_to_default_addon' ) );
		add_action( 'camptix_menu_tools_fetch-export-gravatars', array( $this, 'menu_tools_fetch_export_gravatars' ), 10, 0 );
		add_action( 'load-tix_ticket_page_camptix_tools', array( $this, 'export_admin_init' ) ); // same here, but close
	}

	/**
	 * Add 'Export Selected Columns' Tab to Tickets > Tools
	 * 
	 */
	function menu_tools_fetch_gravatars( $types ) {
		return array_merge( $types, array(
			'fetch-export-gravatars' => __( 'Fetch and Export Gravatars',  'fetch-export-gravatars' ), 
		) );
	}

	/**
	 * Add 'Export Selected Columns' Tab to Tickets > Tools
	 * 
	 */
	function add_to_default_addon( $default_addons ) {
		return array_merge( $default_addons, array(
			'fetch-export-gravatars' => FETCH_GRAVATARS_PLUGIN_URL . "fetch_export_gravatars.php", 
		) );
	}

	/**
	 * Fetch and Export Gravatars tools menu
	 * @see export_admin_init()
	 */
	function menu_tools_fetch_export_gravatars() {
		?>
		<form method="post" action="<?php echo esc_url( add_query_arg( 'tix_export', 1 ) ); ?>">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php _e( 'Export attendees data to CSV', 'camptix' ); ?></th>
						
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Select the Columns to export', 'camptix' ); ?></th>
					</tr>	
					<tr>
						<?php 
							$columns = array(
								'id' => __( 'Attendee ID', 'camptix' ),
								'ticket' => __( 'Ticket Type', 'camptix' ),
								'first_name' => __( 'First Name', 'camptix' ),
								'last_name' => __( 'Last Name', 'camptix' ),
								'email' => __( 'E-mail Address', 'camptix' ),
								'date' => __( 'Purchase date', 'camptix' ),
								'modified_date' => __( 'Last Modified date', 'camptix' ),
								'status' => __( 'Status', 'camptix' ),
								'txn_id' => __( 'Transaction ID', 'camptix' ),
								'coupon' => __( 'Coupon', 'camptix' ),
								'buyer_name' => __( 'Ticket Buyer Name', 'camptix' ),
								'buyer_email' => __( 'Ticket Buyer E-mail Address', 'camptix' ),
							);

							$columns_default_checked = array(
								'first_name' => __( 'First Name', 'camptix' ),
								'last_name' => __( 'Last Name', 'camptix' ),
								'email' => __( 'E-mail Address', 'camptix' ),
							);

							$extra_columns = apply_filters( 'camptix_attendee_report_extra_columns', array() );
							$columns = array_merge( $columns, $extra_columns );

							$field_no = 0;
							
							foreach ( $columns as $column ) {
								
								if ( $field_no != 0 && $field_no % 4 != 0) {
									if ( in_array ( $column , $columns_default_checked ) ){
									?>
										<td><label><input type="checkbox" checked name="tix_export_cols[<?php echo $field_no;?>]" value="1" /> <?php _e( $column, 'camptix' ); ?></label></td>			
							<?php 
										$key = array_search( $column , $columns );
										array_push( $this->columns_available , $key );  
								
									}
									else {
							?>
									<td><label><input type="checkbox" name="tix_export_cols[<?php echo $field_no?>]" value="1" /> <?php _e( $column, 'camptix' ); ?></label></td>
							<?php	
										$key = array_search( $column , $columns );
										array_push( $this->columns_available , $key );
									}
								}
								else {
									if ( in_array ( $column , $columns_default_checked ) ){
							?>
									</tr><tr><td><label><input type="checkbox" checked name="tix_export_cols[<?php echo $field_no?>]" value="1" /> <?php _e( $column, 'camptix' ); ?></label></td>
									<?php 
										$key = array_search( $column , $columns );
										array_push( $this->columns_available , $key );
								}
								else {
							?>
									</tr><tr><td><label><input type="checkbox" name="tix_export_cols[<?php echo $field_no?>]" value="1" /> <?php _e( $column, 'camptix' ); ?></label></td>
							<?php
										$key = array_search( $column , $columns );
										array_push( $this->columns_available , $key );
									}
								}
								$field_no++;
							}		
							
						?>
							
						
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Select Questions to export', 'camptix' ); ?></th>
						<?php 
						global $camptix;
						$questions = $camptix->get_all_questions();

						$field_no = 0;						
						foreach ( $questions as $question ) {
							if ( get_post_meta( $question->ID, 'tix_type', true ) == 'twitter' ) 
								$columns_default_checked[ 'tix_export_q_' . $question->ID ] = $question->post_title; 
					
							if ( $field_no != 0 && $field_no % 4 != 0) {
									if ( in_array ( $question->post_title , $columns_default_checked ) ){
									?>
										<td><label><input type="checkbox" checked name="tix_export_questions[<?php echo $question->ID?>]" value="1" /> <?php _e( $question->post_title, 'camptix' ); ?></label></td>
									<?php
									}else {
							?>
									<td><label><input type="checkbox" name="tix_export_questions[<?php echo $question->ID?>]" value="1" /> <?php _e( $question->post_title, 'camptix' ); ?></label></td>
							<?php	
									
									}
								}
								else {
									if ( in_array ( $question->post_title , $columns_default_checked ) ){
							?>
									</tr><tr><td><label><input type="checkbox" checked name="tix_export_questions[<?php echo $question->ID?>]" value="1" /> <?php _e( $question->post_title, 'camptix' ); ?></label></td>
									
							<?php
								}
								else {
							?>
									</tr><tr><td><label><input type="checkbox" name="tix_export_questions[<?php echo $question->ID?>]" value="1" /> <?php _e( $question->post_title, 'camptix' ); ?></label></td>
							<?php
									
									}
								}
								$field_no++;
						}
						?>
					</tr>
					<tr>
						<th><?php _e('Generate CSV for InDesign', 'camptix'); ?></th>
						<td><label><input type="checkbox" name="tix_export_include_gravatars" value="1" /> <?php _e('Include Gravatars' , 'camptix'); ?></label></td>
					</tr>
					<tr>
						<th><?php _e('Enter Folder/ Directory Path relative to WP ABSPATH to store the temp Zip File '); ?></th>
						<td><input type="textbox" class="regular-text" name="tix_export_path_to_zip" value="output/camptix-gravatar-badges/" placeholder="Ex : /folder_for_zips/"/>
						<?php _e( '<br>Please make sure the Directory exists <br>and it is accessible to the server');?>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<?php wp_nonce_field( 'tix_export' ); ?>
				<input type="hidden" name="tix_export_submit" value="1" />
				<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Generate CSV/ ZIP', 'camptix' ); ?>" />
			</p>
		</form>
		<?php
	}

	/**
	 * Fired at almost admin_init, used to serve the export download file.
	 * @see menu_tools_export()
	 */
	function export_admin_init() {
		global $post, $camptix;
		$errors = array();
		if ( ! current_user_can( $camptix->caps['manage_tools'] ) || 'fetch-export-gravatars' != $camptix->get_tools_section() )
			return;

		if ( isset( $_POST['tix_export_submit'] )  && isset( $_GET['tix_export'] ) ) {
			
			if ( isset( $_POST['tix_export_include_gravatars'] )  && !isset ( $_POST[ 'tix_export_path_to_zip' ] ) ) {
				
				$errors[] = __( 'Please select a Folder Path for ZIP file export.', 'camptix' );
			}

			if ( ! isset( $_POST[ 'tix_export_cols' ] ) || count( $_POST[ 'tix_export_cols' ] ) == 0 )
				$errors[] = __( 'Please Select atleast one column to Export.', 'camptix' );

			if ( isset( $_POST['tix_export_include_gravatars'] )  && isset ( $_POST[ 'tix_export_path_to_zip' ] ) && ! is_dir( ABSPATH . $_POST[ 'tix_export_path_to_zip' ] ) )
				$errors[] = __( 'Please enter a proper Path. Also, make sure it is accessible to the server', 'camptix' );


			if ( ( count( $errors ) == 0 ) ) {

				$format = 'csv';
					
				$selected_columns = array();
				$selected_columns = array_map( 'strval', (array) $_POST['tix_export_cols'] );

				$selected_questions = array();
				$selected_questions = array_map( 'strval', (array) $_POST['tix_export_questions'] );

				$this->generate_attendee_report_with_gravatars( $format , $selected_columns , $selected_questions );
				die();
			}
			else {
				if ( count( $errors ) > 0 )
					foreach ( $errors as $error )
					add_settings_error( 'camptix', false, $error );	
			}

		}
	}		

	/*
	 * Generate and return the raw attendee report contents with or without Gravatars
	 */
	function generate_attendee_report_with_gravatars( $format , $selected_columns , $selected_questions ) {
		global $camptix;

		// Configuration
		$gravatar_dir = ABSPATH . $_POST[ 'tix_export_path_to_zip' ];

		// Boolean to Replace Empty Twitter Account with the First Name of Attendee
		$replace_empty_twitter = false;

		if ( isset( $_POST[ 'tix_export_path_to_zip' ] ))
			$path_to_zip = $_POST[ 'tix_export_path_to_zip' ];
		else
			$path_to_zip = get_option('camptix_zip_folder_path');

		$time_start = microtime( true );

		$columns_nos = (array) array_keys( $selected_columns );

		$columns = array();

		$this->columns_available = $this->get_all_columns_available();
		$this->columns_available = array_values( $this->columns_available );
		
		$questions_selected = array_keys( $selected_questions );

		$question_titles = array();
		foreach ( $questions_selected as $question_id ) {
			array_push( $question_titles , get_the_title( $question_id ));
		}

		$column_key = 0;
		foreach ( $columns_nos as $columns_no ) {
			$columns[ $column_key ] = $this->columns_available[ $columns_no ];
			$column_key++;	
		}

		$all_column_headers = array_merge( $columns , $question_titles ); 

		if ( isset( $_POST[ 'tix_export_include_gravatars' ] ) )
			array_push( $all_column_headers, '@Gravatars' );

		ob_start();
		
		// For CSV Download	
		$report = fopen( "php://output", 'w' );
		
		// For CSV to be included in ZIP Download
		$filename_2 = sprintf( 'camptix-export-%s.csv', date( 'Y-m-d' ));
		$report_2 = fopen( $gravatar_dir . $filename_2, 'w' );

		fputcsv( $report, $all_column_headers );
		fputcsv( $report_2, $all_column_headers );

		$paged = 1;
		while ( $attendees = get_posts( array(
			'post_type' => 'tix_attendee',
			'post_status' => array( 'publish', 'pending' ),
			'posts_per_page' => 200,
			'paged' => $paged++,
			'orderby' => 'ID',
			'order' => 'ASC',
			'cache_results' => false,
		) ) ) {

			$attendee_ids = array();	
			foreach ( $attendees as $attendee )
				$attendee_ids[] = $attendee->ID;


			$camptix->filter_post_meta = $camptix->prepare_metadata_for( $attendee_ids );
			unset( $attendee_ids, $attendee );

			foreach ( $attendees as $attendee ) {
				$attendee_id = $attendee->ID;

				$buyer = get_posts( array(
					'post_type'      => 'tix_attendee',
					'post_status'    => array( 'publish', 'pending' ),
					'posts_per_page' => 1,
					'orderby'        => 'ID',
					'order'          => 'ASC',

					'meta_query'     => array(
						array(
							'key'    => 'tix_access_token',
							'value'  => get_post_meta( $attendee->ID, 'tix_access_token', true ),
						),
					),
				) );

				$attendee_details = array(
					'id' => $attendee_id,
					'ticket' => $camptix->get_ticket_title( intval( get_post_meta( $attendee_id, 'tix_ticket_id', true ) ) ),
					'first_name' => get_post_meta( $attendee_id, 'tix_first_name', true ),
					'last_name' => get_post_meta( $attendee_id, 'tix_last_name', true ),
					'email' => get_post_meta( $attendee_id, 'tix_email', true ),
					'date' => mysql2date( 'Y-m-d g:ia', $attendee->post_date ),
					'modified_date' => mysql2date( 'Y-m-d g:ia', $attendee->post_modified ),
					'status' => ucfirst( $attendee->post_status ),
					'txn_id' => get_post_meta( $attendee_id, 'tix_transaction_id', true ),
					'coupon' => get_post_meta( $attendee_id, 'tix_coupon', true ),
					'buyer_name' => empty( $buyer[0]->post_title ) ? '' : $buyer[0]->post_title,
					'buyer_email' => get_post_meta( $attendee_id, 'tix_receipt_email', true ),
				);

				$line = array();

				$index = 0;
				// Take the Needed Attendee Details and leave everything else
				foreach ( $columns as $column ) {
					
					if ( array_key_exists( $column , $attendee_details ) ){
						if ( $column == 'email' ) 
							$email_index = $index;
						if ( $column == 'first_name' ) 
							$fname_index = $index;
						if ( $column == 'last_name' ) 
							$lname_index = $index;
						array_push( $line, $attendee_details[ $column ] );
					}
					$index++;

				}				

				$answers = (array) get_post_meta( $attendee_id, 'tix_questions', true );

				foreach ( $questions_selected as $question ) {

				
					if ( trim( get_the_title( $question ) == 'Twitter URL' ) ) {
								if ( ! isset ( $answers[ $question] ) )
									$twitter_url = ''; 
								else 
									$twitter_url = $answers[ $question ];

								// If they don't have a Twitter handle, add their first name instead. 
								//Add a @ to Twitter handles to avoid confusing them with first names.
								if ( empty ( $twitter_url ) ) {
									if ( $replace_empty_twitter ) {
										$answers [ $question ] = $line[0];
									}
								} else {
									// Strip out everything but the username, and prefix a @
									$answers [ $question ] = '@' . preg_replace(
										'/
											(https?:\/\/)?
											(twitter\.com\/)?
											(@)?
										/ix',
										'',
										$twitter_url
									);
								}
					}

					// For multiple checkboxes	
					if ( isset( $answers[ $question ] ) && is_array( $answers[ $question ] ) )
						$answers[ $question ] = implode( ', ', (array) $answers[ $question ] );

						if( isset( $answers [ $question ] ) ) {
							array_push( $line, $answers[ $question ] );		
						}
						else {
							array_push( $line, '' );
						}

				}

				$extra_columns = apply_filters( 'camptix_attendee_report_extra_columns', array() );

				foreach ( $extra_columns as $index => $label ) {
					$line[ $index ] = apply_filters( 'camptix_attendee_report_column_value_' . $index, '', $attendee );
					$line[ $index ] = apply_filters( 'camptix_attendee_report_column_value', $line[ $index ], $index, $attendee );
				}


				if ( isset( $_POST['tix_export_include_gravatars'] ) ) {
					
					array_push( $all_column_headers, '@Gravatar');
					// Create empty badges for unknown attendees
					if ( ( 'unknown.attendee@example.org' == $line[ $email_index ] ) ) {
						$line = array(); 
					}

					$filename_jpg = 'mystery-man.jpg';

					// Download the Gravatar
					if ( $line[ $email_index ] ) {
						$gravatar_source = wp_remote_get( 'http://www.gravatar.com/avatar/' . md5( strtolower( trim( $line[ $email_index ] ) ) ) . '.jpg?s=512&default=404' );
						if ( ! is_wp_error( $gravatar_source ) && 200 == $gravatar_source['response']['code'] ) {
							
							$filename_jpg      = strtolower( remove_accents( $line[ $fname_index ] . '-' . $line[ $lname_index ] ) );
							$filename_jpg      = sanitize_file_name( '-' . $filename_jpg . '.jpg' );
							$gravatar_file = fopen( $gravatar_dir . '/' . $filename_jpg, 'w' );
							fwrite( $gravatar_file, $gravatar_source['body'] );
							fclose( $gravatar_file );

						}
					}

					// Update the final CSV
					array_push( $line, $gravatar_dir . $filename_jpg );
				}

				// Make sure every column is printed.
				$clean_line = array();
				foreach ( $all_column_headers as $key => $caption )
					$clean_line[$key] = isset( $line[$key] ) ? $line[$key] : '';

				fputcsv( $report, $clean_line );
				fputcsv( $report_2, $clean_line );

			}


		 	/**
		 	 * Don't forget to clear up the used meta sort-of cache.
		 	 */
			$camptix->filter_post_meta = false;
		}

		
		fclose( $report );
		fclose( $report_2 ); 
		$report = ob_get_clean();
		
		if ( !isset( $_POST['tix_export_include_gravatars'] ) ) {
			export_to_csv( $report );
			return $report; 
		}
		else {

			$format_zip = 'zip';
			$filename_zip = sprintf( 'camptix-export-%s.%s', date( 'Y-m-d' ), $format_zip );

			$camptix->log( sprintf( 'Finished %s data export in %s seconds.', $format, microtime(true) - $time_start ) );

			$files_in_gravatar_dir = scandir( $gravatar_dir );

			zip_Files_and_Download( $files_in_gravatar_dir , $filename_zip , $gravatar_dir );


			exit();
		}
	}


	function get_all_columns_available(){
		global $camptix;

		$columns = array(
			'id' => __( 'Attendee ID', 'camptix' ),
			'ticket' => __( 'Ticket Type', 'camptix' ),
			'first_name' => __( 'First Name', 'camptix' ),
			'last_name' => __( 'Last Name', 'camptix' ),
			'email' => __( 'E-mail Address', 'camptix' ),
			'date' => __( 'Purchase date', 'camptix' ),
			'modified_date' => __( 'Last Modified date', 'camptix' ),
			'status' => __( 'Status', 'camptix' ),
			'txn_id' => __( 'Transaction ID', 'camptix' ),
			'coupon' => __( 'Coupon', 'camptix' ),
			'buyer_name' => __( 'Ticket Buyer Name', 'camptix' ),
			'buyer_email' => __( 'Ticket Buyer E-mail Address', 'camptix' ),
		);

		$columns = array_keys( $columns );
		$extra_columns = apply_filters( 'camptix_attendee_report_extra_columns', array() );
		$columns = array_merge( $columns, $extra_columns );

		return $columns;

	}	
}
	
	function export_to_csv( $report ) {
		
		$format = 'csv';
				
		$content_type = 'csv';

		$filename = sprintf( 'camptix-export-csv-%s.%s', date( 'Y-m-d' ), $format );
		$filename = basename( $filename );
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( "Cache-control: private" );
		header( 'Pragma: private' );
		header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );

		print_r( $report );
		return;

	}


	function zip_Files_and_Download( $file_names, $archive_file_name, $file_path )	{
		
		$zip = new ZipArchive();

		//create the file and throw the error if unsuccessful
		if ( $zip->open( $archive_file_name, ZIPARCHIVE::CREATE ) !== TRUE ) {
	    	exit("cannot open <$archive_file_name>\n");
	    	return;
		}
		
		//add each files of $file_name array to archive
		foreach( $file_names as $files ) {
			if( !is_dir( $files ) && ( $files !== "." ) && ( $files !== ".." ) && !strpos( $files , 'zip' ) )
	  			$zip->addFile( $file_path . $files, $files );			
		}

		$zip->close();
		
		unset($zip);

		//then send the headers to foce download the zip file
		header("Content-type: application/zip"); 
		header("Content-Disposition: attachment; filename='" . basename( $archive_file_name ) . "'"); 
		header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header( "Cache-control: public" );
		readfile( basename( $archive_file_name ) );
		
		unlink( $archive_file_name );

		$files = scandir( $gravatar_dir ); // get all file names
		foreach( $files as $file ){ // iterate files
		  if( is_file( $file ) )
		    unlink( $file ); // delete file
		}

		clearstatcache();

		return;

	}


// Register this addon, creates an instance of this class when necessary.
camptix_register_addon( 'CampTix_Addon_Gravatar_Fetch' );