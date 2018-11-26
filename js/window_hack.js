const WindowHack = function WindowHack() {
    return this
}

WindowHack.goBack = function goBack(message) {
    window.history.go(-1)
    window.alert(message)
}
