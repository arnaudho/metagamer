{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.list_archetypes}
    <form class="form-inline">
        <div class="form-group">
            <select name="id_archetype" id="archetype-select" class="form-control">
                <option value="" disabled{if !$content.archetype} selected{/if}>Choose an archetype</option>
                {foreach from=$content.list_archetypes item="archetype"}
                    <option value="{$archetype.id_archetype}"{if $content.archetype.id_archetype == $archetype.id_archetype} selected{/if}>{$archetype.name_archetype}</option>
                {/foreach}
            </select>
            {include file="includes/list_formats.tpl"}
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span></button>
        </div>
    </form>
{/if}
{if $content.archetype}
    <div class="page-header">
        <h1>Archetype analysis - {$content.archetype.name_archetype}{if $content.format} <small>{$content.format.name_format}</small>{/if}</h1>
    </div>
{/if}

{if $content.cards}
    <div class="panel panel-info archetypes-info">
        <div class="panel-heading">
            <h3 class="panel-title">{$content.count_players} lists - winrate {$content.global.winrate} % ({$content.global.deviation_down}% - {$content.global.deviation_up}%), mirror matches included</h3>
        </div>
        <div class="panel-body">
            <ul>
                <li>Rule 1</li>
                <li>Rule 2</li>
            </ul>
        </div>
    </div>
    <table class="table table-hover table-condensed">
        <thead>
            <tr>
                <th>Name</th>
                <th>Average # copies</th>
                <th>Winrate</th>
                <th>Confidence interval</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$content.cards item="card"}
                {if $card.avg_main > 0}
                    <tr>
                        <td>{$card.name_card}</td>
                        <td {if $card.avg_main == 4}class="highlight"{/if}>{$card.avg_main|floatval}</td>
                        <td {if $card.winrate_main > $content.global.winrate}class="winrate-positive"{/if}
                            {if $card.winrate_main < $content.global.winrate}class="winrate-negative"{/if}>
                            {$card.winrate_main} %
                        </td>
                        <td>{$card.deviation_down_main}% - {$card.deviation_up_main}%</td>
                    </tr>
                {/if}
            {/foreach}
        </tbody>
        <thead>
            <tr>
                <th colspan="3">SIDEBOARDS</th>
            </tr>
        </thead>
        <tbody>
        {foreach from=$content.cards item="card"}
            {if $card.avg_side > 0}
                <tr>
                    <td>{$card.name_card}</td>
                    <td {if $card.avg_side == 4}class="highlight"{/if}>{$card.avg_side|floatval}</td>
                    <td {if $card.winrate_side > $content.global.winrate}class="winrate-positive"{/if}
                        {if $card.winrate_side < $content.global.winrate}class="winrate-negative"{/if}>
                        {$card.winrate_side} %
                    </td>
                    <td>{$card.deviation_down_side}% - {$card.deviation_up_side}%</td>
                </tr>
            {/if}
        {/foreach}
        </tbody>
    </table>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}