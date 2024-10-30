<?php
/*
Plugin Name: Import Legacy Media
Plugin URI: http://freakytrigger.co.uk/wordpress-setup/
Description: Imports existing media items in to WPs media library
Author: Alan Trewartha
Version: 0.1
Author URI: http://freakytrigger.co.uk/author/alan/
*/ 

include_once(ABSPATH . 'wp-admin/includes/import.php');


class Import_Legacy_Media
{	var $importer_id = 'importlegacymedia';
 	var $importer_name = 'Import Legacy Media';
 	var $importer_desc = 'Import files on your server in to the WP Media Library';

	function Import_Legacy_Media()
	{
		// Nothing.
	}

	function dispatch()
	{	?><div class='wrap'><h2>Import Legacy Files</h2>
		<p>This importer allows you to import locally held files (on the server)
		into the WP Media Library</p>
		<?
		
		// current_user_can import, so nothing more to check for.
		if ($_POST['folder'])
			$this->file_import($GLOBALS['_SERVER']['DOCUMENT_ROOT'], urldecode(stripslashes($_POST['folder'])));
		else
			$this->folder_list($GLOBALS['_SERVER']['DOCUMENT_ROOT'], urldecode(stripslashes($_GET['folder'])));
	}


	function folder_list($folder_root, $folder_rel)
	{	global $wpdb;
		$filepath=$folder_root."/".$folder_rel."/";
		
		// read folder contents
		$dh = opendir($folder_root."/".$folder_rel);
		while (($file = readdir($dh)) !== false)
			if (substr($file,0,1)!=".")
			{	if (is_dir($filepath.$file))
					$folder_list[$file]="folder";
				else
				{	if (!is_writable($filepath.$file))
						$folder_list[$file]="unwritable";
					else
						$folder_list[$file]="writable";
					if (strpos($file, ".thumbnail."))
						$folder_list[$file].=" old_thumb";
				}
				
			}
		closedir($dh);
		
		// find files already in the library
		foreach($folder_list as $file=>$state)
		{	if ($state!="folder")
			{	$find_post = $wpdb->get_row("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_wp_attached_file' and meta_value='".$folder_root.$folder_rel."/".$file."'");
				if ($id=$find_post->post_id)
				{	$folder_list[$file]="imported";
	
					// thumbnails too
					$pm=get_post_meta($id,"_wp_attachment_metadata", true);
					if ($pm['sizes'])
						foreach($pm['sizes'] as $size=>$pm_size)
							$folder_list[$pm_size['file']]="imported";
				}
			}
		}
		
		// show form
		echo "<h3>Browsing: ".$folder_root.$folder_rel."</h3><p>";
		if (!is_writable($folder_root.$folder_rel)) echo "This folder is not writable, so WP can't upload to it or move files in and out. ";
		?>Files already in the Media Library are listed without a checkbox.</p>
			<script>
				function filter_files(style,el) { jQuery("li" + style + " :checkbox").attr("checked",jQuery(el).attr("checked"))}
				function colour_code(el)
				{	if (jQuery(el).attr("checked"))
					{	jQuery("li.writable").css({color: "#2a2"});
						jQuery("li.unwritable").css({color: "#a22"});
						jQuery("li li.old_thumb").css({color: "black"});
						jQuery("li.old_thumb").css({fontStyle: "italic"});
					}
					else
						jQuery("li").css({color: "black"});
				}
			</script>
			<form action="admin.php?import=importlegacymedia" method="POST"><?
		echo "<input type=hidden name='ilm-folder-list' value='".wp_create_nonce("ilm-folder-list")."' >";
		echo "<input type=hidden name='folder' value='".urlencode($folder_rel)."' >";
		?>
		(<input type=checkbox onchange='colour_code(this)'>Show colour and style)
		<ul><li><input type=checkbox onchange='filter_files("",this)'><strong>All files</strong>
			<ul>
				<li class='writable'><input type=checkbox onchange='filter_files(".writable",this)'>Editable</li>
				<li class='unwritable'><input type=checkbox onchange='filter_files(".unwritable",this)'>Uneditable</li>
				<li class="writable unwritable old_thumb"><input type=checkbox onchange='filter_files(".old_thumb",this)'>Old 'thumbnail'</li>
			</ul>
			</li>
		<?
		ksort($folder_list);
		foreach($folder_list as $file=>$state)
		{	echo "<li class='".$state."'>";
			if ($state=="folder")
				echo "$file <a href='admin.php?import=importlegacymedia&folder=".urlencode($folder_rel."/".$file)."'>folder</a></li>";
			else
			{	if ($state!="imported")
					echo "<input name='file[]' value='".urlencode($file)."' type=checkbox>".$file;
				else
					echo "$file";
			}
			echo "</li>";
		}
		echo '</ul><p class="submit"><input type="Submit" value="Import Files" /></p>';
	}


		
	function file_import($folder_root, $folder_rel)
	{	check_admin_referer('ilm-folder-list', 'ilm-folder-list');

		// fire up the ID3 library
		require_once(ABSPATH.PLUGINDIR.'/import-legacy-media/getid3/getid3/getid3.php');
		$getID3 = new getID3;

		foreach($_POST['file'] as $file)
		{	$file=urldecode(stripslashes($file));
			$filepath=$folder_root.$folder_rel."/".$file;
			// find TYPE
			$wp_filetype = wp_check_filetype( basename($file), null );
			extract( $wp_filetype );
			if ( !$type ) $type = "";
	
			// find TITLE and optional CONTENT using WP's EXIF routine
			$title = preg_replace('/\.[^.]+$/', '', basename($file));
			if ( $image_meta = @wp_read_image_metadata($filepath) ) {
				if ( trim($image_meta['title']) )
					$title = $image_meta['title'];
				if ( trim($image_meta['caption']) )
					$content = $image_meta['caption'];
			}

			// find TITLE and CONTENT from ID3 tags now.
			$fileinfo = $getID3->analyze($filepath);
			getid3_lib::CopyTagsToComments($fileinfo);
			if ($fileinfo['comments']['title']) $title=$fileinfo['comments']['title'][0];
			if ($fileinfo['comments']['subtitle']) $content=$fileinfo['comments']['subtitle'][0];
			//echo "<PRE>"; print_r($fileinfo['comments']);echo "</PRE>";

			$url="http://".$_SERVER["HTTP_HOST"].$folder_rel."/".$file;
			$file = array(
				'file' 				=> $filepath,
				'url' 				=> $url,
				'type' 				=> $type );
			$attachment = array(
				'guid'				=> $url,
				'post_mime_type'	=> $type,
				'post_title' 		=> $title,
				'post_content' 		=> $content );
			
			//echo "<PRE>"; print_r($file);echo "</PRE>";
			
			$id = wp_insert_attachment($attachment, $filepath, 0);
			if ( !is_wp_error($id) )
			{	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $filepath ) );
				echo "Imported: $filepath ($title) <a href='media.php?action=edit&attachment_id=".$id."'>Edit Media</a><br />";
			}
			
			
		}
	}
}


if(function_exists('register_importer')) {
	$Import_Legacy_Media = new Import_Legacy_Media();
	register_importer(
		$Import_Legacy_Media->importer_id,
		$Import_Legacy_Media->importer_name,
		$Import_Legacy_Media->importer_desc,
		array ($Import_Legacy_Media, 'dispatch')
	);
}

?>