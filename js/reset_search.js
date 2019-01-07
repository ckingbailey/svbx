function resetSearch(ev) {
    for (let el of ev.target.form.elements) el.value = ''
    unnameEmptyFormControls(ev)
    ev.target.form.submit()
};
