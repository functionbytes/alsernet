<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

function getTotalTemporadas()
{

    $totalproductos = Db::getInstance()->getValue("select count(*) from (select distinct id_product from aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import WHERE etiqueta LIKE '%TEMPORADA_VERANO%' OR etiqueta LIKE '%TEMPORADA_INVIERNO%') union SELECT id_product FROM aalv_combinacionunica_import WHERE etiqueta LIKE '%TEMPORADA_VERANO%' OR etiqueta LIKE '%TEMPORADA_INVIERNO%' ) as pruebas");

    return $totalproductos;

}

echo '<html>';
echo '<head><script> var totalp='.getTotalTemporadas().';</script>';
echo '<script type="text/javascript" src="/js/jquery/jquery-1.11.0.min.js"></script>';

echo '</head>';
echo "<body style='text-align:center;'>";

echo '<div><h3>No cierres esta ventana hasta que se indique</h3></div>';
echo "<div id='refreshdata' style='margin-top:50px; margin-bottom:50px; height:100px;'></div>";

echo "<center><table cellpadding='0' cellspacing='0' style='width:200px; border:solid 1px red; border-radius:8px;'>
<tr style='height:16px;'>
	<td id='ProgressBarValue' style='background-color:red; width:0px;'></td>
	<td></td>
</tr>
</table></center>";

echo "<h3 id='message'></h3>";

echo '</body>';

echo "<script>
		function refresca(data,j){
			$('#refreshdata').html(data);
			$('#ProgressBarValue').css('width', parseInt(200*j/totalp)+'px');
			$('#message').html('Procesado '+j+' de '+ totalp+' productos seleccionados');


		}

		$(document).ready(function(){

			
			var j=0;
			


			for (i=0; i<totalp; i++){
				


				$.ajax({
        			type: 'GET',
			        url: '/panel/procesartemporada.php?id='+i,
        			async: false,
        			success : function(data) {
            				j=j+1;
					
					if (j==totalp){
						
						refresca(data,j);
						parent.$.fancybox.close();
					}	
					else{
						
						refresca(data,j);
					}


        			}
   				 });



				

				

			}
			
			

		});
</script>
";

echo '</html>';
