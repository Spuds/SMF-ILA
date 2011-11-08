<?php
// -----------------------------------------------------------------------------------------------
// "Inline Attachments (ILA)" Mod for Simple Machines Forum (SMF) V2.0
// by spuds,
// version 1.11
// based on original code by mouser http://www.donationcoder.com
//
// -----------------------------------------------------------------------------------------------
// Allows users to put image attachments (or any other attachments) anywhere
// they want within a post; has advanced features for creating thumbnails,
// and automatically figuring out which images are inlined so others are
// attached at post footer as normal.
//
// -----------------------------------------------------------------------------------------------
// Instructions for Use:
//
// Just attach images to your post as normal.
// And then inside your post use [attach=1] to indicate where you want the
// first attachment to be shown, and [attach=2] where you want the
// second one to be shown, etc.  See below for more options.
//
// -----------------------------------------------------------------------------------------------
// Features:
// [attach=#] will insert a thumbnail even if none is available via SMF
// (ie will be url link if non image, or thumbnail based on server settings).
// for example, [attach=1]
//
// [attachimg=#] -- insert full image upto the maximum allowed by the server.  If image
// is larger than the allowed size by forum it will clickable to full size
// For example, [attachimg=1]
//
// [attachturl=#] -- insert url link even if the attachment is an image.  Useful
// if you want to attach images as simple download links but not show them as
// pictures in the post.
// For example, [attachurl=1]
//
// [attachmini=#] like attachurl but dont shown any details like file size and download count
// For example, [attachmini=1]
//
// Tags can accept the optional align and width attributes like:
// [attachimg=1 align=right width=200]  -- insert an image that is 200 pixels wide
// and float it to the right of the screen.  Post text will flow next to the image
//
// Some nice aspects:
// When an attachment is shown inline, it will automatically NOT be
// shown at the bottom of a post; however if isn't shown inline it will
// be shown at bottom of post as normal.
// -----------------------------------------------------------------------------------------------

if (!defined('SMF'))
	die('Hacking Attempt...');
	
/* This file contains the following functions

	ila_parse_bbc(&$message, $id_msg = - 1)
		- Traffic cop, checks permissions and finds all [attach tags, determins msg number, inits values
		- and calls needed functions to render ila tags

	ila_parse_bbc_tag($data, $attachments, $id_msg, $ila_num, $ila_new_msg_preview)
		- Breaks up the components of the [attach tag getting id, width, align
		- Fixes common usage errors

	ila_find_nested(&$message, $id_msg)
		- Does [attach replacements in quotes and nested quotes

	ila_hide_bbc(&$message,$hide_tags = '')
		- Makes [attach tags invisible for certain bbc blocks like code, oho, nobbc, etc
		
	ila_showInline($done, $attachments, $id_msg, $ila_num, $ila_new_msg_preview)
		- Does the actual replacement of the [attach tag with the img tag
		
	ila_createfakethumb($attachment, $highslide, $width, $uniqueID, $id_msg)
		- Creates the false thumbnail if none exits
	
	ila_preview_inline($attachname, $type, $id, $align, $width)
		- Renders a preview box for attachments that have not been uploaded, used in preview message
	
	ila_load_attachments($msg_id)
		- Loads attachments for a given msg if they have not yet been loaded
	
	ila_get_topic($msg_id)
		-Get the topic and board for a given message number
		
	ila_str_replace_once($needle, $replace, $haystack)
		- Looks for the first occurence of $needle in $haystack and replaces it with $replace, this is a single replace

	str_ireplace($search, $replace, $subject)
		- Case insensitive str_replace function for those that don't have PHP5 installed

	stripos($haystack, $needle, $offset = 0)
		- Case insenstive stripos replacement for php4 from subs-compat
*/

