import $ from "jquery"


const modal = `
<div class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="cancel btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" class="confirm btn btn-primary">Yes</button>
        <button type="button" class="third btn btn-primary">?</button>
      </div>
    </div>
  </div>
</div>
`

var modalElement = null

const getModal = () => {
    if (modalElement === null) {
        modalElement = $(modal)
        const modalElementConst = modalElement
        $('body').append(modalElement)
        const actionHandler = function () {
            const action = $(this).attr("data-action")
            if (action !== null && action !== undefined) {
                window.location = action
            }
            modalElementConst.modal("hide")
        }
        modalElement.find("button.confirm").bind("click", actionHandler)
        modalElement.find("button.third").bind("click", actionHandler)
    }
    return modalElement
}

const setCssClass = (element, cssClass, enabled) => {
    if (enabled) {
        if (!element.hasClass(cssClass)) {
            element.addClass(cssClass)
        }
    } else {
        if (element.hasClass(cssClass)) {
            element.removeClass(cssClass)
        }
    }
}

const setButtonType = (button, type) => {
    const newCls = "btn-" + type
    for (var cls of button.prop("classList")) {
        if (cls.match(/^btn-/) && cls !== newCls) {
            button.removeClass(cls)
        }
    }
    if (!button.hasClass(newCls)) {
        button.addClass(newCls)
    }
}

const showModalConfirm = (config) => {
    const modal = getModal()
    const body = modal.find("div.modal-body")
    body.html("")
    for (var message of config.message.split(/\n/)) {
        if (!message.match(/^\s*$/)) {
            body.append($("<p>").text(message))
        }
    }
    const title = modal.find("h5")
    title.text(config.title)
    setCssClass(title, "text-danger", config.danger)

    const confirmButton = modal.find("button.confirm")
    const cancelButton = modal.find("button.cancel")
    const thirdButton = modal.find("button.third")
    setButtonType(confirmButton, config.danger ? "danger" : "primary")
    confirmButton.text(config.confirmText)
    cancelButton.text(config.cancelText)
    confirmButton.attr("data-action", config.action)
    if (config.thirdText && config.thirdAction) {
        thirdButton.show()
        thirdButton.text(config.thirdText)
        thirdButton.attr("data-action", config.thirdAction)
        setButtonType(thirdButton, config.thirdType)
    } else {
        thirdButton.hide()
        thirdButton.removeAttr("data-action")
    }
    modal.modal("show")
}

const getConfigFromElement = (element) => {
    const message = element.attr("data-confirm-message")
    const title = element.attr("data-confirm-title") || "potvrdit"
    const danger = element.hasClass("btn-danger") || element.hasClass("text-danger")
    const thirdType = element.attr("data-confirm-third-type") || "primary"
    const action = element.attr("href")
    const confirmText = element.attr("data-confirm-confirm-label") || "potvrdit"
    const cancelText = element.attr("data-confirm-cancel-label") || "zrušit"
    const thirdText = element.attr("data-confirm-third-label")
    const thirdAction = element.attr("data-confirm-third-action")
    return {message, title, danger, action, confirmText, cancelText, thirdText, thirdAction, thirdType}
}

$(() => {
    $("body>div.modal").modal("toggle")
    $("a[data-confirm-message]").bind("click", function (ev) {
        const config = getConfigFromElement($(this))
        showModalConfirm(config)
        ev.preventDefault()
        return false
    })
})

