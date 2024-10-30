<?php
/*
Plugin Name: jQuery Comment Preview
Plugin URI: http://dimox.net/jquery-comment-preview-wordpress-plugin/
Description: Live comment preview using jQuery.
Version: 0.5
Author: Dimox
Author URI: http://dimox.net/
*/



function jcp_get_version() {
	return '0.5';
}



if (strstr($_SERVER['REQUEST_URI'], 'jquery-comment-preview.php') && isset($_GET['ver'])) echo jcp_get_version();
if (!function_exists('add_action')) exit;



$jcp_plugin_path = 'wp-content/plugins/jquery-comment-preview';



function jcp_textdomain() {
	global $jcp_plugin_path;
	load_plugin_textdomain('jquery-comment-preview', $jcp_plugin_path.'/languages/');
	require_once(ABSPATH . '/wp-admin/includes/plugin.php'); // для функции register_setting();
	register_setting('jquery_comment_preview', 'jquery_comment_preview', 'jcp_validate');
}
add_action('init', 'jcp_textdomain');



function jcp_uninstall() {
	delete_option('jquery_comment_preview');
}
register_deactivation_hook( __FILE__, 'jcp_uninstall');



function jcp_default_options() {
	$def_options['textarea_name']  = 'comment';
	$def_options['show_text']      = __('Preview', 'jquery-comment-preview');
	$def_options['hide_text']      = __('Hide preview', 'jquery-comment-preview');
	$def_options['preview_html']   = '{avatar}'."\n".'<div class="comment-author vcard"><cite class="fn">{author}</cite> '.__('says', 'jquery-comment-preview').':</div>'."\n".'<div class="comment-meta commentmetadata">{date:time}</div>'."\n".'{comment}';
	$def_options['avatar_type']    = 1;
	$def_options['avatar_url']     = 'http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=32';
	$def_options['avatar_size']    = '40';
	$def_options['connect_css']    = 1;
	$def_options['show_editor']    = 0;
	$def_options['editor_buttons'] = '<button tag="strong" id="ed_strong">b</button>'."\n".'<button tag="em" id="ed_em">i</button>'."\n".'<button tag="a" id="ed_a">link[href=""]</button>'."\n".'<button tag="blockquote">b-quote</button>'."\n".'<button tag="code">code</button>';
	$def_options['target_blank']   = 1;
	$def_options['smiles']         = 0;
	return $def_options;
}



function jcp_validate($input) {
	$def_options = jcp_default_options();
	$input['textarea_name']  = (!empty($input['textarea_name']) ? $input['textarea_name'] : '');
	$input['show_text']      = (!empty($input['show_text']) ? $input['show_text'] : $def_options['show_text']);
	$input['hide_text']      = (!empty($input['hide_text']) ? $input['hide_text'] : $def_options['hide_text']);
	$input['preview_html']   = (!empty($input['preview_html']) ? $input['preview_html'] : $def_options['preview_html']);
	$input['avatar_type']    = ($input['avatar_type'] == 1 ? 1 : 0);
	$input['avatar_url']     = (!empty($input['avatar_url']) ? $input['avatar_url'] : $def_options['avatar_url']);
	$input['avatar_size']    = (is_numeric($input['avatar_size']) ? $input['avatar_size'] : $def_options['avatar_size']);
	$input['connect_css']    = ($input['connect_css'] == 1 ? 1 : 0);
	$input['show_editor']    = ($input['show_editor'] == 1 ? 1 : 0);
	$input['editor_buttons'] = (!empty($input['editor_buttons']) ? $input['editor_buttons'] : $def_options['editor_buttons']);
	$input['target_blank']   = ($input['target_blank'] == 1 ? 1 : 0);
	$input['smiles']         = ($input['smiles'] == 1 ? 1 : 0);
	if (isset($_POST['jcp_reset'])) 	{
		$input = jcp_default_options();
	}
  return $input;
}



