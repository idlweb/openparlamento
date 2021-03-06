<?php

/**
 * Subclass for performing queries in the opp_project
 *
 * 
 *
 * @package plugins.deppPropelMonitoringBehaviorsPlugin.lib.model
 */ 
class oppNewsPeer extends NewsPeer
{


  /**
   * build and return criteria to fetch all news related to items monitored by a user,
   * filtered with a given filter
   *
   * @param string $user 
   * @param hash $filters 
   * @return Propel Criteria
   * @author Guglielmo Celata
   */
  public static function getMyMonitoredItemsNewsWithFiltersCriteria($user, $filters)
  {
    // fetch degli oggetti monitorati (se c'è il filtro sui tag, fetch solo di quelli associati a questo tag)
    if ($filters['tag_id'] != '0')
    {
      $filter_criteria = new Criteria();
      $filter_criteria->add(TagPeer::ID, $filters['tag_id']);
      $monitored_objects = $user->getMonitoredObjects('Tag', $filter_criteria);
    } else
      $monitored_objects = $user->getMonitoredObjects();



    // criterio di selezione delle news dagli oggetti monitorati    
    $c = self::getMyMonitoredItemsNewsCriteria($monitored_objects);
    
    // eliminazione delle notizie relative agli oggetti bookmarkati negativamente (bloccati)
    $blocked_items_ids = sfBookmarkingPeer::getAllNegativelyBookmarkedIds($user->getId());
    if (array_key_exists('OppAtto', $blocked_items_ids) && count($blocked_items_ids['OppAtto']))
    {
      $blocked_news_ids = array();
      $bc = new Criteria();
      $bc->add(NewsPeer::RELATED_MONITORABLE_MODEL, 'OppAtto');
      $bc->add(NewsPeer::RELATED_MONITORABLE_ID, $blocked_items_ids['OppAtto'], Criteria::IN);
      $bc->clearSelectColumns(); 
      $bc->addSelectColumn(NewsPeer::ID);
      $rs = NewsPeer::doSelectRS($bc);
      while ($rs->next()) {
        array_push($blocked_news_ids, $rs->getInt(1));
      }
      $c0 = $c->getNewCriterion(NewsPeer::ID, $blocked_news_ids, Criteria::NOT_IN);
      $c->addAnd($c0);
    }
    
    // le news di gruppo non sono considerate, perché ridondanti (#247)
    $c->add(NewsPeer::GENERATOR_PRIMARY_KEYS, null, Criteria::ISNOTNULL);

    // aggiunta filtri su tipi di atto, ramo e data
    if ($filters['act_type_id'] != '0')
      $c->add(NewsPeer::TIPO_ATTO_ID, $filters['act_type_id']);

    if ($filters['act_ramo'] != '0')
      $c->add(NewsPeer::RAMO_VOTAZIONE, $filters['act_ramo']);

    if ($filters['date'] != '0')
      if ($filters['date'] == 'W')
      {
        $c->add(NewsPeer::CREATED_AT, date('Y-m-d H:i', strtotime('-1 week')), Criteria::GREATER_THAN);
      }
      elseif ($filters['date'] == 'M') 
      {
        $c->add(NewsPeer::CREATED_AT, date('Y-m-d H:i', strtotime('-1 month')), Criteria::GREATER_THAN);
      }

    if ($filters['main_all'] == 'main')
      $c->add(NewsPeer::PRIORITY, 2, Criteria::LESS_EQUAL);
            
    return $c;
  }


  /**
   * return true, if the sf_news_cache table has a record, regarding 
   * opp_emendamentos presented on a given date,
   * either related to an opp_atto, or presented by an opp_carica
   *
   * @param  date     $data         - the day some emendamentos were presented
   * @param  char     $item_type    - specifies the type of event (OppAtto, OppPolitico) the news relates to
   * @param  integer  $item_id      - the identifier of the OppAtto or OppPolitico object the intervention is related to
   *
   * @return boolean
   * @author Guglielmo Celata
   **/
  public static function hasGroupEmendamento($data, $item_type, $item_id)
  {
    $c = self::buildGroupEmendamentosCriteria($data, $item_type, $item_id);
    $n_res = self::doCount($c);
    
    return $n_res>0?true:false;
  }

