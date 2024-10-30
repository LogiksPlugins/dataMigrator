<?php
if(!defined('ROOT')) exit('No direct script access allowed');

loadModule("pages");

if(!isset($_REQUEST['panel'])) $_REQUEST['panel']="import";

$pageOptions=[
		"toolbar"=>[
		    "importPane"=>["title"=>"Import","align"=>"right","class"=>($_REQUEST['panel']=="import")?"active":""],
            "exportPane"=>["title"=>"Export","align"=>"right","class"=>($_REQUEST['panel']=="export")?"active":""],
            // "migratePane"=>["title"=>"Migrate","align"=>"right","class"=>($_REQUEST['panel']=="migrate")?"active":""],
			// ['type'=>"bar"],
            
            "refreshUI"=>["icon"=>"<i class='fa fa-refresh'></i>"],
		],
		"contentArea"=>"pageContentArea"
	];

function pageContentArea() {
  return "<h3 align=center>Loading ...</h3>";
}

echo _css("dataMigrator");
echo _js(["dataMigrator"]);

printPageComponent(false,$pageOptions);
?>
