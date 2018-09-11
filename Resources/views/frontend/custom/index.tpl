{extends file="parent:frontend/custom/index.tpl"}

{block name="frontend_index_content"}
{$smarty.block.parent}
{if $apiKey}
<script>
    var dividoKey = "{$apiKey}";
</script>
<style>
#calcWidget{
    display:none;
    position: absolute;
}
</style>
<script src="http://cdn.divido.com/calculator/v2.1/production/js/template.divido.js"></script>

<div id="calcWidget"
              data-divido-widget
              data-divido-title-logo
              data-divido-amount="2000"
              data-divido-plans
              data-divido-logo
              data-divido-mode="popup"
              >
</div>
{literal}
<script>
var dividoInputs = document.getElementsByClassName('divido-input');
for(let k = 0; k < dividoInputs.length; k++){
    let dividoInput = dividoInputs[k];
    dividoInput.style.marginBottom = '40px';
    dividoInput
        .addEventListener("keyup",function(event){
            let input = event.target.value;
            let widget = document.getElementById('calcWidget');
            if(input >= 250 && input<=25000){
                widget.setAttribute('data-divido-amount',input);
                widget.style.top = (event.target.offsetHeight + event.target.offsetTop + 2)+"px";
                widget.style.display = 'inline-block';
                widget.style.left = (event.target.offsetLeft + (event.target.offsetWidth - widget.offsetWidth - 10))+"px";
            }else{
                widget.style.display = 'none';
            }
        });
}
</script>
{/literal}
{/if}
{/block}