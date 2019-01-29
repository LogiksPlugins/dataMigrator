<?php
if(!defined('ROOT')) exit('No direct script access allowed');

$tables=_db()->get_TableList();
$import_table=explode(',',getConfig('IMPORT_TABLES'));

if(count($import_table)<=0 || strlen($import_table[0])<=0) {
    $import_table=$tables;
}
// printArray($import_table);
//exit;
?>
<br>
<div class="container">
	<div class="row">
    <form id='uploadForm' action="<?=_service("dataMigrator","upload")?>" method="post" enctype="multipart/form-data" target=targetFrame >
		<div class="col-md-10 col-md-offset-1">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <span class="panel-title">Import Data</span>
                    <span class="pull-right">
                        <!-- Tabs -->
                        <ul class="nav panel-tabs">
                            <li class="active"><a href="#tab1">Select Table</a></li>
                            <li><a href="#tab2">Upload File</a></li>
                            <li><a href="#tab3">Validate</a></li>
                            <li><a href="#tab4">Map</a></li>
                            <li><a href="#tab5">Finalize</a></li>
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
                                    if(in_array($tbl,$import_table)){
                                        echo "<option value='{$tbl}'>{$tbl}</option>";    
                                    }
                                }
                              ?>
                            </select>
                          </div>
                          <div class="form-group col-md-6">
                            <label for="exampleSelect1">Select Primary Column</label>
                            <select class="form-control" name="primary_column" required>
                              <option value='id'>ID</option>
                            </select>
                          </div>
                          <div class="form-group col-md-6">
                            <label for="exampleSelect1">Select Import Type</label>
                            <select class="form-control" name="import_type" required>
                              <option value='insert_update'>Insert and Update</option>
                              <option value='insert'>Insert Only</option>
                              <option value='update'>Update Only</option>
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
                        <div class="tab-pane" id="tab2">
                          <div class="form-group">
                            <label for="exampleInputFile">Attach file to import</label>
                            <input type="file" class="form-control-file" name="attachfile" aria-describedby="fileHelp" id='attachfile'  required>
                            <br>
                            <small class="form-text text-muted">* The file should be well formed CSV file</small>
                            <br>
                            <small class="form-text text-muted">* Max upload file size : <?=ini_get("upload_max_filesize")?></small>
                          </div>
                        </div>
                        <div class="tab-pane" id="tab3">
                             <div class='ajaxloading ajaxloading5'></div>
                        </div>
                        <div class="tab-pane" id="tab4">
                            
                        </div>
                        <div class="tab-pane" id="tab5">
                            
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
var uploadFile=null;
$(function() {
    $("#tab1 select[name=dbtable]").change(function() {
        $("#tab1 select[name=primary_column]").load(_service("dataMigrator","collist","select")+"&src="+$("#tab1 select[name=dbtable]").val());
    });
//     $("#attachfile").change(function() {
//         fname=$(this).val();
//         if(fname!=null && fname.length>0) {
// 			fname=fname.split(".");
// 			ext=fname[fname.length-1].toLowerCase();
			
// 		}
//     });
});
function nextTab() {
    err=[];
    $("input[name][required],select[name][required],textarea[name][required]",".tab-pane.active").each(function() {
    	if($(this).val()==null || $(this).val().length<=0) {
    		$(this).closest(".form-group").addClass("alert-danger");
    		err.push($(this).attr("name"));
    	}
    });
    if(err.length>0) {
    	lgksToast("Some required fields are marked red. Please fill them to continue");
    	return;
    }

    if($(".panel-tabs .active").index()==1) {
        $("#uploadForm").submit();
        $(".panel-footer").hide();
    } else if($(".panel-tabs .active").index()==2) {
        viewColMapper();
    } else if($(".panel-tabs .active").index()==3) {
        lgksConfirm("Are you sure about importing this data?","Import Confirmation", function(ans) {
            if(ans) {
                $($(".panel-tabs a")[$(".panel-tabs .active").index()+1]).tab('show');
                finalizeImport();
            }
        });
        return;
    }
    
    if($(".panel-tabs .active").index()>=$(".panel-tabs li").length) return;
    
    $($(".panel-tabs a")[$(".panel-tabs .active").index()+1]).tab('show');
}
function prevTab() {
    if($(".panel-tabs .active").index()<=0) return;
    $($(".panel-tabs a")[$(".panel-tabs .active").index()-1]).tab('show');
}
function uploadResult(status, msg) {
    $(".panel-footer").show();
    
    if(status) {
        uploadFile=msg;
        $("#tab3").html("<h1 align=center>Upload Successfull.</h1><br><br><div class='text-center'><button onclick='showFileData(\""+msg+"\");' class='btn btn-success'>View Data</button></div><br><br>");
        $("#tab4").html("<br><br><div class='text-center'><button onclick='viewColMapper(\""+msg+"\");' class='btn btn-success'>Map Columns</button></div><br><br>");
        //$("#tab5").html("<br><br><div class='text-center'><button onclick='finalizeImport(\""+msg+"\");' class='btn btn-success'>Finalize Import</button></div><br><br>");
    } else {
        if(msg!=null && msg.length>0) lgksToast(msg);
        prevTab();
    }
}
function showFileData(fs) {
    if(fs==null) fs=uploadFile;
    
    qData=$("#uploadForm").serialize();
    processAJAXPostQuery(_service("dataMigrator","viewfile","table")+"&src="+fs, qData, function(data) {
    	dataHTML="<div class='table-responsive' style='padding:5px;'><table class='table table-stripped table-bordered'><tbody>"+data+"</tbody></table></div>";
    	lgksOverlay(dataHTML, "Data Preview");
    });
}
function viewColMapper(fs) {
    if(fs==null) fs=uploadFile;
    
    qData=$("#uploadForm").serialize();
    processAJAXPostQuery(_service("dataMigrator","viewcolmap")+"&src="+fs, qData, function(data) {
    	dataHTML="<div class='table-responsive'><table class='table table-stripped table-bordered'><thead><tr><th>Table Column</th><th>File Column/Index</th></tr></thead><tbody></tbody></table></div>";
    	$("#tab4").html(dataHTML);
    	htmlSelect="<select name='colName' class='form-control'><option value=''>Not Used</option>";
    	if(data.Data.srccols!=null && data.Data.srccols.length>0) {
    	    for(i=0;i<data.Data.srccols.length;i++) {
    			htmlSelect+="<option value='"+i+"'>Column "+i+" ["+data.Data.srccols[i]+"]</option>";
    		}
    	} else {
    	    for(i=0;i<data.Data.count;i++) {
    			htmlSelect+="<option value='"+i+"'>Column "+i+"</option>";
    		}
    	}
		htmlSelect+="</select>";
    	$.each(data.Data.cols, function(a,b) {
    	    $("#tab4 tbody").append("<tr data-name='"+b+"'><th>"+b+"</th><td>"+htmlSelect+"</td></tr>");
    	});
		$("#tab4 tbody tr").each(function(a,b) {
		    $(this).find("select").attr("name","colname["+$(this).closest("tr").data("name")+"]");
			$(this).find("select").val(a);
		});
    },"json");
}
function finalizeImport() {
    $("#tab5").html("<div class='ajaxloading ajaxloading5'>Processing Data, seat back and relax</div>");
    
    qData=$("#uploadForm").serialize();
    processAJAXPostQuery(_service("dataMigrator","finalize")+"&src="+uploadFile, qData, function(data) {
        $("#tab5").html(data);
    });
    
}
</script>