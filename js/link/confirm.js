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
        <button type="button" class="no btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" class="yes btn btn-primary">Yes</button>
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
        modalElement.find("button.yes").bind("click", function () {
            const action = $(this).attr("data-action")
            if (action !== null && action !== undefined) {
                window.location = action
            }
            modalElementConst.modal("hide")
        })
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

const showModalConfirm = (config) => {
    const modal = getModal()
    console.log("modal-body", modal.find("div.modal-body > p"), config)
    modal.find("div.modal-body > p").text(config.message)
    const title = modal.find("h5")
    title.text(config.title)
    setCssClass(title, "text-danger", config.danger)

    const yes = modal.find("button.yes")
    const no = modal.find("button.no")
    setCssClass(yes, "btn-primary", !config.danger)
    setCssClass(yes, "btn-danger", config.danger)
    yes.text(config.yesText)
    no.text(config.noText)
    yes.attr("data-action", config.action)
    modal.modal("show")
}

const getConfigFromElement = (element) => {
    const message = element.attr("data-confirm-message")
    const title = element.attr("data-confirm-title") || "potvrdit"
    const danger = element.hasClass("btn-danger") || element.hasClass("text-danger")
    const action = element.attr("href")
    return {message, title, danger, action, yesText: "Ano", noText: "Ne"}
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

