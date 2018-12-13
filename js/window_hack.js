const WindowHack = function WindowHack() {
    return this
}

WindowHack.goBack = function goBack(message) {
    message && window.alert(message)
    window.history.go(-1)
}
