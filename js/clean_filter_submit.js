function cleanFilterSubmit(ev) {
    // ev.preventDefault();
    for (let el of ev.target.elements) {
        if (el.value === '') el.removeAttribute('name')
    }
}