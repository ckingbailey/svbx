const getCsv = ev => {
    ev.preventDefault()
    const view = getQueryValue('view')
    const projectDefFields = 'id,bartDefID,location,severity,status,systemaffected,grouptoresolve'
    + ',description,specloc,requiredby,duedate,defType,actionowner,evidenceid,evidencetype'
    + ',safetyCert,certElID,CEID_PDCC,repo,evidencelink,FinalGroup,closurecomments,comment'
    let fields = view && view === 'BART'
        ? 'id,status,date_created,description,resolution,nextStep,comment&view=BART'
        : projectDefFields
    fields = 'fields=' + fields

    let filters = window.location.search
        .slice(1)
        .replace('view=bart', '')

    filters = filters.startsWith('&') ? filters : `&${filters}`

    const querystring = '?' + fields + filters
    const url = '/api/def.php' + querystring
    fetch(url, {
        credentials: 'same-origin',
        headers: { 'Accept': 'text/csv' }
    }).then(res => {
        if (!res.ok) throw Error(`${res.status}: ${res.statusText} @ ${res.url}`)
        return res.blob()
    }).then(blob => {
        const d = new Date()
        const timestamp = d.getFullYear()
            + '' + (d.getMonth() + 1)
            + '' + d.getDate()
            + '' + (d.getHours() < 10 ? '0' + d.getHours() : d.getHours())
            + '' + (d.getMinutes() < 10 ? '0' + d.getMinutes() : d.getMinutes())
            + '' + (d.getSeconds() < 10 ? '0' + d.getSeconds() : d.getSeconds())
        download(blob, `${view || ''}defs_summary_${timestamp}.csv`, 'text/csv')
    }).catch(err => {
        console.error(err)
    })
}