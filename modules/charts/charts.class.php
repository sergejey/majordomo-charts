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
class charts extends module
{
    /**
     * charts
     *
     * Module class constructor
     *
     * @access private
     */
    function charts()
    {
        $this->name = "charts";
        $this->title = "Charts";
        $this->module_category = "<#LANG_SECTION_OBJECTS#>";
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 0)
    {
        $p = array();
        if (isset($this->id)) {
            $p["id"] = $this->id;
        }
        if (isset($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (isset($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (isset($this->data_source)) {
            $p["data_source"] = $this->data_source;
        }
        if (isset($this->tab)) {
            $p["tab"] = $this->tab;
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
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $data_source;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($data_source)) {
            $this->data_source = $data_source;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (isset($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (isset($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['DATA_SOURCE'] = $this->data_source;
        $out['TAB'] = $this->tab;
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'charts' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_charts') {
                $this->search_charts($out);
            }
            if ($this->view_mode == 'edit_charts') {
                $this->edit_charts($out, $this->id);
            }
            if ($this->view_mode == 'delete_charts') {
                $this->delete_charts($this->id);
                $this->redirect("?data_source=charts");
            }
        }

        if ($this->view_mode == 'clone_charts') {
            $rec = SQLSelectOne("SELECT * FROM charts WHERE ID='" . $this->id . "'");

            $charts_data = SQLSelect("SELECT * FROM charts_data WHERE CHART_ID='" . $rec['ID'] . "'");

            unset($rec['ID']);
            $rec['TITLE'] = $rec['TITLE'] . ' (copy)';
            $rec['ID'] = SQLInsert('charts', $rec);

            $total = count($charts_data);
            for ($i = 0; $i < $total; $i++) {
                unset($charts_data[$i]['ID']);
                $charts_data[$i]['CHART_ID'] = $rec['ID'];
                SQLInsert('charts_data', $charts_data[$i]);
            }

            $this->redirect("?id=" . $rec['ID'] . "&view_mode=edit_charts");
        }

        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'charts_data') {
            if ($this->view_mode == '' || $this->view_mode == 'search_charts_data') {
                $this->search_charts_data($out);
            }
            if ($this->view_mode == 'edit_charts_data') {
                $this->edit_charts_data($out, $this->id);
            }
        }
    }


    function getDepthByType($depth)
    {
        if (preg_match('/(\d+)(\w+)/', $depth, $m)) {
            $depth = $m[1];
            if ($m[2] == 'd') {
                $type = 24 * 60;
            } elseif ($m[2] == 'h') {
                $type = 60;
            } elseif ($m[2] == 'm') {
                $type = 24 * 30 * 60;
            }
            $chart['HISTORY_DEPTH'] = $depth;
            $chart['HISTORY_TYPE'] = $type;
        } else {
            $chart['HISTORY_DEPTH'] = 7;
            $chart['HISTORY_TYPE'] = 24;
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
    function usual(&$out)
    {

        $period = gr('period');
        $out['PERIOD'] = $period;

        $group = gr('group');
        $out['GROUP'] = $group;

        $group_type = gr('group_type');
        if (!$group_type) {
            $group_type = 'avg';
        }
        $out['GROUP_TYPE'] = $group_type;

        $id = gr('id');
        $out['ID'] = $id;


        if (!$this->id && $id) {
            $this->id = $id;
        } elseif ($this->id) {
            $id = $this->id;
        }

        if ($_GET['enable_fullscreen']) {
            $this->enable_fullscreen = 1;
        }
        $out['ENABLE_FULLSCREEN'] = (int)$this->enable_fullscreen;
        $out['UNIQ_ID'] = 'chart_' . rand(0, 99999);

        if ($id == 'config') {
            $chart = array();
            $chart['ID'] = 'config';
            $chart_data = array();
            if (gr('prop_id')) {
                $property = gr('prop_id');
            } elseif (gr('property')) {
                $property = gr('property');
            }
            $tmp = explode('.', $property);
            $chart_data['LINKED_OBJECT'] = $tmp[0];
            $chart_data['LINKED_PROPERTY'] = $tmp[1];
            list($chart['HISTORY_DEPTH'], $chart['HISTORY_TYPE']) = $this->getDepthByType($period);
        } elseif ($id) {
            $chart = SQLSelectOne("SELECT * FROM charts WHERE ID='" . (int)$id . "'");
            if (!$chart['ID']) {
                $result['ERROR'] = 1;
                $result['ERROR_DATA'] = "Invalid chart id";
                echo json_encode($result);
                return;
            }
        }

        $history_depth = $chart['HISTORY_DEPTH'];
        $history_type = $chart['HISTORY_TYPE'];
        $real_depth = $history_depth * $history_type * 60;

        $start_time = strtotime(date('Y-m-d H:00:00')) - $real_depth;
        $end_time = time();

        $tm1 = strtotime(date('Y-m-d H:i:s'));
        $tm2 = strtotime(gmdate('Y-m-d H:i:s'));
        $diff = $tm1 - $tm2;


        if ($this->ajax) {

            $result = array();

            /*** add by skysilver ***/
            $op = gr('op');
            if ($op == 'saveJSON') {
                $config = file_get_contents('php://input');
                if ($config = json_decode($config)) {
                    $chart = SQLSelectOne("SELECT * FROM charts WHERE ID='" . (int)$config->id . "'");
                    if ($chart['ID']) {
                        $chart['HIGHCHARTS_CONFIG'] = json_encode($config->config);
                        SQLUpdate('charts', $chart);
                    }
                }
                exit ('OK');
            }
            /************************/

            $prop_id = gr('prop_id');

            if (is_numeric($chart['ID'])) {
                global $multi_data;
                if ($multi_data) {
                    $chart_data = SQLSelect("SELECT * FROM charts_data WHERE CHART_ID='" . (int)$chart['ID'] . "'");
                    $total = count($chart_data);
                    $result['RESULT'] = 'OK';
                    $result['HISTORY'] = array();
                    for ($i = 0; $i < $total; $i++) {
                        $rec = array();
                        $value = getGlobal($chart_data[$i]['LINKED_OBJECT'] . '.' . $chart_data[$i]['LINKED_PROPERTY']);
                        $rec['y'] = (float)$value;
                        $rec['name'] = $chart_data[$i]['TITLE'] . ' (' . $value . ' ' . $chart_data[$i]['UNIT'] . ')';
                        $result['HISTORY'][] = $rec;
                    }
                    echo json_encode($result);
                    exit;
                } else {
                    $chart_data = SQLSelectOne("SELECT * FROM charts_data WHERE ID='" . (int)$prop_id . "' AND CHART_ID='" . (int)$chart['ID'] . "'");
                }

            }

            if ($chart_data['LINKED_OBJECT']) {
                $obj = getObject($chart_data['LINKED_OBJECT']);
                if (is_object($obj)) {
                    $prop_id = $obj->getPropertyByName($chart_data['LINKED_PROPERTY'], $obj->class_id, $obj->id);
                    $pvalue = SQLSelectOne("SELECT * FROM pvalues WHERE PROPERTY_ID='" . $prop_id . "' AND OBJECT_ID='" . $obj->id . "'");
                    $history = array();
                    if ($pvalue['ID']) {

                        if (defined('SEPARATE_HISTORY_STORAGE') && SEPARATE_HISTORY_STORAGE == 1) {
                            $history_table = createHistoryTable($pvalue['ID']);
                        } else {
                            $history_table = 'phistory';
                        }

                        if ($real_depth == 0) {
                            $val = getGlobal($chart_data['LINKED_OBJECT'] . '.' . $chart_data['LINKED_PROPERTY']);
                            $val = (float)preg_replace('/[^\d\.\-]/', '', $val);
                            $history[] = array((float)str_replace(',', '.', $val));
                            // $history[]=array((float)$val);
                        } else {

                            if ($group != '') {
                                $history = array();
                                $periods = $this->getPeriods($start_time, $end_time, $group);
                                $total = count($periods);
                                for ($i = 0; $i < $total; $i++) {
                                    $rec = array();
                                    $rec[0] = $periods[$i]['TITLE'];
                                    $start_period_tm = $periods[$i]['START'];
                                    $end_period_tm = $periods[$i]['END'];
                                    if ($group_type == 'max') {
                                        $value = getHistoryMax($chart_data['LINKED_OBJECT'] . '.' . $chart_data['LINKED_PROPERTY'], $start_period_tm, $end_period_tm);
                                    } elseif ($group_type == 'min') {
                                        $value = getHistoryMin($chart_data['LINKED_OBJECT'] . '.' . $chart_data['LINKED_PROPERTY'], $start_period_tm, $end_period_tm);
                                    } else {
                                        $value = getHistoryAvg($chart_data['LINKED_OBJECT'] . '.' . $chart_data['LINKED_PROPERTY'], $start_period_tm, $end_period_tm);
                                    }
                                    $history[] = round((float)str_replace(',', '.', $value), 2);
                                    // $history[]=round((float)$value,2);
                                }
                            } else {

                                $data0 = SQLSelectOne("SELECT ID, VALUE, UNIX_TIMESTAMP(ADDED) as UNX, ADDED FROM $history_table WHERE VALUE_ID='" . $pvalue['ID'] . "' AND ADDED<=('" . date('Y-m-d H:i:s', $start_time) . "') ORDER BY ADDED DESC LIMIT 1");
                                if ($data0['ID']) {
                                    $dt = ((int)$start_time + $diff) * 1000;
                                    $data0['VALUE'] = (float)str_replace(',', '.', $data0['VALUE']);
                                    $val = (float)preg_replace('/[^\d\.\-]/', '', $data0['VALUE']);
                                    $history[] = array($dt, $val);
                                }

                                if ($chart_data['TYPE'] == 'area_stack') {
                                    $pre_data = SQLSelect("SELECT ID, VALUE, UNIX_TIMESTAMP(ADDED) as UNX, ADDED FROM $history_table WHERE VALUE_ID='" . $pvalue['ID'] . "' AND ADDED>=('" . date('Y-m-d H:i:s', $start_time) . "') AND ADDED<=('" . date('Y-m-d H:i:s', $end_time) . "') ORDER BY ADDED");
                                    if ($history_type >= 1440) {
                                        //day average
                                        $range = 24 * 60 * 60;
                                    } elseif ($history_type >= 60) {
                                        //hour average
                                        $range = 60 * 60;
                                    } else {
                                        //every minute
                                        $range = 5 * 60;
                                    }

                                    $start_time = round($start_time / $range) * $range;
                                    $current_time = $start_time;
                                    $avg_array = array();
                                    $data = array();
                                    $total = count($pre_data);
                                    $avg = 0;
                                    for ($i = 0; $i < $total; $i++) {
                                        $data_time = $pre_data[$i]['UNX'];
                                        if ($data_time >= $current_time) {
                                            $avg_count = count($avg_array);
                                            if ($avg_count > 0) {
                                                $avg = round(array_sum($avg_array) / count($avg_array), 2);
                                            }
                                            //echo date('Y-m-d H:i:s',$current_time)." - ".date('H:i:s',$current_time+$range).": ";echo str_repeat(' ',5*1024);flush();flush();
                                            //echo $avg."<br/> ";echo str_repeat(' ',5*1024);flush();flush();
                                            $data[] = array('UNX' => $current_time, 'VALUE' => $avg);
                                            $current_time += $range;
                                            $avg_array = array();
                                        } else {
                                            $avg_array[] = $pre_data[$i]['VALUE'];
                                        }
                                    }

                                } else {
                                    $data = SQLSelect("SELECT ID, VALUE, UNIX_TIMESTAMP(ADDED) as UNX, ADDED FROM $history_table WHERE VALUE_ID='" . $pvalue['ID'] . "' AND ADDED>=('" . date('Y-m-d H:i:s', $start_time) . "') AND ADDED<=('" . date('Y-m-d H:i:s', $end_time) . "') ORDER BY ADDED");
                                }

                                $total = count($data);
                                $only_boolean = true;
                                for ($i = 0; $i < $total; $i++) {
                                    $dt = ((int)$data[$i]['UNX'] + $diff) * 1000;
                                    $data[$i]['VALUE'] = (float)str_replace(',', '.', $data[$i]['VALUE']);
                                    $val = (float)preg_replace('/[^\d\.\-]/', '', $data[$i]['VALUE']);
                                    if ($val != 0 && $val != 1) {
                                        $only_boolean = false;
                                    }
                                    $history[] = array($dt, $val);
                                }

                                if ($_GET['type'] != 'column') {
                                    $dt = (time() + $diff) * 1000;
                                    $val = getGlobal($chart_data['LINKED_OBJECT'] . '.' . $chart_data['LINKED_PROPERTY']);
                                    $val = (float)str_replace(',', '.', $val);
                                    $val = (float)preg_replace('/[^\d\.\-]/', '', $val);
                                    $history[] = array($dt, (float)$val);
                                }

                                if (count($history) == 1) {
                                    $history[] = array($dt - 60 * 1000, (float)$val);
                                } else {
                                    if ($only_boolean) {
                                        $new_history = array();
                                        $total = count($history);
                                        for ($i = 0; $i < $total; $i++) {
                                            $new_history[] = $history[$i];
                                            if (isset($history[$i + 1])) {
                                                $new_rec = $history[$i + 1];
                                                $new_rec[0] = $new_rec[0] - 1;
                                                $new_rec[1] = $history[$i][1];
                                                $new_history[] = $new_rec;
                                            }
                                        }
                                        $history = $new_history;
                                        unset($new_history);
                                    }
                                }

                            }


                        }


                    }
                }
                $result['HISTORY'] = $history;
            }


            $result['RESULT'] = 'OK';
            echo json_encode($result);
            exit;
        }


        if ($this->id) {

            if ($_GET['from_list']) {
                $out['FROM_LIST'] = 1;
            }

            if ($this->id == 'config') {
                /*
                $rec=array();
                $rec['ID']='config';
                list($rec['HISTORY_DEPTH'], $rec['HISTORY_TYPE']) = $this->getDepthByType($period);
                */

                $prop = array();
                if ($_GET['chart_type'] != '') {
                    $prop['TYPE'] = $_GET['chart_type'];
                } else {
                    $prop['TYPE'] = 'spline_min';
                }
                global $property;
                global $properties;
                if (!is_array($properties)) {
                    $properties = array($property);
                }
                $res_properties = array();
                foreach ($properties as $property) {
                    $tmp = explode('.', $property);
                    $prop['ID'] = $property . '.' . $period;
                    $prop['TITLE'] = $_GET['legend'] ? htmlspecialchars($_GET['legend']) : $property;
                    $prop['LINKED_OBJECT'] = $tmp[0];
                    $prop['LINKED_PROPERTY'] = $tmp[1];
                    $res_properties[] = $prop;
                }
                $properties = $res_properties;
            } else {
                /*
                $rec=SQLSelectOne("SELECT * FROM charts WHERE ID='".$this->id."'");
                if (!$rec['ID']) {
                 return;
                }
                */
                $properties = SQLSelect("SELECT * FROM charts_data WHERE CHART_ID='" . $chart['ID'] . "' ORDER BY PRIORITY DESC, ID");
            }

            if ($group != '') {
                $periods = $this->getPeriods($start_time, $end_time, $group);
                $total = count($periods);
                for ($i = 0; $i < $total; $i++) {
                    $out['GROUP_CATEGORIES'] .= "'" . $periods[$i]['TITLE'] . "',";
                }
            }


            if ($this->width) {
                $out['WIDTH'] = $this->width;
            } else {
                $out['WIDTH'] = '100%';
            }

            if ($_GET['height']) {
                $this->height = $_GET['height'];
            }
            if ($this->height) {
                $out['HEIGHT'] = $this->height;
            } else {
                $out['HEIGHT'] = '300';
            }

            if (!preg_match('/px$/', $out['WIDTH']) && !preg_match('/\%$/', $out['WIDTH'])) {
                $out['HEIGHT'] .= 'px';
            }

            if (!preg_match('/px$/', $out['HEIGHT']) && !preg_match('/\%$/', $out['HEIGHT'])) {
                $out['HEIGHT'] .= 'px';
            }

            if ($this->interval) {
                $out['INTERVAL'] = (int)$this->interval;
                if (!$out['INTERVAL']) {
                    $out['INTERVAL'] = 15 * 60;
                }
            } else {
                if ($chart['HISTORY_DEPTH'] > 0) {
                    $out['INTERVAL'] = 15 * 60;
                } else {
                    $out['INTERVAL'] = 2;
                }
            }

            $total = count($properties);
            $out['FIRST_TYPE'] = $properties[0]['TYPE'];
            $out['FIRST_UNIT'] = $properties[0]['UNIT'];
            if ($out['FIRST_TYPE'] == 'area_stack') {
                $out['FIRST_TYPE'] = 'area';
            }
            if ($out['FIRST_TYPE'] == 'spline_min') {
                $out['FIRST_TYPE'] = 'spline';
            }

            $prop_name = $properties[0]['LINKED_PROPERTY'];
            $unit = $properties[0]['UNIT'];
            $out['FIRST_UNIT'] = $unit;

            for ($i = 0; $i < $total; $i++) {
                $properties[$i]['NUM'] = $i;
                if (($properties[$i]['UNIT'] != $unit || $unit == '')) {
                    $prop_name = $properties[$i]['LINKED_PROPERTY'];
                    $unit = $properties[$i]['UNIT'];
                    $out['MULTIPLE_AXIS'] = 1;
                }

                if ($properties[$i]['TYPE'] == 'area_stack') {
                    $properties[$i]['TYPE'] = 'area';
                    $out['STACK'] = 1;
                }

                if ($properties[$i]['TYPE'] == 'spline_min') {
                    $properties[$i]['TYPE'] = 'spline';
                    $properties[$i]['NO_MARKERS'] = 1;
                }
            }
            $properties[count($properties) - 1]['LAST'] = 1;

            if ($total == 2 && $out['MULTIPLE_AXIS']) {
                $properties[count($properties) - 1]['OPPOSITE'] = 1;
            }

            outHash($chart, $out);
            $out['PROPERTIES'] = $properties;
        } else {
            $charts = SQLSelect("SELECT * FROM charts ORDER BY TITLE");
            $out['CHARTS'] = $charts;
        }

    }

    function getPeriods($start_time, $end_time, $group)
    {
        if ($group == 'month') {
            $start_tm = strtotime(date('Y-m-01 00:00:00', $start_time));
            $end_tm = strtotime(date('Y-m-t 23:59:59', $end_time));
        } elseif ($group == 'day') {
            $start_tm = strtotime(date('Y-m-d 00:00:00', $start_time));
            $end_tm = strtotime(date('Y-m-d 23:59:59', $end_time));
        } else {
            $start_tm = $start_time;
            $end_tm = $end_time;
        }
        if ($group == 'month') {
            $period = 26 * 24 * 60 * 60;
        } elseif ($group == 'hour') {
            $period = 60 * 60;
        } else {
            $period = 24 * 60 * 60;
        }
        $periods = array();
        while ($start_tm < $end_tm) {
            $old_month = date('m', $start_tm);
            $rec = array();


            if ($group == 'month') {
                $rec['END'] = strtotime(date('Y-m-t 23:59:59', $start_tm));
                $rec['START'] = strtotime(date('Y-m-01 00:00:00', $start_tm));
            } else {
                $rec['START'] = $start_tm;
                $rec['END'] = $start_tm + $period;
            }
            $rec['START_TEXT'] = date('Y-m-d H:i:s', $rec['START']);
            $rec['END_TEXT'] = date('Y-m-d H:i:s', $rec['END']);

            if ($group == 'day') {
                $rec['TITLE'] = date('d.m', $start_tm);
            } elseif ($group == 'month') {
                $rec['TITLE'] = date('m,Y', $start_tm);
            } elseif ($group == 'hour') {
                $rec['TITLE'] = date('H', $start_tm);
            } else {
                $rec['TITLE'] = date('Y-m-d', $start_tm);
            }


            $periods[] = $rec;
            $start_tm += $period;
            if ($group == 'month' && date('m', $start_tm) == $old_month) {
                $start_tm += $period;
            }
        }
        //dprint($periods);
        return $periods;
    }

    /**
     * charts search
     *
     * @access public
     */
    function search_charts(&$out)
    {
        require(DIR_MODULES . $this->name . '/charts_search.inc.php');
    }

    /**
     * charts edit/add
     *
     * @access public
     */
    function edit_charts(&$out, $id)
    {
        require(DIR_MODULES . $this->name . '/charts_edit.inc.php');
    }

    /**
     * charts delete record
     *
     * @access public
     */
    function delete_charts($id)
    {
        $rec = SQLSelectOne("SELECT * FROM charts WHERE ID='$id'");
        SQLExec("DELETE FROM charts_data WHERE CHART_ID='" . $rec['ID'] . "'");
        // some action for related tables
        SQLExec("DELETE FROM charts WHERE ID='" . $rec['ID'] . "'");
    }

    /**
     * charts_data search
     *
     * @access public
     */
    function search_charts_data(&$out)
    {
        require(DIR_MODULES . $this->name . '/charts_data_search.inc.php');
    }

    /**
     * charts_data edit/add
     *
     * @access public
     */
    function edit_charts_data(&$out, $id)
    {
        require(DIR_MODULES . $this->name . '/charts_data_edit.inc.php');
    }

    function propertySetHandle($object, $property, $value)
    {
        $table = 'charts_data';
        $properties = SQLSelect("SELECT ID FROM $table WHERE LINKED_OBJECT LIKE '" . DBSafe($object) . "' AND LINKED_PROPERTY LIKE '" . DBSafe($property) . "'");
        $total = count($properties);
        if ($total) {
            for ($i = 0; $i < $total; $i++) {
                //to-do
            }
        }
    }

    /*** add by skysilver ***
     * Экспорт графика в PNG-изображение, используя API сервиса Highcharts.
     * Каталог сохранения файлов по умолчанию ./cms/cached/
     *
     * @param int $chart_id Уникальный идентификатор графика в модуле.
     * @param int $chart_height Высота изображения (пикселей). Опционально.
     * @param int $chart_width Ширина изображения (пикселей). Максимум 2000. Опционально.
     * @param string $path Полный путь к файлу изображения, включая имя файла и расширение. Опционально.
     * @return string|bool           Возращает относительный путь к файлу или false при ошибках.
     */

    function getImage($chart_id, $chart_height = 300, $chart_width = 800, $path = false)
    {
        $chart = SQLSelectOne("SELECT * FROM charts WHERE ID='" . (int)$chart_id . "'");

        if ($chart['ID']) {

            $chart_theme = $chart['THEME'];
            $chart_config = json_decode($chart['HIGHCHARTS_CONFIG']);

            if ($chart_config != false && $chart_config != '') {

                // Размеры
                $chart_config->chart->height = $chart_height;
                $chart_config->chart->width = $chart_width;

                // Исторические данные
                $properties = SQLSelect("SELECT * FROM charts_data WHERE CHART_ID='" . $chart['ID'] . "' ORDER BY PRIORITY DESC, ID");
                $total = count($properties);
                if ($total > 0) {
                    for ($i = 0; $i < $total; $i++) {
                        $data = getURL(BASE_URL . '/ajax/charts.html?id=' . $chart_id . '&prop_id=' . $properties[$i]['ID'], 0);
                        $data = json_decode($data);
                        if ($data->RESULT == 'OK' && count($data->HISTORY) > 0) {
                            $chart_config->series[$i]->data = $data->HISTORY;
                        }
                    }
                }

                // Готовый к отправке конфиг графика
                $chart_config = json_encode($chart_config);

                // Тема/стиль оформления
                $resources['files'] = "http://code.highcharts.com/themes/{$chart_theme}.js";

                // Русская локализация
                $options = '{
                        "lang":{"loading":"Загрузка...",
                        "months":["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],
                        "shortMonths":["Янв","Фев","Март","Апр","Май","Июнь","Июль","Авг","Сент","Окт","Нояб","Дек"],
                        "weekdays":["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"],
                        "shortWeekdays":["Вс","Пн","Вт","Ср","Чт","Пт","Сб"],
                        "exportButtonTitle":"Экспорт","printButtonTitle":"Печать","rangeSelectorFrom":"С","rangeSelectorTo":"По","rangeSelectorZoom":"Период",
                        "downloadPNG":"Скачать PNG","downloadJPEG":"Скачать JPEG","downloadPDF":"Скачать PDF","downloadSVG":"Скачать SVG",
                        "printChart":"Напечатать график","resetZoom":"Сбросить зум","resetZoomTitle":"Сбросить зум",
                        "thousandsSep":" ","decimalPoint":"."}}';

                // Сервис API Highcharts
                $url = 'https://export.highcharts.com/';

                $ch = curl_init($url);

                $data = array('options' => $chart_config,
                    'filename' => 'chart',
                    'type' => 'image/png',
                    'globalOptions' => $options,
                    //'resources' => json_encode($resources),
                    'async' => false
                );

                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                // Отправляем POST-запрос на сервер экспорта Highcharts
                $result = curl_exec($ch);

                curl_close($ch);

                // Если получили результат, то сохраняем в файл.
                if (getimagesizefromstring($result) && strlen($result) > 0) {

                    $file_name = date('Y-m-d_H-i-s') . '_chart_id' . $chart_id . '.png';

                    if ($path === false) {
                        $file_link = 'cms/cached/' . $file_name;
                        $path = ROOT . $file_link;
                    } else {
                        $file_link = str_replace(ROOT, '', $path);
                    }

                    SaveFile($path, $result);

                    // Возвращаем относительную ссылку на файл
                    return '/' . $file_link;
                }
            }
        }
        return false;
    }

//add directman66
    function getImageFromObj($obj, $theme = 'gray', $type = 'area', $days = 7, $chart_height = 300, $chart_width = 800, $path = false)
    {

        $conf =

            '{
  "chart": {
    "renderTo": "container_1",
    "type": "spline",
    "zoomType": "x",
    "events": {}
  },
  "title": {
    "text": "' . $obj . '"
  },
  "xAxis": {
    "type": "datetime",
    "dateTimeLabelFormats": {
      "month": "%e. %b",
      "year": "%b"
    }
  },
  "yAxis": [
    {
      "labels": {
        "format": "{value} ",
        "style": {
          "color": "#2b908f"
        }
      },
      "title": {
        "text": "",
        "style": {
          "color": "#2b908f"
        }
      },
      "index": 0
    }
  ],
  "plotOptions": {
    "spline": {
      "marker": {
        "enabled": true
      }
    },
    "area": {},
    "series": {
      "fillOpacity": 0.25
    }
  },
  "tooltip": {
    "shared": true
  },
  "series": [
    {
      "name": "' . $obj . '",
      "type": "' . $type . '",
      "tooltip": {
        "valueSuffix": " "
      },
      "yAxis": 0,
      "data": [],
      "_colorIndex": 0,
      "_symbolIndex": 0,
      "marker": {
        "enabled": false
      }
    }
  ]}
  ';


        $chart_config = json_decode($conf);

        {
            // Размеры
            $chart_config->chart->height = $chart_height;
            $chart_config->chart->width = $chart_width;

            // Исторические данные

            if ($days) {
                $days = ' and UNIX_TIMESTAMP(ADDED)>=UNIX_TIMESTAMP()-' . (86400 * $days);
            }


            $chart_hist = '{"RESULT":"OK","HISTORY":[';
            $sql = "SELECT  UNIX_TIMESTAMP(ADDED) dt, round(phistory.value,2) value FROM objects, pvalues,phistory where objects.ID=pvalues.OBJECT_ID and pvalues.PROPERTY_NAME='$obj' and phistory.VALUE_ID=pvalues.ID $days   order by added ";
//echo $sql;
            $res = SQLSelect($sql);
//print_r($res);
            $count = count($res);
//echo $count;
            for ($i = 0; $i < $count; $i++) {
                $chart_hist .= '[' . $res[$i]['dt'] . '000' . ',' . $res[$i]['value'] . '],';
//echo '['.$res[$i]['dt'].','.$res[$i]['value'].'],<br>';
            }
            $chart_hist = substr($chart_hist, 0, -1) . ']}';


            $data = json_decode($chart_hist);
            if ($data->RESULT == 'OK' && count($data->HISTORY) > 0) {
                $chart_config->series[0]->data = $data->HISTORY;
            }

            // Готовый к отправке конфиг графика
            $chart_config = json_encode($chart_config);

            // Тема/стиль оформления
            $resources['files'] = "http://code.highcharts.com/themes/" . $theme . ".js";

            // Русская локализация
            $timezone = 0 - date('Z') / 60;
            $options = '{
                        "time": { "timezoneOffset": "' . $timezone . '", },
                        "lang":{"loading":"Загрузка...",
                        "months":["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],
                        "shortMonths":["Янв","Фев","Март","Апр","Май","Июнь","Июль","Авг","Сент","Окт","Нояб","Дек"],
                        "weekdays":["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"],
                        "shortWeekdays":["Вс","Пн","Вт","Ср","Чт","Пт","Сб"],
                        "exportButtonTitle":"Экспорт","printButtonTitle":"Печать","rangeSelectorFrom":"С","rangeSelectorTo":"По","rangeSelectorZoom":"Период",
                        "downloadPNG":"Скачать PNG","downloadJPEG":"Скачать JPEG","downloadPDF":"Скачать PDF","downloadSVG":"Скачать SVG",
                        "printChart":"Напечатать график","resetZoom":"Сбросить зум","resetZoomTitle":"Сбросить зум",
                        "thousandsSep":" ","decimalPoint":"."}}';

            // Сервис API Highcharts
            $url = 'https://export.highcharts.com/';

            $ch = curl_init($url);

            $data = array('options' => $chart_config,
                'filename' => 'chart',
                'type' => 'image/png',
                'globalOptions' => $options,
                //'resources' => json_encode($resources),
                'async' => false
            );

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

            // Отправляем POST-запрос на сервер экспорта Highcharts
            $result = curl_exec($ch);

            curl_close($ch);

            // Если получили результат, то сохраняем в файл.
            if (getimagesizefromstring($result) && strlen($result) > 0) {

                $file_name = 'ch_' . $obj . '_' . date('Y-m-d_H-i-s') . '.png';

                if ($path === false) {
                    $file_link = 'cms/cached/' . $file_name;
                    $path = ROOT . $file_link;
                } else {
                    $file_link = str_replace(ROOT, '', $path);
                }

                SaveFile($path, $result);

                // Возвращаем относительную ссылку на файл
                return '/' . $file_link;
            }
        }

        return false;
    }


    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($data = '')
    {
        //
        //@include_once(ROOT.'languages/'.$this->name.'_'.SETTINGS_SITE_LANGUAGE.'.php');
        //@include_once(ROOT.'languages/'.$this->name.'_default'.'.php');
        parent::install();
        SQLExec("UPDATE project_modules SET TITLE='" . LANG_GENERAL_GRAPHICS . "' WHERE NAME='" . $this->name . "'");
    }

    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall()
    {
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
    function dbInstall($data)
    {
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
 charts: HIGHCHARTS_SETUP text
 charts: HIGHCHARTS_CONFIG text
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