function jquery_comment_preview() {

	$options = get_option('jquery_comment_preview');

	$textarea_name  = $options['textarea_name'];
	$show_text      = $options['show_text'];
	$hide_text      = $options['hide_text'];
	$avatar_type    = $options['avatar_type'];
	$avatar_url     = $options['avatar_url'];
	$avatar_size    = $options['avatar_size'];
	$preview_html   = $options['preview_html'];
	$show_editor    = $options['show_editor'];
	$editor_buttons = $options['editor_buttons'];
	$target_blank   = $options['target_blank'];
	$smiles         = $options['smiles'];

	header('Content-type: text/javascript');
?>
(function($) {
	$(function() {

		var textarea = $('textarea[name="<?php echo $textarea_name; ?>"]');
		var showText = '<?php $show_text = str_replace('\'', '\\\'', $show_text); echo $show_text; ?>';
		var hideText = '<?php $hide_text = str_replace('\'', '\\\'', $hide_text); echo $hide_text; ?>';
		var comment = '';
<?php
if ($smiles == '1' && get_option('use_smilies') == '1') {
  $dirRel = 'wp-includes/images/smilies/';
  $dirAbs = get_bloginfo('wpurl') . '/' . $dirRel;
	global $wpsmiliestrans;
	$smiled = array();
	foreach ($wpsmiliestrans as $tag => $smile) {
		if (!in_array($smile, $smiled)) {
			$smiled[$tag] = $smile;
		}
	}
	echo "		var smiles = " . json_encode($smiled) . "\n";
	echo "		var smilesDir = '" . $dirAbs . "';\n";
}
?>

		textarea.wrap('<div id="jcpWrap"></div>');
		textarea.before('<div id="jcpToolbar"></div>');
		$('#jcpToolbar').prepend('<div id="previewTab">'+ showText +'</div>');

		$('#previewTab').toggle(
			function() {
				comment = textarea.val();
				if (comment != '') comment = comment + '\n\n';
				previewText = comment.replace(/(<\/?)script/g,'$1noscript')
<?php /* если используется плагин CodeColorer
				.replace(/(<code[^>]*?\s*>)((?:[^<]*(?!<\/code).)*)/img, function(s0, s1, s2){ return s1 + s2.replace(/</g, "&lt;").replace(/>/g, "&gt;"); })
*/ ?>
				.replace(/ (http:\/\/\S+)/gi, ' <a href="$1">$1</a>')
				.replace(/\n(http:\/\/\S+)/gi, '\n<a href="$1">$1</a>')
				.replace(/(<blockquote[^>]*>)/g, '\n$1')
				.replace(/(<\/blockquote[^>]*>)/g, '$1\n')
				.replace(/\r\n/g, '\n')
				.replace(/\r/g, '\n')
				.replace(/\n\n+/g, '\n\n')
				.replace(/\n?(.+?)(?:\n\s*\n)/g, '<p>$1</p>')
				.replace(/<p>\s*?<\/p>/g, '')
				.replace(/<p>\s*(<\/?blockquote[^>]*>)\s*<\/p>/g, '$1')
				.replace(/<p><blockquote([^>]*)>/ig, '<blockquote$1><p>')
				.replace(/<\/blockquote><\/p>/ig, '</p></blockquote>')
				.replace(/<p>\s*<blockquote([^>]*)>/ig, '<blockquote$1>')
				.replace(/<\/blockquote>\s*<\/p>/ig, '</blockquote>')
				.replace(/\s*\n\s*/g, '<br />');

<?php if (preg_match('/{avatar}/', $preview_html)) { ?>
<?php if ($avatar_type == '1') { ?>
				var email = $('#email').val();
				if(!email) email = '<?php global $current_user; echo $current_user->user_email; ?>';
				var md5 = MD5(email);
<?php } ?>
<?php if ($avatar_type == '1') { ?>
				var avatar = '<img src="http://www.gravatar.com/avatar/' + md5 + '?s=<?php echo $avatar_size; ?>" alt="" class="avatar" />';
<?php } else { ?>
				var avatar = '<img src="<?php echo $avatar_url; ?>" alt="" class="avatar" />';
<?php } ?>
<?php } ?>
				var author = $('#author').val();
				var url = $('#url').val();
				if(!$('#url').length) url = '<?php global $current_user; echo $current_user->user_url; ?>';
				if(!$('#author').length) author = '<?php global $current_user; echo $current_user->display_name; ?>';
				if(url != '') author = '<a href="'+ url +'">'+ author +'</a>';
<?php if (preg_match('/{date}/', $preview_html)) { ?>
				var date = '<?php echo date_i18n( get_option('date_format'), false ); ?>';
<?php } elseif (preg_match('/{date:time}/', $preview_html)) { ?>
				var date = '<?php printf(__('%1$s at %2$s'), date_i18n( get_option('date_format'), false ), date_i18n( get_option('time_format'), false )); ?>';
<?php } ?>
				var previewHTML = '<?php $preview_html = preg_replace("/<script[^>]*?>.*?<\/script>/si", "", $preview_html);
																	$preview_html = str_replace('\'', '\\\'', $preview_html);
																	$preview_html = preg_replace("/{avatar}/", "'+ avatar +'", $preview_html);
																	$preview_html = preg_replace("/{author}/", "'+ author +'", $preview_html);
																	$preview_html = preg_replace("/{date}/", "'+ date +'", $preview_html);
																	$preview_html = preg_replace("/{date:time}/", "'+ date +'", $preview_html);
																	$preview_html = preg_replace("/{comment}/", "'+ previewText +'", $preview_html);
																	$preview_html = str_replace("\r", "", $preview_html);
																	$preview_html = str_replace("\n", "", $preview_html);
																	$preview_html = str_replace("\t", "", $preview_html);
																	echo $preview_html; ?>';
				var preview = $('<div id="jQueryCommentPreview"></div>');
<?php if ($smiles == '1' && get_option('use_smilies') == '1') { ?>
				$.each(smiles, function(key, value) {
					function str_replace(search, replace, subject) {
						var temp = subject.split(search);
						return temp.join(replace);
					}
					previewHTML = str_replace(key, '<img src="' + smilesDir + value + '" />', previewHTML);
				})
<?php } ?>
				preview.html(previewHTML);
				textarea.after(preview).hide();
				$(this).text(hideText);
				$('#htmlEditor a').hide();
			},
			function() {
				$('#jQueryCommentPreview').remove();
				$('#htmlEditor a').show();
				$(this).text(showText);
				textarea.show().focus();
			}
		)

<?php if ($show_editor == 1) { ?>

		var htmlEditor = '<div id="htmlEditor"><?php $editor_buttons = str_replace("\r", "", $editor_buttons);
													$editor_buttons = str_replace("\n", "", $editor_buttons);
													$editor_buttons = str_replace("\t", "", $editor_buttons);
													$editor_buttons = str_replace("'", "\"", $editor_buttons);
													$editor_buttons = str_replace("<button", "<a href=\"#\"", $editor_buttons);
													$editor_buttons = str_replace("</button>", "</a>", $editor_buttons);
													$editor_buttons = str_replace("target=_blank", "target=\"_blank\"", $editor_buttons);
													echo $editor_buttons ?></div>';

		$('#jcpToolbar').prepend(htmlEditor);

		function insert(start, end, mid) {
			var midText = '';
			element = document.getElementById(textarea.attr('id'));
			if (document.selection) {
				element.focus();
				sel = document.selection.createRange();
				if (sel.text == '') midText = mid;
				sel.text = start + sel.text + midText + end;
			} else if (element.selectionStart || element.selectionStart == '0') {
				element.focus();
				var startPos = element.selectionStart;
				var endPos = element.selectionEnd;
				var selText = element.value.substring(startPos, endPos);
				if (selText == '') midText = mid;
				element.value = element.value.substring(0, startPos) + start + selText + midText + end + element.value.substring(endPos, element.value.length);
			} else {
				element.value += start + end;
			}
		}

		$('#htmlEditor a').each(function() {
			var text = $(this).html().replace(/\[(.*)\]/, '<b> $1</b>');
			$(this).html(text);
		})

		$('#htmlEditor a').click(function() {
			var mid = '';
			var tag = $(this).attr('tag');
			var attribs = $(this).find('b').text();
			if (tag == 'a') {
				var URL = prompt('<?php $url = __('Enter the URL', 'jquery-comment-preview'); $url = str_replace('\'', '\\\'', $url); echo $url; ?>', 'http://');
				if (URL) {
					var blank = '';
<?php if ($target_blank == 1) { ?>
					if (URL.indexOf(window.location.hostname) == -1) blank = ' target="_blank"';
<?php } ?>
					attribs = attribs.replace('href=""', 'href="' + URL + '"' + blank);
<?php if ($target_blank == 1 && preg_match('/_blank/', $editor_buttons)) { ?>
					attribs = attribs.replace(' target="_blank"', '');
<?php } ?>
					var start = '<' + tag + attribs + '>';
					var mid = URL;
					var end = '</' + tag + '>';
				} else {
					var start = end = '';
				}
			} else if (tag == 'img') {
				var image = prompt('<?php $image = __('Enter the URL of the image', 'jquery-comment-preview'); echo $image; ?>', 'http://');
				if (image) {
					var imageAlt = prompt('<?php $imageAlt = __('Enter a description of the image', 'jquery-comment-preview'); echo $imageAlt; ?>', '');
					attribs = attribs.replace('src=""', 'src="' + image + '"');
					if (imageAlt) attribs = attribs.replace('alt=""', 'alt="' + imageAlt + '"');
					var start = '';
					var end = '<' + tag + attribs + ' />';
				} else {
					var start = end = '';
				}
			} else {
				var start = '<' + tag + attribs + '>';
				var end = '</' + tag + '>';
			}
			insert(start, end, mid);
			return false;
		})
<?php if ($smiles == '1' && get_option('use_smilies') == '1') { ?>
		$('#htmlEditor').append('<a id="jcpSmiles" href="#"><img src="' + smilesDir + 'icon_smile.gif" alt="" /><span></span></a>');
		$.each(smiles, function(key, value) {
			$('#jcpSmiles span').append('<img src="' + smilesDir + value + '" alt="' + key + '" />');
		})
		$('#jcpSmiles').click(function() { return false; })
		$('#jcpSmiles img').click(function() {
			var mid = '';
			insert('', ' ' + $(this).attr('alt') + ' ', mid);
			return false;
		})
<?php } ?>
<?php } // if ($show_editor == 1) ?>
		$('#htmlEditor').append('<a href="<?php _e('http://dimox.net/jquery-comment-preview-wordpress-plugin/', 'jquery-comment-preview') ?>" target="_blank" title="<?php _e('About the plugin', 'jquery-comment-preview') ?>">?</a>');

	}) <?php echo "\n"; // end $(function() ?>
})(jQuery)
<?php

	die();

}



