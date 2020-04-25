{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Import tournament</h1>
<form class="form-inline" method="post">
    <div class="form-group">
        {include file="includes/list_formats.tpl"}
        <input type="text" placeholder="Tournament URL" name="url" class="form-control">
        <button type="submit" class="btn btn-primary">Import</button>
    </div>
</form>

{if $content.data!==null}
    <h3>Import successful !</h3>
    <h4>{$content.data.name_tournament}</h4>
    <p>{$content.data.count_players} players imported</p>
    <p>{$content.data.count_matches} matches imported</p>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}