import $ from "jquery"
import showModalProgress from "./modal-progress.js"
import {alertBox} from "./link/confirm.js"

function finishSubmit(url, data)
{
    window.history.replaceState({}, "form submit", url)
    document.open()
    document.write(data)
    document.close()
}

function getFormDataSize(formData)
{
    var size = 0
    for(var e of formData.entries()) {
        size += e[0].length
        if (e[1] instanceof Blob) {
            size += e[1].size
        } else {
            size += e[1].length
        }
    }
    return size
}

function handleSubmit(event)
{
    const submitter = $(event.originalEvent.submitter)
    const form = $(event.currentTarget);
    const uploadLimit = form.attr("data-upload-limit")
    const url = new URL(form.prop("action"));
    const formData = new FormData(form[0]);
    formData.append(submitter.prop("name"), submitter.prop("value"))
    const searchParams = new URLSearchParams(formData);

    if (limitReached(uploadLimit, getFormDataSize(formData))) {
        const message = "Přenášená data jsou příliš velká. Vyberte prosím menší soubory."
        form.find("input[type=file]").val("")
        alertBox(message, "Chyba").then(() => {})
        event.preventDefault()
        return false
    }

    const fetchOptions = {
    	method: form.prop("method").toUpperCase(),
    };

    if (form.prop("method").toLowerCase() === 'post') {
    	if (form.prop("enctype") === 'multipart/form-data') {
    		fetchOptions.body = formData;
            fetchOptions.mime = 'multipart/form-data'
    	} else {
    		fetchOptions.body = searchParams;
            fetchOptions.mime = 'application/x-www-form-urlencoded'
    	}
    } else {
    	url.search = searchParams;
    }

    var showPercent = false

    setTimeout(() => {showPercent = true}, 1000)
    
    const xhr = new XMLHttpRequest();
    xhr.open(fetchOptions.method, "" + url, true)
    xhr.upload.onprogress = function (ev) {
        showModalProgress(showPercent ? (ev.loaded / ev.total) : false)
    }
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            showModalProgress(null)
            if (xhr.status >= 200 && xhr.status < 300) {
                finishSubmit(url, xhr.responseText)
            }
        }
    }

    xhr.send(fetchOptions.body)

    event.preventDefault();
    return false
}

function findParentForm(el)
{
    while (el.length > 0 && el.prop("tagName").toLowerCase() != "form") {
        el = el.parent()
    }
    if (el.length == 0) {
        return null
    }
    return el
}

function autosubmit()
{
    const form = findParentForm($(this))
    if (form === null) {
        return
    }
    form.submit()
}

function getTotalSize(files)
{
    var size = 0
    for (var i = 0; i < files.length; i++) {
        size += files[i].size
    }
    return size
}

function limitReached(limit, realLimit)
{
    if (limit !== undefined && limit !== null) {
        limit = parseInt(limit)
    } else {
        limit = null
    }
    if (limit === null) {
        return false
    }
    return limit < realLimit
}

$(() => {
    $(".non-validated-action").click(function (ev){
        const el = findParentForm($(this))
        if (el === null) {
            return
        }
        el.find("[required]").removeAttr("required")
    })
    $("form.with-progress").bind("submit", handleSubmit)
    $(".autosubmit").each(function(){
        const el = $(this)
        el.find("input").bind("change", autosubmit)
        if (el.is("input")) {
            el.bind("change", autosubmit)
        }
    })
    $("input[type=file]").each(function () {
        const el = $(this)
        const fileLimit = el.attr("data-file-limit")
        const sizeLimit = el.attr("data-size-limit")
        const uploadLimit = el.attr("data-upload-limit")
        el.selectParent("form").attr("data-upload-limit", uploadLimit)
        el.bind("change", function () {
            const realFileLimit = this.files.length
            const realSizeLimit = getTotalSize(this.files)
            var message = null
            if (limitReached(sizeLimit, realSizeLimit)) {
                message = "Soubory jsou příliš velké. Velikost souborů je přitom omezena. Vyberte prosím menší soubory."
            } else if (limitReached(fileLimit, realFileLimit)) {
                message = "Souborů je příliš mnoho. Počet souborů je přitom omezen. Vyberte prosím méně souborů."
            }
            if (message !== null) {
                $(this).val("")
                alertBox(message, "Chyba").then(() => {})
            }
        })
    });
})