  /**
   * returns the array of News object that verify the criteria specified by the parameters
   * see parameters meaning in self::hasGroupEmendamento()
   *
   * @return array of News
   * @author Guglielmo Celata
   **/
  public static function getGroupEmendamentos($data, $item_type, $item_id)
  {
    $c = self::buildGroupEmendamentosCriteria($data, $item_type, $item_id);
    return self::doSelect($c);
  }
  
  /**
   * returns the array of all the News that regard grouped events for emendamentos 
   *
   * @return array of News
   * @author Guglielmo Celata
   **/
  public static function getAllGroupEmendamentos()
  {
    $c = new Criteria();
    $c->add(self::GENERATOR_MODEL, 'OppEmendamento');
    $c->add(self::GENERATOR_PRIMARY_KEYS, null);
    return self::doSelect($c);
  }
  
  /**
   * insert a record in the sf_news_cache table, whenever at least one emendamento is presented for an act
   * or a politician signs an emendamento
   *
   * @param  date     $data         - the date of the presentation/signing
   * @param  char     $item_type    - specifies the type of event (OppAtto, OppPolitico) the news relates to
   * @param  integer  $item_id      - the identifier of the OppAtto or OppPolitico object the intervention is related to
   *
   * @return void
   * @author Guglielmo Celata
   **/
  public static function addGroupEmendamento($data, $item_type, $item_id)
  {
    if (!isset($data) || !isset($item_type) || !isset($item_id)) 
      throw new deppPropelActAsNewsGeneratorException('$data, $item_type, $item_id are required');
      
    $news = new News();
    $news->setGeneratorModel('OppEmendamento');
    $news->setDate($data);
    $news->setRelatedMonitorableId($item_id);
    $news->setRelatedMonitorableModel($item_type);
    $news->setPriority(2);

    $news->save();
  }

  /**
   * builds the Propel Criterion to extract the group news generated by interventions
   *
   * @param  date     $data         - the date of the presentation/signing
   * @param  char     $item_type    - specifies the type of event (OppAtto, OppPolitico) the news relates to
   * @param  integer  $item_id      - the identifier of the OppAtto or OppPolitico object the intervention is related to
   *
   * @return void
   * @author Guglielmo Celata
   **/
  public static function buildGroupEmendamentosCriteria($data, $item_type, $item_id)
  {
    if (!isset($data) || !isset($item_type) || !isset($item_id)) 
      throw new deppPropelActAsNewsGeneratorException('$data, $item_type, $item_id are required');
    
    $c = new Criteria();
    $c->add(self::GENERATOR_MODEL, 'OppEmendamento');
    $c->add(self::GENERATOR_PRIMARY_KEYS, null, Criteria::ISNULL);
    $c->add(self::RELATED_MONITORABLE_MODEL, $item_type);
    $c->add(self::RELATED_MONITORABLE_ID, $item_id);          
    $c->add(self::DATE, $data);
    $c->add(self::PRIORITY, 2);
    return $c;
  }
  



  /**
   * return true, if the sf_news_cache table has a record, regarding 
   * an intervention on given date, place and act, eventually by an indentified politician
   * the act is an optional parameter, and, when not passed, the search assumes a 
   * different meaning alltogether, that is, a record with priority set to 1
   * is searched, where the value of the RELATED_MONITORABLE_MODEL field is uninfluent
   * the politician is also an optional parameter, passed as third parameter, in place
   * of the atto; the meaning, in this case is to look for an intervention of the politician
   * on any acts
   *
   * @param  date     $data    - the date of the seduta when the votation happened
   * @param  integer  $sede_id - the identifier for the OppSede object where the intervention was held
   * @param  char     $type    - specifies the type of search (Any, Atto, Politico)
   * @param  char     $tipo_atto_id - the type of act the news is related to (use along with $id if $type == 'Atto')
   * @param  integer  $id      - the identifier of the OppAtto or OppPolitico object the intervention is related to
   *
   * @return boolean
   * @author Guglielmo Celata
   **/
  public static function hasGroupIntervention($data, $sede_id, $tipo_atto_id, $id)
  {
    $c = self::buildGroupInterventionsCriteria($data, $sede_id, $tipo_atto_id, $id);
    $n_res = self::doCount($c);
    return $n_res>0?true:false;
  }

