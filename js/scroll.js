import $ from "jquery"

const getMaxScroll = (scroll) => Math.max(scroll[0].scrollWidth - scroll.outerWidth(), 0)

const setScrollStatus = (scroll, left, right) => {
    const maxScroll = getMaxScroll(scroll)
    if (scroll.scrollLeft() > 0) {
        left.show()
    } else {
        left.hide()
    }
    if (scroll.scrollLeft() < maxScroll) {
        right.show()
    } else {
        right.hide()
    }
}

const animateAddToScroll = (scroll, shift) => {
    const maxScroll = getMaxScroll(scroll)
    const finalScroll = Math.max(Math.min(scroll.scrollLeft() + shift, maxScroll), 0)
    scroll.animate({scrollLeft: finalScroll})
    
}

const enableScrollFeature = (scroll, left, right) => {
    const shift = scroll.outerWidth() / 5
    scroll.bind("scroll", () => {
        setScrollStatus(scroll, left, right)
    })
    left.bind("click", (ev) => {
        animateAddToScroll(scroll, -shift)
        ev.preventDefault()
        return false
    })
    right.bind("click", (ev) => {
        animateAddToScroll(scroll, shift)
        ev.preventDefault()
        return false
    })
    setScrollStatus(scroll, left, right)
}

$(() => {
    $(".hscroll").each(function () {
        const element = $(this)
        const leftArrow = $("<a href=\"#\" class=\"button-left bi bi-caret-left-fill text-primary\"></a>")
        const rightArrow = $("<a href=\"#\" class=\"button-right bi bi-caret-right-fill text-primary\"></a>")
        element.prepend(rightArrow).prepend(leftArrow)
        enableScrollFeature(element.find(">div"), leftArrow, rightArrow)
    })
})

