{extends file="frontend/checkout/finish.tpl"}

{block name="frontend_checkout_finish_teaser_content"}

   <div class="panel--body is--wide is--align-center">
                        {if $confirmMailDeliveryFailed}
                            {include file="frontend/_includes/messages.tpl" type="error" content="{s name="FinishInfoConfirmationMailFailed"}{/s}"}
                        {/if}

                
                    </div>



        <script>
    document.body.classList.add("is--ctl-checkout");
    document.body.classList.add("is--act-finish");
    document.body.classList.add("is--minimal-header");

    </script>
{/block}
