{extends file="frontend/checkout/confirm.tpl"}
{debug}

{block name="frontend_index_content"}
{debug}

    <div id="payment">
    <h2>
    Choose your Finance option
    </h2>
      <script> 
        var dividoKey = "{$apiKey}";
      </script>
    <script src="https://cdn.divido.com/calculator/v2.1/production/js/template.divido.js"></script>
  

     <form id="dividoFinanceForm" action="{url controller='DividoPayment' action='direct'}" method="post" {$displayForm}>

            <div
              data-divido-widget
              data-divido-prefix="finance for"
              {$prefix}
              {$suffix}
              data-divido-title-logo
              data-divido-amount="{$amount}"
              data-divido-apply="true"
              data-divido-apply-label="Apply Now"
              data-divido-plans
              >
            </div>
                    <button id="divido-finance-submit-button" type="submit"
                       title="finance"
                       class="finance-action btn is--primary"
                       data-product-compare-add="true">
                        Continue to Finance Application
                    </button>
                </form>

            <br>
            {$displayWarning}
            <br>
            <a class="btn"
               href="{url controller=checkout action=cart}"
               title="change cart">change cart
            </a>
            <a class="btn right"
               href="{url controller=checkout action=shippingPayment sTarget=checkout}"
               title="change payment method">change payment method
            </a>


    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function(
      
    ){
      var button = document.querySelectorAll("#divido-finance-submit-button")[0];
      button.addEventListener("click", function(){
        this.setAttribute("disabled", true);;
              console.log('true disbaled');
              document.getElementById("dividoFinanceForm").submit();
      })
      console.log('loaded');
    })
    </script>
{/block}
