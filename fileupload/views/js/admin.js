function toggleCheckboxes(source) {
    var checkboxes = document.getElementsByName('file_ids[]');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}