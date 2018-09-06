Ext.define('Shopware.apps.DividoPayment.controller.LocalController', {
    /**
     * Override the customer main controller
     * @string
     */
    override: 'Shopware.apps.Article.controller.Main',

    init: function () {
        var me = this;

        me.callParent(arguments);
    }
});