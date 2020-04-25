<select name="id_format" id="format-select" class="form-control">
    <option value="" disabled{if !$content.format} selected{/if}>Choose a format</option>
    {foreach from=$content.list_formats item="format"}
        <option value="{$format.id_format}"{if $content.format.id_format == $format.id_format} selected{/if}>{$format.name_format}</option>
    {/foreach}
</select>