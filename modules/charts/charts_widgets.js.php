<?php
chdir(dirname(__FILE__) . '/../../');
include_once("./config.php");
include_once("./lib/loader.php");
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
?>

(function()
{

    freeboard.loadWidgetPlugin({
        // Same stuff here as with datasource plugin.
        "type_name"   : "charts_plugin",
        "display_name": "Chart",
        "description" : "MajorDoMo charts",
        "fill_size" : true,
        "settings"    : [
            {
                "name"        : "chart",
                "display_name": "Chart",
                "required" : true,
                "type"        : "option",
                <?php
                $scripts=SQLSelect("SELECT ID,TITLE FROM charts ORDER BY TITLE");
                ?>
                "options"     : [
                    <?php
                    foreach($scripts as $k=>$v) {
                        echo '{';
                        echo '"name" : "'.($v['TITLE']).'",'."\n";
                        echo '"value" : "'.$v['ID'].'"';
                        echo '},';
                    }
                    ?>
                ]
            },
            {
                "name"        : "size",
                "display_name": "Size",
                "type"        : "option",
                "options"     : [
                    {"name" : "1","value": "1"},
                    {"name" : "2","value": "2"},
                    {"name" : "3","value": "3"},
                    {"name" : "4","value": "4"},
                    {"name" : "5","value": "5"},
                    {"name" : "6","value": "6"},
                    {"name" : "7","value": "7"},
                    {"name" : "8","value": "8"}
                ]
            }

        ],
// Same as with datasource plugin, but there is no updateCallback parameter in this case.
        newInstance   : function(settings, newInstanceCallback)
        {
            newInstanceCallback(new myChartsPlugin(settings));
        }
    });

    var myChartsPlugin = function(settings)
    {
        var self = this;
        var currentSettings = settings;
        var widgetElement;
        function updateChartFrame()
        {
            if(widgetElement)
            {
                var newHeight=parseInt(currentSettings.size)*100-20;
                var myTextElement = $("<iframe style='margin-top:20px;height:"+newHeight+"px' src='<?php echo ROOTHTML;?>module/charts.html?id="+currentSettings.chart+"' width='100%' height='"+newHeight+"' frameborder=0></iframe>");
                $(widgetElement).append(myTextElement);
            }
        }

        self.render = function(element)
        {
            widgetElement = element;
            updateChartFrame();
        }

        self.getHeight = function()
        {
            return parseInt(currentSettings.size);
        }

        self.onSettingsChanged = function(newSettings)
        {
            currentSettings = newSettings;
            updateChartFrame();
        }

        self.onCalculatedValueChanged = function(settingName, newValue)
        {
            updateChartFrame();
        }

        self.onDispose = function()
        {
        }

    }


}());

<?php
$db->Disconnect();
?>