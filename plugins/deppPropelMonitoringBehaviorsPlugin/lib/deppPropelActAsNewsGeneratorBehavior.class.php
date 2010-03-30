<?php
/*
 * This file is part of the deppPropelMonitoringBehaviors package.
 *
 * (c) 2008 Guglielmo Celata <guglielmo.celata@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
?>
<?php
/**
 * This Propel behavior aims at transforming a propel object into a news generator
 * News can then be monitored by the application and the actAsMonitorable behavior.
 *
 * @package    plugins
 * @subpackage monitoring
 * @author     Guglielmo Celata <guglielmo.celata@gmail.com>
 */
class deppPropelActAsNewsGeneratorBehavior
{
  
  protected $wasNew;
  protected $succNews = false;
  
  
  /**
   * return news generated by this generator
   *
   * @return array of Objects
   * @author Guglielmo Celata
   **/
  public function getGeneratedNews(BaseObject $object)
  {
    return NewsPeer::getNewsGeneratedByGenerator($object);
  }
  

  /**
   * return an array of primary keys for the object
   *
   * @param  BaseObject object - the object
   * @return associative array of primary keys (col_name => id_value)
   * @author Guglielmo Celata
   **/
  public function getPrimaryKeysArray(BaseObject $object)
  {
    // get table map and columns map for this generator
    $model_table = call_user_func(get_class($object).'Peer::getTableMap'); 
    $model_columns = $model_table->getColumns();

    // find and store primary keys
    $pks = array();
    foreach($model_columns as $column){
      if ($column->isPrimaryKey())
      {
        $column_php_name = $column->getPhpName();
        $column_getter = 'get'.$column_php_name;
        $pks[$column_php_name] = $object->$column_getter();
      }
    }
    
    return $pks;
  }
  
  /**
   * create as many news as the number of monitorable objects related to the 
   * generating object
   *
   * @param BaseObject $object - the generator object
   * @param int $priority - the priority (it's an override if not null)
   * @param bool $isSuccNews - flag to generate a succNews (only called from batch script)
   *
   * @return int - the number of generated news
   * @author Guglielmo Celata
   **/
  public function generateNews(BaseObject $object, $priority = null, $isSuccNews = null)
  {
    $n_gen_news = 0;
    
    // fetch the monitorable objects related to this generator
    $monitorable_objects = $this->getRelatedMonitorableObjects($object);
    foreach($monitorable_objects as $obj)
    {
      
      // temporarily skip news generation when tagging emendamento
      if ($obj instanceOf OppEmendamento) continue;
      
      $n = new News();
      $n->setGeneratorModel(get_class($object));
      $n->setGeneratorPrimaryKeys(serialize($this->getPrimaryKeysArray($object)));
      $n->setRelatedMonitorableModel(get_class($obj));
      $n->setRelatedMonitorableId($obj->getPrimaryKey());      
      
      // the following methods store data related to the generating object in the cache
      // only data needed to sort, sum, average, or count, are cached
      
      if ($object->getCreatedAt() != null)
        $n->setCreatedAt($object->getCreatedAt());
      
      $n->setDate($object->getNewsDate());
      
      if (!is_null($priority))
        $n->setPriority($priority);
      else
        $n->setPriority($object->getNewsPriority());



      # TODO: spostare le eccezioni fuori dal plugin, in un contesto di applicazione
      // eccezioni
      
      // eccezione per non generare notizia alla presentazione di un decreto legge
      if ($object instanceof OppAtto && $object->getTipoAttoId() == 12) continue;

      if ($obj instanceof OppAtto)
      {
        $n->setDataPresentazioneAtto($obj->getDataPres());
        $n->setTipoAttoId($obj->getOppTipoAtto()->getId());
        $n->setRamoVotazione($obj->getRamo());
      }
      
      // eccezione per modifica valore campo succ (opp_atto)
      if (isset($this->succNews) && $this->succNews || $isSuccNews)
      {
        $n->setSucc($object->getSucc());
        
        $succ_obj = OppAttoPeer::retrieveByPK($object->getSucc());
        $n->setDate($succ_obj->getDataPres('Y-m-d h:i:s'));
      }
      
      // eccezione per news generate dal tagging
      if ($object instanceof Tagging)
        $n->setTagId($object->getTagId());

      $n->save();
      $n_gen_news ++;
    }
    
    return $n_gen_news;
  }
            
  /**
   * retrieve all monitorable objects related to this generating object
   *
   * @return array of objects
   * @author Guglielmo Celata
   **/
  public function getRelatedMonitorableObjects(BaseObject $object)
  {
    $monitorable_models =  sfConfig::get(
      sprintf('propel_behavior_deppPropelActAsNewsGeneratorBehavior_%s_monitorable_models', 
              get_class($object)), array());
    $monitorable_objects = array();
    foreach ($monitorable_models as $model => $callable)
    {
      // self exception: the object itself is added to the monitorable ones
      if ($callable == 'self')
        $monitorable_objects []= $object;
      elseif (is_array($callable))
      {
        // if callable is an array,
        // the related object(s) is/are retrieved through a chain of methods
        $res = $object;
        foreach ($callable as $method)
        {
          $res = $res->$method();
          printf("  %s = (%d)\n", $method, count($res));
        }
        
        // multiple results
        if (is_array($res) && count($res) > 0)
          foreach ($res as  $res_obj)
            $monitorable_objects []= $res_obj;
        
        // single result
        if(is_object($res))
          $monitorable_objects []= $res;
      }
      else
      {
        // if callable is a string (not an array)
        $res = $object->$callable();
        
        // multiple results
        if (is_array($res) && count($res) > 0)
          foreach ($res as  $res_obj)
            $monitorable_objects []= $res_obj;
        
        // single result
        if(is_object($res))
          $monitorable_objects [] = $res;
      }
    }
    return $monitorable_objects;
  }    
  
  

