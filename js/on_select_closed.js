function onSelectStatusClosed(ev) {
    const form = document.forms['defForm']
    const selectedStatus = form['status'].selectedOptions[0].innerText.toLowerCase()
    const requiredFields = [ 'repo', 'evidenceType', 'evidenceID' ]

    if (selectedStatus === 'closed') {
        requiredFields.forEach(field => {
            document.querySelector(`label[for="${field}"]`).classList.add('required')
            form[field].setAttribute('required', '')
            $('#closureInfo').collapse('show')
        })
    }

    if (selectedStatus === 'open') {
        requiredFields.forEach(field => {
            document.querySelector(`label[for="${field}"]`).classList.remove('required')
            form[field].removeAttribute('required')
        })
    }
}
