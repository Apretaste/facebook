<h1>Bienvenido a la Web</h1>
<p align="justify">Este servicio te permite <b>buscar en la internet</b>, adem&aacute;s de <b>visitar paginas webs</b> e incluso <b>publicar sus propios sitios webs</b> mediante el correo electr&oacute;nico.</p>

<center>
	{button icon="&#9729;" size="small" href="WEB" caption="Ver p&aacute;gina Web" body="Escriba una direccion web en el asunto, despues de la palabra WEB, por ejemplo: WEB apretaste.com" desc="Escriba una direccion web, por ejemplo: apretaste.com" popup="true"}
	{button icon="&#8494;" size="small" href="WEB" caption="Buscar en internet" body="Escriba una palabra o frase en el asunto, despues de donde dice WEB, por ejemplo: WEB comida cubana" desc="Escriba una palabra o frase, por ejemplo: comida cubana" popup="true"}
</center>

{if $sites !== false}
	{space10}
	<h1>Sitios m&aacute;s populares publicados en Apretaste</h1>

	{foreach item=item from=$sites}
		<li>{link href="WEB {$item->domain}.apretaste.com" caption="{$item->domain}.apretaste.com"}</li>
	{/foreach}

	{space10}

	<center>
		{button href="WEB PAGINAS" caption="M&aacute;s sitios" color="green"}
	</center>
{/if}

{space5}

{if $visits !== false}
	<h1>Sitios m&aacute;s visitados</h1>
	<p>Estos son los sitios m&aacute;s visitados por los usuarios de este servicio:</p>
	<ul>
	{foreach item=item from=$visits}
		<li>{link href="WEB {$item->site}" caption="{$item->site}"}</li>
	{/foreach}
	</ul>
{/if}

{space5}

<h1>&iquest;C&oacute;mo publicar mi sitio web por email?</h1>
<p align="justify">Escribe un correo a Apretaste y en el asunto pon la palabra WEB PUBLICAR seguida de una palabra que identifica tu sitio. Luego adjunta los archivos que componen tu sitio.</p>
<p>Por ejemplo:</p>

Para: <b>{apretaste_email}</b><br/>
Asunto: <b>WEB PUBLICAR misitio</b><br/>
Ajuntos: <b>index.html, imagen.jpg, style.css</b><br/>

<p align="justify">El adjunto index.html es requerido y si no se env&iacute;a se crear&aacute; uno por defecto. Tu sitio web quedar&aacute; publicado en la direcci&oacute;n: <u>http://<b>misitio</b>.apretaste.com</u>.
