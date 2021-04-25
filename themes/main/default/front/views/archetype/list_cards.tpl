{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.archetype}
    <div class="page-header">
        <h1>Cards list - {$content.archetype.name_archetype}{if $content.format} <small>{$content.format.name_format}</small>{/if}</h1>
    </div>
    {if $content.link_analysis}
        <a href="{$content.link_analysis}" class="btn btn-info" target="_blank">
            <span class="glyphicon glyphicon-screenshot"></span> Full analysis
        </a>
    {/if}
{/if}

{if $content.cards}

    <table class="table table-hover table-condensed archetype-analysis" data-toggle="table" data-sort-name="count_players" data-sort-order="desc">
        <thead>
            <tr>
                <th data-field="name_card" data-sortable="true">Name</th>
                <th data-field="avg_copies" data-sortable="true">Average # copies</th>
                <th data-field="count_players" data-sortable="true"># lists</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$content.cards item="card"}
                {if $card.count_players_main > 0}
                    <tr>
                        <td>{$card.name_card}</td>
                        <td {if $card.avg_main == 4}class="highlight"{/if}>{$card.avg_main|floatval}</td>
                        <td {if $card.count_players_main == $content.count_players}class="highlight"{/if}>{$card.count_players_main}</td>
                    </tr>
                {/if}
            {/foreach}
        </tbody>
    </table>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}