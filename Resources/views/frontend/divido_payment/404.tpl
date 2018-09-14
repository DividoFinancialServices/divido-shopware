{extends file="parent:frontend/checkout/finish.tpl"}

{block name="frontend_checkout_finish_teaser_content"}
    <p class="teaser--text is--align-center">This order could not be found. Please try again</p>
    <script>
        document.body.classList.add("is--ctl-checkout");
        document.body.classList.add("is--act-finish");
        document.body.classList.add("is--minimal-header");
    </script>
{/block}