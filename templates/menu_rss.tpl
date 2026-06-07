{strip}
{if $packageMenuTitle}<a class="dropdown-toggle" data-toggle="dropdown" href="#"> {tr}{$packageMenuTitle}{/tr} <b class="caret"></b></a>{/if}
<ul class="{$packageMenuClass}">
	<li><a class="item" href="{$smarty.const.RSS_PKG_URL}index.php" title="{tr}Syndication{/tr}" >{biticon ipackage="icons" iname="network-transmit" iexplain="Syndication" ilocation=menu}</a></li>
</ul>
{/strip}
