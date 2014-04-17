var form = $("#add_many_form");
form.submit(function(event) {
    event.preventDefault();
    console.log(manyFormToJSON($(this)));
    var href = $(this).data('href');
    // $.ajax({
    //     type: 'POST',
    //     contentType: 'application/json',
    //     url: href,
    //     dataType: "json",
    //     data: formToJSON($(this)),
    //     success: function(data) {
    //         if (data.success) {
    //             window.location.replace(href + '/' + data.id);
    //         } else {
    //             alert("Could not create this entry. See console log for details");
    //             console.log(data.message);
    //         };
    //     },
    //     error: function(data) {
    //         alert('Errors occured during your request.');
    //         console.log(data);
    //     }
    // });
});

function manyFormToJSON(form) {
    // assmues that the form is using select multiples
    var inputs = {};
    form.children().children('select').each(function() {
        console.log($(this));
        inputs[$(this).attr('id')] = $(this).val();
    });
    return inputs;
}