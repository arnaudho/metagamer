<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$configuration.global_encoding}" >
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <base href="{$configuration.server_url}"/>
    <title>{$head.title}</title>
    {if !null == $content.canonical && !empty($content.canonical)}
        <link rel="canonical" href="{$content.canonical}">
    {/if}
    <meta name="description" content="{$head.description}"/>
    <link type="text/css" rel="stylesheet" href="{$path_to_theme}/css/style.css">
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
    {foreach from=$styles item=style}
        <link type="text/css" rel="stylesheet" href="{$style}">
    {/foreach}
    {foreach from="$scripts" item=script}
        <script type="text/javascript" src="{$script}"></script>
    {/foreach}
</head>
<body>
<div class="container">
    <div class="login-wrap">
        <div class="login-html">
            <div class="form-title">
                <h3>Sign in</h3>
            </div>
            {if $content.error!=""}
                <div class='error'>{$content.error}</div>
            {/if}
            <div class="login-form">
                <div class="sign-in-htm">
                    {form.login->display}
                    <div class="hr"></div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>