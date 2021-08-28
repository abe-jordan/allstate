
<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center">
    <div class="col-md-12">
        <div id="worker">
            <span id="current_row"></span>
            <span id="total_rows"></span>
            <p id="completion_percent"></p>
        </div>
        <div class="barWrapper">
            <span><B class="progressText"></B></span>
            <div class="progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100">
                    <span  class="popOver" data-toggle="tooltip" data-placement="top" title="70%"> </span>  
                </div>
            </div>
        </div>
        <div id="update_results"></div>
    </div>
        <div class="col-md-8" id="content_area">
            <div class="card">
                <div class="card-header">
                Upload File
                </div>
                <div class="card-body">
                <form  enctype="multipart/form-data" id="file_upload_form" role="form" method="POST" action="" >
                <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <label for="avatar">Choose CSV File.
                            <small>The system is looking for field names: id, street, city, state, and postalcode in the csv file.</small>
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
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
<script>
var job_data;
var numRows;
$('#file_upload_form').on('submit', function(e) {
    e.preventDefault();
    var address_file = $('#address_file')[0].files[0];
    form = new FormData();
    form.append('address_file', address_file);
    $.ajax({
        url: '/cleaner/read',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: form,
        cache: false,
        contentType: false,
        processData: false,
        type: 'POST',
        success:function(response) {
            job_data = response;
            numRows = response.length;
            $('#update_results').append(response.length+' rows loaded.<br><button class="clean_address" onclick="clean_address();">Get Corrected Addresses</button><br>');
            $('#content_area').html('<table id="records_table" style="width:100%"><thead id="head_content">'+
            '<th>ID</th>'+
            '<th>Street</th>'+
            '<th>City</th>'+
            '<th>State</th>'+
            '<th>PostalCode</th><thead>'+
            '<tbody id="body_content"></tbody>'+
            '</table>'+
            '<button class="clean_address" onclick="clean_address()">Get Corrected Addresses</button>');
        $.each(response, function(i, item) {
        var $tr = $('<tr id="'+i+'">').append(
            $('<td >').text(item.id),
            $('<td>').text(item.street),
            $('<td>').text(item.city),
            $('<td>').text(item.state),
            $('<td>').text(item.postalcode)
        ).appendTo('#body_content');
        //console.log($tr.wrap('<p>').html());
    });
        }
    });
});
    function clean_address(){
        var counter = 1;
        $('#worker').prepend('Working: ');
        $('#current_row').text(counter);
        $('#total_rows').text(' of '+numRows);
        var progressbar = $(".progress-bar").attr('aria-valuenow');
        $('.clean_address').prop( "disabled", true );
        $('#body_content tr').each(function(i, item) {
            var row = {};
            var tbl = $(this).find('td').get().map(function(cell) {
                return $(cell).html();
            });
                row['id'] = tbl[0];
                row['street'] = tbl[1];
                row['city'] = tbl[2];
                row['state'] = tbl[3];
                row['postalcode'] = tbl[4];
            $.ajax({
                url: '/cleaner/clean',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: row,
                type: 'POST',
                success:function(response) {
                var loaded_percent = Math.round((counter/numRows)*100);
                    $('.progressText').text(loaded_percent+'% completed');
                    $('.progress-bar').width(loaded_percent + '%');
                    $('#current_row').text(counter);
                    counter++;
                var new_address ='<td>'+response.street+'</td>'+
                '<td>'+response.city+'</td>'+
                '<td>'+response.state+'</td>'+
                '<td>'+response.zip+'</td>';
                $('#'+i).append(new_address);
                $( "#loading" ).remove();
                $('.clean_address').prop( "disabled", false );
                $('.clean_address').attr('onClick','tableToJson()');
                $('.clean_address').html('Save');
                },error:function(response) {
                    var loaded_percent = Math.round((counter/numRows)*100);
                    $('.progressText').text(loaded_percent+'% completed');
                    $('.progress-bar').width(loaded_percent + '%');
                    $('#current_row').text(counter);
                    counter++;
                }
            });
        });
            var google = '<th>Google/Street</th>'+
            '<th>Google/City</th>'+
            '<th>Google/State</th>'+
            '<th>Google/PostalCode</th>';
            $('#head_content tr').append(google);

    };
    function tableToJson() { 
// Loop through grabbing everything
var myRows = [];
var $headers = $("th");
var $rows = $("tbody tr").each(function(index) {
  $cells = $(this).find("td");
  myRows[index] = {};
  $cells.each(function(cellIndex) {
    myRows[index][$($headers[cellIndex]).html()] = $(this).html();
  });    
});
// Let's put this in the object like you want and convert to JSON (Note: jQuery will also do this for you on the Ajax request)
var myObj = {};
myObj = myRows;
var row = [];
$('#body_content tr').each(function(i, item) {
            
            var tbl = $(this).find('td').get().map(function(cell) {
                return $(cell).html();
            });
            row[i]={
                "id":tbl[0],
                "street":tbl[1],
                "city":tbl[2],
                "state":tbl[3],
                "postalcode":tbl[4],
                "Google":{
                    "street":tbl[5],
                    "city": tbl[6],
                    "state": tbl[7],
                    "postalcode": tbl[8]
                    }
                };
        });
                $.ajax({
                url: '/cleaner/export',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {"locations" : JSON.stringify(row)},
                type: 'POST',
                success:function(response) {
                     window.location.href = response;
                }
            });
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>