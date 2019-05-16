<?php

if (!isset($modx)) {
    require_once dirname(dirname(__DIR__)) . '/config.core.php';
    require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
    $modx = new modX();
    $modx->initialize('mgr');
    $modx->getService('error','error.modError', '', '');
    $modx->loadClass('transport.modPackageBuilder');
    $modx->loadClass('transport.xPDOTransport', XPDO_CORE_PATH, true, true);
}

$commerceAvailable = false;
$corePath = $modx->getOption('commerce.core_path',null,$modx->getOption('core_path').'components/commerce/');
if ($commerce = $modx->getService('commerce','Commerce',$corePath.'model/commerce/')) {
    $commerceAvailable = true;
}
$def = include __DIR__ . '/data/theme.inc.php';
$flagModified = <<<HTML
<span class="icon icon-warning-sign" title="It looks like this element was modified since its initial installation. Choosing to upgrade it will overwrite your modifications.">[modified]</span>
HTML;

//var_dump($def);

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:

        $output = array();

        $output[] = '<h2>Theme Red Installation</h2>';
        $output[] = '<p>Please choose the elements that you would like to create and/or update. For a first installation, choosing all options is recommended to make sure you start with a fully functional template. For updates you will need to make sure any custom changes are not overwritten (unless you want them too).</p>';

        $attributes = 'checked="checked"';
        $output[] = <<<HTML
<p>
    <label>
        <input type="checkbox" name="write_assets" value="1" {$attributes}>
        <b>Create/Overwrite JavaScript/CSS</b>
    </label>
    Check this to overwrite the JavaScript/CSS. Theme Red allows you to <a href="#docs">write custom assets</a> that extend the default and are safe from upgrades, but if you want to revert to the standard (or if this is the first installation), check this box to create them fresh for you.
</p>
HTML;


        $output[] = <<<HTML