function jcp_options_page() {
 	add_options_page('jQuery Comment Preview', 'jQuery Comment Preview', 8, __FILE__, 'jcp_options');
	if (!get_option("jquery_comment_preview")) {
		$options = jcp_default_options();
		add_option("jquery_comment_preview", $options) ;
	}
}
add_action('admin_menu', 'jcp_options_page');



function jcp_options() {
	$options = get_option('jquery_comment_preview');
	$error = false;
	if ( empty($options['textarea_name']) ) {
		echo '<div id="message" class="error"><p>' . __('Please specify the <code style="background:#FFFBFB">name</code> attribute of the <code style="background:#FFFBFB">textarea</code> tag, else preview will not work.', 'jquery-comment-preview') . '</p></div>';
		$error = ' style="border: 1px solid #C00; background: #FFEBE8;"';
	}
?>

<div class="wrap">

	<h2><?php _e('jQuery Comment Preview Options', 'jquery-comment-preview'); ?></h2>

	<form method="post" action="options.php">
		<?php settings_fields('jquery_comment_preview'); ?>
		<?php $options = get_option('jquery_comment_preview'); ?>

		<div id="poststuff" class="ui-sortable">

			<p><input type="submit" name="jcp_update" class="button-primary" value="<?php _e('Update Options', 'jquery-comment-preview') ?>" /><br /><br /></p>

			<div class="postbox">

		    <h3><?php _e('Preview Options', 'jquery-comment-preview'); ?></h3>

				<div class="inside">

					<table class="form-table">

					 	<tr valign="top">
							<th scope="row" style="width: 260px"><label for="textarea_name"><?php _e('The value of the <code>name</code> attribute of the <code>textarea</code> tag:', 'jquery-comment-preview'); ?></label></th>
							<td>
								<input<?php echo $error; ?> name="jquery_comment_preview[textarea_name]" type="text" id="textarea_name" value="<?php echo $options['textarea_name']; ?>" size="30" /><br />
								<?php _e('The <code>textarea</code> tag is in the <code>comments.php</code> file of your theme. Commonly this value is <code>comment</code>.', 'jquery-comment-preview'); ?>
							</td>
			      </tr>

					 	<tr valign="top">
							<th scope="row"><label for="show_text"><?php _e('"Preview" button text:', 'jquery-comment-preview'); ?></label></th>
							<td>
								<input name="jquery_comment_preview[show_text]" type="text" id="show_text" value="<?php echo $options['show_text']; ?>" size="30" />
							</td>
			      </tr>

					 	<tr valign="top">
							<th scope="row"><label for="hide_text"><?php _e('"Hide preview" button text:', 'jquery-comment-preview'); ?></label></th>
							<td>
								<input name="jquery_comment_preview[hide_text]" type="text" id="hide_text" value="<?php echo $options['hide_text']; ?>" size="30" />
							</td>
			      </tr>

					 	<tr valign="top">
							<th scope="row"><label for="preview_html"><?php _e('Preview block template:', 'jquery-comment-preview'); ?></label></th>
							<td>
								<textarea name="jquery_comment_preview[preview_html]" id="preview_html" cols="85" rows="10" /><?php echo $options['preview_html']; ?></textarea><br />
								<strong><?php _e('Designations:', 'jquery-comment-preview'); ?></strong><br />
								<code>{avatar}</code> - <?php _e('avatar', 'jquery-comment-preview'); ?>;<br />
								<code>{author}</code> - <?php _e('author\'s name with a link', 'jquery-comment-preview'); ?>;<br />
								<code>{date}</code> - <?php _e('comment date', 'jquery-comment-preview'); ?> (<?php _e('will look like', 'jquery-comment-preview'); ?>: <code><?php echo date_i18n( get_option('date_format'), false ); ?></code>);<br />
								<code>{date:time}</code> - <?php _e('comment date and time', 'jquery-comment-preview'); ?> (<?php _e('will look like', 'jquery-comment-preview'); ?>: <code><?php printf(__('%1$s at %2$s'), date_i18n( get_option('date_format'), false ), date_i18n( get_option('time_format'), false )); ?></code>);<br />
								<code>{comment}</code> - <?php _e('comment text', 'jquery-comment-preview'); ?>.<br />
								<?php _e('<strong>The format of the date and time</strong> depends on your', 'jquery-comment-preview'); ?> <a href="<?php echo get_bloginfo('wpurl') ?>/wp-admin/options-general.php"><?php _e('settings', 'jquery-comment-preview'); ?></a>.
							</td>
			      </tr>

					 	<tr valign="top">
							<th scope="row"><label for="avatar_type"><?php _e('Avatar type:', 'jquery-comment-preview'); ?></label></th>
							<td>
<script type="text/javascript">
function CheckHideBlocks() {
	if (jQuery('#avatar_type').val()=='1') {
		jQuery('.hide').addClass('hide_this');
		jQuery('.show').removeClass('hide_this');
	} else {
		jQuery('.hide').removeClass('hide_this');
		jQuery('.show').addClass('hide_this');
	}
}
</script>
<style>
.hide_this th,
.hide_this td,
.hide_this th *,
.hide_this td *,
div.hide_this {width: 0 !important; height: 0 !important; overflow: hidden !important; position: absolute; bottom: 0; display: none}
</style>
								<select onchange="CheckHideBlocks()" name="jquery_comment_preview[avatar_type]" id="avatar_type">
									<option value="1"<?php selected('1', $options['avatar_type']); ?>><?php _e('Dynamic', 'jquery-comment-preview'); ?></option>
									<option value="0"<?php selected('0', $options['avatar_type']); ?>><?php _e('Static', 'jquery-comment-preview'); ?></option>
								</select>
								<div class="show<?php if($options['avatar_type'] == 0) echo ' hide_this'; ?>"><?php _e('Avatar will vary depending on an e-mail address of commentator. Used <a href="http://gravatar.com/" target="_blank">Gravatar</a> service.', 'jquery-comment-preview'); ?></div>
							</td>
			      </tr>

					 	<tr valign="top" class="hide<?php if($options['avatar_type'] == 1) echo ' hide_this'; ?>">
							<th scope="row"><label for="avatar_url"><?php _e('Link on static avatar:', 'jquery-comment-preview'); ?></label></th>
							<td>
								<input name="jquery_comment_preview[avatar_url]" id="avatar_url" type="text" value="<?php $avatar_url = preg_replace('/=(\d+)/', '='.$options['avatar_size'], $options['avatar_url']); echo $avatar_url; ?>" size="85" /><br />
								<?php _e('Avatar will <strong>not</strong> vary depending on an e-mail address of commentator.', 'jquery-comment-preview'); ?>
							</td>
			      </tr>

					 	<tr valign="top">
							<th scope="row"><label for="avatar_size"><?php _e('Avatar size:', 'jquery-comment-preview'); ?></label></th>
							<td>
<script type="text/javascript">
jQuery(function() {
	jQuery('#avatar_size').keyup(function() {
		jQuery('#avatar_size2').val(jQuery('#avatar_size').val());
	})
})
</script>
								<input name="jquery_comment_preview[avatar_size]" id="avatar_size" type="text" value="<?php echo $options['avatar_size']; ?>" size="2" maxlength="3" style="width:33px" /> x <input name="" id="avatar_size2" type="text" value="<?php echo $options['avatar_size']; ?>" size="2" maxlength="3" disabled="disabled" style="width:33px" /> px
							</td>
			      </tr>

					 	<tr valign="top">
							<th scope="row"><label for="connect_css"><?php _e('Connect a plugin\'s CSS file?', 'jquery-comment-preview'); ?></label></th>
							<td>
								<select name="jquery_comment_preview[connect_css]">
									<option value="1"<?php selected('1', $options['connect_css']); ?>><?php _e('Yes', 'jquery-comment-preview'); ?></option>
									<option value="0"<?php selected('0', $options['connect_css']); ?>><?php _e('No', 'jquery-comment-preview'); ?></option>
								</select>
								<br />
								<?php _e('If you wish to reduce a number of queries to the server it can be useful not to connect an additional CSS file applied to the plugin. In that case choose "No". Thus it is necessary to insert a styles from the <code>jquery-comment-preview.css</code> file into a CSS file of your theme (<code>style.css</code>).', 'jquery-comment-preview'); ?>
			<br /><br />
			<?php _e('The CSS file is located on the following path', 'jquery-comment-preview'); ?>: <br /><code>../wp-content/plugins/jquery-comment-preview/jquery-comment-preview.css</code>
							</td>
			      </tr>

			    </table>

				</div><!-- .inside -->

			</div><!-- .postbox -->

			<div class="postbox">

		    <h3><?php _e('HTML Editor Options', 'jquery-comment-preview'); ?></h3>

				<div class="inside">

					<table class="form-table">

					 	<tr valign="top">
							<td scope="row" colspan="2"><strong style="color: #F60"><?php _e('The HTML editor allows to quickly insert a HTML tags into a comment text.', 'jquery-comment-preview'); ?></strong></td>
			      </tr>

					 	<tr valign="top">
							<th scope="row" style="width: 260px"><label><?php _e('Show the HTML editor?', 'jquery-comment-preview'); ?></label></th>
							<td>
<script type="text/javascript"><!--
function CheckHideBlocks2() {
	if (document.getElementById('show_editor').value=='0') {
		jQuery('.hide2').addClass('hide_this');
	} else {
		jQuery('.hide2').removeClass('hide_this');
	}
}
//--></script>
								<select onchange="CheckHideBlocks2()" name="jquery_comment_preview[show_editor]" id="show_editor">
									<option value="1"<?php selected('1', $options['show_editor']); ?>><?php _e('Yes', 'jquery-comment-preview'); ?></option>
									<option value="0"<?php selected('0', $options['show_editor']); ?>><?php _e('No', 'jquery-comment-preview'); ?></option>
								</select>
							</td>
			      </tr>

					 	<tr valign="top" class="hide2<?php if($options['show_editor'] == 0) echo ' hide_this'; ?>">
							<th scope="row"><label for="editor_buttons"><?php _e('HTML code of a buttons of the editor:', 'jquery-comment-preview'); ?></label></th>
							<td>
								<textarea name="jquery_comment_preview[editor_buttons]" id="editor_buttons" cols="85" rows="10" /><?php echo $options['editor_buttons']; ?></textarea><br />
								<p><strong><?php _e('Explanations:', 'jquery-comment-preview'); ?></strong></p>
								<ol>
									<li><p><?php _e('A button <strong>necessarily</strong> should always have a <code>tag</code> parameter and a tag name as value. For example, If you create a button for the <code>&lt;code>&lt;/code></code> tag, this parameter should be looks like this: <code>&lt;button <strong>tag="code"</strong>>...&lt;button></code>.', 'jquery-comment-preview'); ?></p></li>
								  <li><p><?php _e('Between the tags <code><strong>&lt;button></strong></code> and <code><strong>&lt;/button></strong></code> it is necessary to specify a <strong>name of a button</strong>.', 'jquery-comment-preview'); ?></p></li>
								  <li><p><?php _e('If you wish to <strong>add a parameters to a tag</strong>, it is necessary to set them in square brackets after a button name. For example, to receive a button for the <code>&lt;a href="">&lt;/a></code> tag, it is necessary to write the following code: <code>&lt;button <strong>tag="a"</strong>>link<strong>[href=""]</strong>&lt;/button></code>.', 'jquery-comment-preview'); ?></p></li>
								</ol>

							</td>
			      </tr>

					 	<tr valign="top" class="hide2<?php if($options['show_editor'] == 0) echo ' hide_this'; ?>">
							<th scope="row"><label><?php _e('Add the <code>target="_blank"</code> parameter to external links?', 'jquery-comment-preview'); ?></label></th>
							<td>
								<select name="jquery_comment_preview[target_blank]" id="target_blank">
									<option value="1"<?php selected('1', $options['target_blank']); ?>><?php _e('Yes', 'jquery-comment-preview'); ?></option>
									<option value="0"<?php selected('0', $options['target_blank']); ?>><?php _e('No', 'jquery-comment-preview'); ?></option>
								</select>
								<br />
								<?php _e('This option is used for the button adds a link.', 'jquery-comment-preview'); ?>
							</td>
			      </tr>

					 	<tr valign="top" class="hide2<?php if($options['show_editor'] == 0) echo ' hide_this'; ?>">
							<th scope="row"><label><?php _e('Show button with WordPress smilies?', 'jquery-comment-preview'); ?></label></th>
							<td>
								<select name="jquery_comment_preview[smiles]" id="smiles">
									<option value="1"<?php selected('1', $options['smiles']); ?>><?php _e('Yes', 'jquery-comment-preview'); ?></option>
									<option value="0"<?php selected('0', $options['smiles']); ?>><?php _e('No', 'jquery-comment-preview'); ?></option>
								</select>
								<br />
								<?php _e('To make this feature work, the following option should be activated on ', 'jquery-comment-preview'); ?> <a href="<?php echo get_bloginfo('wpurl') ?>/wp-admin/options-writing.php"><?php _e('this page', 'jquery-comment-preview'); ?></a>: '<?php _e('Convert emoticons like <code>:-)</code> and <code>:-P</code> to graphics on display') ?>'.
							</td>
			      </tr>

		    	</table>

				</div><!-- .inside -->

			</div><!-- .postbox -->

			<p><input type="submit" name="jcp_update" class="button-primary" value="<?php _e('Update Options', 'jquery-comment-preview') ?>" /><br /><br /></p>
	    <p><input type="submit" name="jcp_reset" class="button-primary" value=" <?php _e('Reset Defaults', 'jquery-comment-preview') ?> " /><br /><br /></p>

			<div class="postbox">

		    <h3><?php _e('Copyright', 'article-directory'); ?></h3>

				<div class="inside">

					<p>&copy; 2008-<?php echo date('Y'); ?> <a href="<?php _e('http://dimox.net', 'jquery-comment-preview') ?>">Dimox</a> | <a href="<?php _e('http://dimox.net/jquery-comment-preview-wordpress-plugin/', 'jquery-comment-preview') ?>">jQuery Comment Preview</a> | <?php _e('version', 'jquery-comment-preview') ?> <?php echo jcp_get_version() ?></p>

				</div><!-- .inside -->

			</div><!-- .postbox -->

		</div><!-- #poststuff -->

	</form>

