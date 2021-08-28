<?php $__env->startSection('styles'); ?>
<style>
.tooltip{ 
  position:relative;
  float:right;
}
.tooltip > .tooltip-inner {background-color: #eebf3f; padding:5px 15px; color:rgb(23,44,66); font-weight:bold; font-size:13px;}
.popOver + .tooltip > .tooltip-arrow {	border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 5px solid #eebf3f;}

section{
  margin:100px auto; 
  height:1000px;
}
.progress{
  border-radius:0;
  overflow:visible;
}
.progress-bar{
   background:rgb(23,44,60); 
  -webkit-transition: width 1.5s ease-in-out;
  transition: width 1.5s ease-in-out;
}
</style>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8" id="content_area">
            <div class="card">
                <div class="card-header">
                Upload File
                </div>
                <div class="card-body">
                <form  enctype="multipart/form-data" id="file_upload_form" role="form" method="POST" action="" >
                <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <label for="avatar">Choose CSV File.
                        </label>
                        <input type="file"
                            id="address_file"
                            name="address_file"
                            accept="text/csv, .csv, application/csv" />
                        <button type="submit" id="read_btn">Read File</button>
                </form>
                </div>
            </div>
        </div>
    </div>
    <div id="worker">
        <span id="current_row"></span>
        <span id="total_rows"></span>
        <p id="completion_percent"></p>
    </div>
    <div class="barWrapper">
        <span>
            <B class="progressText"></B>
            <B class="results"></B>
        </span>
        <div class="progress">
            <div class="progress-bar" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100">
                <span  class="popOver" data-toggle="tooltip" data-placement="top" title="70%"> </span>  
            </div>
        </div>
    </div>
    <div id="update_results"></div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script>
var job_data;
var numRows;
$('#file_upload_form').on('submit', function(e) {
    e.preventDefault();
    var address_file = $('#address_file')[0].files[0];
    form = new FormData();
    form.append('address_file', address_file);
    $.ajax({
        url: '/readRec',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: form,
        cache: false,
        contentType: false,
        processData: false,
        type: 'POST',
        success:function(response) {
            job_data = response;
            numRows = response.length;
            $('#update_results').append(response.length+' rows loaded.<br><button onclick="update_locations();">Update Records</button><br>');
        }
    });
});
function update_locations()
{
        var counter = 1;
        var results = { updated:0, skipped:0};
        $('#worker').prepend('Working: ');
        $('#current_row').text(counter);
        $('#total_rows').text(' of '+numRows);
        console.log(results);
        var progressbar = $(".progress-bar").attr('aria-valuenow');
        $.each(job_data, function(i, item) {
                
            $.ajax({
                url: '/updateRec',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: item,
                type: 'POST',
                success:function(response) {
                    if(response.status == 'updated'){
                    results.updated++;
                    }else{
                    results.skipped++;
                    }
                    var loaded_percent = Math.round((counter/numRows)*100);
                    $('.progressText').text(loaded_percent+'% completed');
                    $('.results').html('<p>Updated:'+results.updated+'</p><p>Skipped:'+results.skipped+'</p>');
                    $('.progress-bar').width(loaded_percent + '%');
                    $('#current_row').text(counter);
                    counter++;
                    console.log(response.status);
                    $('#update_results').append(response.message);
                },error:function(response) {
                    var loaded_percent = Math.round((counter/numRows)*100);
                    $('.progressText').text(loaded_percent+'% completed');
                    results.skipped++;
                    $('.results').html('<p>Results:</p><p>Updated:'+results.updated+'</p><p>Skipped:'+results.skipped+'</p>');
                    $('.progress-bar').width(loaded_percent + '%');
                    $('#current_row').text(counter);
                    counter++;
                }
            });
            
    });
}
function timer(setting)
{
    if(setting == 'start'){

    }
    if(setting == 'stop'){

    }
}
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>