<h1>{$query|capitalize}</h1>

{foreach from=$responses item=response name=google}
	<b>{$smarty.foreach.google.index + 1}) {link href="WEB {$response['url']}" caption="{$response['title']}"}</b><br/>
	<font style="color: #545454;font-size: small;">{$response['note']}</font><br/>
	{space15}
{/foreach}
