function dedupeSelection(target, group, options) {
    group.forEach(selectEl => {
        if (selectEl !== target && target.value !== '') {
            Object.keys(options).forEach(optionVal => {
                if (!selectEl.children[optionVal]
                    && selectEl.value !== target.value
                ) {
                    const optionEl = document.createElement('option')
                    optionEl.setAttribute('value', optionVal)
                    optionEl.setAttribute('id', optionVal)
                    optionEl.innerText = options[optionVal]
                    selectEl.appendChild(optionEl)
                }
            })
            if (selectEl.children[target.value]) {
                selectEl.options[target.value].remove()
            }
        }
    })
}