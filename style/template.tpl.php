<?php

if (($link===False) AND ($text===False)) {
	print <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px;{$heightcode}">
<div class="video-wrapper">$embed</div>
</div>
CODEBLOCK;
} elseif (($link===False) AND ($text!==False)) {
	print <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px; border: solid 1px #DDD; background: #CCC;">
<div class="video-wrapper" style="{$heightcode}">$embed</div>
<div class="video-desc">$text</div>
</div>
CODEBLOCK;
} else {
	print <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px; border: solid 1px #DDD; background: #CCC;">
<div class="video-wrapper" style="{$heightcode}">$embed</div>
<div class="video-desc"><a href="$link" target="_blank">$source_text: $text</a></div>
</div>
CODEBLOCK;
}
