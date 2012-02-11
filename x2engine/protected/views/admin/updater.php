<header>
<script>
var count=1;
var fileList=<?php echo json_encode($fileList);?>;
var i=0;
var sqlList=<?php echo json_encode($sqlList);?>;
var j=0;
var sqlCount=1;

function downloadFile(fileList, i){
    $.ajax({
          url: "download",
          type: "GET",
          data: {'url':'<?php echo $url;?>','file':fileList[i]},
          context: document.body,
          success: function(){
              if(count==fileList.length){
                  $('#update-text').html('Download complete.');
                  alert('Download complete.');
                  sql(sqlList, j);
              }else{
                  count++;
                  var width=count/fileList.length*100;
                  width=Math.round(width);
                  $('#progress').css({'width':width+'%'});
                  $('#progress-text').html(width+"%");
                  $('#update-text').html('Downloading file: '+fileList[count-1]);
                  downloadFile(fileList, i+1);
              }
              
          },
          error: function(){
              cleanUp('error');
          }
        });
}

function sql(sqlList, j){
    $.ajax({
          url: "sql",
          type: "POST",
          data: {'sql':sqlList[j]},
          context: document.body,
          success: function(){
              if(sqlCount==sqlList.length){
                  $('#update-text').html('Update complete.');
                  alert("SQL Exectution Complete.");
                  cleanUp('success');
              }else{
                  sqlCount++;
                  var width=sqlCount/sqlList.length*100;
                  width=Math.round(width);
                  $('#progress').css({'width':width+'%'});
                  $('#progress-text').html(width+"%");
                  $('#update-text').html('Executing SQL: '+sqlList[sqlCount-1]);
                  sql(sqlList, j+1);
              }
              
          },
          error: function(){
              cleanUp('error');
              
          }
        });
}

function cleanUp(status){
    $.ajax({
          url: "cleanUp",
          context: document.body,
          type: "POST",
          data: {'status':status, 'version':<?php echo $newVersion; ?>, 'fileList':fileList},
          success: function(response){
              alert(response);
              window.location.reload();
          }
        });
}
</script>
<style>
    #progress{
        background:-webkit-gradient(linear, left top, left bottom, from(#729C00), to(#579100));
	background:-moz-linear-gradient(top,  #729C00,  #579100);
        width:0px;
        height:30px;
    }
</style>
</header>
<?php
Yii::app()->clientScript->registerScript("updater","$('#update-button').click(function(){
    downloadFile(fileList, i);
    $('#update-status').show();
});",CClientScript::POS_READY);
?>

<h2>X2CRM Automatic Update</h2>
<?php
echo "Number of files to download: <b>".count($fileList)."</b><br />";
echo "Number of changes to database schema: <b>".count($sqlList)."</b><br /><br />";
echo "Your updater version: <b>".$updaterVersion."</b><br />";
echo "Current updater version: <b>".$updaterCheck."</b><br /><br />";
echo "Your X2CRM version: <b>".$version."</b><br />";
echo "Current X2CRM version: <b>".$versionTest."</b><br /><br />";
echo $changelog;
?>
<a href="#" class="x2-button" id="update-button">Update</a><br /><br />
<div id="update-status" style="">
<div id="progress-bar" style="width:300px;height:30px;border-style:solid;border-width:2px;">
    <div id="progress"><div id="progress-text" style="height:30px;width:300px;text-align:center;font-weight:bold;font-size:15px;">0%</div></div>
</div><br />
<div id="update-text" style="">Click "Update" to begin the update.</div>
</div>

