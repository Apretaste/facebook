<h1>Webs publicadas en Apretaste</h1>

{foreach item=$item from=$sites}
	<h2 style="margin-bottom:5px;">{link href="WEB http://{$item->domain}.apretaste.com" caption="{$item->title}"}</h2>
	<font style="color: #545454;font-size: small;"><small>Publicado por {$item->owner} el {$item->inserted|date_format:"%e de %B del %Y"}</small></font>
	{$item->summary|strip}<br/>
{/foreach}

{space10}

<center>
	{foreach item=p from=$pagging}{link href="WEB PAGINAS $p" caption="{$p}"}&nbsp;{/foreach}
</center>