  /**
   * returns the array of News object that verify the criteria specified by the parameters
   * see parameters meaning in self::hasGroupIntervention()
   *
   * @return array of News
   * @author Guglielmo Celata
   **/
  public static function getGroupInterventions($data, $sede_id, $tipo_atto_id, $id)
  {
    $c = self::buildGroupInterventionsCriteria($data, $sede_id, $type, $tipo_atto_id, $id);
    return self::doSelect($c);
  }
  
  /**
   * returns the array of all the News that regards grouped events for interventions 
   *
   * @return array of News
   * @author Guglielmo Celata
   **/
  public static function getAllGroupInterventions()
  {
    $c = new Criteria();
    $c->add(self::GENERATOR_MODEL, 'OppIntervento');
    $c->add(self::GENERATOR_PRIMARY_KEYS, null);
    return self::doSelect($c);
  }
  
  /**
   * insert a record in the sf_news_cache table, regarding an intervention on a given
   * date, place and, act; 
   * see description of hasGroupVotation, for the meaning of parameters
   *
   * @param  date     $data    - the date of the seduta when the votation happened
   * @param  integer  $sede_id - the identifier for the OppSede object where the intervention was held
   * @param  char     $type    - specifies the type of search (Any, Atto, Politico)
   * @param  integer  $tipo_atto_id - type of act (if type=='Atto')
   * @param  integer  $id      - the identifier of the OppAtto or OppPolitico object the intervention is related to
   *
   * @return void
   * @author Guglielmo Celata
   **/
  public static function addGroupIntervention($data, $sede_id, $tipo_atto_id, $id)
  {
    // in un certo giorno, in una sede, 
    // qualcuno è intervenuto su un certo atto

    if (!isset($data) || !isset($sede_id) || !isset($tipo_atto_id) || !isset($id)) 
      throw new deppPropelActAsNewsGeneratorException('$data, $sede_id, $tipo_atto_id and $id are required');
      
    $news = new News();
    $news->setGeneratorModel('OppIntervento');
    $news->setDate($data);
    $news->setSedeInterventoId($sede_id);
    $news->setRelatedMonitorableId($id);
    $news->setTipoAttoId($tipo_atto_id);
    $news->setRelatedMonitorableModel('OppAtto');        
    $news->setPriority(2);

    $news->save();
  }

  /**
   * builds the Propel Criterion to extract the group news generated by interventions
   *
   * @return void
   * @author Guglielmo Celata
   **/
  public static function buildGroupInterventionsCriteria($data, $sede_id, $tipo_atto_id, $id)
  {
    if (!isset($data) || !isset($sede_id) || !isset($tipo_atto_id) || !isset($id)) 
      throw new deppPropelActAsNewsGeneratorException('$data, $sede_id, $tipo_atto_id and $id are required');
    
    $c = new Criteria();
    $c->add(self::GENERATOR_MODEL, 'OppIntervento');
    $c->add(self::GENERATOR_PRIMARY_KEYS, null, Criteria::ISNULL);
    $c->add(self::RELATED_MONITORABLE_MODEL, 'OppAtto');
    $c->add(self::RELATED_MONITORABLE_ID, $id);          
    $c->add(self::DATE, $data);
    $c->add(self::SEDE_INTERVENTO_ID, $sede_id);
    $c->add(self::TIPO_ATTO_ID, $tipo_atto_id);
    $c->add(self::PRIORITY, 2);
    return $c;
  }
  

  /**
   * return true, if the sf_news_cache table has a record, regarding 
   * a votation, on given date, place and act
   * the act is an optional parameter, and, when not passed, the search assumes a 
   * different meaning alltogether, that is, a record with priority set to 1
   * is searched, where the value of the RELATED_MONITORABLE_MODEL field is uninfluent
   *
   * @param  date     $data - the date of the seduta when the votation happened
   * @param  char     $ramo - the place where the votation happened (C|S)
   * @param  char     $tipo_atto_id - the type of act the news is related to
   * @param  integer  $atto_id - the identifier for the OppAtto object the votation is related to
   *
   * @return boolean
   * @author Guglielmo Celata
   **/
  public static function hasGroupVotation($data, $ramo, $tipo_atto_id, $atto_id = null)
  {
    $c = self::buildGroupVotationsCriteria($data, $ramo, $tipo_atto_id, $atto_id);
    $n_res = self::doCount($c);
    
    return $n_res>0?true:false;
  }

