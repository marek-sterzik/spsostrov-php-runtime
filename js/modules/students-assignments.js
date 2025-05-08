import $ from "jquery"

const getCellViewVisibility = (cell) => {
    return !cell.find(".student-assignment-box").is(":hidden")
}

var cells = []

const updateCellViewRaw = (cell, visibility) => {
    const currentVisibility = getCellViewVisibility(cell)
    if (visibility === "toggle") {
        visibility = !currentVisibility
    }
    if (visibility !== currentVisibility) {
        const toggleLink = cell.find(".toggle-show")
        const descriptionBox = cell.find(".student-assignment-box")
        const actions = cell.find(".student-assignment-actions")
        if (visibility) {
            toggleLink.css("visibility", "hidden")
            descriptionBox.slideDown({duration: 300, always: () => {
                toggleLink.css("visibility", "visible")
                toggleLink.text(toggleLink.attr(visibility ? 'data-less-label' : 'data-more-label'))
            }})
        } else {
            toggleLink.css("visibility", "hidden")
            descriptionBox.slideUp({duration: 300, always: () => {
                toggleLink.css("visibility", "visible")
                toggleLink.text(toggleLink.attr(visibility ? 'data-less-label' : 'data-more-label'))
            }})
        }
    }
    return visibility
}

const updateCellView = (cell, visibility) => {
    if (updateCellViewRaw(cell, visibility)) {
        for (var c of cells) {
            if (c !== cell) {
                updateCellViewRaw(c, false)
            }
        }
    }
}

$(() => {
    $(".toggle-show").each(function (){
        $(this).text($(this).attr("data-more-label"))
        const cell = $(this).selectParent("td")
        $(this).bind("click", (ev) => {
            updateCellView(cell, "toggle")
            ev.preventDefault()
            return false
        })
        cell.bind("click", () => updateCellView(cell, true))
        cells.push(cell)
    })
})
