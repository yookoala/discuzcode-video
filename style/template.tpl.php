<?php

/**
 * @file template.tpl.php
 *
 * template file for video block
 */

namespace yookoala\discuzcode;

?>
<div class="videoblock <?php if ($d->dynamic) print 'videoblock-dynamic'; ?>"
	style="<?php print style_block($embed); ?>">
	<div class="video-wrapper wrap-<?php print $d->scale_model;?>"
		style="<?php print style_wrapper($embed); ?>"><?php print $embed['html']; ?></div>
	<?php if ($link !== FALSE) { ?>
		<div class="video-desc">
			<a href="<?php print $link; ?>" target="_blank">
				<?php print $text; ?>
			</a>
		</div>
	<?php } ?>
</div>