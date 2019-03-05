<?php
if(!defined('ROOT')) exit('No direct script access allowed');

switch($_REQUEST['action']){
    case "panel":
        if(isset($_REQUEST['panel'])) {
            $f=__DIR__."/panels/{$_REQUEST['panel']}.php";
            if(file_exists($f)) {
              include_once $f;
            } else {
              echo "<h1 align=center>Panel Not Found</h1>";
            }
        } else {
          echo "<h1 align=center>Panel Not Defined</h1>";
        }
    break;
    case "collist":
        if(isset($_REQUEST['src'])) {
            $dbTable=$_REQUEST['src'];
            $colList=_db()->get_ColumnList($dbTable);
            $fData=[];
            foreach($colList as $a=>$b) {
                $fData[$b]=$b;
            }
            printServiceMsg($fData);
        } else {
          printServiceMsg(["id"=>"ID"]);
        }
    break;
    case "upload":
        if(isset($_FILES) && isset($_FILES['attachfile'])) {
            if(isset($_SESSION['DATAMIGRATORCSVCOLS'])) {
                unset($_SESSION['DATAMIGRATORCSVCOLS']);
            }
            
            // $type=explode("/",$_FILES['attachfile']['type']);
            // $type=strtolower(end($type));
            
            $file=$_FILES['attachfile']['tmp_name'];
            $fname=$_FILES['attachfile']['name'];
            $ext=explode(".",$fname);
            $ext=strtolower(end($ext));
            
            if($_FILES['attachfile']['error']>0) {
                sendResponse(false,"File upload error");
                return;
            }
            
            $tempDir=_dirTemp("datamigrator/".date("Y-m-d"));
            if(!is_dir($tempDir)) {
                mkdir($tempDir,0777, true);
                if(!is_dir($tempDir)) {
                    sendResponse(false,"Cache dir could not be found");
                    return;
                }
            }
            $tName=md5($fname).".{$ext}";//.time()
            $tFile=$tempDir.$tName;
            $a=move_uploaded_file($file,$tFile);
            if(!file_exists($tFile)) {
                sendResponse(false,"Uploading file to temp failed");
                return;
            }
            
            //switch($type) {
            switch($ext) {
                case "csv":
                    sendResponse(true,$tName);
                    break;
                case "tsv":
                    sendResponse(true,$tName);
                    break;
                default:
                    sendResponse(false,"File type not supported");
            }
        } else {
            sendResponse(false,"No file found");
        }
    break;
    case "viewfile":
        if(isset($_REQUEST['src'])) {
            $tempDir=_dirTemp("datamigrator/".date("Y-m-d"));
            $f=$tempDir.$_REQUEST['src'];
            if(file_exists($f)) {
                $data=parseCSVFile($f);
                printServiceMsg($data);
            } else {
              echo "<h1 align=center>Data File Not Found. Try uploading again</h1>";
            }
        } else {
          echo "<h1 align=center>Data File Not Defined</h1>";
        }
    break;
    case "viewcolmap":
        if(isset($_REQUEST['src'])) {
            $tempDir=_dirTemp("datamigrator/".date("Y-m-d"));
            $f=$tempDir.$_REQUEST['src'];
            if(file_exists($f)) {
                $dbTable=$_POST['dbtable'];
                $srcCols=getCSVCols($f);
                $colList=_db()->get_ColumnList($dbTable);
                
                $colList = array_diff($colList, ["created_on","created_by","edited_on","edited_by","guid","access_level","access_rule","privilegeid","workflow"]);
                //$colList=array_flip($colList);
                
                // $data=parseCSVFile($f);
                // printServiceMsg($data);
                
                printServiceMsg(["cols"=>$colList,"srccols"=>$srcCols,"count"=>count($colList)]);
            } else {
              echo "<h1 align=center>Data File Not Found. Try uploading again</h1>";
            }
        } else {
          echo "<h1 align=center>Data File Not Defined</h1>";
        }
    break;
    case "finalize":
        if(isset($_REQUEST['src'])) {
            $tempDir=_dirTemp("datamigrator/".date("Y-m-d"));
            $f=$tempDir.$_REQUEST['src'];
            if(file_exists($f)) {
                $importType=strtolower($_POST['import_type']);
                $charSet=$_POST['charset'];
                $firstColumnName=$_POST['first_column_name'];
                
                $primaryColumn=$_POST['primary_column'];
                
                $colMap=$_POST['colname'];
                $dbTable=$_POST['dbtable'];
                $colList=_db()->get_ColumnList($dbTable);
                
                $csvData=parseCSVFile($f);
                
                $curDate=date("Y-m-d H:i:s");
                $curUser=$_SESSION['SESS_USER_ID'];
                
                $finalData=[];$idArr=[];
                foreach($csvData as $a=>$row) {
                    if($firstColumnName=="true" && $a==0) {
                        continue;
                    }
                    $finalRow=[];
                    foreach($colMap as $k=>$v) {
                        if(isset($row[$v])) {
                            $finalRow[$k]=$row[$v];
                        }
                    }
                    $finalRow['created_by']=$curUser;
                    $finalRow['created_on']=$curDate;
                    $finalRow['edited_by']=$curUser;
                    $finalRow['edited_on']=$curDate;
                    
                    if(isset($finalRow[$primaryColumn]) && strlen($finalRow[$primaryColumn])>0) {
                        $idArr[]=$finalRow[$primaryColumn];
                        $finalData[$finalRow[$primaryColumn]]=$finalRow;
                    } else {
                        $finalData[md5(microtime())]=$finalRow;
                    }
                }
                // printArray($finalData);exit();
                //$toUpdateData=_db()->_selectQ($dbTable,$primaryColumn)->_whereIn($primaryColumn,$idArr)->_GET();
                //printArray($_POST);
                //exit();
                switch($importType) {
                    case "insert_update":
                        if(count($idArr)>0) {
                            $toUpdateData=_db()->_selectQ($dbTable,$primaryColumn)->_whereIn($primaryColumn,$idArr)->_GET();
                            if(count($toUpdateData)>0) {
                                foreach($toUpdateData as $row) {
                                    if(isset($finalData[$row[$primaryColumn]])) {
                                        $qdata=$finalData[$row[$primaryColumn]];
                                        unset($qdata[$primaryColumn]);
                                        
                                        unset($qdata['created_by']);
                                        unset($qdata['created_on']);

                                        $a=_db()->_updateQ($dbTable,$qdata,[$primaryColumn=>$row[$primaryColumn]])->_RUN();
                                        
                                        unset($finalData[$row[$primaryColumn]]);
                                    }
                                }
                            }
                        }
                    case "insert":
                        if(!$finalData || count($finalData)<=0) {
                            echo "<h1 align=center>Data successfully imported into database</h1>";
                            return;
                        }
                        // printArray($finalData);
                        // echo _db()->_insert_BatchQ($dbTable,array_values($finalData))->_SQL();
                        $a=_db()->_insert_BatchQ($dbTable,array_values($finalData))->_RUN();
                        if($a) {
                            echo "<h1 align=center>Data successfully imported into database</h1>";
                        } else {
                            echo "<h1 class='error' align=center>Error inserting into database.</h1><p align=center>"._db()->get_error()."</p>";
                        }
                        break;
                    case "update":
                        if(count($idArr)>0) {
                            $toUpdateData=_db()->_selectQ($dbTable,$primaryColumn)->_whereIn($primaryColumn,$idArr)->_GET();
                            if(count($toUpdateData)>0) {
                                foreach($toUpdateData as $row) {
                                    if(isset($finalData[$row[$primaryColumn]])) {
                                        $qdata=$finalData[$row[$primaryColumn]];
                                        unset($qdata[$primaryColumn]);
                                        
                                        $a=_db()->_updateQ($dbTable,$qdata,[$primaryColumn=>$row['id']])->_RUN();
                                        
                                        unset($finalData[$row[$primaryColumn]]);
                                    }
                                }
                                echo "<h1 align=center>Data successfully updated into database</h1>";
                            } else {
                                echo "<h1 align=center>Found nothing to update</h1>";
                            }
                        } else {
                            echo "<h1 align=center>Could not update without ID</h1>";
                        }
                        break;
                    default:
                        echo "<h1 align=center>Import Type Not Supported</h1>";
                }
            } else {
              echo "<h1 align=center>Data File Not Found. Try uploading again</h1>";
            }
        } else {
          echo "<h1 align=center>Data File Not Defined</h1>";
        }
    break;
}
function sendResponse($status, $msg) {
    if($status) $status="true";
    else $status="false";
    echo "{$msg}<script>parent.uploadResult({$status},'{$msg}');</script>";
}
function parseCSVFile($csvFilePath) {
    if(!file_exists($csvFilePath)) return [];
    $csvFile = file($csvFilePath);
    $data = [];
    foreach ($csvFile as $k=>$line) {
        $rowData=str_getcsv($line);
        if($k==0) {
            $_SESSION['DATAMIGRATORCSVCOLS'] = $rowData;
        }
        $data[] = $rowData;
    }
    return $data;
}
function getCSVCols($csvFilePath) {
    if(isset($_SESSION['DATAMIGRATORCSVCOLS'])) return $_SESSION['DATAMIGRATORCSVCOLS'];
    else {
        parseCSVFile($csvFilePath);
        if(isset($_SESSION['DATAMIGRATORCSVCOLS'])) {
            return $_SESSION['DATAMIGRATORCSVCOLS'];
        }
    }
    return [];
}
?>
