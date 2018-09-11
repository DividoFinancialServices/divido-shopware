{extends file="parent:frontend/custom/index.tpl"}

{block name="frontend_index_content"}
{$smarty.block.parent}
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
              data-divido-prefix="finance for"
              data-divido-suffix="with"
              data-divido-title-logo
              data-divido-amount="2000"
              data-divido-apply="true"
              data-divido-apply-label="APPLY”
              data-divido-footnote="If you apply"
              data-divido-product="test"
              data-divido-plans
              data-divido-logo
              data-divido-mode="popup"
              >
</div>
{literal}
<script>
var dividoInputs = document.getElementsByClassName('divido-input');
console.log(dividoInputs);
for(let k = 0; k < dividoInputs.length; k++){
    let dividoInput = dividoInputs[k];
    console.log(dividoInput);
    dividoInput
        .addEventListener("keyup",function(event){
            let input = event.target.value;
            console.log(input);
            let widget = document.getElementById('calcWidget');
            if(input >= 250 && input<=25000){
                widget.setAttribute('data-divido-amount',input);
                widget.style.left = (event.target.offsetLeft+12)+"px";
                widget.style.top = (event.target.offsetHeight + event.target.offsetTop + 2)+"px";
                widget.style.display = 'block';
            }else{
                widget.style.display = 'none';
            }
        });
    dividoInput.style.marginBottom = '40px';
}
</script>
{/literal}
{/block}