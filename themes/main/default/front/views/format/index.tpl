{if !$request_async}{include file="includes/head.tpl"}{/if}

<form class="form-inline" id="create-format" method="post">
    <h4>Create folder</h4>
    <div class="form-group">
        <select name="create-format[id_type_format]" class="form-control">
            {foreach from=$content.type_formats item="type_format"}
                <option value="{$type_format.id_type_format}">{$type_format.name_type_format}</option>
            {/foreach}
        </select>
        <input type="text" placeholder="Format name" name="create-format[name_format]" class="form-control">
        <button type="submit" class="btn btn-primary">Create</button>
    </div>
</form>
{if $content.formats}
    <div class="formats-container">
        {foreach from=$content.formats key="id_format" item="format"}
            <div id="format-{$id_format}" class="format">
                {if $format.tournaments}
                    <div class="home-links">
                        <a href="{$format.link_metagame}" data-toggle="tooltip" title="Metagame breakdown" target="_blank">
                            <span class="glyphicon glyphicon-signal"></span>
                        </a>
                        <a href="{$format.link_other}" data-toggle="tooltip" title="Browse 'Other' decklists">
                            <span class="glyphicon glyphicon-list"></span>
                        </a>
                        <a href="{$format.link_dashboard}" data-toggle="tooltip" title="Go to Dashboard">
                            <span class="glyphicon glyphicon-dashboard"></span>
                        </a>
                    </div>
                {/if}
                <h4>
                    <a data-toggle="collapse" href="#collapse-format-{$id_format}"
                       aria-expanded="false" aria-controls="collapse-format-{$id_format}">
                        <span class="glyphicon glyphicon-folder-open format-icon"></span>
                        <span class="glyphicon glyphicon-folder-close format-icon"></span>
                        {$format.name_format}
                    </a>
                    <span style="font-size: 11px; margin: 0 10px;">{$format.date_min}{if $format.date_min != $format.date_max} - {$format.date_max}{/if}</span>
                    {if $format.date_diff > 7}<span class="glyphicon glyphicon-warning-sign gl-danger" data-toggle="tooltip" title="Time interval > 1 week"></span>{/if}
                </h4>
                <div class="collapse {if $format.opened==1}in{/if}" id="collapse-format-{$id_format}">
                    <ul>
                        {foreach from=$format.tournaments key="id_tournament" item="tournament"}
                            <li>
                                <span class="tournament-info-icon" data-toggle="tooltip" title="{$tournament.count_players} players"><span class="glyphicon glyphicon-user"></span> {$tournament.count_players}</span>
                                <span class="tournament-info-icon" data-toggle="tooltip" title="{$tournament.count_matches} matches"><span class="glyphicon glyphicon-adjust"></span> {$tournament.count_matches}</span>
                                <span class="tournament-info-icon tournament-count-rounds" data-toggle="tooltip" title="{$tournament.count_rounds} rounds"><span class="glyphicon glyphicon-expand"></span> {$tournament.count_rounds}</span>
                                <span>{$tournament.name_tournament}</span>
                            </li>
                            {foreachelse}
                            <li class="format-empty">No tournaments</li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        {/foreach}
        {if $content.link_full}
            <a href="{$content.link_full}" class="btn btn-default link-full">Load more<br /><span class="glyphicon glyphicon-chevron-down"></span></a>
        {/if}
    </div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}