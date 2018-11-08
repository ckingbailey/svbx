// TODO:
// register event handler
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
    }).then((res, rej) => { // TODO: see THolowachuk article about handling fetch errors
        if (res.ok) return res.text()
    }).then(blob => {
        callback(blob)
    })
}