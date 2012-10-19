Widgetkit K2 pulls together K2 and Widgetkit displaying K2 content with Widgetkit anywhere on your site.

You don't need any additional knowledge to get this going:
- install and activate the plugin located in the system folder
- go to the Widgetkit component and click on "Use K2"
- you will be presented with the already familiar options of:
    - which contents to select from K2 and what part of those contents to display
    - how to display the selected contents in WidgetKit

Layout overrides
----------------
- there is a default layout template provided in `layouts/item.php`. You can customize that layout to your liking. Best practice for such customization is through override. You can copy that file to your `templates/YOURTEMPLATE/html/plg_widgetkit_k2` folder (you probably need to create that folder yourself).
- the products layout can vary based on product, product category or simply generic one that applies to all by applying the naming conventions below:
    - `i<k2 item id>.php` - product specific layout
    - `c<k2 category id>.php` - category specific layout
    - `item.php` - generic layout

Requirements
------------
K2 Content module (included in your K2 installation) must be installed but not necessarily activated