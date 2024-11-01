<?php
    /**
     * User: twicklund
     * Date: 2/1/2013
       ---Credit given to mgyura for the original code in his SmugSlider plugin
     */


    /*-----------------------------------------------------------------------------------*/
    /* Settings and oAuth approval for SmugMug Embed */
    /*-----------------------------------------------------------------------------------*/

    function SME_smugmugembed_option_settings() {
        global $SME_api, $SME_Helper, $SME_smugmugembed_api, $SME_Settings;
		echo '<div class="wrap">';
        echo '<h2>SmugMug Embed Settings</h2>'; 
		$SME_api_progress = get_option("SME_api_progress");

		if ($SME_api_progress !="Verified") {
		if (!isset($_SESSION['SmugGalReqToken'])) {
			// Step 1: Get a request token using an optional callback URL back to ourselves
			$callback = '//'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['SCRIPT_NAME'].'?page=smugmugembed-settings';;
			$request_token = $SME_api->getRequestToken($callback);
			$_SESSION['SmugGalReqToken'] = serialize($request_token);

			// Step 2: Get the User to login to SmugMug and authorise this demo
			echo '<p>Click <a href="'.$SME_api->getAuthorizeURL(array("Access"=>"Full")).'"><strong>HERE</strong></a> to Authorize This Application.</p>';
		// Alternatively, automatically direct your visitor by commenting out the above line in favour of this:
			//header("Location:".$client->getAuthorizeURL());
		} else {
			$reqToken = unserialize($_SESSION['SmugGalReqToken']);
			unset($_SESSION['SmugGalReqToken']);
			// Step 3: Use the Request token obtained in step 1 to get an access token
			$SME_api->setToken($reqToken['oauth_token'], $reqToken['oauth_token_secret']);
			$oauth_verifier = $_GET['oauth_verifier'];  // This comes back with the callback request.
			$token = $SME_api->getAccessToken($oauth_verifier);  // The results of this call is what your application needs to store.
			update_option('SME_api_progress','Verified');
			update_option('SME_api_token',$token);
		}
		} 
	    $SME_api_progress = get_option('SME_api_progress');
		if ($SME_api_progress =="Verified") {
   /*-----------------------------------------------------------------------------------*/
        /* oAuth process start at the bottom of the page with the last else  */
        /* Now that we have the OAUth credentials we can make a settings page  */
        /* First step is to allow users to filter categories  */
        /*-----------------------------------------------------------------------------------*/

            try {
			  // Get the username of the authenticated user
				$username = $SME_api->get('!authuser')->User->NickName;
				echo "<h3>Logged into SmugMug as <em>$username</em></h3>";

				echo "<div class='SME_Settings_Holder'>";
				$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general_options';
				?>
				<h2>SmugMugEmbed Options</h2>
				<h2 class="nav-tab-wrapper">
				<a href="?page=smugmugembed-settings&tab=general_options" class="nav-tab <?php echo $active_tab == 'general_options' ? 'nav-tab-active' : ''; ?>">General Options</a>
				<a href="?page=smugmugembed-settings&tab=gellery_options" class="nav-tab <?php echo $active_tab == 'gellery_options' ? 'nav-tab-active' : ''; ?>">Gallery Options</a>
				<a href="?page=smugmugembed-settings&tab=image_options" class="nav-tab <?php echo $active_tab == 'image_options' ? 'nav-tab-active' : ''; ?>">Image Options</a>
				</h2>
				
				<div>
					<form method="post" action="options.php">
					<?php

						if ($active_tab =="general_options") {
							 settings_fields( 'SME_settings-general_options' ); 
							 do_settings_sections( 'SME_settings-general_options' );  
							 
						} else if($active_tab =="gellery_options") {
							 settings_fields( 'SME_settings-gallery-options' ); 
							 do_settings_sections( 'SME_settings-gallery-options' );  
							 
						} else if ($active_tab=="image_options") {
							settings_fields( 'SME_settings-image-options' ); 
							do_settings_sections( 'SME_settings-image-options' );
						submit_button();
							
						}
						?>
						 
					</form>
					 
				</div><!-- /.wrap -->	
	<?php
				

            } catch ( Exception $e ) {
                echo "{$e->getMessage()} (Error Code: {$e->getCode()})";
            }


?>
			<hr />
        <div class="SMEsmug_reset">
            <p>If you want to reset all available options, click this button</p>
            <form method="post" action="options-general.php">
                <?php settings_fields( 'SME_smugmugembed_settings_group' ); ?>
                <input type="hidden" name="SME_Settings[availableGalleries]" value="" />
                <input type="hidden" name="SME_Settings[availableClickResponses]" value="None" />
                <input type="hidden" name="SME_Settings[clickResponse]" value="0" />
                <input type="hidden" name="SME_Settings[caption]" value="0" />
                <input type="hidden" name="SME_Settings[keywords]" value="0" />
                <input type="hidden" name="SME_Settings[imageName]" value="0" />
                <input type="hidden" name="SME_Settings[defaultSize]" value="0" />
                <input type="hidden" name="SME_Settings[defaultAlign]" value="0" />
                <p class="submit">
                    <input type="submit" class="button-secondary" value="Reset Options" />
                </p>
            </form>
        </div>
 
        <div class="SMEsmug_reset">
            <p>If you want to link to a different SmugMug account, or have an error with the current SmugMug Embed authorization, click this button</p>
            <form method="post" action="options.php">
                <?php settings_fields( 'SME_smugmugembed_api_group' );
				?>
                <input type="hidden" name="SME_smugmugembed_api" value="" />
                <input type="hidden" name="SME_api_progress" value="" />

                <p class="submit">
                    <input type="submit" class="button-secondary" value="Delete SmugMug Authorization" />
                </p>
            </form>
        </div>
        <hr class="SMEClear" />
		
        <?php
        }
		echo '</div>';
		
    }
    /*-----------------------------------------------------------------------------------*/
    /* Create settings menu for our functions */
    /*-----------------------------------------------------------------------------------*/

    function SME_SmugMugEmbed_settings_menu() {
        add_submenu_page( 'options-general.php', 'SmugMug Embed', 'SmugMug Embed', 'edit_posts', 'smugmugembed-settings', 'SME_smugmugembed_option_settings' );
    }

    add_action( 'admin_menu', 'SME_SmugMugEmbed_settings_menu' );

	/*-----------------------------------------------------------------------------------*/
    /* Create HTML for Image Options Tab settings										 */
    /*-----------------------------------------------------------------------------------*/
	function SME_SmugMugEmbed_options_sizes_display() {
		global $SME_api, $SME_Helper;
	?>
							 <?php 
							foreach (  $SME_Helper->getAvailableSizes() as $size => $sizevalue ) {
								if ( isset( $SME_smug_available_sizes['Thumbnail'] )) {
                                   $checked=  'checked="checked"';
                                } else $checked = "";
								echo '<input type="checkbox" id="sizes[$size]" name="SME_Settings[availableSizes]['.$size.']" '.$checked.' />'.$sizevalue.'</br>';  
							}
							?>
                            
	<em>This controls the available sizes from SmugMug that are displayed in the media chooser dialogue. </em>
										
	<?php
	}
	function SME_SmugMugEmbed_options_default_size_display() {
		global $SME_api, $SME_Helper, $SME_Settings;
	?>
		<select name="SME_Settings[defaultSize]">
			 <?php 
			foreach (  $SME_Helper->getAvailableSizes() as $size => $sizevalue ) {
				?>
				<option value="<?php echo $size ?>" <?php selected($SME_Settings[ 'defaultSize' ], $size ); ?>><?php echo $sizevalue ?></option>                            
				<?php
			}
			?>
		</select>
		
	<?php
	}
	function SME_SmugMugEmbed_options_default_wide_size() {
		global $SME_api, $SME_Helper, $SME_Settings;
	?>
		<select name="SME_Settings[defaultSize]">
			 <?php 
			foreach (  $SME_Helper->getAvailableSizes() as $size => $sizevalue ) {
				?>
				<option value="<?php echo $size ?>" <?php selected($SME_Settings[ 'defaultSize' ], $size ); ?>><?php echo $sizevalue ?></option>                            
				<?php
			}
			?>
		</select>
		
	<?php
	}
	function SME_SmugMugEmbed_options_default_align_display() {
	global $SME_Settings;
	?>
		<select name="SME_Settings[defaultAlign]">
				<option value="Left" <?php selected($SME_Settings[ 'defaultAlign' ], 'Left' ); ?>> Left</option>                            
				<option value="Center" <?php selected($SME_Settings[ 'defaultAlign' ], 'Center' ); ?>> Center</option>
				<option value="Right" <?php selected($SME_Settings[ 'defaultAlign' ], 'Right' ); ?>> Right</option>
		</select>
	<?php
	}
	function SME_SmugMugEmbed_options_available_click_response_display() {
		global $SME_api, $SME_Helper;

		foreach (  $SME_Helper->getAvailableClickResponses() as $response => $responsevalue ) {
			if ( isset( $SME_smug_available_clicks[$response] ) ) {
			   $checked=  'checked="checked"';
			} else $checked = "";
			echo '<input type="checkbox" name="SME_Settings[availableClickResponses]['.$response.']" '.$checked.' />'.$responsevalue.'</br>';  
		}
	}
	
	function SME_SmugMugEmbed_options_default_click_response_display() {
		global $SME_api, $SME_Helper, $SME_Settings;
	?>
		<select name="SME_Settings[clickResponse]">
	<?php 
		foreach (  $SME_Helper->getAvailableClickResponses() as $response => $responsevalue ) {
			echo '<option value="'.$response .'" '.selected( $SME_Settings[ 'clickResponse' ],  $response ) .' />'.$responsevalue.'</br>';  
		}
		?>
		</select>
		<br/>
		<em>This controls the available sizes from Smug Mug that are displayed in the media chooser dialogue. </em>
	<?php
	}
	function SME_SmugMugEmbed_options_default_open_new_window_display() {
		global $SME_Settings;
	?>
		<select name="SME_Settings[newWindow]">
			<option value="Yes" <?php selected( $SME_Settings[ 'newWindow' ], 'Yes' ); ?>>Yes</option>                            
			<option value="No" <?php selected( $SME_Settings[ 'newWindow' ], 'No' ); ?>>No</option>
		</select>
    <?php
	}
	function SME_SmugMugEmbed_options_default_keywords_display() {
		global $SME_Settings;
	?>
		<select name="SME_Settings[keywords]">
			<option value="1" <?php selected( $SME_Settings[ 'keywords' ], '1' ); ?>> True </option>
			<option value="0" <?php selected( $SME_Settings[ 'keywords' ], '0' ); ?>> False </option>
		</select>
	<?php
	}
	function SME_SmugMugEmbed_options_default_caption_display() {
		global $SME_Settings;
	?>
		<select name="SME_Settings[caption]">
			<option value="1" <?php selected( $SME_Settings[ 'caption' ], '1' ); ?>> True </option>
			<option value="0" <?php selected( $SME_Settings[ 'caption' ], '0' ); ?>> False </option>
		</select>
	<?php
	}
	function SME_SmugMugEmbed_options_default_imageName_display() {
		global $SME_Settings;
	?>
		<select name="SME_Settings[imageName]">
			<option value="1" <?php selected( $SME_Settings[ 'imageName' ], '1' ); ?>> True </option>
			<option value="0" <?php selected( $SME_Settings[ 'imageName' ], '0' ); ?>> False </option>
		</select>
	<?php
	}
	/*-----------------------------------------------------------------------------------*/
    /* Create HTML for Gallery Options Tab settings										 */
    /*-----------------------------------------------------------------------------------*/
	function SME_SmugMugEmbed_options_gallery_display() {
		global $SME_api, $SME_Settings, $SME_Helper;
		$selectedAlbums = get_option("SME_SelectedAlbums", array());
		if (is_array($selectedAlbums))
			echo "<script>var selectedAlbums = ". json_encode($selectedAlbums) .";</script>";		
		else 
			echo "<script>var selectedAlbums = [];</script>";		
		echo $SME_Helper->getGalleryAlbums($SME_api);
		echo "<div style='width:100%;padding-top:2px;text-align:right;border-top:2px solid #3c3e43;'><input type='button' class='button button-primary' value='Save Albums' onclick='SME_saveSelectedAlbums()' style='margin-right:20px;'/></div>";
	}	
	
							
	function SME_SmugMugEmbed_register_settings() {
		add_settings_section("SME_Settings_general_options","General Options",null,"SME_settings-general-options");
		add_settings_section("SME_Settings_image_options","Image Options",null,"SME_settings-image-options");
		add_settings_section("SME_Settings_gallery_options","Gallery Options",null,"SME_settings-gallery-options");

		//Image options
		add_settings_field("SME_SmugMugEmbed_options_AvailableSizes", "Available Sizes", "SME_SmugMugEmbed_options_sizes_display", "SME_settings-image-options", "SME_Settings_image_options");     	 
		add_settings_field("SME_SmugMugEmbed_options_DefaultSize", "Default Size", "SME_SmugMugEmbed_options_default_size_display", "SME_settings-image-options", "SME_Settings_image_options");     	 
		add_settings_field("SME_SmugMugEmbed_options_DefaultAlign", "Default Alignment", "SME_SmugMugEmbed_options_default_align_display", "SME_settings-image-options", "SME_Settings_image_options");
		add_settings_field("SME_SmugMugEmbed_options_AvailableClickResponse", "Available Click Response", "SME_SmugMugEmbed_options_Available_click_response_display", "SME_settings-image-options", "SME_Settings_image_options");
		add_settings_field("SME_SmugMugEmbed_options_DefaultClickResponse", "Default Click Response", "SME_SmugMugEmbed_options_default_click_response_display", "SME_settings-image-options", "SME_Settings_image_options");
		add_settings_field("SME_SmugMugEmbed_options_DefaultOpenNewWindow", "Default Click Response", "SME_SmugMugEmbed_options_default_open_new_window_display", "SME_settings-image-options", "SME_Settings_image_options");
		add_settings_field("SME_SmugMugEmbed_options_DefaultImageName", "Default Click Response", "SME_SmugMugEmbed_options_default_imageName_display", "SME_settings-image-options", "SME_Settings_image_options");
		add_settings_field("SME_SmugMugEmbed_options_DefaultCaption", "Default Click Response", "SME_SmugMugEmbed_options_default_caption_display", "SME_settings-image-options", "SME_Settings_image_options");
		add_settings_field("SME_SmugMugEmbed_options_DefaultKeywords", "Default Click Response", "SME_SmugMugEmbed_options_default_keywords_display", "SME_settings-image-options", "SME_Settings_image_options");

 

	
	
		//general options
		add_settings_field("PS_PhotoSession_options_Types", "Available Types", "PS_PhotoSession_options_Types_display", "photo_sessions-settings", "PS_PhotoSessions_general_options");     	 
		add_settings_field("PS_PhotoSession_options_Time_Increments-select", "Increment Value", "PS_PhotoSession_options_Time_Increments_select_display", "photo_sessions-settings", "PS_PhotoSessions_general_options");     
		add_settings_field("PS_PhotoSession_options_Start_Time", "Earliest Time Available", "PS_PhotoSession_options_Start_Time_display", "photo_sessions-settings", "PS_PhotoSessions_general_options");     
		add_settings_field("PS_PhotoSession_options_End_Time", "Latest Time Available", "PS_PhotoSession_options_End_Time_display", "photo_sessions-settings", "PS_PhotoSessions_general_options");     
		add_settings_field("PS_PhotoSession_options_ShowTakenSessions", "Show Taken Sessions", "PS_PhotoSession_options_ShowTakenSessions_display", "photo_sessions-settings", "PS_PhotoSessions_general_options");     
		add_settings_field("PS_PhotoSession_options_Header_Style", "Header Style", "PS_PhotoSession_options_Header_Style_display", "photo_sessions-settings", "PS_PhotoSessions_general_options"); 
		add_settings_field("PS_PhotoSession_options_Link_Style", "Link Style", "PS_PhotoSession_options_Link_Style_display", "photo_sessions-settings", "PS_PhotoSessions_general_options");
		add_settings_field("PS_PhotoSession_options_Success_Text", "Success Confirmation Text", "PS_PhotoSession_options_Success_Text_display", "photo_sessions-settings", "PS_PhotoSessions_general_options");    
	   
	   //Gallery options
		add_settings_field("SME_SmugMugEmbed_options_Galleries", "Available Albums", "SME_SmugMugEmbed_options_gallery_display", "SME_settings-gallery-options", "SME_Settings_gallery_options");    
	   
		//general options
		register_setting( 'photo_sessions-settings', 'PS_PhotoSession_options_Types' ); 
		register_setting( 'photo_sessions-settings', 'PS_PhotoSession_options_Start_Time' ); 
		register_setting( 'photo_sessions-settings', 'PS_PhotoSession_options_Time_Increments-select' ); 
		register_setting( 'photo_sessions-settings', 'PS_PhotoSession_options_End_Time' ); 
		register_setting( 'photo_sessions-settings', 'PS_PhotoSession_options_ShowTakenSessions' ); 
		register_setting( 'photo_sessions-settings', 'PS_PhotoSession_options_Header_Style' ); 
		register_setting( 'photo_sessions-settings', 'PS_PhotoSession_options_Link_Style' ); 
		register_setting( 'photo_sessions-settings', 'PS_PhotoSession_options_Success_Text' ); 

		//image options
		register_setting( 'SME_settings-image-options', 'SME_SmugMugEmbed_options_AvailableSizes' ); 
		register_setting( 'SME_settings-image-options', 'SME_SmugMugEmbed_options_DefaultSize' ); 
		register_setting( 'SME_settings-image-options', 'SME_SmugMugEmbed_options_DefaultAlign' ); 
		register_setting( 'SME_settings-image-options', 'SME_SmugMugEmbed_options_AvailableClickResponse' ); 
		register_setting( 'SME_settings-image-options', 'SME_SmugMugEmbed_options_DefaultClickResponse' ); 
		register_setting( 'SME_settings-image-options', 'SME_SmugMugEmbed_options_DefaultKeywords' ); 
		register_setting( 'SME_settings-image-options', 'SME_SmugMugEmbed_options_DefaultCaption' ); 
		register_setting( 'SME_settings-image-options', 'SME_SmugMugEmbed_options_DefaultImageName' ); 
		register_setting( 'SME_settings-image-options', 'SME_SmugMugEmbed_options_DefaultOpenNewWindow' );

		//gallery options
		register_setting( 'SME_settings-gallery-options', 'SME_SmugMugEmbed_options_Galleries
' );
		
	}
add_action( 'admin_init', 'SME_SmugMugEmbed_register_settings' );
?>