  /**
   * returns the array of News object that verify the criteria specified by the parameters
   * see parameters meaning in self::hasGroupVotation()
   *
   * @return array of News
   * @author Guglielmo Celata
   **/
  public static function getGroupVotations($data, $ramo, $tipo_atto_id, $atto_id = null)
  {
    $c = self::buildGroupVotationsCriteria($data, $ramo, $tipo_atto_id, $atto_id);
    return self::doSelect($c);
  }
  
  /**
   * returns the array of all the News that regards grouped events for votations 
   *
   * @return array of News
   * @author Guglielmo Celata
   **/
  public static function getAllGroupVotations()
  {
    $c = new Criteria();
    $c->add(self::GENERATOR_MODEL, 'OppVotazioneHasAtto');
    $c->add(self::GENERATOR_PRIMARY_KEYS, null);
    return self::doSelect($c);
  }
  
  
  /**
   * insert a record in the sf_news_cache table, regarding votation on given
   * date, place and act; see description of hasGroupVotation, for the meaning of
   * parameters
   *
   * @param  date     $data - the date of the seduta when the votation happened
   * @param  char     $ramo - the place where the votation happened (C|S)
   * @param  char     $tipo_atto_id - the type of act the news is related to
   * @param  integer  $atto_id - the identifier for the OppAtto object the votation is related to
   *
   * @return void
   * @author Guglielmo Celata
   **/
  public static function addGroupVotation($data, $ramo, $tipo_atto_id, $atto_id = null)
  {
    if (!isset($data) || !isset($ramo) || !isset($tipo_atto_id)) 
      throw new deppPropelActAsNewsGeneratorException('$data, $ramo and $tipo_atto_id are required');
      
    $news = new News();
    $news->setGeneratorModel('OppVotazioneHasAtto');
    $news->setDate($data);
    $news->setRamoVotazione($ramo);
    $news->setRelatedMonitorableModel('OppAtto');
    $news->setTipoAttoId($tipo_atto_id);
    if (!isset($atto_id))
    {
      $news->setPriority(1);
    } else {
      $news->setPriority(2);
      $news->setRelatedMonitorableId($atto_id);
    }
    
    $news->save();
  }

  /**
   * builds a Propel Criteria to count or extract all the group votations
   *
   * @return Criteria object
   * @author Guglielmo Celata
   **/
  public static function buildGroupVotationsCriteria($data, $ramo, $tipo_atto_id, $atto_id = null)
  {
    if (!isset($data) || !isset($ramo) || !isset($tipo_atto_id)) 
      throw new deppPropelActAsNewsGeneratorException('$data, $ramo and $tipo_atto_id are required');
      
    $c = new Criteria();
    $c->add(self::GENERATOR_MODEL, 'OppVotazioneHasAtto');
    $c->add(self::GENERATOR_PRIMARY_KEYS, null, Criteria::ISNULL);
    $c->add(self::DATE, $data);
    $c->add(self::RAMO_VOTAZIONE, $ramo);
    $c->add(self::TIPO_ATTO_ID, $tipo_atto_id);
    $c->add(self::RELATED_MONITORABLE_MODEL, 'OppAtto');
    if (!isset($atto_id))
    {
      $c->add(self::PRIORITY, 1);
    } else {
      $c->add(self::PRIORITY, 2);
      $c->add(self::RELATED_MONITORABLE_ID, $atto_id);
    }
           
    return $c;
  }
  
  
  public static function countHomeNews()
  {
    $c = self::getHomeNewsCriteria();
    return self::doCount($c);
  }

  public static function getHomeNewsGroupedByDayRS()
  {
    $c = self::getHomeNewsCriteria();
    $c->clearSelectColumns();
    $c->addSelectColumn(self::DATE);
    $c->addAsColumn('numNews', 'count('.self::DATE.')');
    $c->addGroupByColumn(self::DATE);
    $c->addDescendingOrderByColumn(self::DATE);
    
    return self::doSelectRS($c);
  }

