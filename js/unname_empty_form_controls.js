function unnameEmptyFormControls(ev) {
    const formEls = ev.target.tagName === 'FORM' ? ev.target : ev.target.form
    for (let el of formEls) {
        if (el.value === '') el.removeAttribute('name')
    }
}