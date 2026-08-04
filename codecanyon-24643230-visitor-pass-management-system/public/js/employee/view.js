/**
 *
 * You can write your JS code here, DO NOT touch the default style file
 * because it will make it harder for you to update.
 *
 */

"use strict";

load_data();

function load_data(status = '',requests='') {
    var table = $('#preregistertable').DataTable({
        processing : true,
        serverSide : true,
        responsive: true,
        autoWidth: false,
        ajax : {
            url : $('#preregistertable').attr('data-url'),
            data : {status : status, requested : ''}
        },
        columns : [
            {data : 'id', name : 'id'},
            {data : 'name', name : 'name'},
            {data : 'email', name : 'email'},
            {data : 'phone', name : 'phone'},
            {data : 'expected_date', name : 'expected_date'},
            {data : 'expected_time', name : 'expected_time'},
            {data : 'action', name : 'action'},
        ],
        "ordering" : false
    });

    let hidecolumn = $('#preregistertable').data('hidecolumn');
    if(!hidecolumn) {
        table.column(5).visible( false );
    }
    window.onresize = function() {
        table.columns.adjust().responsive.recalc();
    };

    var table2 = $('#visitortable').DataTable({
        processing : true,
        serverSide : true,
        responsive: true,
        autoWidth: false,
        ajax : {
            url : $('#visitortable').attr('data-url'),
            data : {status : status, requested : ''}
        },
        columns : [
            {data : 'id', name : 'id'},
            {data : 'image', name : 'image'},
            {data : 'name', name : 'name'},
            {data : 'email', name : 'email'},
            {data : 'date', name : 'date'},
            {data : 'action', name : 'action'},
        ],
        "ordering" : false
    });

    let hidecolumn2 = $('#visitortable').data('hidecolumn');
    if(!hidecolumn2) {
        table2.column( 5 ).visible( false );
    }
    window.onresize = function() {
        table2.columns.adjust().responsive.recalc();
    };
}

$('#preregistertable').on('draw.dt', function () {
    $('[data-toggle="tooltip"]').tooltip();
})

$('#visitortable').on('draw.dt', function () {
    $('[data-toggle="tooltip"]').tooltip();
})

$( document ).ready(function() {
    var css = idCardCss;
    function printData(data,css)
    {
        var frame1 = $('<iframe />');
        frame1[0].name = "frame1";
        frame1.css({ "position": "absolute", "top": "-1000000px" });
        $("body").append(frame1);
        var frameDoc = frame1[0].contentWindow ? frame1[0].contentWindow : frame1[0].contentDocument.document ? frame1[0].contentDocument.document : frame1[0].contentDocument;
        frameDoc.document.open();
        //Create a new HTML document.
        frameDoc.document.write('<html><head><title>Employee ID Card</title>');
        frameDoc.document.write('<link href="'+css+'" rel="stylesheet" type="text/css" />');
        frameDoc.document.write('</head><body>');
        //Append the external CSS file.
        //Append the DIV contents.
        frameDoc.document.write(data);
        frameDoc.document.write('</body></html>');
        frameDoc.document.close();
        setTimeout(function () {
            window.frames["frame1"].focus();
            window.frames["frame1"].print();
            frame1.remove();
        }, 500);
    }

    $('#print').on('click',function(){
        var data = $("#printidcard").html();
        printData(data,css);
    });
});
