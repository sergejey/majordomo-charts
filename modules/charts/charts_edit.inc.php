<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='charts';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='update') {
   $ok=1;
  // step: default
  if ($this->tab=='') {
  //updating '<%LANG_TITLE%>' (varchar, required)
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }

   global $history_depth;
   $rec['HISTORY_DEPTH']=(int)$history_depth;

   global $history_type;
   $rec['HISTORY_TYPE']=(int)$history_type;

  //updating 'SUBTITLE' (varchar)
   global $subtitle;
   $rec['SUBTITLE']=$subtitle;
  //updating 'TYPE' (varchar)
   global $type;
   $rec['TYPE']=$type.'';

   global $theme;
   $rec['THEME']=$theme.'';

   global $highcharts_setup;
   $rec['HIGHCHARTS_SETUP']=$highcharts_setup.'';


  }
  // step: data
  if ($this->tab=='data') {
  }
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  }
  // step: data
  if ($this->tab=='data') {
  }
  if ($this->tab=='data') {
   //dataset2
   $new_id=0;
   if ($this->mode=='update') {
    global $title_new;
        if ($title_new) {
         $prop=array('TITLE'=>$title_new,'CHART_ID'=>$rec['ID'], 'SETTINGS'=>'', 'LINKED_OBJECT'=>'', 'LINKED_PROPERTY'=>'');
         $new_id=SQLInsert('charts_data',$prop);
        }
   }
   global $delete_id;
   if ($delete_id) {
    SQLExec("DELETE FROM charts_data WHERE ID='".(int)$delete_id."'");
   }
   $properties=SQLSelect("SELECT * FROM charts_data WHERE CHART_ID='".$rec['ID']."' ORDER BY PRIORITY DESC, ID");
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($properties[$i]['ID']==$new_id) continue;
    if ($this->mode=='update') {
      global ${'title'.$properties[$i]['ID']};
      $properties[$i]['TITLE']=trim(${'title'.$properties[$i]['ID']});
      global ${'linked_object'.$properties[$i]['ID']};
      $properties[$i]['LINKED_OBJECT']=trim(${'linked_object'.$properties[$i]['ID']});
      global ${'linked_property'.$properties[$i]['ID']};
      $properties[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$properties[$i]['ID']});
          
      global ${'settings'.$properties[$i]['ID']};
      $properties[$i]['SETTINGS']=trim(${'settings'.$properties[$i]['ID']});
          
          if (!$rec['HISTORY_DEPTH'] && !preg_match('/min/is',$properties[$i]['SETTINGS'])) {
           $properties[$i]['SETTINGS']="min:0";
          }

      global ${'type'.$properties[$i]['ID']};
      $properties[$i]['TYPE']=trim(${'type'.$properties[$i]['ID']});

      global ${'unit'.$properties[$i]['ID']};
      $properties[$i]['UNIT']=trim(${'unit'.$properties[$i]['ID']});

      global ${'priority'.$properties[$i]['ID']};
      $properties[$i]['PRIORITY']=(int)(${'priority'.$properties[$i]['ID']});



      SQLUpdate('charts_data', $properties[$i]);
      $old_linked_object=$properties[$i]['LINKED_OBJECT'];
      $old_linked_property=$properties[$i]['LINKED_PROPERTY'];

     }
     $properties[$i]['NUM']=$i;
   }

   $properties[count($properties)-1]['LAST']=1;

   if ($properties[0]['ID']) {
    $out['PROPERTIES']=$properties;   
   }

  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);

  $path=ROOT.'3rdparty/highcharts/themes';

  $files=scandir($path);
  foreach($files as $f) {
   if ($f=='.' || $f=='..') {
    continue;
   }
   $f=str_replace('.js', '', $f);
   $out['THEMES'][]=array('TITLE'=>$f);
  }
