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

const doSelect = (input, from, to) => {
	input = input[0];
	if (input.createTextRange) {
		var range = input.createTextRange();
		range.collapse(true);
		range.moveEnd('character', to);
		range.moveStart('character', from);
		range.select();
	} else if (input.setSelectionRange) {
		input.focus();
		input.setSelectionRange(from, to);
	}
}

const createFileSelection = (input) => {
    const filename = input.val()
    const match = filename.match(/(\.[a-zA-Z0-9]{1,6})+$/)
    const len = filename.length - (match ? match[0].length : 0)
    doSelect(input, 0, len)
}

const setupEdit = (cell, enabled) => {
    const showWidget = cell.find(".submitted-file-show")
    const editWidget = cell.find(".submitted-file-edit")
    const table = cell.selectParent("table")
    const prevEnabled = !editWidget.is(":hidden")
    if (enabled === "toggle") {
        enabled = !prevEnabled
    }
    if (enabled) {
        showWidget.hide()
        editWidget.show()
        if (!prevEnabled) {
            const inputWidget = editWidget.find("input")
            inputWidget.focus()
            createFileSelection(inputWidget)
        }
        table.find(".submitted-file-actions-button").addClass("disabled")
    } else {
        const inputWidget = editWidget.find("input")
        inputWidget.val(inputWidget.attr("data-original-value"))
        showWidget.show()
        editWidget.hide()
        table.find(".submitted-file-actions-button").removeClass("disabled")
    }
}

$(() => {
    $(".submitted-file-item").each(function () {
        const element = $(this)
        const scrollElement = element.find(".submitted-file")
        const leftArrow = element.find(".submitted-file-left")
        const rightArrow = element.find(".submitted-file-right")
        enableScrollFeature(scrollElement, leftArrow, rightArrow)
    })
    $(".submitted-file-do-edit").bind("click", function (ev) {
        setupEdit($(this).selectParent("tr").find("td.submitted-file-item"), true)
        $(this).selectParent("div.btn-group").find(".show").removeClass("show")
        ev.preventDefault()
        return false
    })

    $(".submitted-file-edit input").bind("keydown", function (ev) {
        if (ev.key == "Escape") {
            setupEdit($(this).selectParent("tr").find("td.submitted-file-item"), false)
            ev.preventDefault()
            return false
        }
    })
})
