Hevelop Smart Bundling
=======================

This module changes the behaviour of the core JS bundling to generates bundles
by pages (cms-index-index, catalog-category-view, catalog-product-view, etc).

Installation
-------------
1. Add the module to your project (and enable it!);
2. Set the following config:
   - dev/js/enable_smart_bundling = 1
3. I file js dovranno essere inseriti con l'apposita estensione.
    - se alcuni file js del progetto hanno il .min.js, andrà inserito anche quello.
4. per i file .html, il path `text!ui/template/{file_path}` andrà sostituito con `Magento_Ui/templates/{file_path}`
5. I file .js che vengono inclusi tramite xml nella sezione `<head></head>` non possono essere inseriti nello smart_bundling
6. Per alcuni file js (es domReady.js) è necessario specificare il path corretto:
    - Il bundling segnalerà: domReady.js
    - Dovrà essere incluso in questo modo: `<file>requirejs/domReady.js</file>`
7. Eliminare tutti i prefissi `text!` e utilizzare il path del modulo
8. Per i .js inseriti in `app/design/frontend/{VendorName}/{ModuleName}/web/js/` basterò inserire:
    - `<file>js/{fileName}.js</file>`
9. E' possibile inserire anche file che nel branch attuale non ci sono, ma che poi facendo il merge in stg o prd ci saranno.
    - Se lo smart_bundling non trova il file, non lo inserisce.

Configuration of the bundles
-----------------------------
Now you have to configure the bundles. The raccomanded tool is https://github.com/magento/m2-devtools/blob/master/docs/panels/RequireJS.md

 