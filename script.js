$(function() {
  
  if($(".nav.navbar-right li.active a").length>0) {
    cmd = $(".nav.navbar-right li.active a").data("cmd");
    if(typeof window[cmd]=="function") window[cmd]();
    else importPane();
  } else {
    importPane();
  }
});
function refreshUI() {
  window.location.reload();
}
function importPane(src) {
    $(".nav.navbar-right li.active").removeClass("active");
    $("#toolbtn_importPane").parent().addClass("active");
    $("#pgworkspace").load(_service("dataMigrator","panel")+"&panel=import");
}
function exportPane(src) {
    $(".nav.navbar-right li.active").removeClass("active");
    $("#toolbtn_exportPane").parent().addClass("active");
    $("#pgworkspace").load(_service("dataMigrator","panel")+"&panel=export");
}
function migratePane(src) {
    $(".nav.navbar-right li.active").removeClass("active");
    $("#toolbtn_migratePane").parent().addClass("active");
    $("#pgworkspace").load(_service("dataMigrator","panel")+"&panel=migrate");
}