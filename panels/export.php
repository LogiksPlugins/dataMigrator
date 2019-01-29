<?php
if(!defined('ROOT')) exit('No direct script access allowed');

$tables=_db()->get_TableList();
?>
<br>
<div class="container">
	<div class="row">
    <form id='uploadForm' action="<?=_service("dataMigrator","upload")?>" method="post" enctype="multipart/form-data" target=targetFrame >
		<div class="col-md-10 col-md-offset-1">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <span class="panel-title">Export Data</span>
                    <span class="pull-right">
                        <!-- Tabs -->
                        <ul class="nav panel-tabs">
                            <li class="active"><a href="#tab1">Select Table</a></li>
                            <li><a href="#tab2">Upload File Settings</a></li>
                            <li><a href="#tab3">Download</a></li>
                        </ul>
                    </span>
                </div>
                <div class="panel-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab1">
                          <div class="form-group col-md-6">
                            <label for="exampleSelect1">Select Database Table</label>
                            <select class="form-control" name="dbtable" required>
                                <option value=''>Select DB Table</option>
                              <?php
                                foreach($tables as $tbl) {
                                  echo "<option value='{$tbl}'>{$tbl}</option>";
                                }
                              ?>
                            </select>
                          </div>
                        </div>
                        <div class="tab-pane" id="tab2">
                          <div class="form-group col-md-6">
                            <label for="exampleSelect1">Select Export Type</label>
                            <select class="form-control" name="import_type" required>
                              <option value='template'>Template for Import</option>
                              <option value='withdata'>With Data</option>
                            </select>
                          </div>
                          <div class="form-group col-md-6">
                            <label for="exampleSelect1">Character set of the file</label>
                            <select class="form-control" name="charset" required>
                              <option value='utf8'>utf-8</option>
                            </select>
                          </div>
                          <div class="form-group col-md-6">
                            <label for="exampleSelect1">First record is column names</label>
                            <select class="form-control" name="first_column_name" required>
                              <option value='true'>true</option>
                              <option value='false'>false</option>
                            </select>
                          </div>
                        </div>
                        <div class="tab-pane" id="tab3">
                            <h3 align=center>Download complete</h3>
                        </div>
                    </div>
                </div>
                <div class="panel-footer text-right">
                    <button type='button' class='btn btn-danger pull-left' onclick='window.location.reload();'>Restart</button>
                    
                    <button type='button' class='btn btn-info prevbutton' onclick='prevTab()'><< Previous</button>
                    <button type='button' class='btn btn-info nextbutton' onclick='nextTab()'>Next >></button>
                </div>
            </div>
        </div>
    </form>
	</div>
</div>
<iframe id='targetFrame' name='targetFrame' class='hidden' style='display:none !important;'></iframe>
<script>
$(function() {
});
function nextTab() {
    if($(".panel-tabs .active").index()==0) {
        if($("select[name=dbtable]").val()==null || $("select[name=dbtable]").val().length<=0) {
        	lgksToast("Please select source table");
        	return;
        }
    } else if($(".panel-tabs .active").index()==1) {
        $($(".panel-tabs a")[$(".panel-tabs .active").index()+1]).tab('show');
        downloadExportFile();
        return;
    }
    if($(".panel-tabs .active").index()>=$(".panel-tabs li").length) return;
    $($(".panel-tabs a")[$(".panel-tabs .active").index()+1]).tab('show');
}
function prevTab() {
    if($(".panel-tabs .active").index()<=0) return;
    $($(".panel-tabs a")[$(".panel-tabs .active").index()-1]).tab('show');
}
function downloadExportFile() {
    // $(".prevbutton,.nextbutton").hide();
    lgksToast("Downloading File");
    window.open(_service("dataMigrator","export")+"&"+$("form").serialize());
}
</script>