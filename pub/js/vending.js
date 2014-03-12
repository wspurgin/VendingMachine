function formToJSON(form) {
    var inputs = {}
    $.each(form.serializeArray(), function(i, input) {
        inputs[input.name] = input.value;
    });

    return JSON.stringify(inputs);
}