  /**
   * retrieve the monitorable object of given type
   *
   * @param  String - model name of the object to filter
   * @return void
   * @author Guglielmo Celata
   **/
  public function getRelatedMonitorableObject(BaseObject $object, $model_name)
  {
    $monitorable_models =  sfConfig::get(
      sprintf('propel_behavior_deppPropelActAsNewsGeneratorBehavior_%s_monitorable_models', 
              get_class($object)), array());
    $monitorable_objects = array();
    foreach ($monitorable_models as $model => $callable_chain)
    {
      if ($model_name == $model)
      {
        return call_user_func($object, $callable_chain);
      }
    }
    return null;
  }  
  
  /**
   * returns the date when the event took place (as reported in the DB)
   * the getter method is build out of the 'date_method' advanced configuration parameter
   * If the parameter is not set, then the 'Date' string is used (getDate method, thus) 
   *
   * @param  the format to be used
   * @return a date
   * @author Guglielmo Celata
   **/
  public function getNewsDate(BaseObject $object, $format = null)
  {
    $method =  sfConfig::get(
      sprintf('propel_behavior_deppPropelActAsNewsGeneratorBehavior_%s_date_method', 
              get_class($object)), null);

    if (!is_null($method))
    {
      if (is_array($method))
      {
        // the date is retrieved through a chain of methods
        $res = $object;
        foreach ($method as $chain_method)
        {
          $res = $res->$chain_method();
        }
      }
      else
      {
        $getter = "get" . $method;
        $res = $object->$getter($format);      
      }
      return $res;
    } else
      return null;
  }
  
  /**
   * returns the priority value, as defined in the advanced behavior configuration
   * the default value, if the parameter is not explicitly set, is 0 (no priority)
   * priority values accepted:
   * 1 - maximum (Home page)
   * 2 - medium (Lists)
   * 3 - low (Leaves)
   *
   * In some cases, priorities may be different.
   * Votations are more important when the're final.
   * Iters are more important when they're conclusive.
   *
   * This method returns the default priority. To alter the value of priority for a given
   * item, use the priority parameter in the generateNews method
   *
   * @return an integer, showing the priority of the news
   * @author Guglielmo Celata
   **/
  public function getNewsPriority(BaseObject $object)
  {
    $priority =  sfConfig::get(
      sprintf('propel_behavior_deppPropelActAsNewsGeneratorBehavior_%s_priority', 
              get_class($object)), 0);
    return $priority;
  }
  
  
  /**
   * This hook is called before object is saved.
   *
   * @param      BaseObject    $object
   */
  public function preSave(BaseObject $object)
  {
    $this->wasNew = $object->isNew();

    // gestisce eccezione generazione passaggi di iter
    if ($object instanceof OppAtto && !$object->isNew() && $object->isColumnModified(OppAttoPeer::SUCC))
    {
      $this->succNews = true;
    }
  }
  
  /**
   * Intercepts the save method
   * and generates a news in the sf_news_cache table.
   * 
   * Depending on object's attributes, modifies the behaviour
   * 
   *
   * @return void
   * @author Guglielmo Celata
   **/
  public function postSave(BaseObject $object)
  {
    if ((isset($this->wasNew) && $this->wasNew === true) || 
        (isset($this->succNews) && $this->succNews === true))
    {
      // allow news_generation_skipping
      if (isset($object->skip_news_generation) && $object->skip_news_generation == true) return;

      // grouped news generation
      if (isset($object->generate_group_news) && $object->generate_group_news == true ||
          isset($object->generate_only_group_news) && $object->generate_only_group_news == true)
        $object->generateUnlessAlreadyHasGroupNews();        

      // simple news generation
      if (!isset($object->generate_only_group_news) || 
          isset($object->generate_only_group_news) && $object->generate_only_group_news == false)
      {
        if (isset($object->priority_override) && $object->priority_override > 0)
          $object->generateNews($object->priority_override);
        else
          $object->generateNews();                
      }
        
      unset($this->wasNew);    
      unset($this->succNews);
    }
  }
  
  
  /**
   * Deletes all news originated from a generator object (delete cascade emulation)
   * 
   * @param  BaseObject  $object
   */
  public function preDelete(BaseObject $object)
  {
    try
    {
      $c = new Criteria();
      $c->add(NewsPeer::GENERATOR_MODEL, get_class($object));
      $c->add(NewsPeer::GENERATOR_PRIMARY_KEYS, serialize($this->getPrimaryKeysArray($object)));
      NewsPeer::doDelete($c);          
    }
    catch (Exception $e)
    {
      throw new deppPropelActAsNewsGeneratorException(
        'Unable to delete related monitorable object records');
    }

  }
  

}