function ila_parse_bbc(&$message, $id_msg = -1)
{
	global $modSettings, $context, $sourcedir, $topicinfo, $ila_attachments, $txt, $attachments, $settings;

	// Set some vars so we don't throw a notice cog
	$ila_attachments_context = array();
	$ila_new_msg_preview = array();
	$ila_attachments = array();
	$settings['ila_dont_show_attach_below'] = array();
	$topic = -1;
	$board = null;

	// Mod or BBC disabled
	if (empty($modSettings['ila_enabled']) || empty($modSettings['enableBBC']))
		return;

	// Go for the pad save ... we could be doing a preview of a modified message, if so we set the $_REQUEST['msg']
	if ($id_msg == '' || $id_msg == - 1)
		$id_msg = (isset($_REQUEST['msg'])) ? $_REQUEST['msg'] : -1;

	// No message id and not previewing a new message ($_REQUEST['ila']) will be set, why bother ?
	if ($id_msg == -1 && !isset($_REQUEST['ila']))
	{
		ila_find_nested($message, $id_msg);
		return;
	} elseif ($id_msg != -1) {
		// Lets make sure we have the attachments to work with so we can get the context array
		if (!isset($attachments[$id_msg]))
			$attachments[$id_msg] = ila_load_attachments($id_msg);

		// now get the rest of the details for these attachments
		if (!function_exists('loadAttachmentContext'))
			include_once ($sourcedir . '/Display.php');
		$ila_attachments_context = loadAttachmentContext($id_msg);
	}

	// do we have new --- not yet uploaded ---- attachments in either a new or a modified message?
	if (isset($_REQUEST['ila']))
	{
		$start_num = isset($attachments[$id_msg]) ? count($attachments[$id_msg]) : 0;
		$ila_temp = explode(',', $_REQUEST['ila']);
		foreach($ila_temp as $new_attach)
		{
			$start_num++; // add at the end of the uploaded attachment count index
			$ila_new_msg_preview[$start_num] = $new_attach;
		}
	}

	// Take care of any attach links that reside in quote blocks, we must render these first
	ila_find_nested($message, $id_msg);

	// Find all of the inline attach tags in this message [attachimg=xx] [attach=xx] [attachthumb=xx] [attachurl=xx] [attachmini=xx] or
	// some malformed ones like [attachIMG = "xx"] ila_tags[0] will hold the entire tag [1] will hold after attach and before the ] like img=1
	if (preg_match_all('~\[attach\s*?(.*?(?:".+?")?.*?|.*?)\][\r\n]?~i', $message, $ila_tags))
	{
		// load an simple array of elements.  We use it to keep track of attachment number usage in the message body
		$ila_attachments = isset($start_num) ? range(1, $start_num) : range(1, count($attachments));
		$ila_num = 0;

		// if there is a message number then get the topic and board that its from
		if ($id_msg != -1)
			list($topic, $board) = ila_get_topic($id_msg);
		
		// if they have no permissions to view attachments then we sub out the tag with the appropriate so 'sorry message'
		if (!allowedTo('view_attachments', $board))
		{
			if ($context['user']['is_guest'])
				$message = preg_replace('~\[attach\s*?(.*?(?:".+?")?.*?|.*?)\][\r\n]?~i', $txt['ila_forbidden_for_guest'], $message);
			else
				$message = preg_replace('~\[attach\s*?(.*?(?:".+?")?.*?|.*?)\][\r\n]?~i', $txt['ila_nopermission'], $message);
		} else {
			// if we have attachments, and ILA tags then go through each ILA tag, one by one, and resolve it back to the correct SMF attachment
			if (!empty($ila_tags) && ((count($ila_attachments_context) > 0) || (isset($_REQUEST['ila']))))
			{
				foreach($ila_tags[1] as $id => $ila_replace)
				{
					$message = ila_str_replace_once($ila_tags[0][$id], ila_parse_bbc_tag($ila_replace, $ila_attachments_context, $id_msg, $ila_num, $ila_new_msg_preview), $message);
					$ila_num++;
				}
			} elseif (!empty($ila_tags)) {
				// we have tags in the message and no attachments ... there are a few reasons why this can occur
				// -- the tags in the message but there is no attachment, perhaps the attachment did not upload correctly
				// -- maybe the user put the tag in wrong because they are rock dumb and did not read our fantastic help, just kidding,
				//    really the help is not that good :)
				foreach($ila_tags[1] as $id => $ila_replace)
					$message = ila_str_replace_once($ila_tags[0][$id], $txt['ila_invalid'], $message);
			}
		}
	}
	return;
}

