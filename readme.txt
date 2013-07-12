[hr]
[center][size=16pt][b]In Line Attachments v1.2[/b][/size]
[url=http://custom.simplemachines.org/mods/index.php?action=search;author=11359][b]By Spuds[/b][/url]
[/center]
[hr]

[color=blue][b][size=12pt][u]License[/u][/size][/b][/color]
This modification is released under a MPL V1.1 license, a copy of it with its provisions is included with the package.

[color=blue][b][size=12pt][u]Introduction[/u][/size][/b][/color]
This modification adds the ability to position your attachments in your post, like the IMG tag, using newly created BBC codes. This is useful for inserting attachments inline with your text.  Several types of attachments are possible such as : full-size, thumbnail or link. For each attachment you can select how and where the attachment appears within the message. You can also use the same image in multiple areas in the same post. You can of course still have the attachment at the bottom of the post as is the SMF default.

[color=blue][b][size=12pt][u]Original mods[/u][/size][/b][/color]
Based on the mod and ideas in :
- "Inline Attachments" (by mouser - [url=http://www.donationcoder.com/Forums/bb/index.php?topic=13011.0]http://www.donationcoder.com[/url])

[color=blue][b][size=12pt][u]Features[/u][/size][/b][/color]
o Adds bbcodes [attachimg=n], [attach=n], [attachurl=n] or [attachmini=n] to position attachments within the post.  n is the number of the attachment in the post, eg first = 1, second = 2, etc.
o Adds optional align and width attributes as [attachimg=1 align=left width=80]
o Align can be left, right or center.  For left and right aligns the text will flow around the image
o Width works on attachimg, if the specified width is less than the image then a link (or highslide) is added to one to view the full sized image
o The mod is HS4SMF aware, if the highslide mod is installed it will work on the attachment (assuming its not full size)
o Adds in line options next to the attachment/upload box to insert the correct bbcode
o The default placement of attachments at the bottom of the post is unaffected for the attachments that were not used in line.
o Works in all areas of the board such as new posts since last visit / all posts of user / new, reply, modify messages / Topic/Reply History.
o Allows pseudo preview for attachments that have not been uploaded by providing an image box / text string shown in the preview window to help ensure proper layout and placement of attachments

There are some basic admin settings available with this mod, go to admin - configuration - modification settings - ILA.  Here you can disable/enable the mod.

[color=blue][b][size=12pt][u]Installation[/u][/size][/b][/color]
Simply install the package to install this modification on the SMF Default Curve theme.
Manual edits will be required for other themes.

This mod is compatible with SMF 2.0x.

[color=blue][b][size=12pt][u]Support[/u][/b][/color]
Please use the ILA modification thread for support with this modification.

[color=blue][b][size=12pt][u]Changelog[/u][/size][/b][/color]
[b]1.21 - 22 Jan 2012[/b]
o ! Fixed issue with left side portal blocks were used on the message view page
o ! Fixed issue with displaying > 9 ILA attachments in a message introduced in V1.2

[b]1.2 - 04 Dec 2011[/b]
o + re-Release under proper BSD open license
o - Simplifed tag support by removed support for [attachment tags in messages (if you are upgrading from a mod that may use these you will need to manually replace those tags in your database.
o - Simplied Highslide mod support to only HS4SMF
o ! Fixed error where [attach] shortcut was not working
o ! Fixed error where previewing a modified message would not render the 'fake' thumb properly
o + Improved error handling for improperly constructed tags

[b]1.11 - 21 Mar 2011[/b]
o ! fixed issue where an ILA tag in a nested quote would not correctly create a back link
o ! minor code formatting updates

See changelog for older changes.