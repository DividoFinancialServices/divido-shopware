{extends file="parent:frontend/custom/index.tpl"}

{block name="frontend_index_content"}
{$smarty.block.parent}
{if $apiKey}
<script>
    var dividoKey = "{$apiKey}";
</script>
<style>
.dividoCalcWidget{
    display:none;
}
</style>
<script src="http://cdn.divido.com/calculator/v2.1/production/js/template.divido.js"></script>

<div class="dividoCalcWidget"
              data-divido-widget
              data-divido-title-logo
              data-divido-amount="2000"
              data-divido-plans
              data-divido-logo
              data-divido-mode
              >
</div>
{literal}
<script>
var mainCalcWidget = document.getElementsByClassName('dividoCalcWidget')[0];
var dividoInputs = document.getElementsByClassName('divido-calculate');
for(let k = 0; k < dividoInputs.length; k++){
    let dividoInput = dividoInputs[k];
    var calcWidget = mainCalcWidget.cloneNode(true);
    calcWidget.setAttribute('id','dividoCalc'+k);
    calcWidget.style.display = 'block';
    dividoInput.parentNode.insertBefore(calcWidget,dividoInput.nextSibling);
    dividoInput.value = calcWidget.getAttribute('data-divido-amount');
    if(dividoInput.classList.contains('divido-popup')){
        calcWidget.setAttribute('data-divido-mode','popup');
        calcWidget.style.marginLeft = '50px';
    }
    dividoInput
        .addEventListener("keyup",function(event){
            let input = event.target.value;
            if(input >= 250 && input<=25000){
                calcWidget.setAttribute('data-divido-amount',input);
                calcWidget.style.display = 'block';
            }else {
                calcWidget.style.display = 'none';
            }
        });
}
</script>
{/literal}
{/if}
{/block}