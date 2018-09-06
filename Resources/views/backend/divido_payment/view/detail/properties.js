//{block name="backend/article/view/detail/properties"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.DividoPayment.view.detail.Properties', {
    override: 'Shopware.apps.Article.view.detail.Properties',

    createElements: function () {

        var me = this,
            fieldset = me.callParent();

        fieldset.items.push(Ext.create('Shopware.apps.DividoPayment.view.detail.PlansContainer'));

        return fieldset;
    }
});
//{/block}