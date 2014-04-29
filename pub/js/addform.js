var form = $("#add_form");
form.submit(function(event) {
    event.preventDefault();

    var href = $(this).data('api-href');
    var redirect = $(this).data('href');
    $.ajax({
        type: 'POST',
        contentType: 'application/json',
        url: href,
        dataType: "json",
        data: formToJSON($(this)),
        success: function(data) {
            if (data.success) {
                window.location.replace(redirect + '/' + data.id);
            } else {
                alert("Could not create this entry. See console log for details");
                console.log(data.message);
            };
        },
        error: function(data) {
            alert('Errors occured during your request.');
            console.log(data);
        }
    });
});