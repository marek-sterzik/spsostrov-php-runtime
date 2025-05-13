import $ from "jquery"

var frame

const adjustSize = () => {
    const element = $("div.adminer-frame")
    const wh = $(window).height()
    const eot = element.offset().top
    const eh = Math.max(wh - eot, Math.floor(wh/2))
    element.css("height", eh + "px")

}

const getInitialSrc = (path) => {
    const hash = window.location.hash.replace(/^#/, '')
    return path + (hash !== "" ? "?" : "") + hash
}

const buildHash = (frameLocation, mainLocation, adminerPath) => {
    if (frameLocation.origin !== mainLocation.origin) {
        return '';
    }
    if (frameLocation.pathname !== adminerPath) {
        return '';
    }
    return frameLocation.search.replace(/^\?/, '')
}

$(() => {
    adjustSize()
    $(window).resize(adjustSize)
    frame = $("div.adminer-frame iframe")
    frame.bind("load", function(){
        const hash = buildHash(this.contentWindow.location, window.location, $(this).attr("data-src"))
        window.location.hash = hash
    })
    frame.attr("src", getInitialSrc(frame.attr("data-src")))
})
