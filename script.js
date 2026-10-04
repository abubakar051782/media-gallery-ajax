$(document).ready(function () {


    /* DIALOGS (upload form + full-size viewer) */

    var uploadDialog = document.getElementById("uploadDialog");
    var viewer = document.getElementById("viewer");

    $("#openUpload").click(function () {
        uploadDialog.showModal();
    });

    $("#closeUpload").click(function () {
        uploadDialog.close();
    });

    $("#closeViewer").click(function () {
        viewer.close();
    });

    // clicking the dark backdrop closes a dialog
    $("dialog").on("click", function (e) {
        if (e.target === this) {
            this.close();
        }
    });

    // clicking a card opens the full-size viewer
    $(document).on("click", ".media-card", function (e) {

        if ($(e.target).closest(".delete-btn").length) {
            return;
        }

        var card = $(this);

        $("#viewerImg")
            .attr("src", card.find("img").attr("src"))
            .attr("alt", card.find("h3").text());

        $("#viewerTitle").text(card.find("h3").text());
        $("#viewerDesc").text(card.find("p").text());

        viewer.showModal();
    });


    /* LOAD MEDIA */

    $("#loadMedia").click(function () {

        loadMedia();

    });


    function showSkeletons() {

        var heights = [220, 300, 180, 260, 340, 200, 280, 240];

        $.each(heights, function (i, h) {
            $("#gallery").append(
                "<div class='skeleton' style='height:" + h + "px'></div>"
            );
        });
    }


    function loadMedia() {

        $("#status").html("Loading media...");

        $("#gallery").empty();

        showSkeletons();


        $.ajax({

            url: "index.php?action=load",

            type: "GET",

            dataType: "json",


            success: function (data) {

                $("#gallery").empty();


                if (data.length == 0) {

                    $("#gallery").html(
                        "<div class='empty-message'>" +
                        "No media found. Use Upload media to add your first image." +
                        "</div>"
                    );

                    $("#status").html(
                        "No media found."
                    );

                    return;
                }


                $.each(data, function (index, media) {


                    var html = "";

                    html += "<div class='media-card'>";

                    html +=
                        "<img src='uploads/" +
                        media.file_name +
                        "' alt='" +
                        media.title +
                        "' loading='lazy'>";

                    html += "<div class='media-overlay'>";

                    html += "<div class='overlay-top'>";

                    html +=
                        "<button class='delete-btn' data-id='" +
                        media.id +
                        "'>Delete</button>";

                    html += "</div>";

                    html += "<div class='media-info'>";

                    html += "<h3>" + media.title + "</h3>";

                    html += "<p>" + media.description + "</p>";

                    html +=
                        "<span class='media-type'>" +
                        media.media_type +
                        "</span>";

                    html += "</div>";

                    html += "</div>";

                    html += "</div>";


                    $("#gallery").append(html);

                });


                $("#status").html(
                    "Media loaded successfully."
                );

            },


            error: function (xhr, status, error) {

                console.log(
                    "Status:",
                    status
                );

                console.log(
                    "Error:",
                    error
                );

                console.log(
                    "Server Response:",
                    xhr.responseText
                );


                $("#status").html(
                    "Error loading media."
                );


                $("#gallery").html(
                    "<div class='empty-message'>" +
                    "Could not load media." +
                    "</div>"
                );

            }

        });

    }


    /* DELETE MEDIA */

    $(document).on(
        "click",
        ".delete-btn",
        function () {


            var id = $(this).data("id");


            var confirmDelete = confirm(
                "Are you sure you want to delete this media?"
            );


            if (!confirmDelete) {
                return;
            }


            $("#status").html(
                "Deleting media..."
            );


            $.ajax({

                url:
                    "index.php?action=delete&id=" +
                    id,

                type: "GET",

                dataType: "json",


                success: function (response) {


                    if (response.success) {

                        $("#status").html(
                            "Media deleted successfully."
                        );


                        loadMedia();

                    } else {

                        $("#status").html(
                            response.message
                        );

                    }

                },


                error: function (xhr) {

                    console.log(
                        xhr.responseText
                    );


                    $("#status").html(
                        "Error deleting media."
                    );

                }

            });

        }

    );

});