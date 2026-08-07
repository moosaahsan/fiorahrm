window.ajax_modal = function ($data, $url = "") {
    console.log($data);
    $.ajax({
        url: $url,
        data: {
            data: $data,
        },
        type: "GET",
        error: (err) => {
            console.log(err);
            alert("An error occured");
        },
        success: function (resp) {
            // alert(console.log(resp));
            if (resp) {
                if ($(".uni-modal-content").length) {
                    $(".uni-modal-content").html(resp);
                } else if ($("#shift_update_ajax_modal .modal-dialog").length) {
                    $("#shift_update_ajax_modal .modal-dialog").html(resp);
                    $("#shift_update_ajax_modal").modal('show');
                } else {
                    // Fallback
                    $("#shift_update_ajax_modal .modal-dialog").html(resp);
                }
            }
        },
    });
};
function get_ajax_call(url) {
    $.ajax({
        url: url,
        type: "Get",
        dataType: "json",
        success: function (resp) {
            if (resp == true) {
                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: "Updated",
                    showConfirmButton: false,
                    timer: 500,
                });
                setTimeout(function () {
                    // wait for 5 secs(2)
                    location.reload(); // then reload the page.(3)
                }, 500);
            }
        },
    });
}
function post_ajax_call(url, formData) {
    $.ajax({
        url: url,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (resp) {
            if (resp.success == true) {
                setTimeout(function () {
                    // wait for 5 secs(2)
                    location.reload(); // then reload the page.(3)
                }, 500);
            }
        },
    });
}
function settingFileUpload(url, formData, errorClass) {
    $.ajax({
        url: url,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (resp) {
            if (resp.success == true) {
                $("" + errorClass + "").css("display", "none");
                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: "Updated",
                    showConfirmButton: false,
                    timer: 500,
                });
                setTimeout(function () {
                    // wait for 5 secs(2)
                    location.reload(); // then reload the page.(3)
                }, 500);
            }
        },
        error: function (xhr, status, error) {
            console.log(xhr.responseText);
            console.log(xhr.responseJSON.errors);
            errors = xhr.responseJSON.errors;
            console.log(errors.file[0]);
            $("" + errorClass + "")
                .text(errors.file[0])
                .css("color", "#F00")
                .css("display", "block");
        },
    });
}

// ----------Setting Attrubtes Management----------------------
function showEditDiv(id1, id2, id3) {
    var text1 = document.getElementById(id1);
    var text2 = document.getElementById(id3);
    var btn1 = document.getElementById(id2);
    text1.classList.toggle("show");
    btn1.classList.toggle("show");
    text2.classList.toggle("show");
}
$(function () {
    //Initialize Select2 Elements (SaaS Tailwind skin via admin.css — default theme)
    $(".select2").select2();
    $(".select2bs4").select2();
});

// ---------- Swla Remarks-------------
function getswalinput(url) {
    Swal.fire({
        title: 'Disapprove Remarks',
        input: 'textarea',
        inputLabel: 'Remarks',
        inputPlaceholder: 'Enter remarks of dissaprove',
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'Remarks is required!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            let remarks = result.value;
            url =url+ '?remarks='+ remarks;
             get_ajax_call(url)
        }
    });
}