function ila_parse_bbc_tag($data, $attachments, $id_msg, $ila_num, $ila_new_msg_preview)
{
	global $context, $ila_attachments;
	$done = array('id' => '', 'type' => '', 'align' => '', 'width' => '');
	$data = trim($data);

	// A little brut force is needed, we are using the existing syntax of inline attach so we don't have a nice id or type tags and
	// we are going to add align and width tags plus lots of future cool stuff, or not, so .....
	// find the align tag, save its value and remove it from the data string
	if (preg_match('~align\s{0,1}=(?:&quot;)?(right|left|center)(?:&quot;)?~i', $data, $matches))
	{
		$done['align'] = strtolower($matches[1]);
		$data = str_replace($matches[0], '', $data);
	}

	// find the width tag, save its value and remove it from the data string
	if (preg_match('~width\s{0,1}=(?:&quot;)?(\d+)(?:&quot;)?~i', $data, $matches))
	{
		$done['width'] = strtolower($matches[1]);
		$data = str_replace($matches[0], '', $data);
	}

	// all that should be left is the legacy tag, explode on = to see what we have
	$temp = explode('=', $data);
	if ($temp[0] != '')
	{
		// one of img=1 thumb=1 mini=1 url=1
		$done['id'] = isset($temp[1]) ? trim($temp[1]) : '';
		$done['type'] = $temp[0];

		// support for attachment tags from different version of this mod and moving depreciated thumb tag to be the same as attach
		if ($done['type'] == 'ment' || $done['type'] == 'thumb')
			$done['type'] = 'none';
	} else {
		// nothing but a =x or perhaps nothing at all
		$done['id'] = isset($temp[1]) ? trim($temp[1]) : '';
		$done['type'] = 'none';
	}

	// Lets help the kids out by fixing some common erros in usage, I mean did they read the super great help, sheesh!
	// like attach=#1 -> attach=1
	$done['id'] = str_replace('#', '', $done['id']);

	// like [attach] -> attach=1 by assuming attachments are sequentally placed in the topic and sub in the attachment index increment
	if (is_numeric($done['id']))
		$ila_attachments = array_diff($ila_attachments, array($done['id'])); // remove this attach choice since we have used it
	else
	{
		$done['id'] = array_shift($ila_attachments); // take the first un-used attach number and use it
		array_push($ila_attachments, $done['id']); // stick it back on the end in case we need to loop around
	}

	// Replace this tag with the inlined attachment
	$result = ila_showInline($done, $attachments, $id_msg, $ila_num, $ila_new_msg_preview);

	return !empty($result) ? $result : '[attach' . $data . ']';
}
	
