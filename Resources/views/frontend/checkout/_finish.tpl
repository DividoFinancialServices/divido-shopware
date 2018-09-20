{extends file="frontend/index/index.tpl"}

{* Hide sidebar left *}
{block name='frontend_index_content_left'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}
{/block}

{* Hide breadcrumb *}
{block name='frontend_index_breadcrumb'}{/block}

{* Hide shop navigation *}
{block name='frontend_index_shop_navigation'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}
{/block}

{* Step box *}
{block name='frontend_index_navigation_categories_top'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}
{/block}

{* Hide top bar *}
{block name='frontend_index_top_bar_container'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}
{/block}

{* Footer *}
{block name='frontend_index_footer'}
    {if !$theme.checkoutFooter}
        {$smarty.block.parent}
    {else}
        {block name='frontend_index_checkout_finish_footer'}
            {include file="frontend/index/footer_minimal.tpl"}
        {/block}
    {/if}
{/block}

{* Back to the shop button *}
{block name='frontend_index_logo_trusted_shops'}
    {$smarty.block.parent}
    {if $theme.checkoutHeader}
        <a href="{url controller='index'}"
           class="btn is--small btn--back-top-shop is--icon-left"
           title="{"{s name='FinishButtonBackToShop'}{/s}"|escape}">
            <i class="icon--arrow-left"></i>
            {s name="FinishButtonBackToShop"}{/s}
        </a>
    {/if}
{/block}

{* Main content *}
{block name="frontend_index_content"}
    <div class="content checkout--content finish--content">

        {* Finish teaser message *}
        {block name='frontend_checkout_finish_teaser'}
            <div class="finish--teaser panel has--border is--rounded">

                {block name='frontend_checkout_finish_teaser_title'}
                    <h2 class="panel--title teaser--title is--align-center">{s name="FinishHeaderThankYou"}{/s} {$sShopname|escapeHtml}!</h2>
                {/block}

                {block name='frontend_checkout_finish_teaser_content'}
                    <div class="panel--body is--wide is--align-center">
                        {if $confirmMailDeliveryFailed}
                            {include file="frontend/_includes/messages.tpl" type="error" content="{s name="FinishInfoConfirmationMailFailed"}{/s}"}
                        {/if}

                        <p class="teaser--text">
                            {if !$confirmMailDeliveryFailed}
                                {s name="FinishInfoConfirmationMail"}{/s}
                                <br />
                            {/if}

                            {s name="FinishInfoPrintOrder"}{/s}
                        </p>

                        {block name='frontend_checkout_finish_teaser_actions'}
                            <p class="teaser--actions">

                                {strip}
                                {* Back to the shop button *}
                                <a href="{url controller='index'}" class="btn is--secondary teaser--btn-back is--icon-left" title="{"{s name='FinishButtonBackToShop'}{/s}"|escape}">
                                    <i class="icon--arrow-left"></i>&nbsp;{"{s name="FinishButtonBackToShop"}{/s}"|replace:' ':'&nbsp;'}
                                </a>

                                {* Print button *}
                                <a href="#" class="btn is--primary teaser--btn-print" onclick="self.print()" title="{"{s name='FinishLinkPrint'}{/s}"|escape}">
                                    {s name="FinishLinkPrint"}{/s}
                                </a>
                                {/strip}
                            </p>

                            {* Print notice *}
                            {block name='frontend_checkout_finish_teaser_print_notice'}
                                <p class="print--notice">
                                    {s name="FinishPrintNotice"}{/s}
                                </p>
                            {/block}
                        {/block}
                    </div>
                {/block}
            </div>
        {/block}
    </div>
{/block}
