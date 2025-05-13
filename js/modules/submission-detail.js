import $ from "jquery"

const doUpdate = async (updateUrl) => {
    var result
    try {
        result = await $.get(updateUrl)
    } catch(e) {
        return 5
    }
    $(".submission-state").html(result.state)
    if (result.stateIsFinal) {
        $(".submission-not-final").remove()
    }
    return result.stateIsFinal ? null : result.timeout
}

const tick = (updateUrl) => async () => {
    const timeout = await doUpdate(updateUrl)
    if (timeout !== null) {
        setTimeout(tick(updateUrl), timeout * 1000)
    }
}

$(() => {
    const updateUrl = $(".submission-not-final").attr("data-submission-state-update")
    if (updateUrl) {
        const timeout = parseInt($(".submission-not-final").attr("data-submission-state-update-timeout") || "5")
        setTimeout(tick(updateUrl), timeout * 1000)
    }
});