  public static function getHomeNewsCriteria()
  {
    $c = new Criteria();
    $c->add(self::PRIORITY, 1);
    $c->add(self::GENERATOR_MODEL, 'Tagging', Criteria::NOT_EQUAL);
    return $c;
  }

  // some static constants used to defint the type of acts
  const ATTI_DDL_TIPO_IDS = "1";
  const ATTI_DECRETI_TIPO_IDS = "12";
  const ATTI_DECRETI_LEGISLATIVI_TIPO_IDS = "15, 16, 17";
  const ATTI_NON_LEGISLATIVI_TIPO_IDS = "2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 14";

  public static function countAttiListNews($atto_type_ids, $max_priority = 1)
  {
    $c = self::getAttiListNewsCriteria($atto_type_ids);
    return self::doCount($c);
  }

  public static function getAttiListNews($atto_type_ids, $limit = null, $max_priority = 1)
  {
    $c = self::getAttiListNewsCriteria($atto_type_ids, $limit, $max_priority);
    return self::doSelect($c);
  }

  public static function getAttiListNewsGroupedByDayRS($atto_type_ids)
  {
    $c = self::getAttiListNewsCriteria($atto_type_ids);
    $c->clearSelectColumns();
    $c->addSelectColumn(self::DATE);
    $c->addAsColumn('numNews', 'count('.self::DATE.')');
    $c->addGroupByColumn(self::DATE);
    $c->addDescendingOrderByColumn(self::DATE);
    
    return self::doSelectRS($c);
  }

  /**
   * build the criteria to extract the news for list of acts specified
   * through the $atto_type_ids parameter
   *
   * @param array or int   $atto_type_ids - single id or array of ids of opp_tipo_atto 
   * @param integer        $limit         - the limit to apply (null = no limit)
   * @return Propel Criteria
   * @author Guglielmo Celata
   */
  public static function getAttiListNewsCriteria($atto_type_ids, $limit = null, $max_priority = 1)
  {
    $c = new Criteria();
    $c->add(self::RELATED_MONITORABLE_MODEL, 'OppAtto');
    $atto_type_ids_arr = explode(",", $atto_type_ids);
    
    $exclude = array('OppCaricaHasEmendamento');

    $c->add(self::TIPO_ATTO_ID, $atto_type_ids_arr, Criteria::IN);      
    $c->add(self::PRIORITY, $max_priority, Criteria::LESS_EQUAL);
    $c->add(self::GENERATOR_MODEL, $exclude, Criteria::NOT_IN);

    if (!is_null($limit))
      $c->setLimit($limit);
    $c->addDescendingOrderByColumn(self::DATE);
    return $c;    
  }


  public static function getNewsForItemCriteria($item_type, $item_id)
  { 
    if ($item_type == 'OppPolitico')
      $exclude = array();
    else
      $exclude = array('OppCaricaHasEmendamento');
    
    $c = new Criteria();
    $c->add(self::GENERATOR_MODEL, $exclude, Criteria::NOT_IN);
    $c->add(self::RELATED_MONITORABLE_MODEL, $item_type);
    $c->add(self::RELATED_MONITORABLE_ID, $item_id);
    return $c;
  }

  public static function countNewsForItem($item_type, $item_id)
  {
    $c = self::getNewsForItemCriteria($item_type, $item_id);      
    return self::doCount($c);
  }

  public static function getNewsForItem($item_type, $item_id, $limit = 0)
  {    
    $c = self::getNewsForItemCriteria($item_type, $item_id);      
    $c->addDescendingOrderByColumn(self::DATE);
    $c->addDescendingOrderByColumn(self::CREATED_AT);
    if ($limit > 0)
      $c->setLimit($limit);
    return self::doSelect($c);
  }

