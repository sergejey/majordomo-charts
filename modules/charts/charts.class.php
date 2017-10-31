<?php
/**
* Charts 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 15:03:32 [Mar 03, 2016])
*/
//
//
class charts extends module {
/**
* charts
*
* Module class constructor
*
* @access private
*/
function charts() {
  $this->name="charts";
  $this->title="Charts";
  $this->module_category="<#LANG_SECTION_OBJECTS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='charts' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_charts') {
   $this->search_charts($out);
  }
  if ($this->view_mode=='edit_charts') {
   $this->edit_charts($out, $this->id);
  }
  if ($this->view_mode=='delete_charts') {
   $this->delete_charts($this->id);
   $this->redirect("?data_source=charts");
  }
 }

  if ($this->view_mode=='clone_charts') {
   $rec=SQLSelectOne("SELECT * FROM charts WHERE ID='".$this->id."'");

   $charts_data=SQLSelect("SELECT * FROM charts_data WHERE CHART_ID='".$rec['ID']."'");
   
   unset($rec['ID']);
   $rec['TITLE']=$rec['TITLE'].' (copy)';
   $rec['ID']=SQLInsert('charts', $rec);

   $total=count($charts_data);
   for($i=0;$i<$total;$i++) {
    unset($charts_data[$i]['ID']);
    $charts_data[$i]['CHART_ID']=$rec['ID'];
    SQLInsert('charts_data', $charts_data[$i]);
   }

   $this->redirect("?id=".$rec['ID']."&view_mode=edit_charts");
  }

 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='charts_data') {
  if ($this->view_mode=='' || $this->view_mode=='search_charts_data') {
   $this->search_charts_data($out);
  }
  if ($this->view_mode=='edit_charts_data') {
   $this->edit_charts_data($out, $this->id);
  }
 }
}


 function getDepthByType($depth) {
   if (preg_match('/(\d+)(\w+)/', $depth, $m)) {
    $depth=$m[1];
    if ($m[2]=='d') {
     $type=24*60;
    } elseif ($m[2]=='h') {
     $type=60;
    } elseif ($m[2]=='m') {
     $type=24*30*60;
    }
   } else {
    $chart['HISTORY_DEPTH']=7;
    $chart['HISTORY_TYPE']=24;
   }
   return array($depth, $type);
 }
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 if ($this->ajax) {

  $result=array();

  global $id;
  global $prop_id;

  if ($id=='config') {
   $chart=array();
   $chart_data=array();

   $tmp=explode('.', $prop_id);
   $chart_data['LINKED_OBJECT']=$tmp[0];
   $chart_data['LINKED_PROPERTY']=$tmp[1];
   $depth=$tmp[2];
   list($chart['HISTORY_DEPTH'], $chart['HISTORY_TYPE']) = $this->getDepthByType($depth);

  } else {
   $chart=SQLSelectOne("SELECT * FROM charts WHERE ID='".(int)$id."'");
   if (!$chart['ID']) {
    $result['ERROR']=1;
    $result['ERROR_DATA']="Invalid chart id";
    echo json_encode($result);
    exit;
   }
   $chart_data=SQLSelectOne("SELECT * FROM charts_data WHERE ID='".(int)$prop_id."' AND CHART_ID='".(int)$chart['ID']."'");
  }

  $obj=getObject($chart_data['LINKED_OBJECT']);
  $prop_id=$obj->getPropertyByName($chart_data['LINKED_PROPERTY'], $obj->class_id, $obj->id);

  $pvalue=SQLSelectOne("SELECT * FROM pvalues WHERE PROPERTY_ID='".$prop_id."' AND OBJECT_ID='".$obj->id."'");


  $history=array();

  $result['RESULT']='OK';
  if ($pvalue['ID']) {

   if (defined('SEPARATE_HISTORY_STORAGE') && SEPARATE_HISTORY_STORAGE == 1) {
    $history_table = createHistoryTable($pvalue['ID']);
   } else {
    $history_table = 'phistory';
   }

   $history_depth=$chart['HISTORY_DEPTH'];
   $history_type=$chart['HISTORY_TYPE'];

   $real_depth=$history_depth*$history_type*60;
   
   if ($real_depth==0) {
   
        $val=getGlobal($chart_data['LINKED_OBJECT'].'.'.$chart_data['LINKED_PROPERTY']);
        $val=(float)preg_replace('/[^\d\.\-]/', '', $val);
        $history[]=array((float)$val);
   
   
   } else {

   $start_time=time()-$real_depth;
   $end_time=time();

   $tm1=strtotime(date('Y-m-d H:i:s'));
   $tm2=strtotime(gmdate('Y-m-d H:i:s'));
   $diff=$tm1-$tm2;

   $data0=SQLSelectOne("SELECT ID, VALUE, UNIX_TIMESTAMP(ADDED) as UNX, ADDED FROM $history_table WHERE VALUE_ID='".$pvalue['ID']."' AND ADDED<=('".date('Y-m-d H:i:s', $start_time)."') ORDER BY ADDED DESC LIMIT 1");
   if ($data0['ID']) {
    $dt=((int)$start_time+$diff)*1000;
    $val=(float)preg_replace('/[^\d\.\-]/', '', $data0['VALUE']);
    $history[]=array($dt, $val);
   }

   $data=SQLSelect("SELECT ID, VALUE, UNIX_TIMESTAMP(ADDED) as UNX, ADDED FROM $history_table WHERE VALUE_ID='".$pvalue['ID']."' AND ADDED>=('".date('Y-m-d H:i:s', $start_time)."') AND ADDED<=('".date('Y-m-d H:i:s', $end_time)."') ORDER BY ADDED");
   $total=count($data);


   $only_boolean=true;
   for($i=0;$i<$total;$i++) {
    //$dt=($data[$i]['UNX']+3*60*60)*1000;
    $dt=((int)$data[$i]['UNX']+$diff)*1000;
    $val=(float)preg_replace('/[^\d\.\-]/', '', $data[$i]['VALUE']);
        if ($val!=0 && $val!=1) {
         $only_boolean=false;
        }
    $history[]=array($dt, $val);
   }

  }

  $dt=(time()+$diff)*1000;
  $val=getGlobal($chart_data['LINKED_OBJECT'].'.'.$chart_data['LINKED_PROPERTY']);
  $val=(float)preg_replace('/[^\d\.\-]/', '', $val);
  $history[]=array($dt, (float)$val);

  if (count($history)==1) {
   $history[]=array($dt-60*1000, (float)$val);
  } else {
   if ($only_boolean) {
    $new_history=array();
        $total=count($history);
         for($i=0;$i<$total;$i++) {
          $new_history[]=$history[$i];
          if (isset($history[$i+1])) {
           $new_rec=$history[$i+1];
           $new_rec[0]=$new_rec[0]-1;
           $new_rec[1]=$history[$i][1];
           $new_history[]=$new_rec;
          }
         }
         $history=$new_history;
         unset($new_history);
   }
  }

  }

  $result['HISTORY']=$history;


  echo json_encode($result);
  exit;
 }


 global $id;
 if (!$this->id && $id) {
  $this->id=$id;
 }

 if ($this->id) {

  if ($_GET['from_list']) {
   $out['FROM_LIST']=1;
  }

  if ($this->id=='config') {
   $rec=array();
   $rec['ID']='config';
   list($rec['HISTORY_DEPTH'], $rec['HISTORY_TYPE']) = $this->getDepthByType($period);
  } else {
   $rec=SQLSelectOne("SELECT * FROM charts WHERE ID='".$this->id."'");
   if (!$rec['ID']) {
    return;
   }

  }

  if ($this->width) {
   $out['WIDTH']=$this->width;
  } else {
   $out['WIDTH']='100%';
  }

  if ($this->height) {
   $out['HEIGHT']=$this->height;
  } else {
   $out['HEIGHT']='400';
  }

  if (!preg_match('/px$/', $out['WIDTH']) && !preg_match('/\%$/', $out['WIDTH'])) {
   $out['HEIGHT'].='px';
  }

  if (!preg_match('/px$/', $out['HEIGHT']) && !preg_match('/\%$/', $out['HEIGHT'])) {
   $out['HEIGHT'].='px';
  }

  if ($this->interval) {
   $out['INTERVAL']=(int)$this->interval;
   if (!$out['INTERVAL']) {
    $out['INTERVAL']=15*60;
   }
  } else {
   if ($rec['HISTORY_DEPTH']>0) {
    $out['INTERVAL']=15*60;   
   } else {
    $out['INTERVAL']=2;
   }
  }


  if ($this->id!='config') {
   $properties=SQLSelect("SELECT * FROM charts_data WHERE CHART_ID='".$rec['ID']."' ORDER BY PRIORITY DESC, ID");
  } else {
   $prop=array();
   $prop['TYPE']='spline';
   global $period;
   global $property;
   $tmp=explode('.', $property);
   $prop['ID']=$property.'.'.$period;
   $prop['TITLE']=$property;
   $prop['LINKED_OBJECT']=$tmp[0];
   $prop['LINKED_PROPERTY']=$tmp[1];
   $properties=array($prop);
   //print_r($properties);exit;
  }
  $total=count($properties);

  $out['FIRST_TYPE']=$properties[0]['TYPE'];

  $prop_name=$properties[0]['LINKED_PROPERTY'];
  $unit=$properties[0]['UNIT'];
  $out['FIRST_UNIT']=$unit;

  for($i=0;$i<$total;$i++) {
   $properties[$i]['NUM']=$i;
   if (($properties[$i]['UNIT']!=$unit || $unit=='')) {
    $prop_name=$properties[$i]['LINKED_PROPERTY'];
    $unit=$properties[$i]['UNIT'];
    $out['MULTIPLE_AXIS']=1;
   }
   if ($properties[$i]['TYPE']=='spline_min') {
    $properties[$i]['TYPE']='spline';
        $properties[$i]['NO_MARKERS']=1;
   }
  }
  $properties[count($properties)-1]['LAST']=1;

  if ($total==2 && $out['MULTIPLE_AXIS']) {
   $properties[count($properties)-1]['OPPOSITE']=1;
  }

  outHash($rec, $out);
  $out['PROPERTIES']=$properties;
 } else {

  $charts=SQLSelect("SELECT * FROM charts ORDER BY TITLE");
  $out['CHARTS']=$charts;

 }

}
/**
* charts search
*
* @access public
*/
 function search_charts(&$out) {
  require(DIR_MODULES.$this->name.'/charts_search.inc.php');
 }
