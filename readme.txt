[hr]
[center][size=16pt][b]In Line Attachments v1.11[/b][/size]
[url=http://custom.simplemachines.org/mods/index.php?action=search;author=11359][b]By Spuds[/b][/url]
[/center]
[hr]

[color=blue][b][size=12pt][u]Introduction[/u][/size][/b][/color]
This modification adds the ability to position your attachments in your post, like the IMG tag, using newly created bbcodes. This is great for inserting attachments as inline images and links in to your posts.  Several types of attachments are possible such as : full-size, thumbnail or link. For each attachment you can select how and where the attachment appears within the message. You can of course still have the attachment at the bottom of the post.  

[color=blue][b][size=12pt][u]Original mods[/u][/size][/b][/color]
Based on the mods and ideas in :
- "Inline Attachments" (by mouser - [url=http://www.donationcoder.com/Forums/bb/index.php?topic=13011.0]http://www.donationcoder.com[/url]) 
- "Attachments Positioning" (by quake101 - [url=http://www.badassmustangs.com]http://www.badassmustangs.com[/url])
- "Attachments in Message" (by slinouille - [url=http://custom.simplemachines.org/mods/index.php?mod=1179]http://custom.simplemachines.org[/url]) 

[color=blue][b][size=12pt][u]Features[/u][/size][/b][/color]
o Adds bbcodes [attachimg=n], [attach=n], [attachurl=n] or [attachmini=n] to position attachments within the post.  n is the number of the attachment in the post, eg first = 1, second = 2, etc.
o Adds optional align and width attributes as [attachimg=1 align=left width=80]
o Align can be left, right or center.  For left and right aligns the text will flow around the image
o Width works on attachimg, if the specified width is less than the image then a link (or highslide) is added to one to view the full sized image
o The mod is highslide aware, if the highslide mod is installed it will work on attach (and attachimg assuming its not full size) tags
o Adds in line options next to the attachment/upload box to insert the correct bbcode
o The default placement of attachments at the bottom of the post is unaffected for the attachments that were not used in line.
o Works in all areas of the board such as new posts since last visit / all posts of user / new, reply, modify messages / Topic/Reply History.
o Allows pseudo preview for attachments that have not been uploaded by providing an image box / text string shown in the preview window to help ensure proper layout and placement of attachments
o Supports older [attachment=] tag to provide an upgrade path

There are some basic admin settings available with this mod, go to admin - configuration - modification settings - ILA.  Here you can disable/enable the mod.

[color=blue][b][size=12pt][u]Installation[/u][/size][/b][/color]
Simply install the package to install this modification on the SMF Default Curve theme.
Manual edits will be required for other themes.

This mod is compatible with SMF RC3 (1.07-) / RC4-RC5 (1.08+).

[color=blue][b][size=12pt][u]Support[/u][/b][/color]
Please use the ILA modification thread for support with this modification.

[color=blue][b][size=12pt][u]Changelog[/u][/size][/b][/color]
[b]1.11 - 21 Mar 2011[/b]
o ! fixed issue where an ILA tag in a nested quote would not correctly create a back link
o ! minor code formatting updates

[b]1.10 - 19 Feb 2011[/b]
o + RC5 support
o ! fixed a real_width, real_height undefined index error log spam
o ! fixed error when called from news.php via rss feeds
o ! fixed potential issue where a normal below the post attachment may not show up after a quoted block with ila tags in the quote
o ! fixed improper nesting of attachments array when loaded by ILA

[b]1.09 - 19 Dec 2010[/b]
o + added Danish Translation (thanks henrik1782)
o ! fixed undefined index error where javascript image code was not being set by smf
o ! fixed issue where you had to toggle the basic menu item on/off to get the full menu

See changelog for older changes.