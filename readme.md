##In Line Attachments
###Introduction

This modification adds the ability to position your attachments in your post, like the IMG tag, using newly created bbcodes. This is great for inserting attachments as inline images and links in to your posts. Several types of attachments are possible such as : full-size, thumbnail or link. For each attachment you can select how and where the attachment appears within the message. You can of course still have the attachment at the bottom of the post.
Original mods

Based on the mods and ideas in : - "Inline Attachments" (by mouser - http://www.donationcoder.com/Forums/bb/index.php?topic=13011.0)
###Features
 - Adds bbcodes attachimg=n?, attach=n?, attachurl=n? or attachmini=n? to position attachments within the post. n is the number of the attachment in the post, eg first = 1, second = 2, etc.
 - Adds optional align and width attributes as align=left width=80?
 - Align can be left, right or center. For left and right aligns the text will flow around the image
 - Width works on attachimg, if the specified width is less than the image then a link (or highslide) is added to one to view the full sized image
 - The mod is highslide aware, if the hs4smf mod is installed it will work on attach (and attachimg assuming its not full size) tags - Adds in line options next to the attachment/upload box to insert the correct bbcode
 - The default placement of attachments at the bottom of the post is unaffected for the attachments that were not used in line.
 - Works in all areas of the board such as new posts since last visit / all posts of user / new, reply, modify messages / Topic/Reply History.
 - Allows pseudo preview for attachments that have not been uploaded by providing an image box / text string shown in the preview window to help ensure proper layout and placement of attachments

There are some basic admin settings available with this mod, go to admin - configuration - modification settings - ILA. Here you can disable/enable the mod.
###Installation
Simply install the package to install this modification on the SMF Default Curve theme. Manual edits will be required for other themes.

This mod is compatible with SMF 2.0 only
###Changelog
1.21 - 22 Dec 2011
o ! Fixed issue with left side portal blocks were used on the message view page
o ! Fixed issue with displaying > 9 ILA attachments in a message introduced in V1.2

1.2 - 04 Dec 2011
o + re-Release under proper open license
o - Simplified tag support by removed support for [attachment tags in messages (if you are upgrading from a mod that may use these you will need to manually replace those tags in your database.
o - Simplified Highslide mod support to only HS4SMF
o ! Fixed error where [attach] shortcut was not working
o ! Fixed error where previewing a modified message would not render the 'fake' thumb properly
o + Improved error handling for improperly constructed tags