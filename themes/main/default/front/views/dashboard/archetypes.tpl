{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Archetypes mapping</h1>

<p class="bg-info">
    Archetypes order matter !<br /> A rule will only be tested if all previous archetypes did not match for a given decklist.
</p>

{if $content.formats}
    <ul class="nav nav-tabs">
        {foreach from=$content.formats key="id_format" item="format"}
            <li{if $id_format==1} class="active"{/if}><a data-toggle="tab" href="#format_{$id_format}">{$format.name_format}</a></li>
        {/foreach}
    </ul>

    <div class="tab-content">
        {foreach from=$content.formats key="id_format" item="format"}
            <div id="format_{$id_format}" class="tab-pane fade{if $id_format==1} in active{/if}">
                <table class="table table-hover table-condensed table-archetypes">
                    <thead>
                        <tr>
                            <th class="archetype-image">Image</th>
                            <th>Archetype</th>
                            <th>Must contain</th>
                            <th>Must not contain</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$format.archetypes key="archetype" item="rules"}
                            <tr>
                                <td class="archetype-image" style="background: no-repeat  top -32px right 50%/120% url({$rules.image_card});"></td>
                                <td class="archetype-name">{$archetype}</td>
                                <td>
                                    {foreach from=$rules.contains item="card"}
                                        {$card}<br />
                                    {/foreach}
                                </td>
                                <td>
                                    {foreach from=$rules.exclude item="card"}
                                        {$card}<br />
                                    {/foreach}
                                </td>
                            </tr>
                        {/foreach}
                        <tr>
                            <td>Tier 3 - Other</td>
                            <td colspan="2">Everything else</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        {/foreach}
    </div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}