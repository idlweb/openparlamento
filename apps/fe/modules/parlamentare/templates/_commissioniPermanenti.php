<div class="W100_100 float-right"><h5 class="subsection"><?php echo OppSedePeer::retrieveByPk($sede_id)->getDenominazione() ?></h5></div>
<div class="W73_100 float-left" style="width:40%">
  <table class="disegni-decreti column-table lazyload">
    <thead>
      <tr>
        <th scope="col">Gruppo:</th>
        <th scope="col">Presidente:</th> 	
        <th scope="col">Vicepresidenti:</th>
        <th scope="col">Segretari:</th>
        <th scope="col">Membri:</th>
        <th scope="col">Totale:</th>
      </tr>
    </thead>

    <tbody>
<?php $tr_class = 'even'; ?>
<?php foreach ($gruppi_all as $k => $gruppo) : ?>
  <tr class="<?php echo ($tr_class == 'even' ? 'odd' : 'even' ); ?>">
  <?php if (OppGruppoIsMaggioranzaPeer::isGruppoMaggioranza($k,date('Y-m-d'))==1) : ?>
    <?php $color_gruppo="#022468"; ?>
  <?php else : ?>
    <?php $color_gruppo="#766d04"; ?>
   <?php endif; ?>  
  <th scope='row'><span style='background-color:<?php echo $color_gruppo ?>; color:white; margin:5px;'>&nbsp;</span><?php echo OppGruppoPeer::retrieveByPk($k)->getAcronimo(); ?></th>
  <td>
    <?php if (array_key_exists($k,$gruppi_p)) : ?>
      <span style="font-weight:bold; background-color:yellow; padding:3px;"><?php echo $gruppi_p[$k]; ?></span>
    <?php else : ?>  
      <?php echo "0"; ?>
    <?php endif; ?>
  </td>
  <td>
    <?php if (array_key_exists($k,$gruppi_vp)) : ?>
      <?php echo $gruppi_vp[$k]; ?>
    <?php else :?>  
      <?php echo "0"; ?>
    <?php endif; ?>
  </td>
  <td>
    <?php if (array_key_exists($k,$gruppi_s)) : ?>
      <?php echo $gruppi_s[$k]; ?>
    <?php else :?>  
      <?php echo "0"; ?>
    <?php endif; ?>
  </td>
  <td>
    <?php if (array_key_exists($k,$gruppi_c)) : ?>
      <?php echo $gruppi_c[$k]; ?>
    <?php else :?>  
      <?php echo "0"; ?>
    <?php endif; ?>
  </td>
  <td class="evident">
      <strong><?php echo $gruppo ?></strong>
  </td>
 </tr>
   
<?php endforeach; ?>  
<tr>
   <th scope='row'>Totali</th>
   <td><?php echo array_sum($gruppi_p)?></td>
   <td><?php echo array_sum($gruppi_vp)?></td>
   <td><?php echo array_sum($gruppi_s)?></td>
   <td><?php echo array_sum($gruppi_c)?></td>
   <td class="evident"><strong><?php echo array_sum($gruppi_all)?></strong></td>
  </tr>
</tbody>
</table>
<br/>
<div><span style="background-color:#022468; color:white; padding: 3px; margin-right:10px; font-size:10px;">maggioranza</span><span style="background-color:#766d04; color:white; padding: 3px; margin-right:10px; font-size:10px">opposizione</span></div>
</div>
<div class="W73_100 float-right" style="width:56%;">
<?php  
  $perc_magg=array();
  $perc_min=array();
  $num_totale=0;
  foreach ($gruppi_all as $k => $gruppo)
  {
    if (OppGruppoIsMaggioranzaPeer::isGruppoMaggioranza($k,date('Y-m-d'))==1)
      $perc_magg[$k]=$gruppo;
    else
      $perc_min[$k]=$gruppo;
    
    $num_totale=$num_totale+$gruppo;  
  }
  $perc_grafico="50,";
  $label_grafico="|";
  $color_grafico="FFFFFF|";
  foreach($perc_magg as $k => $perc)
  {

    $perc_grafico=$perc_grafico.(number_format($perc*100/$num_totale/2,2)).",";
    $label_grafico=$label_grafico.OppGruppoPeer::retrieveByPk($k)->getAcronimo()." [".$perc."]|";
  }
  foreach($perc_min as $k => $perc)
  {

    $perc_grafico=$perc_grafico.(number_format($perc*100/$num_totale/2,2)).",";
    $label_grafico=$label_grafico.OppGruppoPeer::retrieveByPk($k)->getAcronimo()." [".$perc."]|"; 
  }
  for ($x=0;$x<count($perc_magg);$x++)
  {
    switch ($x) {
        case 0:
            $color_grafico=$color_grafico."022468|";
            break;
        case 1:
            $color_grafico=$color_grafico."063cab|";
            break;
        case 2:
            $color_grafico=$color_grafico."0b50dc|";
            break;
        case 3:
            $color_grafico=$color_grafico."105dfb|";
            break;    
        case 4:
            $color_grafico=$color_grafico."3c7af9|";
            break;
        case 5:
            $color_grafico=$color_grafico."6f9df9|";
            break;    
    }

  }

  for ($x=0;$x<count($perc_min);$x++)
  {
    switch ($x) {
        case 0:
            $color_grafico=$color_grafico."766d04|";
            break;
        case 1:
            $color_grafico=$color_grafico."ac9f09|";
            break;
        case 2:
            $color_grafico=$color_grafico."e1cf0a|";
            break;
        case 3:
            $color_grafico=$color_grafico."f9e50b|";
            break;    
        case 4:
            $color_grafico=$color_grafico."f8e72b|";
            break;
        case 5:
            $color_grafico=$color_grafico."f9ee70|";
            break;    
    }

  }
  
 
  $chld="";
   $color="";
   $z=0;
   arsort($membri_regione);
  foreach ($membri_regione as $k => $m)
  {
    $chld=$chld."IT-".$k."|";

      if ($m>=10)
         $color=$color."CC0000|";
       elseif ($m<10 && $m>=8)  
         $color=$color."CC3D00|";
       elseif ($m<8 && $m>=6)  
           $color=$color."CC5100,";
       elseif ($m<6 && $m>=4)  
           $color=$color."CC6600|"; 
       elseif ($m==3)  
           $color=$color."CC7A00|"; 
      elseif ($m==1)  
          $color=$color."CC8400|";     
       elseif ($m==1)  
           $color=$color."CC9900|";
       elseif ($m==0)  
           $color=$color."676767|";               
      
    $z++;
  }
  
  $color="FFFFFF|".$color;
  
?>  
<img src="http://chart.apis.google.com/chart?cht=p&chd=t:<?php echo rtrim($perc_grafico,',') ?>&chs=380x240&chl=<?php echo rtrim($label_grafico, '|') ?>&chco=<?php echo rtrim($color_grafico,'|') ?>">

<img src="http://chart.apis.google.com/chart?cht=map&chs=200x300&chld=<?php echo trim($chld,"|") ?>&chco=<?php echo trim($color,"|") ?>">
</div>