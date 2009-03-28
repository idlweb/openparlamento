<?php 

/*
 * This file is part of the deppPropelMonitoringBehaviors package.
 * (c) 2008 Guglielmo Celata
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    deppPropelMonitoringBehaviors
 * @author     Guglielmo Celata <guglielmo.celata@symfony-project.com>
 * @version    SVN: $Id$
 */

function news($news)
{
  return news_date($news->getDate('d/m/Y')). "<p>" . news_text($news). "</p>";
}

function news_date($newsdate)
{
  return content_tag('strong',$newsdate);
}



function link_to_in_mail($name = '', $internal_uri = '', $options = array())
{

  $html_options = _parse_attributes($options);
  $html_options = _convert_options_to_javascript($html_options);

  $site_url = sfConfig::get('app_site_url', '');
  if (isset($html_options['site_url']))
  {
    $site_url = $html_options['site_url'];
  }

  $url = url_for($internal_uri);
  $url_in_mail = preg_replace('/.*\/symfony\/(.*)/i',  'http://'.$site_url.'/$1', $url);
  return "<a href=\"$url_in_mail\">$name</a>";
}

/**
 * torna l'elenco ul/li delle news passate in argomento
 *
 * @param string $news array di oggetti News
 * @return string html
 * @author Guglielmo Celata
 */
function news_list($news)
{
  $news_list = '';
  
  foreach ($news as $n)
  {
    $news_list .= content_tag('li', news_text($n));
  }
  
  return content_tag('ul', $news_list, array('class' => 'square-bullet')); 
}


