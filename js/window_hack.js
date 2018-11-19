const WindowHack = function WindowHack() {
    return this
}

WindowHack.prototype.goBack = function goBack(message) {
    window.history.go(-1)
    window.alert(message)
}
