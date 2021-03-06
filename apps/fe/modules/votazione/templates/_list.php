<?php echo use_helper('PagerNavigation'); ?>

<table class="disegni-decreti column-table">
  <thead>
    <tr>
      <th scope="col"><br />sigla/titolo:</th>
      <th scope="col">esito in<br />Parlamento:</th>
      <th scope="col">voti di<br />scarto:</th>
      <th scope="col">numero di<br />ribelli:</th>
    </tr>
  </thead>

  <tbody>
   <?php $tr_class = 'even' ?>
    <?php foreach ($pager->getResults() as $votazione): ?>
      <tr class="<?php echo $tr_class; ?>">
      <?php $tr_class = ($tr_class == 'even' ? 'odd' : 'even' )  ?>
        <th scope="row">
          <p class="content-meta">
            <span class="date"><?php echo format_date($votazione->getOppSeduta()->getData(), 'dd/MM/yyyy') ?> - <?php echo ($votazione->getOppSeduta()->getRamo()=='C' ? 'Camera' : 'Senato' ) ?>, seduta n. <?php echo ($votazione->getOppSeduta()->getNumero() ) ?></span>
          </p>
          <p><?php echo link_to($votazione->getTitolo(), '@votazione?'.$votazione->getUrlParams()) ?></p>
       </th>
        
	    <td>
		  <?php if($votazione->getEsito()=='APPROVATA'): ?>
		    <?php $class = "green thumb-approved"; ?>
		  <?php elseif($votazione->getEsito()=='RESPINTA'): ?>
		    <?php $class = "red thumb-rejected"; ?>
		  <?php else: ?>
		    <?php $class = ""; ?>
          <?php endif; ?>					
		  <span class="<?php echo $class ?>"><?php echo $votazione->getEsito() ?></span>
		</td>
        <td><p><?php echo $votazione->getMargine() ?></p></td>
        <td><p><?php echo $votazione->getRibelli() ?></p></td>        
      </tr>
    <?php endforeach; ?>
  </tbody>

  <tfoot>		  		  
    <tr>
      <td colspan="6" align="center">
        <?php $paginationParams = array();
				$sf_user->getAttribute('legislatura') AND ($paginationParams[] = 'legislatura='. $sf_user->getAttribute('legislatura'));
				$sf_user->getAttribute('ramo') AND ($paginationParams[] = 'ramo='. $sf_user->getAttribute('ramo'));
				echo pager_navigation($pager, '@votazioni?sort='. $sf_params->get('sort').'&type='. $sf_params->get('type'). ( !empty($paginationParams) ? '&'. implode('&', $paginationParams) : '' )) ?>
      </td>	
    </tr>
    <tr>
      <td colspan="6" align="center">
        <?php echo format_number_choice('[0] nessun risultato|[1] 1 risultato|(1,+Inf] %1% risultati', array('%1%' => $pager->getNbResults()), $pager->getNbResults()) ?>
	  </td>
    </tr>
  </tfoot>  		  		
</table>	  	  