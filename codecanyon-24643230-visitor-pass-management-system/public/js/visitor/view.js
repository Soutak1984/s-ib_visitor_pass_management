/**
 * Admin visitor show — print ID card
 * Card HTML includes embedded styles so print matches on-screen design.
 */
"use strict";

$(document).ready(function () {
    function printData(data) {
        var frame1 = $('<iframe />');
        frame1[0].name = "frame1";
        frame1.css({ "position": "absolute", "top": "-1000000px" });
        $("body").append(frame1);

        var frameDoc = frame1[0].contentWindow
            ? frame1[0].contentWindow
            : (frame1[0].contentDocument.document
                ? frame1[0].contentDocument.document
                : frame1[0].contentDocument);

        frameDoc.document.open();
        frameDoc.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Visitor ID Card</title>');
        frameDoc.document.write('<style>@page{margin:10mm}html,body{margin:0;padding:0;background:#fff}</style>');
        frameDoc.document.write('</head><body style="margin:0;padding:16px;background:#fff;display:flex;justify-content:center;">');
        // Embedded <style> from partial is inside data — no external CSS needed
        frameDoc.document.write(data);
        frameDoc.document.write('</body></html>');
        frameDoc.document.close();

        setTimeout(function () {
            window.frames["frame1"].focus();
            window.frames["frame1"].print();
            frame1.remove();
        }, 600);
    }

    $('#print').on('click', function (e) {
        e.preventDefault();
        var data = $("#printidcard").html();
        printData(data);
    });
});
