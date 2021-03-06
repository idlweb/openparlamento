<?php echo use_helper('I18N'); ?>

<div style="margin-bottom: 15px">
  <?php echo format_number_choice('[0]non c\'&egrave; nessun risultato|[1] c\'&egrave; 1 risultato|(1,+Inf] ci sono %1% risultati', array('%1%' => $results), $results) ?>
  <?php if (deppFiltersAndSortVariablesManager::arrayHasNonzeroValue(array_values($filters))): ?>
    <?php echo format_number_choice('[0,1] che risponde|(1,+Inf] che rispondono', array('%1%' => $results), $results) ?>
    ai <b>filtri impostati</b>
    <?php 
      if (!isset($route)) 
        //$route = sfContext::getInstance()->getModuleName() . '/' . sfContext::getInstance()->getActionName(); 
		$route = '@'. sfRouting::getInstance()->getCurrentRouteName();
    ?>
    <?php echo link_to('rimuovi tutti i filtri',  "$route" .(strpos($route, '?')?'&':'?'). "reset_filters=true") ?>
  <?php endif ?>
</div>

