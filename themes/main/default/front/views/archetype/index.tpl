{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.list_archetypes}
    <form class="form-inline">
        <div class="form-group">
            {include file="includes/list_formats.tpl"}
            <select name="id_archetype" id="archetype-select" class="form-control">
                <option value="" disabled{if !$content.archetype} selected{/if}>Choose an archetype</option>
                {foreach from=$content.list_archetypes item="archetype"}
                    <option value="{$archetype.id_archetype}"{if $content.archetype.id_archetype == $archetype.id_archetype} selected{/if}>{$archetype.name_archetype}</option>
                {/foreach}
            </select>
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
            <h3 class="panel-title">{$content.global.count_players} lists -
                winrate {$content.global.winrate} %
                <span class="confidence-interval">({if $content.global.deviation_down < 0}0{else}{$content.global.deviation_down}{/if}% -
                {if $content.global.deviation_up > 100}100{else}{$content.global.deviation_up}{/if}%)</span>,
                mirror matches included
            </h3>
        </div>
        {if $content.global_rules.count_players != $content.global.count_players}
            <div class="panel-body">
                <p>
                    {$content.global_rules.count_players} lists matching rules - winrate
                    <span
                        {if $content.global_rules.winrate > $content.global.winrate}class="winrate-positive"{/if}
                        {if $content.global_rules.winrate < $content.global.winrate}class="winrate-negative"{/if}>
                        {$content.global_rules.winrate} %
                    </span>
                    <span class="confidence-interval">({if $content.global_rules.deviation_down < 0}0{else}{$content.global_rules.deviation_down}{/if}% -
                    {if $content.global_rules.deviation_up > 100}100{else}{$content.global_rules.deviation_up}{/if}%)</span>
                </p>
                <p>
                    Lists without rules - winrate {$content.global_without_rules.winrate} %
                    <span class="confidence-interval">({if $content.global_without_rules.deviation_down < 0}0{else}{$content.global_without_rules.deviation_down}{/if}% -
                        {if $content.global_without_rules.deviation_up > 100}100{else}{$content.global_without_rules.deviation_up}{/if}%)</span>
                </p>

            </div>
        {/if}
        {if !$content.included.main && !$content.included.side && !$content.excluded.main && !$content.excluded.side}
            <div class="panel-body">
                <em>No card filter yet -- click on the icons to add one !</em>
            </div>
        {else}
            <table class="table table-hover">
                {if $content.included.main}
                    <thead>
                        <tr>
                            <th colspan="2">Included cards MAINDECK</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$content.included.main key="id_card" item="name_card"}
                            <tr>
                                <td>
                                <span class="glyphicon glyphicon-remove rule-remove"
                                      data-card-id="included-main-{$id_card}"
                                      title="Remove rule"></span>
                                </td>
                                <td>{$name_card}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                {/if}
                {if $content.included.side}
                    <thead>
                        <tr>
                            <th colspan="2">Included cards SIDEBOARD</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$content.included.side key="id_card" item="name_card"}
                            <tr>
                                <td>
                                <span class="glyphicon glyphicon-remove rule-remove"
                                      data-card-id="included-side-{$id_card}"
                                      title="Remove rule"></span>
                                </td>
                                <td>{$name_card}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                {/if}
                {if $content.excluded.main}
                    <thead>
                        <tr>
                            <th colspan="2">Excluded cards MAINDECK</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$content.excluded.main key="id_card" item="name_card"}
                            <tr>
                                <td>
                                <span class="glyphicon glyphicon-remove rule-remove"
                                      data-card-id="excluded-main-{$id_card}"
                                      title="Remove rule"></span>
                                </td>
                                <td>{$name_card}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                {/if}
                {if $content.excluded.side}
                    <thead>
                        <tr>
                            <th colspan="2">Excluded cards SIDEBOARD</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$content.excluded.side key="id_card" item="name_card"}
                            <tr>
                                <td>
                                <span class="glyphicon glyphicon-remove rule-remove"
                                      data-card-id="excluded-side-{$id_card}"
                                      title="Remove rule"></span>
                                </td>
                                <td>{$name_card}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                {/if}
            </table>
        {/if}
    </div>
    <div>
        <a href="{$content.link_decklists}" class="btn btn-info link-decklists" target="_blank">
            <span class="glyphicon glyphicon-duplicate"></span> Browse all decklists with those filters
        </a>
    </div>
    {if $content.confidence}
        <p>Confidence level : {$content.confidence}</p>
    {/if}
    <table class="table table-hover table-condensed archetype-analysis" data-toggle="table" data-sort-name="winrate_with" data-sort-order="desc">
        <thead>
            <tr>
                <th>Actions</th>
                <th data-field="name_card" data-sortable="true">Name</th>
                <th data-field="count_copies" data-sortable="true">Average # copies</th>
                <th data-field="count_lists" data-sortable="true"># lists</th>
                <th data-field="winrate_with" data-sortable="true">Winrate</th>
                <th>Confidence</th>
                <th data-field="winrate_without" data-sortable="true">W% without</th>
                <th>Confidence</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$content.cards item="card"}
                {if $card.avg_main > 0}
                    <tr>
                        <td>
                            {if $card.display_actions_main == 1}
                                <span class="glyphicon glyphicon-plus-sign card-include"
                                      data-card-id="included-main-{$card.id_card}"
                                      title="Select only decklists containing this card"></span>
                                <span class="glyphicon glyphicon-minus-sign card-exclude"
                                      data-card-id="excluded-main-{$card.id_card}"
                                      title="Exclude decklists containing this card"></span>
                            {/if}
                        </td>
                        <td>
                            {$card.name_card}
                        </td>
                        <td {if $card.avg_main == 4}class="highlight"{/if}>{$card.avg_main|floatval}</td>
                        <td {if $content.global_rules.count_players == $card.count_players_main}class="strong"{/if}>{$card.count_players_main}</td>
                        <td {if $card.winrate_main > ($content.global_rules.winrate+5)}class="winrate-positive"
                                {else}
                                {if $card.winrate_main > $content.global_rules.winrate}class="winrate-slightly-positive"
                                {else}
                                    {if $card.winrate_main < ($content.global_rules.winrate-5)}class="winrate-negative"
                                    {else}
                                        {if $card.winrate_main < $content.global_rules.winrate}class="winrate-slightly-negative"
                                        {/if}
                                    {/if}
                                {/if}
                                {/if}>
                            {$card.winrate_main} %
                        </td>
                        <td>
                            <span class="confidence-interval">
                                ({if $card.deviation_down_main < 0}0{else}{$card.deviation_down_main}{/if}% -
                                {if $card.deviation_up_main > 100}100{else}{$card.deviation_up_main}{/if}%)
                            </span>
                        </td>
                        <td>
                            {if $card.winrate_without_main}{$card.winrate_without_main} %{/if}
                        </td>
                        <td>
                            {if $card.winrate_without_main}
                                <span class="confidence-interval">
                                    ({if $card.deviation_down_without_main < 0}0{else}{$card.deviation_down_without_main}{/if}% -
                                    {if $card.deviation_up_without_main > 100}100{else}{$card.deviation_up_without_main}{/if}%)
                                </span>
                            {/if}
                        </td>
                    </tr>
                {/if}
            {/foreach}
        </tbody>
    </table>
    <h3>SIDEBOARDS</h3>
    <table class="table table-hover table-condensed archetype-analysis" data-toggle="table" data-sort-name="winrate_with" data-sort-order="desc">
        <thead>
            <tr>
                <th>Actions</th>
                <th data-field="name_card" data-sortable="true">Name</th>
                <th data-field="count_copies" data-sortable="true">Average # copies</th>
                <th data-field="count_lists" data-sortable="true"># lists</th>
                <th data-field="winrate_with" data-sortable="true">Winrate</th>
                <th>Confidence</th>
                <th data-field="winrate_without" data-sortable="true">W% without</th>
                <th>Confidence</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$content.cards item="card"}
                {if $card.avg_side > 0}
                    <tr>
                        <td>
                            {if $card.display_actions_side == 1}
                                <span class="glyphicon glyphicon-plus-sign card-include"
                                      data-card-id="included-side-{$card.id_card}"
                                      title="Select only decklists containing this card"></span>
                                <span class="glyphicon glyphicon-minus-sign card-exclude"
                                      data-card-id="excluded-side-{$card.id_card}"
                                      title="Exclude decklists containing this card"></span>
                            {/if}
                        </td>
                        <td>{$card.name_card}</td>
                        <td {if $card.avg_side == 4}class="highlight"{/if}>{$card.avg_side|floatval}</td>
                        <td {if $content.global_rules.count_players == $card.count_players_side}class="strong"{/if}>{$card.count_players_side}</td>
                        <td {if $card.winrate_side > ($content.global_rules.winrate+5)}class="winrate-positive"
                            {else}
                                {if $card.winrate_side > $content.global_rules.winrate}class="winrate-slightly-positive"
                                {else}
                                    {if $card.winrate_side < ($content.global_rules.winrate-5)}class="winrate-negative"
                                    {else}
                                        {if $card.winrate_side < $content.global_rules.winrate}class="winrate-slightly-negative"
                                        {/if}
                                    {/if}
                                {/if}
                            {/if}>
                            {$card.winrate_side} %
                        </td>
                        <td class="confidence-interval">
                            {if $card.deviation_down_side < 0}0{else}{$card.deviation_down_side}{/if}% -
                            {if $card.deviation_up_side > 100}100{else}{$card.deviation_up_side}{/if}%
                        </td>
                        <td>
                            {if $card.winrate_without_side}{$card.winrate_without_side} %{/if}
                        </td>
                        <td class="confidence-interval">
                            {if $card.winrate_without_side}
                                {if $card.deviation_down_without_side < 0}0{else}{$card.deviation_down_without_side}{/if}% -
                                {if $card.deviation_up_without_side > 100}100{else}{$card.deviation_up_without_side}{/if}%
                            {/if}
                        </td>
                    </tr>
                {/if}
            {/foreach}
        </tbody>
    </table>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}