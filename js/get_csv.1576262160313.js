const getCsv = ev => {
    ev.preventDefault()
    const view = getQueryValue('view')
    let fields = view && view === 'BART'
        ? 'id,status,date_created,description,resolution,nextStep,comment&view=BART'
        : 'id,bartDefID,location,severity,status,systemaffected,grouptoresolve'
        + ',description,specloc,requiredby,duedate,deftype,actionowner,evidenceid'
        + ',evidencetype,repo,evidencelink,FinalGroup,closurecomments,comment'
    fields = 'fields=' + fields

    let filters = window.location.search
        .toLowerCase()
        .slice(1)
        .replace('view=bart', '')

    filters = filters.startsWith('&') ? filters : `&${filters}`

    const querystring = '?' + fields + filters
    const url = '/api/def.php' + querystring
    fetch(url, {
        credentials: 'same-origin',
        headers: { 'Accept': 'text/csv' }
    }).then(res => {
        if (!res.ok) {
            console.error(`${res.status}: ${res.statusText}`)
            throw res.text()
        }
        return res.text()
    }).then(text => {
        const d = new Date()
        const timestamp = d.getFullYear()
            + '' + (d.getMonth() + 1)
            + '' + d.getDate()
            + '' + (d.getHours() < 10 ? '0' + d.getHours() : d.getHours())
            + '' + (d.getMinutes() < 10 ? '0' + d.getMinutes() : d.getMinutes())
            + '' + (d.getSeconds() < 10 ? '0' + d.getSeconds() : d.getSeconds())
        download(text, `${view || ''}defs_summary_${timestamp}.csv`, 'text/csv')
    }).catch(err => {
        if (typeof err.then === 'function') {
            err.then(text => {
                console.error(text)
            })
        }
        else console.error(err)
    })
}