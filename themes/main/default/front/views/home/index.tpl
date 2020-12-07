{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.formats}
    <div class="formats-container">
        {foreach from=$content.formats key="id_format" item="format"}
            <div id="format-{$id_format}" class="format">
                <a href="{$format.link_format}" class="link-dashboard" data-toggle="tooltip" title="Go to Dashboard">
                    <span class="glyphicon glyphicon-dashboard"></span>
                </a>
                <h4>
                    <a data-toggle="collapse" href="#collapse-format-{$id_format}"
                       aria-expanded="false" aria-controls="collapse-format-{$id_format}">
                        <span class="glyphicon glyphicon-folder-open format-icon"></span>
                        <span class="glyphicon glyphicon-folder-close format-icon"></span>
                        {$format.name_format}
                    </a>
                </h4>
                <div class="collapse {if $format.opened==1}in{/if}" id="collapse-format-{$id_format}">
                    <ul>
                        {foreach from=$format.tournaments key="id_tournament" item="tournament"}
                            <li>
                                <span class="tournament-count-players"><span class="glyphicon glyphicon-user"></span> {$tournament.count_players}</span>
                                <span>{$tournament.name_tournament}</span>
                            </li>
                        {foreachelse}
                            <li class="format-empty">No tournaments</li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        {/foreach}
    </div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}