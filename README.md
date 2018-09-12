# Divido Shopware

# Installation

For manual installation of the most recent version of the plugin follow all steps.

Login to your shopware backend, and open the plugin manager.

1. Click upload plugin
2. Select the provided Zip File
3. Click Upload Plugin
4. You should now see Divido Payment in the Uninstalled plugin list
5. Click the green install plugin button
#![Steps 1-5](https://s3-eu-west-1.amazonaws.com/content.divido.com/images/documentation/shopware/Step1-5.png)

6. Hit install
#![Step 6](https://s3-eu-west-1.amazonaws.com/content.divido.com/images/documentation/shopware/Step6.png)
7. Enter your configuration details - you api key will be provided by your Client Success manager.
8. Click Save
9. Click Activate
10. Click Configuration > Payment Methods
#![Steps 7-10](https://s3-eu-west-1.amazonaws.com/content.divido.com/images/documentation/shopware/Step7-10.png)

11. Click into the Divido Payment method
12. Click Active to selected
13. Hit Save - The plugin is now installed configured and active you may also need to clear your caches for the plugin to be visible 
14. To clear the cache go to Configuration > Cache/Performance 
#![Step 11-14](https://s3-eu-west-1.amazonaws.com/content.divido.com/images/documentation/shopware/Step11-14.png)

## Custom Finance Calculator

Version 0.3.0.0 brings about new added flexibility to incorporate Divido widgets into your custom pages. By following the instructions below you can generate one of two types of finance calculator, which will update automatically based on the figure entered into a text box.

1. Enter the Backend of your online shop
![Step 1](https://s3-eu-west-1.amazonaws.com/content.divido.com/images/documentation/shopware/embedded-calculator/1.png)
2. Go to the "Shop pages" subsection of the "Content" section
![Step 2](https://s3-eu-west-1.amazonaws.com/content.divido.com/images/documentation/shopware/embedded-calculator/2.png)
3. Select the directory and the page you would like to edit / add a new page to the shop
4. Click on the HTML Source Editor button in the text editor
5. Insert the html for an input field, including the class name ‘divido-calculate’ (ie. <input type=“number” class=“divido-calculate” />). This will generate the block version of the widget (fig.9)
![Steps 3-8](https://s3-eu-west-1.amazonaws.com/content.divido.com/images/documentation/shopware/embedded-calculator/3-8.png)
6. If you wish to use a pop-up version of the widget (fig.10), include the class ‘divido-popup’ also (ie. <input type=“number” class=“divido-calculate divido-popup” />)
![Step 6](https://s3-eu-west-1.amazonaws.com/content.divido.com/images/documentation/shopware/embedded-calculator/6.png)
7. Click on the Update button in the HTML Source Editor window
8. Click on the Save button on the Shop pages page
9. The block payment calculator widget will generate directly below the input box
![Step 9](https://s3-eu-west-1.amazonaws.com/content.divido.com/images/documentation/shopware/embedded-calculator/9.png)
10. If you have chosen the popup option (as outlined in point 6), a small area of text will be generated underneath the input box which can be clicked on to obtain the full list of payment options available to the customer
![Step 10](https://s3-eu-west-1.amazonaws.com/content.divido.com/images/documentation/shopware/embedded-calculator/10.png)

Please be aware that may experience technical issues if you try to create more than one finance calculator on a page.

## Support

If you are having general issues with this plugin, your first port of call should be our documentation.

If you want to keep up to date with release announcements, discuss ideas for the project,
or ask more detailed questions, you can contact our support deparment support@divido.com

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/DividoFinancialServices/divido-shopware/issues),
or better yet, fork the library and submit a pull request.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email jonathan.carter@divido.com instead of using the issue tracker.

## Credits

- [:DividoFinancialServices](https://github.com/DividoFinancialServices)
- [All Contributors](../../contributors)

