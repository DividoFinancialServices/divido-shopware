Ext.define('Shopware.apps.DividoPayment.controller.MyOwnController', {
    /**
     * Override the customer main controller
     * @string
     */
    override: 'Shopware.apps.Customer.controller.Main',

    init: function () {
        var me = this;

        me.callParent(arguments);
    }
});