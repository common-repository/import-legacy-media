=== Import Legacy Media ===
Contributors: alanft
Tags: admin, upload, folder, media, media library, legacy, server
Requires at least: 2.6
Tested up to: 2.6.3
Stable tag: 0.1

Import Legacy Media lets you import files on your server into the WP Media Library.


== Description ==
The import lists all the files and folders in your web-site home directory. 

Navigate to sub-folders by clicking on 'folder' links.

Files already in the Media Library are shown without a tickbox. Filters at the top of the file listing allow you to tick all the files, or just editable files. You can then also choose to untick 'old thumbnail' files.

If there are other filters on file selection you would like to see, let me know on the [WP Forums](http://wordpress.org/tags/import-legacy-media?forum_id=10#postform).

Files are imported with Title and Description data taken from (in order of precedence) ID3 tags, EXIF data, or the filename.

Once you have selected the files you want imported click the 'Import Files' button.

(This plugin comes complete with the getID3 PHP library in a sub-folder. The library can be found at http://www.getid3.org/.)

= Version History =
0.1 - Just starting out, something to get it working

== Installation ==

1. Upload `import-legacy-media` folder to the plugins directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. 'Import Legacy Media' should appear in the Manage > Import page.


== Frequently Asked Questions ==

= What's the significance of uneditable files? =

If you use plugins that allow you to manipulate (crop, edit, etc) Media Library items - then these plugins will fail on these files even though you have imported them into the Media Library.

Similarly if you use plugins to rename or move files, then the folder itself needs to be writable by the web server.

Making folders and files editable/writable by the web-server will vary from server configuration to configuration.

== Screenshots ==

1. Use simple filters at the top of the file listing to choose groups of files.