  /**
   * fetch all the news related to objects of type atto tagged with the given tag
   *
   * @param string $tag_id 
   * @return array of News object
   * @author Guglielmo Celata
   */
  public static function getNewsForTagCriteria($tag_id)
  {
    
    $c = new Criteria();
    $ct = $c->getNewCriterion(NewsPeer::RELATED_MONITORABLE_MODEL, 'Tag');
    $ct->addAnd($c->getNewCriterion(NewsPeer::RELATED_MONITORABLE_ID, $tag_id));

    // fetch all news ids related to acts tagged with the tag_id tag 
    // not generated by tagging
    $ca = new Criteria();
    $ca->add(TaggingPeer::TAG_ID, $tag_id);
    $ca->addJoin(TaggingPeer::TAGGABLE_ID, self::RELATED_MONITORABLE_ID);
    $ca->add(TaggingPeer::TAGGABLE_MODEL, 'OppAtto');
    $ca->add(self::GENERATOR_MODEL, 'Tagging', Criteria::NOT_EQUAL);
    $ca->clearSelectColumns();
    $ca->addSelectColumn(self::ID);
    $rs = self::doSelectRS($ca);
    $to_add_ids = array();
    while ($rs->next())
    {
      $to_add_ids []= $rs->getInt(1);
    }    
    $ca = $c->getNewCriterion(self::ID, $to_add_ids, Criteria::IN);
    
    $ct->addOr($ca);
    $c->add($ct);
    return $c;
  }

  public static function countNewsForTag($tag_id)
  {
    $c = self::getNewsForTagCriteria($tag_id);
    return self::doCount($c);
  }

  public static function getMyMonitoredItemsNewsCriteria($monitored_objects)
  {
    $opp_user = OppUserPeer::retrieveByPK(sfContext::getInstance()->getUser()->getId());
    return self::getUserMonitoredItemsNewsCriteria($opp_user, $monitored_objects);
  }

  public static function getUserMonitoredItemsNewsCriteria($user, $monitored_objects)
  {    
    sfLogger::getInstance()->info('{NewsPeer} n of objects directly monitored by the user: ' . count($monitored_objects));

    // costruzione dell'array associativo tipo_oggetto => array_di_id
    // di oggetti monitorati dall'utente (solo atti e politici)
    // per i tag, vengono considerti tutti gli oggetti (atti) taggati con l'atto monitorato
    $monitored_hash = array('OppAtto' => array(), 'OppPolitico' => array(), 'OppEmendamento' => array());
    
    $monitored_tags_ids = array();
    foreach ($monitored_objects as $obj)
    {
      if (in_array(get_class($obj), array('OppAtto', 'OppPolitico')))
        array_push($monitored_hash[get_class($obj)], $obj->getId());
      if (get_class($obj) == 'Tag')
      {
        $monitored_tags_ids [] = $obj->getId();
        $tagged_with = $obj->getTaggedWith();
        foreach ($tagged_with as $tagged_obj) {
          array_push($monitored_hash[get_class($tagged_obj)], $tagged_obj->getId());
        }
      }
    }
    
    sfLogger::getInstance()->info('{NewsPeer} n of acts monitored by the user: ' . count($monitored_hash['OppAtto']));
    sfLogger::getInstance()->info('{NewsPeer} n of politicians monitored by the user: ' . count($monitored_hash['OppPolitico']));
    
    // costruzione della query paginata
    $c = new Criteria();
  	$c->addDescendingOrderByColumn(self::DATE);

    // criterio per gli atti
    $crit0 = $c->getNewCriterion(self::RELATED_MONITORABLE_MODEL, 'OppAtto');
    $crit1 = $c->getNewCriterion(self::RELATED_MONITORABLE_ID, $monitored_hash['OppAtto'], Criteria::IN);
    $crit0->addAnd($crit1);

    // criterio per i politici
    $crit2 = $c->getNewCriterion(self::RELATED_MONITORABLE_MODEL, 'OppPolitico');
    $crit3 = $c->getNewCriterion(self::RELATED_MONITORABLE_ID, $monitored_hash['OppPolitico'], Criteria::IN);
    $crit2->addAnd($crit3);

    // politici e atti in OR
    $crit0->addOr($crit2);

    $c->add($crit0);

    // filtro per rimuovere le notizie di tagging non riferite ai tag in monitoraggio
    $to_zap_ids = array();
    $cf = clone $c;
    $cf->add(self::GENERATOR_MODEL, 'Tagging');
    $cf->add(self::TAG_ID, $monitored_tags_ids, Criteria::NOT_IN);
    $cf->clearSelectColumns();
    $cf->addSelectColumn(self::ID);
    $rs = self::doSelectRS($cf);
    while ($rs->next())
    {
      $to_zap_ids []= $rs->getInt(1);
    }
    unset($cf);

    sfLogger::getInstance()->info('{NewsPeer} n of news to zap: ' . count($to_zap_ids));    
    
    $c->add(self::ID, $to_zap_ids, Criteria::NOT_IN);
    
    return $c;
    
  }

