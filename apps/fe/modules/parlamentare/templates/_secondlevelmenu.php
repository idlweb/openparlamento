<div class="float-container">
	<ul class="second-level-tabs float-container">
    <li class="<?php echo($current=='cosa' ? 'current' : '' ) ?>">
		  <h5>
	      <?php echo link_to('Cosa fa', $current=='cosa'?'#':'@parlamentare?id='.$parlamentare_id) ?>
		  </h5>
		</li>
    <li class="<?php echo($current=='atti' ? 'current' : '' ) ?>">
		  <h5>
	      <?php echo link_to('I suoi atti', $current=='atti'?'#':'@parlamentare_atti?id='.$parlamentare_id) ?>
		  </h5>
		</li>
    <li class="<?php echo($current=='voti' ? 'current' : '' ) ?>">
		  <h5>
	      <?php echo link_to('Come ha votato', $current=='voti'?'#':'@parlamentare_voti?id='.$parlamentare_id) ?>
		  </h5>
		</li>
    <li class="<?php echo($current=='interventi' ? 'current' : '' ) ?>">
		  <h5>
	      <?php echo link_to('I suoi interventi parlamentari', $current=='interventi'?'#':'@parlamentare_interventi?id='.$parlamentare_id) ?>
		</li>
	</ul>
</div>