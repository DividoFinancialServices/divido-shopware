Ext.define('Shopware.apps.DividoPayment.view.detail.PlansContainer', {
    extend: 'Ext.container.Container',
    padding: 10,
    title: 'Plans',

    initComponent: function () {
        var me = this;

        me.items = [{
            xtype: 'label',
            html: '<h1>Hello world</h1>'
        }];

        me.callParent(arguments);
    }
});