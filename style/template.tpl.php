<?php

/**
 * @file template.tpl.php
 *
 * template file for video block
 */

namespace yookoala\discuzcode;

?>
<div <?php print render_block_attrs($embed); ?>>
	<div <?php print render_wrapper_attrs($embed); ?>><?php print $embed['html']; ?></div>
	<?php if ($link !== FALSE) { ?>
		<div class="video-desc">
			<a href="<?php print $link; ?>" target="_blank">
				<?php print $text; ?>
			</a>
		</div>
	<?php } ?>
</div>