function news_text($news)
{
  $news_string = "";
  
  // fetch del modello e dell'oggetto che ha generato la notizia
  $generator_model = $news->getGeneratorModel();

  if (is_null($news->getGeneratorPrimaryKeys()))
  {
    if ($generator_model == 'OppVotazioneHasAtto')
    {
      if ($news->getPriority() == 1)
      {
        $news_string .= 'si &egrave; svolta almeno una VOTAZIONE';
        $news_string .= ($news->getRamoVotazione()=='C')?' alla Camera ' : ' al Senato ';        
      } else {
        $news_string .= 'si &egrave; svolta una VOTAZIONE';
        $news_string .= ($news->getRamoVotazione()=='C')?' alla Camera ' : ' al Senato ';        
        $news_string .= 'per ' . OppTipoAttoPeer::retrieveByPK($news->getTipoAttoId())->getDenominazione() .  ' ';
        
        // link all'atto
        $atto = call_user_func_array(array($news->getRelatedMonitorableModel().'Peer', 'retrieveByPK'), 
                                           $news->getRelatedMonitorableId());
        
        $atto_link = link_to_in_mail($atto->getRamo() . '.' .$atto->getNumfase(), 
                             'atto/index?id=' . $atto->getId(),
                             array('title' => $atto->getTitolo()));
        $news_string .= $atto_link;
      }
    } else if ($generator_model == 'OppIntervento') {
      $news_string .= 'c\'&egrave; stato almeno un intervento ';
      $news_string .= 'in ' . OppSedePeer::retrieveByPK($news->getSedeInterventoId())->getDenominazione() .  ' ';
      $news_string .= 'per ' . OppTipoAttoPeer::retrieveByPK($news->getTipoAttoId())->getDenominazione() .  ' ';

      // link all'atto
      $atto = call_user_func_array(array($news->getRelatedMonitorableModel().'Peer', 'retrieveByPK'), 
                                         $news->getRelatedMonitorableId());
      
      $atto_link = link_to_in_mail($atto->getRamo() . '.' .$atto->getNumfase(), 
                           'atto/index?id=' . $atto->getId(),
                           array('title' => $atto->getTitolo()));
      $news_string .= $atto_link;
      
    }
      
    return $news_string;
  }
  
  $pks = array_values(unserialize($news->getGeneratorPrimaryKeys()));
  $generator = call_user_func_array(array($generator_model.'Peer', 'retrieveByPK'), $pks);

  if ($generator) 
  {

  $related_monitorable_model = $news->getRelatedMonitorableModel();
  if ($related_monitorable_model == 'OppPolitico')
  {
    // fetch del politico
    $c = new Criteria(); $c->add(OppPoliticoPeer::ID, $news->getRelatedMonitorableId());
    $politici = OppPoliticoPeer::doSelect($c);

    if (count($politici) == 0) return 'empty OppPolitico:' . $news->getRelatedMonitorableId();

    $politico = $politici[0];

    // link al politico
    $politico_link = link_to_in_mail($politico->getNome() . ' ' .$politico->getCognome(), 
                         '@parlamentare?id=' . $politico->getId(),
                         array('title' => 'Vai alla scheda del politico'));


    // nuovo incarico
    if ($generator_model == 'OppCarica'){
      $news_string .= $politico_link . " assume l'incarico di " . $generator->getCarica();
    }
    
    // nuovo gruppo
    else if ($generator_model == 'OppCaricaHasGruppo'){
      $news_string .= $politico_link . " si unisce al gruppo " . $generator->getOppGruppo()->getNome();
    }

    // intervento
    else if ($generator_model == 'OppIntervento'){
      $atto = $generator->getOppAtto();
      $tipo = $atto->getOppTipoAtto();
      $atto_link = link_to_in_mail($atto->getRamo() . '.' .$atto->getNumfase(), 
                           'atto/index?id=' . $atto->getId(),
                           array('title' => $atto->getTitolo()));
                           
      $news_string .= $politico_link . " interviene su ";
      $news_string .= $tipo->getDenominazione() . " ";
      $news_string .= $atto_link;
    }

    // firma
    else if ($generator_model == 'OppCaricaHasAtto'){
      $atto = $generator->getOppAtto();
      $tipo = $atto->getOppTipoAtto(); 
      $atto_link = link_to_in_mail($atto->getRamo() . '.' .$atto->getNumfase(), 
                           'atto/index?id=' . $atto->getId(),
                           array('title' => $atto->getTitolo()));
                           
      $news_string .= $politico_link . " firma ";
      $news_string .= $tipo->getDenominazione() . " ";
      $news_string .= $atto_link;
    }

    else $news_string .= $generator_model;
    
  }
  
  if ($related_monitorable_model == 'OppAtto')
  {
    // fetch dell'atto
    $c = new Criteria(); $c->add(OppAttoPeer::ID, $news->getRelatedMonitorableId());
    $atti = OppAttoPeer::doSelectJoinOppTipoAtto($c);

    // detect a void query
    if (count($atti) == 0) return 'empty OppAtto:' . $news->getRelatedMonitorableId();

    $atto = $atti[0];
    
    // tipo di atto e genere per gli articoli e la desinenza
    $tipo = $atto->getOppTipoAtto();
    if (in_array($tipo->getId(), array(1, 10, 11)))
      $gender = 'm';
    else
      $gender = 'f';

    // link all'atto
    $atto_link = link_to_in_mail(troncaTesto(Text::denominazioneAtto($atto,'list'),200), 
                         'atto/index?id=' . $atto->getId(),
                         array('title' => $atto->getTitolo()));
    
    // presentazione
    if ($generator_model == 'OppAtto'){
      $news_string .= "presentat" .($gender=='m'?'o':'a') . " ";
      $news_string .= ($news->getRamoVotazione()=='C')?' alla Camera ' : ' al Senato '; 
      $news_string .= $tipo->getDescrizione() . " ";
      $news_string .= $atto_link;
    }
    
    // intervento
    else if ($generator_model == 'OppIntervento'){
      $politico = $generator->getOppCarica()->getOppPolitico();
      $politico_link = link_to_in_mail($politico, 
                           '@parlamentare?id=' . $politico->getId(),
                           array('title' => 'Vai alla scheda del politico'));
                           
      $news_string .= $politico_link . " interviene su ";
      $news_string .= $tipo->getDescrizione() . " ";
      $news_string .= $atto_link;
    }

    // firma
    else if ($generator_model == 'OppCaricaHasAtto'){
      $politico = $generator->getOppCarica()->getOppPolitico();
      $politico_link = link_to_in_mail($politico, 
                           '@parlamentare?id=' . $politico->getId(),
                           array('title' => 'Vai alla scheda del politico'));

      $news_string .= ' firmat' . ($gender=='m'?'o':'a') . " ";
      $news_string .= $tipo->getDescrizione() . " ";
      $news_string .= $atto_link;
      $news_string .= " da " . $politico_link;      
    }
    
    // spostamento in commissione
    else if ($generator_model == 'OppAttoHasSede'){
      $news_string .= $tipo->getDenominazione() . " ";
      $news_string .= $atto_link . " ";
      $news_string .= "&egrave; spostat" . ($gender=='m'?'o':'a') . " in ";
      $news_string .= content_tag('b', ucfirst(strtolower($generator->getOppSede()->getDenominazione())));
    }
    
    // votazioni
    else if ($generator_model == 'OppVotazioneHasAtto'){
      $news_string .= ' si &egrave; svolta la votazione finale relativa a ';
      $news_string .= $tipo->getDescrizione() . " ";
      $news_string .= $atto_link;        
    }
    
    // status conclusivo
    else if ($generator_model == 'OppAttoHasIter'){
      $news_string .= "lo status del" .($gender=='m'?"l'":"la ");
      $news_string .= $tipo->getDescrizione() . " ";
      $news_string .= $atto_link . " ";
      $news_string .= "&egrave; ora ";
      $news_string .= content_tag('b', ucfirst(strtolower($generator->getOppIter()->getFase())));
    } 
    
    else $news_string .= $generator_model;
                                  
    
    
  }

  } else {
    sfLogger::getInstance()->info('xxx: errore per: ' . $generator_model . ': chiavi: ' . $news->getGeneratorPrimaryKeys());
  }
  
  return $news_string;
  
}

