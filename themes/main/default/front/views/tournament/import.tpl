{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Import tournament</h1>

<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#cfb">CFB Events</a></li>
    <li><a data-toggle="tab" href="#mtgmelee">MTG Melee</a></li>
    <li><a data-toggle="tab" href="#mtgmelee_decklists">MTG Melee - decklists</a></li>
</ul>

<div class="tab-content">
    <div id="cfb" class="tab-pane fade in active">
        <h3>CFB Events</h3>
        <form class="form-inline" method="post">
            <div class="form-group">
                <select name="import-cfb[id_format]" class="form-control">
                    <option value="" disabled{if !$content.format} selected{/if}>Choose a format</option>
                    {foreach from=$content.list_formats item="format"}
                        <option value="{$format.id_format}"{if $content.format.id_format == $format.id_format} selected{/if}>{$format.name_format}</option>
                    {/foreach}
                </select>
                <input type="text" placeholder="Tournament URL" name="import-cfb[url]" class="form-control">
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
    <div id="mtgmelee" class="tab-pane fade">
        <h3>MTG Melee</h3>

        <form class="form-inline" id="import-mtgmelee" method="post">
            <div class="form-group">
                <h5>Enter tournament name, date and format for first round</h5>
                <input name="import-mtgmelee[tournament_name]" type="text" placeholder="Tournament name" class="form-control" />
                <input name="import-mtgmelee[tournament_date]" type="date" class="form-control" />
                <select name="import-mtgmelee[id_format]" class="form-control">
                    <option value="" disabled{if !$content.format} selected{/if}>Format</option>
                    {foreach from=$content.list_formats item="format"}
                        <option value="{$format.id_format}"{if $content.format.id_format == $format.id_format} selected{/if}>{$format.name_format}</option>
                    {/foreach}
                </select>
                <hr />
                <textarea name="import-mtgmelee[data]" class="form-control" rows="8" cols="40" placeholder="Paste pairings data here..."></textarea>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
    <div id="mtgmelee_decklists" class="tab-pane fade">
        <h3>MTG Melee - Parse decklists</h3>

        {if $content.count_waiting}
            <p>{$content.count_waiting} decklists without archetype</p>
        {/if}
        <form class="form-inline" method="post">
            <div class="form-group">
                <input type="text" value="{if $content.count_waiting == 0}0{else}50{/if}" name="import-mtgmelee-decklists[count]" class="form-control">
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>

{if $content.data!==null}
    <h3>Import successful !</h3>
    <h4>{$content.data.name_tournament} (id#{$content.data.id_tournament})</h4>
    <p>{$content.data.count_players} players imported</p>
    <p>{$content.data.count_matches} matches imported</p>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}