/**
* charts edit/add
*
* @access public
*/
 function edit_charts(&$out, $id) {
  require(DIR_MODULES.$this->name.'/charts_edit.inc.php');
 }
/**
* charts delete record
*
* @access public
*/
 function delete_charts($id) {
  $rec=SQLSelectOne("SELECT * FROM charts WHERE ID='$id'");
  SQLExec("DELETE FROM charts_data WHERE CHART_ID='".$rec['ID']."'");
  // some action for related tables
  SQLExec("DELETE FROM charts WHERE ID='".$rec['ID']."'");
 }
/**
* charts_data search
*
* @access public
*/
 function search_charts_data(&$out) {
  require(DIR_MODULES.$this->name.'/charts_data_search.inc.php');
 }
/**
* charts_data edit/add
*
* @access public
*/
 function edit_charts_data(&$out, $id) {
  require(DIR_MODULES.$this->name.'/charts_data_edit.inc.php');
 }
 function propertySetHandle($object, $property, $value) {
   $table='charts_data';
   $properties=SQLSelect("SELECT ID FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
     //to-do
    }
   }
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  //
  //@include_once(ROOT.'languages/'.$this->name.'_'.SETTINGS_SITE_LANGUAGE.'.php');
  //@include_once(ROOT.'languages/'.$this->name.'_default'.'.php');
  parent::install();
  SQLExec("UPDATE project_modules SET TITLE='".LANG_GENERAL_GRAPHICS."' WHERE NAME='".$this->name."'");
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS charts');
  SQLExec('DROP TABLE IF EXISTS charts_data');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
charts - 
charts_data - 
*/
  $data = <<<EOD
 charts: ID int(10) unsigned NOT NULL auto_increment
 charts: TITLE varchar(100) NOT NULL DEFAULT ''
 charts: SUBTITLE varchar(255) NOT NULL DEFAULT ''
 charts: TYPE varchar(255) NOT NULL DEFAULT ''
 charts: THEME varchar(255) NOT NULL DEFAULT ''
 charts: HISTORY_DEPTH int(10) NOT NULL DEFAULT '0'
 charts: HISTORY_TYPE int(3) NOT NULL DEFAULT '1'
 charts_data: ID int(10) unsigned NOT NULL auto_increment
 charts_data: TITLE varchar(100) NOT NULL DEFAULT ''
 charts_data: VALUE varchar(255) NOT NULL DEFAULT ''
 charts_data: TYPE varchar(50) NOT NULL DEFAULT ''
 charts_data: UNIT varchar(50) NOT NULL DEFAULT ''
 charts_data: CHART_ID int(10) NOT NULL DEFAULT '0'
 charts_data: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 charts_data: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 charts_data: SETTINGS text
 charts_data: PRIORITY int(10) NOT NULL DEFAULT '0'
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTWFyIDAzLCAyMDE2IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
