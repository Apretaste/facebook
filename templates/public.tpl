<h1>Su sitio web '{$domain}' ha sido publicado</h1>
<p>Hemos publicado su sitio web en Apretaste con el nombre <b>{$domain}</b> y el t&iacute;tulo <b>{$title}</b> y agregamos <b>{$num_files}</b> archivos a su web, reconocidos de los adjuntos que nos envi&oacute;.</p>

{space5}

{if $num_files > 0}
	<ul>
	{foreach from=$files_changed item=item}
		<li>{$item}</li>
	{/foreach}
	</ul>
{/if}

<center>
	<a href="http://{$domain}.apretaste.com/index.html">Haga clic aqu&iacute; para visitar su sitio web</a>
</center>
