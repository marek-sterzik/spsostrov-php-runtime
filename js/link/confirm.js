import $ from "jquery"


const modal = `
<div class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="btn-close" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="cancel btn btn-sm btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" class="confirm btn btn-sm btn-primary">Yes</button>
        <button type="button" class="third btn btn-sm btn-primary">?</button>
      </div>
    </div>
  </div>
</div>
`

var modalElement = null

const triggerAction = (action) => {
    if (typeof action === "function") {
        action()
    } else {
        window.location = action
    }
}

const getModal = () => {
    if (modalElement === null) {
        modalElement = $(modal)
        const modalElementConst = modalElement
        $('body').append(modalElement)
        const actionHandler = (actionField) => function () {
            const action = modalElement.data("config")[actionField]
            if (action !== null && action !== undefined) {
                triggerAction(action)
            }
            modalElementConst.modal("hide")
        }
        modalElement.find("button.confirm").bind("click", actionHandler("action"))
        modalElement.find("button.third").bind("click", actionHandler("thirdAction"))
        modalElement.find("button.cancel").bind("click", actionHandler("cancelAction"))
        modalElement.find("button.btn-close").bind("click", actionHandler("cancelAction"))
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
        if (cls !== 'btn-sm' && cls.match(/^btn-/) && cls !== newCls) {
            button.removeClass(cls)
        }
    }
    if (!button.hasClass(newCls)) {
        button.addClass(newCls)
    }
}

const showModalConfirm = (config) => {
    const modal = getModal()
    modal.data("config", config)
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
    setButtonType(confirmButton, config.confirmType)
    confirmButton.text(config.confirmText)
    if (config.cancelText) {
        cancelButton.text(config.cancelText)
        cancelButton.show()
    } else {
        cancelButton.hide()
    }
    if (config.thirdText && config.thirdAction) {
        thirdButton.show()
        thirdButton.text(config.thirdText)
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
    const confirmType = element.attr("data-confirm-confirm-type") || (danger ? "danger" : "primary")
    const action = element.attr("href")
    const confirmText = element.attr("data-confirm-confirm-label") || "potvrdit"
    const cancelText = element.attr("data-confirm-cancel-label") || "zrušit"
    const cancelAction = null
    const thirdText = element.attr("data-confirm-third-label")
    const thirdAction = element.attr("data-confirm-third-action")
    return {message, title, danger, action, confirmText, cancelText, cancelAction, thirdText, thirdAction, confirmType, thirdType}
}

const alertBox = (message, title = undefined) => {
    return new Promise((resolve) => {
        const config = {
            message: message,
            title: title || "",
            danger: false,
            thirtType: "primary",
            confirmType: "primary",
            action: () => resolve(),
            cancelAction: () => resolve(),
            confirmText: "ok",
            cancelText: undefined,
            thirdText: undefined,
            thirdAction: undefined,
        }
        showModalConfirm(config)
    })
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

export {alertBox}