<style type="text/css">
.cti .elements-container {
    display: flex;    
}
.cti .element-column {
    flex: 1 0 25%;
}
.cti .element-column h3 {
    border-bottom: solid 3px #38008d;
    font-weight: normal;
    font-size: 1.5rem;
    margin-right: 1rem;
}
.cti .element-meta {
    color: #999;
}
.cti .element-list {
    list-style-type: none;
    margin: 0;
    padding: 0;
}
.cti .nested-element {
    padding-left: 1.5rem;
    list-style-type: none;
}
.cti .element {
    
}
</style>
HTML;

        $packages = [];
        foreach ($def['packages'] as $provider => $provPackages) {
            $thisPackages = [];
            foreach ($provPackages as $package) {
                $attributes = 'checked="checked"';
                $thisPackages[] = <<<HTML
    <li class="element">
        <label>
            <input type="checkbox" class="element-checkbox" {$attributes}>
            <span class="element-name">{$package}</span>
        </label>
    </li>
HTML;
            }
            $thisPackages = implode("\n", $thisPackages);
            $packages[] = <<<HTML
<p class="element-meta">From {$provider}:</p>
<ul class="element-list">
    {$thisPackages}
</ul>
HTML;
        }
        $packages = implode("\n", $packages);

        $templates = [];
        foreach ($def['templates'] as $templateName => $template) {
            $attributes = 'checked="checked"';
            $flag = '';
            /** @var modElement $el */
            if ($el = $modx->getObject('modTemplate', $template['primary'])) {
                $props = $el->get('properties');
                $props = is_array($props) ? $props : [];
                $hash = (string)$props['_theme_hash'];
                if ($hash !== sha1($el->get('content'))) {
                    $flag = $flagModified;
                    $attributes = '';
                }
            }

            $displayName = substr($templateName, strlen($def['template_prefix']));

            $templates[] = <<<HTML
<li class="element">
    <label>
        <input type="checkbox" name="templates[]" value="{$templateName}" class="element-checkbox" {$attributes}>
        <span class="element-name">{$displayName}</span>
        <span class="element-flag">{$flag}</span>
    </label>
</li>
HTML;
        }
        $templates = implode("\n", $templates);

        $chunks = [];
        foreach ($def['chunks'] as $chunkName => $chunk) {
            $attributes = 'checked="checked"';
            $flag = '';
            /** @var modElement $el */
            if ($el = $modx->getObject('modChunk', $chunk['primary'])) {
                $props = $el->get('properties');
                $props = is_array($props) ? $props : [];
                $hash = (string)$props['_theme_hash'];
                if ($hash !== sha1($el->get('content'))) {
                    $flag = $flagModified;
                    $attributes = '';
                }
            }

            $displayName = substr($chunkName, strlen($def['chunk_prefix']));

            $chunks[] = <<<HTML
<li class="element">
    <label>
        <input type="checkbox" name="chunks[]" value="{$chunkName}" class="element-checkbox" {$attributes}>
        <span class="element-name">{$displayName}</span>
        <span class="element-flag">{$flag}</span>
    </label>
</li>
HTML;
        }
        $chunks = implode("\n", $chunks);


        $resources = [];
        foreach ($def['resources'] as $alias => $resource) {
            $children = [];
            foreach ($resource['children'] as $childAlias => $childResource) {


                $nestedChildren = [];
                foreach ($childResource['children'] as $nestedChildAlias => $nestedChildResource) {
                    $displayName = $nestedChildResource['pagetitle'];
                    $flag = '';
                    $attributes = 'checked="checked"';
                    $nestedChildren[] = <<<HTML
<li class="element">
    <label>
        <input type="checkbox" name="resources[]" value="{$alias}.{$childAlias}.{$nestedChildAlias}" class="element-checkbox" {$attributes}>
        <span class="element-name">{$displayName}</span>
        <span class="element-flag">{$flag}</span>
    </label>
</li>
HTML;
                }
                $nestedChildren = implode("\n", $nestedChildren);
                if (!empty($nestedChildren)) {
                    $nestedChildren = <<<HTML
<ul class="nested-element">
    {$nestedChildren}
</ul>
HTML;
                }

                $displayName = $childResource['pagetitle'];
                $flag = '';
                $attributes = 'checked="checked"';
                $children[] = <<<HTML
<li class="element">
    <label>
        <input type="checkbox" name="resources[]" value="{$alias}.{$childAlias}" class="element-checkbox" {$attributes}>
        <span class="element-name">{$displayName}</span>
        <span class="element-flag">{$flag}</span>
    </label>
    {$nestedChildren}
</li>
HTML;
            }
            $children = implode("\n", $children);
            if (!empty($children)) {
                $children = <<<HTML
<ul class="nested-element">
    {$children}
</ul>
HTML;
            }

            $displayName = $resource['pagetitle'];
            $flag = '';
            $attributes = 'checked="checked"';
            $resources[] = <<<HTML
<li class="element">
    <label>
        <input type="checkbox" name="resources[]" value="{$alias}" class="element-checkbox" {$attributes}>
        <span class="element-name">{$displayName}</span>
        <span class="element-flag">{$flag}</span>
    </label>
    {$children}
</li>
HTML;
        }
        $resources = implode("\n", $resources);

        $output[] = <<<HTML
<div class="cti">
    <div class="elements-container">
        <div class="element-column">
            <h3>Packages</h3>
            {$packages}
        </div>
        <div class="element-column">
            <h3>Templates</h3>
            <p class="element-meta">Includes TVs. Prefix: "{$def['template_prefix']}"</p>
            <ul class="element-list">
                {$templates}
            </ul>
        </div>
        <div class="element-column">
            <h3>Chunks</h3>
            <p class="element-meta">Prefix: "{$def['chunk_prefix']}"</p>
            <ul class="element-list">
                {$chunks}
            </ul>
        </div>
        <div class="element-column">
            <h3>Resources</h3>
            <div class="element-meta">
                Context: 
                <select name="target_context">
                    <option value="web">Web</option>
                    <option value="web2">Web2</option>
                </select>
                </div>
            <p class="element-meta">
                <b>Important:</b> <b>new resources</b> are created when checked, regardless if they already exist, and associated (context) settings will be updated.<br>
            </p>
            <ul class="element-list">
                {$resources}
            </ul>
        </div>
    </div>
</div>
HTML;

        $output = implode('', $output);
    break;
    default:
    case xPDOTransport::ACTION_UNINSTALL:
        $output = '';
    break;
}
echo $output; exit(); //@fixme
return $output;