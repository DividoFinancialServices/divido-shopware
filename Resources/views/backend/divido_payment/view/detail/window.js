//{block name="backend/article/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.DividoPayment.view.detail.Window', {
    override: 'Shopware.apps.Article.view.detail.Window',
    
    getTabs: function () {
        
        var me = this,
            tabs = me.callParent();

        tabs.push(Ext.create('Shopware.apps.DividoPayment.view.detail.MyOwnTab'));
        
        return tabs;
    }
});
//{/block}
