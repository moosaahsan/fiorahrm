/*
 Template Name: Veltrix - Responsive Bootstrap 4 Admin Dashboard
 Author: Themesbrand
 File: Datatable js
 */

$(document).ready(function() {
    $('#datatable').DataTable({
        // "columnDefs": [ { 'type': 'date', 'targets': [0] } ],
        // "order": [[ 0, "desc" ]], //or asc
        columnDefs: [
            { type: 'date-eu', targets: 0 },
          ],
          order: [[ 0, 'desc' ]]
    });

    //Buttons examples
    var table = $('#datatable-buttons').DataTable({
        lengthChange: false,
        buttons: ['copy', 'excel', 'pdf', 'colvis'],
        // "ordering": false
        columnDefs: [
            { type: 'date-eu', targets: 0 },
            { type: 'time', targets: 3 }
          ],
          order: [[ 0, 'desc' ], [ 3, 'desc' ]]
    });


    if (typeof table !== 'undefined' && table.buttons) {
        table.buttons().container()
            .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
    }
    
    if (typeof todaytable !== 'undefined' && todaytable.buttons) {
        todaytable.buttons().container()
            .appendTo('#today-Datatable_wrapper .col-md-6:eq(0)');
    }
} );


// $(document).ready(function () {
//     $('#datatable').DataTable({
//         columnDefs: [{ type: 'date-eu', targets: 0 }],
//         order: [[0, 'desc']]
//     });

//     // Ensure DataTable is properly initialized
//     if ($.fn.DataTable.isDataTable("#datatable-buttons")) {
//         $("#datatable-buttons").DataTable().destroy();
//     }

//     // Buttons example with color in PDF
//     var table = $("#datatable-buttons").DataTable({
//         dom: "Bfrtip",
//         lengthChange: false,
//         destroy: true,
//         buttons: [
//             "copy",
//             "excel",
//             {
//                 extend: "pdfHtml5",
//                 text: "Download PDF",
//                 orientation: "landscape",
//                 pageSize: "A4",
//                 exportOptions: {
//                     columns: ":visible",
//                     format: {
//                         body: function (data, row, column, node) {
//                             return $(node).text().trim();
//                         }
//                     }
//                 },
//                 customize: function (doc) {
//                     let colCount = doc.content[1].table.body[0].length;
//                     let colWidths = Array(colCount).fill("*");
//                     doc.content[1].table.widths = colWidths;

//                     // Style headers
//                     doc.styles.tableHeader = {
//                         fillColor: "#343a40",
//                         color: "white",
//                         bold: true,
//                         alignment: "center",
//                     };

//                     // Apply color styling to "Late" (Red), "On Time" (Green), and "Half Day" (Yellow)
//                     doc.content[1].table.body.forEach((row) => {
//                         row.forEach((cell) => {
//                             let text = cell.text.toLowerCase();
//                             if (text.includes("on time")) {
//                                 cell.fillColor = "#28a745"; // Green
//                                 cell.color = "white";
//                             }
//                             if (text.includes("late")) {
//                                 cell.fillColor = "#dc3545"; // Red
//                                 cell.color = "white";
//                             }
//                             if (text.includes("half day")) {
//                                 cell.fillColor = "#ffc107"; // Yellow
//                                 cell.color = "black";
//                             }
//                             cell.alignment = "center";
//                         });
//                     });

//                     // Title customization
//                     doc.content[0].text = "Employee Attendance Report";
//                     doc.content[0].alignment = "center";

//                     // Add border styling
//                     doc.content[1].layout = {
//                         hLineWidth: function (i, node) {
//                             return i === 0 || i === node.table.body.length ? 2 : 1;
//                         },
//                         vLineWidth: function (i, node) {
//                             return i === 0 || i === node.table.widths.length ? 2 : 1;
//                         },
//                         hLineColor: function (i) {
//                             return i === 0 ? "#000000" : "#aaaaaa";
//                         },
//                         vLineColor: function (i) {
//                             return "#aaaaaa";
//                         },
//                     };
//                 }
//             },
//             "colvis"
//         ],
//         columnDefs: [
//             { type: "date-eu", targets: 0 }, // Sorting for date column
//             { type: "time", targets: 3 }    // Sorting for time column
//         ],
//         order: [[0, "desc"], [3, "desc"]]
//     });

//     // table.buttons().container().appendTo("#datatable-buttons_wrapper .col-md-6:eq(0)");
// });