function ila_find_nested(&$message, $id_msg)
{
	// Look for quote blocks with ila attach tags and build the link.  This serves two purposes, it replaces ila attach tags in
	// quotes with a link back to the post with the attachment, and prevents ILA from firing on those attach tags should we have
	// a quote block with an attach placed in a message with an attach

	global $modSettings, $context, $txt, $scripturl;
	
	if (empty($modSettings['enableBBC']))
		return;
	
	// regexs to seach the message for quotes, nested quotes and quoted text, and tags
	$regex['quotelinks'] = '~<div\b[^>]*class="topslice_quote">(?:.*?)</div>~si';
	$regex['ila'] = '~\[attach\s*?(.*?(?:".+?")?.*?|.*?)\][\r\n]?~i';
	
	// break up the quotes on the endtags, this way we will get all the needed text
	$quotes = array();
	$quotes = preg_split('~(.*?</blockquote>)~si', $message, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	array_pop($quotes); // the last one is junk, strip it off ...

	// init
	$quote_count = count($quotes);
	$loop = $quote_count;
	$start= 0;
	$end = 0;

	// loop through the quote array
	while ($quote_count > 0 && $loop > 0)
	{
		//  Get all the topslice quotes, they contain the links (or not) of the message that was quoted, each link represents a quoteblock
		$blockquote_count = preg_match_all($regex['quotelinks'], $quotes[$start], $links, PREG_SET_ORDER);
		$quote_count = $quote_count - $blockquote_count;
		$loop += -1; // $quote_count will really control the while, but belt and suspenders here we keep a loop count to stop a run away ....

		// if this has blockquotes, we have work to do, we will have a nesting level of blockquote_count
		if (!empty($blockquote_count))
		{
			// flip the array, quotes are outside to inside and links are inside to outside, its a nesting thang.
			$link_count = $blockquote_count;
			$links = array_reverse($links);

			// scrape off anything ahead of a leading quoteheader ... its regular message text, likely between quoted zones
			if ((strpos($quotes[$start],'<div class="quoteheader">') != 0) && (preg_match('~.*(<div class="quoteheader">.*)~si', $quotes[$start], $temp)))
				$quotes[$start] = $temp[1];

			// Set the end of the link/quote array look ahead
			$end = $start + $blockquote_count - 1;
			$which_link = 0;
			
			// This quote block runs from array elements $start to $end
			for($i = $start; $i <= $end; $i++)
			{
				// Search the link to get the msg_id
				$msg_id = '';
				if (preg_match('~<a href="(?:.*)#(.*?)">~i', $links[$which_link][0], $href_temp) == 1)
					$msg_id = $href_temp[1];

				// we either found the quoted msg id above or we did not, yes profound I know .... if none set the link to the first message.
				if ($msg_id == '')
					$msg_id = isset($context['topic_first_message']) ? $context['topic_first_message'] : '';

				// Build the link, we will replace any quoted ILA tags with this bad boy
				if ($msg_id != '')
				{
					if (!isset($context['current_topic']))
						list($topic,$board) = ila_get_topic($id_msg);
					else
						$topic = $context['current_topic'];
					$linktoquotedmsg = '<a href="' . $scripturl . '/topic,' . $topic . '.' . $msg_id . '.html#' . $msg_id . '">' . $txt['ila_quote_link'] . '</a>';
				}
				else
					$linktoquotedmsg = $txt['ila_quote_nolink'];

				// The link back is the same for all the ila tags in an individual quoteblock (they all point to the same message)
				if (preg_match_all($regex['ila'], $quotes[$i], $ila_tags))
				{
					// We have found an ila tag, in this quoted message section
					$ila_string = $quotes[$i];
					
					// replace the ila attach tag with the link back to the message that was quoted
					foreach($ila_tags[0] as $id => $ila_replace)						
						$ila_string  = ila_str_replace_once($ila_replace, $linktoquotedmsg, $ila_string);
					
					// At last the final step, sub in the attachment link
					$message = str_replace($quotes[$i],$ila_string,$message);
				}
				$which_link++;
			}
			$start += $blockquote_count;	
		}
	}
	return;
}

function ila_hide_bbc(&$message,$hide_tags = '')
{
	global $modSettings;
	
	if (empty($modSettings['enableBBC']))
		return;

	// if our ila attach tags are nested inside of these tags we need to hide them so they don't fire
	if ($hide_tags == '')
		$hide_tags = array('code', 'html', 'php', 'noembed', 'nobbc');

	// look for each tag, if its found the text between the tags is returned in $1, now
	// hide them by replacing [ with a hex so we don't try to render them later
	foreach ($hide_tags as $tag)
	{
		if (stripos($message, '[' . $tag . ']') !== false)
			$message = preg_replace('~\[' . $tag . ']((?>[^[]|\[(?!/?' . $tag . '])|(?R))+?)\[/' . $tag . ']~ie',
				"'[" . $tag . "]' . str_ireplace('[attach', '&#91;attach', '$1') . '[/" . $tag . "]'",
				$message);
	}
}

function ila_showInline($done, $attachments, $id_msg, $ila_num, $ila_new_msg_preview)
{
	global $txt, $context, $modSettings, $settings, $sourcedir;

	// $done = array('id' => '', 'type' => '', 'align' => '', 'width' => '');
	$images = array('none', 'img', 'thumb');
	extract($done); // expand the done array back into vars that equal the keys ... ie $id $type
	$inlinedtext = '';

	// see if any highslide mods are installed
	$highslide = false;
	$hs4smf = false;
	
	// highslide image viewier?
	if (function_exists('highslide_images'))
		$highslide = true;
	
	// highslide 4 smf? if so we also need to work with slideshows
	if (!empty($modSettings['hs4smf_enabled']) && file_exists($sourcedir . '/hs4smf-Subs.php'))
	{
		$hs4smf = true;
		if (!function_exists('hs4smf'))
			require_once($sourcedir . '/hs4smf-Subs.php');
		$slidegroup = hs4smf_get_slidegroup($id_msg);
		$settings['hs4smf_img_count'] = (isset($settings['hs4smf_img_count'])) ? $settings['hs4smf_img_count'] + $ila_num + 1 : $ila_num + 1;
        if (!isset($settings['hs4smf_slideshow']) && $settings['hs4smf_img_count'] > 1)
			$settings['hs4smf_slideshow'] = 1;
		hs4smf_track_slidegroup($id_msg);
	}
	
	// find the text of the attachment being referred to, the attachment array starts at 0 but the
	// tags in the message start at 1 to make it easy for those humons, need to shift the id back one
	if (isset($attachments[$id - 1]))
		$attachment = $attachments[$id - 1];
	else
		$attachment = '';

	// We found an attachment that matches our attach id in the message
	if ($attachment != '')
	{
		// we need a unique css id for javascript to find the correct image, cant just use the attach id since we allow the users
		// to use the same attachment many times in the same post.
		$uniqueID = $attachment['id'] . $ila_num;
		$rel_tag = ($highslide) ? 'highslide' : 'ila_' . $id_msg;
		
		if ($attachment['is_image'])
		{
			// make sure we have the javascript call set, for admins who turn off thumbnails and set a max width and other crazy stuff
			if (!isset($attachment['thumbnail']['javascript']))
			{
				if (((!empty($modSettings['max_image_width']) && $attachment['real_width'] > $modSettings['max_image_width']) || (!empty($modSettings['max_image_height']) && $attachment['real_height'] > $modSettings['max_image_height'])))
				{
					if (isset($attachment['width']) && isset($attachment['height']))
						$attachment['thumbnail']['javascript'] = 'return reqWin(\'' . $attachment['href'] . ';image\', ' . ($attachment['width'] + 20) . ', ' . ($attachment['height'] + 20) . ', true);';
					else
						$attachment['thumbnail']['javascript'] = 'return expandThumb(' . $attachment['href'] . ');';			
				}
				else
					$attachment['thumbnail']['javascript'] = 'return ILAexpandThumb(' . $uniqueID . ');';
			}
			
			// set up our private js call if needed, taken from display.php but with our ilaexpandthumb replacement
			if (!empty($attachment['thumbnail']['has_thumb']))
			{
				// If the image is too large to show inline, make it a popup window.
				if (((!empty($modSettings['max_image_width']) && $attachment['real_width'] > $modSettings['max_image_width']) || (!empty($modSettings['max_image_height']) && $attachment['real_height'] > $modSettings['max_image_height'])))
					$attachment['thumbnail']['javascript'] = $attachment['thumbnail']['javascript'];
				else
					$attachment['thumbnail']['javascript'] = 'return ILAexpandThumb(' . $uniqueID . ');';
			}
		}

		// fix any tag option incompatabilities
		if (isset($modSettings['ila_alwaysfullsize']) && $modSettings['ila_alwaysfullsize'] == 1) $type = 'img'; // we were overidden in the admin conrol panel
		if ((!$attachment['is_image']) && (in_array($type, $images))) $type = 'url'; // cant show an image for a non image attachment

		// now create the image tag based off the type given
		switch ($type)
		{
			// [attachimg=xx -- full sized image type=img
			case 'img':
				// Make sure the width its not bigger than the actual image or bigger than allowed by the admin
				if ($width != '') $width = !empty($modSettings['max_image_width']) ? min($width, $attachment['real_width'], $modSettings['max_image_width']) : min($width, $attachment['real_width']);
				else              $width = !empty($modSettings['max_image_width']) ? min($attachment['real_width'], $modSettings['max_image_width']) : $attachment['real_width'];
				
				$ila_title = isset($context['subject']) ? $context['subject'] : (isset($attachment['name']) ? $attachment['name'] : '');

				// now insert the correct image tag, there are 4 possible scenarios, highslide, hs4smf, clickable or just a full image
				if ($width < $attachment['real_width'] && $highslide)  $inlinedtext = '<a href="' . $attachment['href'] . ';image" id="link_' . $uniqueID . '" class="highslide" rel="' . $rel_tag . '" onclick="return hs.expand(this)"><img src="' . $attachment['href'] . ';image" alt="ila_rendered" title="' . $ila_title . '" id="thumb_' . $uniqueID . '" style="width:' . $width . 'px;border:0;" /></a><br /><div class="highslide-heading">' . $ila_title . '</div>';
				elseif ($width < $attachment['real_width'] && $hs4smf) $inlinedtext = '<a href="' . $attachment['href'] . ';image" id="link_' . $uniqueID . '" class="highslide" onclick="return hs.expand(this, ' . $slidegroup . ')"><img src="' . $attachment['href'] . ';image" alt="ila_rendered" title="' . $ila_title . '" id="thumb_' . $uniqueID . '" style="width:' . $width . 'px;border:0;" /></a>';
				elseif ($width < $attachment['real_width'])            $inlinedtext = '<a href="' . $attachment['href'] . ';image" id="link_' . $uniqueID . '" onclick="' . $attachment['thumbnail']['javascript'] . '"><img src="' . $attachment['href'] . ';image" alt="ila_rendered" title="' . $ila_title . '" id="thumb_' . $uniqueID . '" style="width:' . $width . 'px;border:0;" /></a>';
				else                                                   $inlinedtext = '<img src="' . $attachment['href'] . ';image" alt="" title="' . $ila_title . '" id="thumb_' . $uniqueID . '" style="width:' . $width . 'px;border:0;" />';
				break;

			// [attach=xx] or depreciated [attachment=xx] or [attachthumb=xx]-- thumbnail
			case 'none':
				// if a thumbnail is available use it, if not create one and use it
				if ($width != '' && $attachment['thumbnail']['has_thumb']) $width = min($width, isset($attachment['real_width']) ? $attachment['real_width'] : (isset($modSettings['attachmentThumbWidth']) ? $modSettings['attachmentThumbWidth'] : 160));
				elseif ($attachment['thumbnail']['has_thumb'])             $width = isset($modSettings['attachmentThumbWidth']) ? $modSettings['attachmentThumbWidth'] : 160;
				elseif ($width != '')                                      $width = min($width, isset($modSettings['attachmentThumbWidth']) ? $modSettings['attachmentThumbWidth'] : 160, $attachment['real_width']);
				else                                                       $width = min(isset($modSettings['attachmentThumbWidth']) ? $modSettings['attachmentThumbWidth'] : 160, $attachment['real_width']);
				
				$ila_title = isset($context['subject']) ? $context['subject'] : (isset($attachment['name']) ? $attachment['name'] : '');

				// Now with the width defined insert the thumbnail if available or create the fake html resized thumb
				if ($attachment['thumbnail']['has_thumb'] && $highslide)  $inlinedtext = '<a href="' . $attachment['href'] . ';image" id="link_' . $uniqueID . '" class="highslide" rel="' . $rel_tag . '" onclick="return hs.expand(this)"><img src="' . $attachment['thumbnail']['href'] . '" alt="ila_rendered" title="' . $ila_title . '" id="thumb_' . $uniqueID . '" style="width:' . $width . 'px;border:0;" /></a><br /><div class="highslide-heading">' . $ila_title . '</div>';
				elseif ($attachment['thumbnail']['has_thumb'] && $hs4smf) $inlinedtext = '<a href="' . $attachment['href'] . ';image" id="link_' . $uniqueID . '" class="highslide" onclick="return hs.expand(this, ' . $slidegroup . ')"><img src="' . $attachment['thumbnail']['href'] . '" alt="ila_rendered" title="' . $ila_title . '" id="thumb_' . $uniqueID . '" style="width:' . $width . 'px;border:0;" /></a>';
				elseif ($attachment['thumbnail']['has_thumb'])            $inlinedtext = '<a href="' . $attachment['href'] . ';image" id="link_' . $uniqueID . '" onclick="' . $attachment['thumbnail']['javascript'] . '"><img src="' . $attachment['thumbnail']['href'] . '" alt="ila_rendered" title="' . $ila_title . '" id="thumb_' . $uniqueID . '"  style="width:' . $width . 'px;border:0;" /></a>';
				else                                                      $inlinedtext = ila_createfakethumb($attachment, $highslide, $hs4smf, $width, $uniqueID, $id_msg, $rel_tag);
				break;

			// [attachurl=xx] -- no image, just a link with size/view details type = url
			case 'url':
				$inlinedtext = '<a href="' . $attachment['href'] . '"><img src="' . $settings['images_url'] . '/icons/clip.gif" align="middle" alt="*" border="0" />&nbsp;' . $attachment['name'] . '</a> (' . $attachment['size'] . ($attachment['is_image'] ? '. ' . $attachment['real_width'] . 'x' . $attachment['real_height'] . ' - ' . $txt['attach_viewed'] : ' - ' . $txt['attach_downloaded']) . ' ' . $attachment['downloads'] . ' ' . $txt['attach_times'] . '.)';
				break;

			// [attachmini=xx] -- just a plain link type = mini
			case 'mini':
				$inlinedtext = '<a href="' . $attachment['href'] . '"><img src="' . $settings['images_url'] . '/icons/clip.gif" align="middle" alt="*" border="0" />&nbsp;' . $attachment['name'] . '</a>';
				break;
		}

		// handle the align tag if it was supplied
		if ($align == 'left' || $align == 'right')
			$inlinedtext = '<div style="float:' . $align . ';margin: .5ex 1ex 1ex 1ex;">' . $inlinedtext . '</div>';
		elseif ($align == 'center')
			$inlinedtext = '<div style="width:100%;margin:0 auto;text-align:center">' . $inlinedtext . '</div>';

		// keep track of the attachments we have in-lined so we can exclude them from being displayed in the post footers
		$settings['ila_dont_show_attach_below'][$attachment['id']] = 1;		
	} else {
		// couldn't find the attachment specified
		// - they may have specified it wrong
		// - or they don't have permissions for attachments
		// - or they are replying to a message and this is in a quote, code or other type of tag
		// - or it has not been uploaded yet because they are previewing a new message,
		// - or they are modifiying a message and added new attachments and hit preview
		// .... simple huh?
		if (allowedTo('view_attachments'))
		{
			// check to see if the preview flag, via attach number, is set, if so try to render a preview ILA
			if (isset($ila_new_msg_preview[$id]))
				$inlinedtext = ila_preview_inline($ila_new_msg_preview[$id], $type, $id, $align, $width);
			else
				$inlinedtext = $txt['ila_attachment_missing'];
		}
		else $inlinedtext = $txt['ila_forbidden_for_guest'];
	}
	return $inlinedtext;
}
	
function ila_createfakethumb($attachment, $highslide, $hs4smf, $width, $uniqueID, $id_msg, $rel_tag)
{
	// we were requested to show a thumbnail but none exists? how embarrassing, we should hang our heads in shame!
	// create our own thumbnail display using html img width / height attributes on the attached image
	global $modSettings, $context;
	
	$dst_width = '';
	$dst_height = '';
	$inlinedtext = '';

	// Get the attachment size
	$src_width = $attachment['real_width'];
	$src_height = $attachment['real_height'];

	// Set thumbnail limits
	$max_width = $width;
	$max_height = min(isset($modSettings['attachmentThumbHeight']) ? $modSettings['attachmentThumbHeight'] : 120, floor($width / 1.333));

	// Determine whether to resize to max width or to max height (depending on the limits.)
	if ($src_height * $max_width / $src_width <= $max_height)
	{
		$dst_width = $max_width;
		$dst_height = floor($src_height * $max_width / $src_width);
	} else {
		$dst_width = floor($src_width * $max_height / $src_height);
		$dst_height = $max_height;
	}

	// Get the slidegroup if needed
	if ($hs4smf)
		$slidegroup = hs4smf_get_slidegroup($id_msg);
	
	// Don't show a link if we can't resize or if we were asked not to
	$ila_title = isset($context['subject']) ? $context['subject'] : (isset($attachment['name']) ? $attachment['name'] : '');

	// build the relacement string
	if (($dst_width < $src_width || $dst_height < $src_height) && $highslide)
		$inlinedtext = '<a href="' . $attachment['href'] . ';image" id="link_' . $uniqueID . '" class="highslide" rel="' . $rel_tag . '" onclick="return hs.expand(this)"><img src="' . $attachment['href'] . '" alt="ila_rendered" title="' . $ila_title . '" id="thumb_' . $uniqueID . '" style="width:' . $dst_width . 'px;height:' . $dst_height . 'border:0;" /></a><br /><div class="highslide-heading">' . $ila_title . '</div>';
	if (($dst_width < $src_width || $dst_height < $src_height) && $hs4smf)
		$inlinedtext = '<a href="' . $attachment['href'] . ';image" id="link_' . $uniqueID . '" class="highslide" onclick="return hs.expand(this, ' . $slidegroup . ')"><img src="' . $attachment['href'] . '" alt="ila_rendered" title="' . $ila_title . '" id="thumb_' . $uniqueID . '" style="width:' . $dst_width . 'px;height:' . $dst_height . 'border:0;" /></a><br />';
	elseif ($dst_width < $src_width || $dst_height < $src_height)
		$inlinedtext = '<a href="' . $attachment['href'] . ';image" id="link_' . $uniqueID . '" onclick="return ILAexpandThumb(' . $uniqueID . ');"><img src="' . $attachment['href'] . '" alt="ila_rendered" title="' . $ila_title . '" style="width:' . $dst_width . 'px;height:' . $dst_height . 'border:0;" id="thumb_' . $uniqueID . '" /></a>';
	elseif ($hs4smf)
		$inlinedtext = '<a href="' . $attachment['href'] . ';image" id="link_' . $uniqueID . '" class="highslide" onclick="return hs.expand(this, ' . $slidegroup . ')"><img src="' . $attachment['href'] . '" alt="ila_rendered" title="' . $ila_title . '" id="thumb_' . $uniqueID . '" style="width:' . $dst_width . 'px;height:' . $dst_height . 'border:0;" /></a><br />';
	else
		$inlinedtext = '<img src="' . $attachment['href'] . ';image" alt="" title="' . $ila_title . '" border="0" />';
	return $inlinedtext;
}

function ila_preview_inline($attachname, $type, $id, $align, $width)
{
	// we are trying to preview a message but the attachments have not been uploaded, lets sub in a fake image box
	// with our ILA text so the user can check things are positioned correctly even if they cant yet see the image

	global $txt, $context, $modSettings, $settings;
	$inlinedtext = '';
	$txt_name = 'ila_' . $type;

	// Decide how to do our fake preview based on the type
	switch ($type)
	{
		// [attachimg=xx -- full sized image type=img
		case 'img':
			if ($width != '') $width = !empty($modSettings['max_image_width']) ? min($width, $modSettings['max_image_width']) : $width;
			else $width = !empty($modSettings['max_image_width']) ? min($modSettings['max_image_width'], 400) : 160;
			$inlinedtext = '<div style="display:-moz-inline-box;display:inline-block;background: white;width:' . $width . 'px;height:' . floor($width / 1.333) . 'px;border:1px solid #000;vertical-align:bottom;">[Attachment:' . $id . ': <strong>' . $attachname . '</strong> ' . $txt[$txt_name] . ']</div>';
			break;

		// [attach=xx] or depreciated [attachment=xx] or [attachthumb=xx]-- thumbnail
		case 'none':
			if ($width != '') $width = min($width, isset($modSettings['attachmentThumbWidth']) ? $modSettings['attachmentThumbWidth'] : 160);
			else $width = isset($modSettings['attachmentThumbWidth']) ? $modSettings['attachmentThumbWidth'] : 160;
			$inlinedtext = '<div style="display:-moz-inline-box;display:inline-block;background: white;width:' . $width . 'px;height:' . floor($width / 1.333) . 'px;border:1px solid #000;vertical-align:bottom;">[Attachment:' . $id . ': <strong>' . $attachname . '</strong> ' . $txt[$txt_name] . ']</div>';
			break;

		// [attachurl=xx] -- no image, just a link with size/view details type = url
		case 'url':
			$inlinedtext = '[Attachment:' . $id . ': ' . $attachname . ' ' . $txt[$txt_name] . ']';
			break;

		// [attachmini=xx] -- just a plain link type = mini
		case 'mini':
			$inlinedtext = '[Attachment:' . $id . ': ' . $attachname . ' ' . $txt[$txt_name] . ']';
			break;
	}

	// handle the align tag if it was supplied
	if ($align == 'left' || $align == 'right')
		$inlinedtext = '<div style="float:' . $align . ';margin: .5ex 1ex 1ex 1ex;">' . $inlinedtext . '</div>';
	elseif ($align == 'center')
		$inlinedtext = '<div style="width:100%;margin:0 auto;text-align:center">' . $inlinedtext . '</div>';
	
	return $inlinedtext;
}
	
function ila_load_attachments($msg_id)
{
	global $modSettings, $context, $sourcedir, $topic, $topicinfo, $ila_attachments, $txt, $smcFunc;

	$message = array($msg_id);
	$attachments = array();
	list($topic,$board) = ila_get_topic($msg_id);

	// with a message id and the topic we can fetch the attachments, go boy go
	if (!empty($modSettings['attachmentEnable']) && allowedTo('view_attachments',$board) && $topic != -1)
	{
		$request = $smcFunc['db_query']('', '
			SELECT
				a.id_attach, a.id_folder, a.id_msg, a.filename, a.file_hash, IFNULL(a.size, 0) AS filesize, a.downloads, a.approved,
				a.width, a.height' . (empty($modSettings['attachmentShowImages']) || empty($modSettings['attachmentThumbnails']) ? '' : ',
				IFNULL(thumb.id_attach, 0) AS id_thumb, thumb.width AS thumb_width, thumb.height AS thumb_height') . '
			FROM {db_prefix}attachments AS a' . (empty($modSettings['attachmentShowImages']) || empty($modSettings['attachmentThumbnails']) ? '' : '
				LEFT JOIN {db_prefix}attachments AS thumb ON (thumb.id_attach = a.id_thumb)') . '
			WHERE a.id_msg IN ({array_int:message_list})
				AND a.attachment_type = {int:attachment_type}' . (!$modSettings['postmod_active'] || allowedTo('approve_posts') ? '' : '
				AND a.approved = {int:is_approved}'),
			array(
				'message_list' => $message,
				'attachment_type' => 0,
				'is_approved' => 1,
			)
		);

		// now sort the attachments on the id_attach, we need them in order
		$temp = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$temp[$row['id_attach']] = $row;

		$smcFunc['db_free_result']($request);

		// This is better than sorting it with the query...so say we all
		ksort($temp);

		foreach ($temp as $row)
			$attachments[] = $row;
	}
	return $attachments;
}

function ila_get_topic($msg_id)
{
	// get the topic, needed to find attachments and the board, needed to check permissions
	global $smcFunc;
	$topic_and_board = array(-1, null);
	
	// No message is comlete without a topic, its like bread and butter
	if (!empty($msg_id))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_topic, id_board
			FROM {db_prefix}messages
			WHERE id_msg = {int:msg}
			LIMIT 1',
			array(
				'msg' => (int) $msg_id,
		));
		
		if ($smcFunc['db_num_rows']($request) != 1)
			$topic_and_board = array(-1, null);
		else
			$topic_and_board = $smcFunc['db_fetch_row']($request);
			
		// close up this request
		$smcFunc['db_free_result']($request);
	}
	return $topic_and_board;
}

function ila_str_replace_once($needle, $replace, $haystack)
{
	// Looks for the first occurence of $needle in $haystack and replaces it with $replace, this is a single replace
	$pos = strpos($haystack, $needle);
	if ($pos === false)
	{
		// Nothing found
		return $haystack;
	}
	return substr_replace($haystack, $replace, $pos, strlen($needle));
}

//  case insensitive str_replace function for those that don't have PHP5 installed, from php.net
if (!function_exists('str_ireplace'))
{
	function str_ireplace($search, $replace, $subject)
	{
		global $context;
		$endu = '~i' . ($context['utf8'] ? 'u' : '');
		if (is_array($search))
			foreach (array_keys($search) as $word)
				$search[$word] = '~' . preg_quote($search[$word], '~') . $endu;
		else
			$search = '~' . preg_quote($search, '~') . $endu;
		return preg_replace($search, $replace, $subject);
	}
}

// case insenstive stripos replacement for php4 from subs-compat
if (!function_exists('stripos'))
{
	function stripos($haystack, $needle, $offset = 0)
	{
		return strpos(strtolower($haystack), strtolower($needle), $offset);
	}
}
?>