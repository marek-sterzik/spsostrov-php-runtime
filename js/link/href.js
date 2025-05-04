import $ from "jquery"

$(() => {
    $("[data-link-href]").bind("click", function (ev) {
        const href = $(this).attr("data-link-href")
        if (href !== null && href !== undefined && href !== '') {
            window.location = href
        }
        ev.preventDefault()
        return false
    })
})