</div><!-- .wrap -->

<?php

}



$options = get_option('jquery_comment_preview');



// Подключаем CSS-файл
add_action('wp_head', 'jcp_css');
function jcp_css() {
	if ( comments_open() && ( is_single() || is_page() ) ) {
		global $jcp_plugin_path;
		$css_links = "\n".'<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/' . $jcp_plugin_path . '/jquery-comment-preview.css?v='.jcp_get_version().'" type="text/css" media="screen" />';
		global $options;
		if (isset($options['connect_css']) && $options['connect_css'] == 0) {
			$css_links = '';
		}
		echo $css_links;
	}
}



//Подключаем jQuery
function jcp_jquery() {
	if ( comments_open() && ( is_single() || is_page() ) ) {
		wp_deregister_script('jquery');
		wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.6.3/jquery.min.js"), false, '1.6.3');
		wp_enqueue_script('jquery');
	}
}
add_action('wp_head', 'jcp_jquery', 8);



// Подключаем JavaScript
add_action('wp_head', 'echo_script');
function echo_script() {
	if ( comments_open() && ( is_single() || is_page() ) ) {
		global $jcp_plugin_path, $options;
		if ( (!isset($options['avatar_type']) && !isset($options['preview_html'])) || ($options['avatar_type'] == '1' && preg_match('/{avatar}/', $options['preview_html'])) ) {
			echo "\n".'<script src="' . get_bloginfo('wpurl') . '/'.$jcp_plugin_path.'/md5.js" type="text/javascript"></script>';
		}
		echo "\n".'<script src="' . get_bloginfo('wpurl') . '/?s=jquery-comment-preview.js?' . time() . '" type="text/javascript"></script>';
	}
}



if( stristr($_SERVER['REQUEST_URI'], 'jquery-comment-preview.js') ) {
	add_action('template_redirect', 'jquery_comment_preview');
}


?>