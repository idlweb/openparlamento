<table class="disegni-decreti column-table">
  <thead>
    <tr>
  	<th scope="col">Gruppo</th>
	<th scope="col">Favorevoli (<?php echo ($votazione->getFavorevoli()>$votazione->getContrari() ? 'Maggioranza' : 'Minoranza') ?>)</th>
	<th scope="col">Contrari (<?php echo ($votazione->getContrari()>$votazione->getFavorevoli() ? 'Maggioranza' : 'Minoranza') ?>)</th>
	<th scope="col">Astenuti</th>
	<th scope="col">Assenti</th>
	<th scope="col">In missione</th>
    </tr>
  </thead>
  
<?php $totale_assenti = 0 ?>  
<?php $totale_missioni = 0 ?> 	
  <tbody>
  <?php foreach ($risultati as $gruppo => $risultato): ?>
  <tr>
  	<?php $direttiva_gruppo = max($risultato['Favorevole'], $risultato['Contrario'], $risultato['Astenuto']) ?>
    <th scope="row"><?php echo $gruppo ?></th>
	<td><span <?php echo ( ($gruppo!='Gruppo Misto' && $risultato['Favorevole']<$direttiva_gruppo && $risultato['Favorevole']!=0 ) ? 'style="font-weight:bold; background-color:yellow"' :'') ?>"><?php echo  "&nbsp;".$risultato['Favorevole']."&nbsp;" ?></span></td>
	<td><span <?php echo ( ($gruppo!='Gruppo Misto' && $risultato['Contrario']<$direttiva_gruppo && $risultato['Contrario']!=0 ) ? 'style="font-weight:bold; background-color:yellow"' :'') ?>"><?php echo  "&nbsp;".$risultato['Contrario']."&nbsp;" ?></span></td>
	<td><span <?php echo ( ($gruppo!='Gruppo Misto' && $risultato['Astenuto']<$direttiva_gruppo && $risultato['Astenuto']!=0 ) ? 'style="font-weight:bold; background-color:yellow"' :'') ?>"><?php echo  "&nbsp;".$risultato['Astenuto']."&nbsp;" ?></span></td>
	<td class="evident"><?php echo $risultato['Assente'] ?></td>
	<?php $totale_assenti += $risultato['Assente'] ?>
	<td class="evident"><?php echo $risultato['In missione'] ?></td>
	<?php $totale_missioni += $risultato['In missione'] ?>
<?php endforeach; ?>
    
    <tr>
  	<th scope="row" style="font-weight:bold;">Totali</th>
  	<td style="font-weight:bold;"><?php echo $votazione->getFavorevoli() ?></td>
	<td style="font-weight:bold;"><?php echo $votazione->getContrari() ?></td>
	<td style="font-weight:bold;"><?php echo $votazione->getAstenuti() ?></td>
	<td style="font-weight:bold;" class="evident"><?php echo $totale_assenti ?></td> 
	<td style="font-weight:bold;" class="evident"><?php echo $totale_missioni ?></td>
   </tr>
   
 </tbody>   
</table>