{extends file="parent:frontend/detail/content/buy_container.tpl"}

{block name="frontend_detail_index_buybox"}
{$smarty.block.parent}

{if {$sArticle.price|replace:',':'.'} >= $min_product_amount }
<!-- BEGIN DIVIDO --!>
      <script> 
        var dividoKey = "{$apiKey}";
      </script>
      <style>
       .divido-widget-logo{ 
         display:inline-block;
         position:relative;
         top:2px;
         } 
         #divido-widget{
           padding-bottom:5px;
         }
       </style>

    <script src="https://cdn.divido.com/calculator/v2.1/production/js/template.divido.js"></script>
            <div
            id="divido-widget"
              data-divido-widget
              data-divido-mode="popup"
              data-divido-title-logo
              {$prefix}
              {$suffix}
              data-divido-amount="{$sArticle.price|replace:',':'.'}
              data-divido-apply="true"
              data-divido-apply-label="Apply Now"
              data-divido-plans
              >
            </div>
<!-- END DIVIDO --!>
{/if}
{/block}


