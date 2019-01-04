function dedupeSelection(target, group, options) {
    console.log(target.value)
    group.forEach(selectEl => {
        if (selectEl !== target && target.value !== '') {
            Object.keys(options).forEach(optionVal => {
                console.log('!selectEl.children.optionVal, ', !selectEl.children[optionVal], 'selectEl.value !== target.value, ', selectEl.value !== target.value)
                console.log('cur el: ',  selectEl, ', el val: ', selectEl.value, ', target val: ', target.value, ' cur opt: ', optionVal)
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