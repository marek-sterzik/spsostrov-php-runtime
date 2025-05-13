import $ from "jquery"

const scheduleTimeout = () => 5000

var x = false

const doUpdate = async (updateUrl) => {
    var result
    try {
        result = await $.get(updateUrl)
    } catch(e) {
        return false
    }
    $(".submission-state").html(result.state)
    if (result.stateIsFinal) {
        $(".submission-not-final").remove()
    }
    return result.stateIsFinal
}

const tick = (updateUrl) => async () => {
    if (!await doUpdate(updateUrl)) {
        setTimeout(tick(updateUrl), scheduleTimeout())
    }
}

$(() => {
    const updateUrl = $(".submission-not-final").attr("data-submission-state-update")
    if (updateUrl) {
        setTimeout(tick(updateUrl), scheduleTimeout())
    }
});