  public static function fetchTodayNewsForUser($user, $date = null)
  {
    $c = self::getTodayNewsForUserCriteria($user, $date);
    return self::doSelect($c);
  }
  
  public static function countTodayNewsForUser($user, $date = null)
  {
    $c = self::getTodayNewsForUserCriteria($user, $date);
    return self::doCount($c);    
  }


  public static function getTodayNewsForUserCriteria($user, $date = null)
  {
    $monitored_objects = $user->getMonitoredObjects();

    // criterio di selezione delle news dagli oggetti monitorati    
    $c = self::getMyMonitoredItemsNewsCriteria($monitored_objects);

    // eliminazione delle notizie relative agli oggetti bookmarkati negativamente (bloccati)
    $blocked_items_ids = sfBookmarkingPeer::getAllNegativelyBookmarkedIds($user->getId());
    if (array_key_exists('OppAtto', $blocked_items_ids) && count($blocked_items_ids['OppAtto']))
    {
      $blocked_news_ids = array();
      $bc = new Criteria();
      $bc->add(self::RELATED_MONITORABLE_MODEL, 'OppAtto');
      $bc->add(self::RELATED_MONITORABLE_ID, $blocked_items_ids['OppAtto'], Criteria::IN);
      $bc->clearSelectColumns(); 
      $bc->addSelectColumn(self::ID);
      $rs = self::doSelectRS($bc);
      while ($rs->next()) {
        array_push($blocked_news_ids, $rs->getInt(1));
      }
      $c0 = $c->getNewCriterion(self::ID, $blocked_news_ids, Criteria::NOT_IN);
      $c->addAnd($c0);
    }
    
    // le news di gruppo non sono considerate, perché ridondanti (#247)
    $c->add(self::GENERATOR_PRIMARY_KEYS, null, Criteria::ISNOTNULL);
    
    // add a filter on the date (today's news) or a test date
    // remove news related to actions dated more than 15 days ago (related to the date passed)
    if (is_null($date)) 
    {
      $date = date('Y-m-d');
    }

    # some dates
    $default_time = sfConfig::get('app_default_newsletter_sendtime', '12:45:00');
    $date_noon = date("Y-m-d $default_time", strtotime($date));
    $date_noon_minus_24 = date("Y-m-d $default_time", strtotime('1 day ago', strtotime($date)));
    $fifteen_days_ago = date('Y-m-d', strtotime('15 days ago', strtotime($date))); 	 	 


    # today's news are last 24 hours news, starting at 12:45:00
    $crit0 = $c->getNewCriterion(self::CREATED_AT, $date_noon, Criteria::LESS_EQUAL); 	 	 
    $crit1 = $c->getNewCriterion(self::CREATED_AT, $date_noon_minus_24, Criteria::GREATER_THAN); 	 	 
    $crit0->addAnd($crit1); 	 	 
    $c->add($crit0);          


    # check date, if present 	 	 
    $crit0 = $c->getNewCriterion(self::DATE, null, Criteria::ISNOTNULL); 	 	 
    $crit1 = $c->getNewCriterion(self::DATE, $fifteen_days_ago, Criteria::GREATER_THAN); 	 	 
    $crit0->addAnd($crit1); 	 	 
	 
    # check data_presentazione_atto, if present 	 	 
    $crit2 = $c->getNewCriterion(self::DATA_PRESENTAZIONE_ATTO, null, Criteria::ISNOTNULL); 	 	 
    $crit3 = $c->getNewCriterion(self::DATA_PRESENTAZIONE_ATTO, $fifteen_days_ago, Criteria::GREATER_THAN); 	 	 
    $crit2->addAnd($crit3); 	 	 
	 
    # perform OR 	 	 
    $crit0->addOr($crit2); 	 	 
	 
    # add orred criterion to main criteria 	 	 
    $c->add($crit0); 	 	 
   
    return $c;
  }
  
  
}
