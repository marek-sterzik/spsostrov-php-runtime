import $ from "jquery"

const adjustSize = () => {
    const element = $("div.adminer-frame")
    const wh = $(window).height()
    const eot = element.offset().top
    const eh = Math.max(wh - eot, Math.floor(wh/2))
    element.css("height", eh + "px")

}

$(() => {
    adjustSize()
    $(window).resize(adjustSize)
})
