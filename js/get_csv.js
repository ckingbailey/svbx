// TODO:
// fetch probably needs more headers, e.g., Accept
function getCsv(ev, data, callback) {
    const stringJson = JSON.stringify(data) // TODO: validate JSON before stringifying it
    ev.preventDefault();
    fetch('/api/def/csv.php', {
        method: 'POST',
        body: stringJson,
        headers: {
            "Content-Type": "application/json"
        }
    }).then(res => {
        if (!res.ok) throw res
        return res.text()
    }).then(text => {
        callback(text)
    }).catch(err => {
        if (err.status) { // err is a Response obj, which contains http fail code, resulting from a resolved Promise above
            console.error(`${err.url} ${err.status} ${err.statusText}`)
        }
        else console.error(err) // only "network error" results in Promise reject
    })
}