import $ from "jquery"
import {alertBox} from "../link/confirm.js"

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
    input.scrollLeft = input.scrollWidth
}

const createFileSelection = (input) => {
    const filename = input.val()
    const match = filename.match(/(\.[a-zA-Z0-9]{1,6})+$/)
    const len = filename.length - (match ? match[0].length : 0)
    doSelect(input, 0, len)
}

const finishRename = (input) => {
    const nameFrom = input.attr("data-original-value")
    const nameTo = input.val()
    const fileActionUrl = $("table.submitted-files").attr("data-file-action-url")
    if (nameFrom !== nameTo) {
        const params = "mvfrom=" + encodeURIComponent(nameFrom) + "&mvto=" + encodeURIComponent(nameTo)
        const url = fileActionUrl + (fileActionUrl.includes("?") ? "&" : "?") + params
        window.location = url
    }
}

const setupEdit = (row, enabled) => {
    const cell = row.find("td.submitted-file-item")
    const actionCell = row.find("td.submitted-file-actions")
    const showWidget = cell.find(".submitted-file-show")
    const editWidget = cell.find(".submitted-file-edit")
    const showActionWidget = actionCell.find(".submitted-file-show")
    const editActionWidget = actionCell.find(".submitted-file-edit")
    const table = cell.selectParent("table")
    const prevEnabled = !editWidget.is(":hidden")
    const inputWidget = editWidget.find("input")
    if (enabled === "toggle") {
        enabled = !prevEnabled
    }
    if (enabled) {
        inputWidget.val(inputWidget.attr("data-original-value"))
        showWidget.hide()
        showActionWidget.hide()
        editWidget.show()
        editActionWidget.show()
        if (!prevEnabled) {
            createFileSelection(inputWidget)
            inputWidget.focus()
        }
        table.find(".submitted-file-actions-button").addClass("disabled")
    } else {
        showWidget.show()
        showActionWidget.show()
        editWidget.hide()
        editActionWidget.hide()
        table.find(".submitted-file-actions-button").removeClass("disabled")
    }
}

$(() => {
    $(".submitted-file-do-edit").bind("click", function (ev) {
        setupEdit($(this).selectParent("tr"), true)
        $(this).selectParent("div.btn-group").find(".show").removeClass("show")
        ev.preventDefault()
        return false
    })

    $("button.finish-rename").bind("click", function () {
        const row = $(this).selectParent("tr")
        setupEdit(row, false)
        finishRename(row.find(".submitted-file-edit input"))
    })

    $("button.cancel-rename").bind("click", function () {
        setupEdit($(this).selectParent("tr"), false)
    })

    $(".submitted-file-edit input").bind("keydown", function (ev) {
        const keycode = (ev.keyCode ? ev.keyCode : ev.which)
        if (ev.key == "Escape") {
            setupEdit($(this).selectParent("tr"), false)
            ev.preventDefault()
            return false
        } else if (keycode == 13) {
            setupEdit($(this).selectParent("tr"), false)
            finishRename($(this))
            ev.preventDefault()
            return false
        }
    })

    const errorMessage = $("table.submitted-files").attr("data-file-error-message")
    if (errorMessage) {
        const reloadUrl = $("table.submitted-files").attr("data-reload-url")
        alertBox(errorMessage, "Chyba").then(() => {
            window.location = reloadUrl
        })
    }
})
