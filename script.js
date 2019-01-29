$(function() {
  
  importPane(this);
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