function community_news_text($news)
{
  $news_string = "";
  
  // fetch del modello e dell'oggetto che ha generato la notizia
  $generator_model = $news->getGeneratorModel();

  $related_model = $news->getRelatedModel();
  $related_id = $news->getRelatedId();

  // fetch dell'item
  $item = call_user_func_array($related_model.'Peer::retrieveByPK', array($related_id));

  if (is_null($item))
    return "notizia su oggetto inesistente: ($related_model:$related_id)";
  
  // costruzione del link all'item (differente a seconda dell'item)
  switch ($related_model)
  {
    case 'OppPolitico':
      // link al politico
      $item_type = 'il parlamentare';
      $link = link_to_in_mail($item, 
                             '@parlamentare?id=' . $related_id,
                             array('title' => 'Vai alla scheda del politico'));
      break;

    case 'OppAtto':
      // link all'atto
      $item_type = 'l\'atto';
      $link = link_to_in_mail($item->getRamo() . '.' . $item->getNumfase(), 
                              'atto/index?id=' . $related_id,
                              array('title' => $item->getTitolo()));
      break;

    case 'OppVotazione':
      // link alla votazione
      $item_type = 'la votazione';
      $link = link_to_in_mail($item->getTitolo(), 
                              '@votazione?id=' . $related_id,
                              array('title' => 'Vai alla pagina della votazione'));
      break;

    case 'Tag':
      // link all'argomento
      $item_type = 'l\'argomento';
      $link = link_to_in_mail($item->getTripleValue(), 
                              '@argomento?triple_value=' . $item->getTripleValue(),
                              array('title' => 'Vai alla pagina dell\'argomento'));
      break;
  }      

  
  switch ($generator_model) 
  {
    case 'sfComment':
      return sprintf("%s ha commentato %s %s", $news->getUsername(), $item_type, $link);
      break;
    case 'Monitoring':
      if ($news->getType() == 'C')
        return sprintf("un utente si è aggiunto agli altri %d che stanno monitorando %s %s", 
                      $news->getTotal(), $item_type, $link);
      else
        return sprintf("un utente ha smesso di monitorare %s %s", 
                       $item_type, $link);          
      break;
    case 'sfVoting':
      if ($news->getType() == 'C')
      {
        if ($news->getVote() == 1) $fav_contr = 'favorevoli';
        else $fav_contr = 'contrari';
        return sprintf("un utente si è aggiunto agli altri %d %s per %s %s", 
                      $news->getTotal(), $fav_contr, $item_type, $link);
      } else {
        return sprintf("un utente ha ritirato il suo voto per %s %s", 
                       $item_type, $link);          
      }
      break;
    case 'nahoWikiRevision':
      return sprintf("%s ha modificato la descrizione wiki per %s %s", $news->getUsername(), $item_type, $link);
      break;
  }
  
  
}



function troncaTesto($testo, $caratteri) { 

    if (strlen($testo) <= $caratteri) return $testo; 
    $nuovo = wordwrap($testo, $caratteri, "|"); 
    $nuovotesto=explode("|",$nuovo); 
    return $nuovotesto[0]."..."; 
} 


?>
