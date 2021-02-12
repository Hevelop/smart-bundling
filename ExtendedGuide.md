Hevelop Smart Bundling
=======================

Extended Guide
-------------

Step 1)

Install the module `composer require hevelop/smart-bundling`

Step 2)
Add a new file to one of your modules in the `etc` folder. Call the file `smart_bundling.xml`

_Example_ 
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Hevelop_SmartBundling:etc/smart_bundling.xsd">
    <area name="frontend">
        <theme name="/ThemeVendor\/ThemeName/">
            <shared>
                <file>jquery.js</file>
                <file>requirejs/domReady.js</file>
                <file>mage/requirejs/text.min.js</file>
                <file>jquery/patches/jquery.js</file>
                ....
            </shared>
    
            <action name="catalog-category-view">
                <file>mage/trim-input.js</file>
            </action>
        </theme>
    </area>
</config>
```

Quick explanation: 
* in the shared node put as many `<file>` nodes as files
that are shared through multiple views/controllers
* in the `<action name="action-name">` node put as many `<file>` nodes
as you need for a specific view/action

Step 3)

Deploy :)

#### Notes

For quick testing: 

1. Put your local development in production mode `php bin/magento deploy:mode:set production -s` (the bundling will work only in 
production mode).
2. Build your assets as you would do during deploy.
3. Visit your frontend and observe, in the browsers devtools, you network requests. The bundling
should work if you do not see any, or very few, js downloaded and requestet by requrie.js

Quick development:

You can use the following tool https://github.com/magento/m2-devtools (you need to build it locally and
activate dev mode for your extension in chrome in order to use it). Using the following guide
https://github.com/magento/m2-devtools/blob/master/docs/panels/RequireJS.md scan your frontend
in order to retrive all the modules that are requried.

_Example_
```javascript
({
    ...
    "modules": [
        {
            // Modules used on > 1 page(s) of the store
            "name": "bundles/shared",
            "include": [
                "jquery",
                "domReady",
                "jquery/patches/jquery",
                "jquery/jquery.mobile.custom",
                "jquery/jquery.cookie",
                "jquery/jquery-migrate",
                "jquery-ui-modules/widget",
                "Amasty_Shopby/js/amShopbyTopFilters",
                "underscore",
                "mage/template",
                "Magento_Theme/js/device.min",
                "jquery/patches/jquery-ui",
                "matchMedia",
                "knockoutjs/knockout",
                "knockoutjs/knockout-es5",
    ...
``` 

As you can see the module will output a list of modules for every page you've visisted. 
The fastest way to get starting is to copy the content (replacing `"..",` with `<file>...</file>` ) in
the `shared` (and in the relative `action`) node.

Here comes the tricky part: this chrome extension does only half the work.
1. we need to add the extensions to all files (eg. `.js`)
2. We need to remove all `text!` strings
3. We need to replace `text!ui/template` with `Magento_Ui/templates`

This is the first step.

The bundling is generated AFTER the various theme assets are generated, this means that
if you enabled js minification, you need to add `.min.js` as well.

Now it's time to scan your frontend and analyze your network tab. You will see that require.js
adds requests and downloads a lot of files. Adjust your `file` nodes accordingly

_Example_
```
https://yourdomain.com/static/version1613139455/frontend/ThemeVendor/ThemeName/en_US/Magento_Ui/js/lib/logger/logger.js
```
becomes
```xml
<file>Magento_Ui/js/lib/logger/logger.js</file>
```