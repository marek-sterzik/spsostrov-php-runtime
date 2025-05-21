import $ from "jquery"

async function testSync(url, element)
{
    var result = null
    while (result === null) {
        try {
            result = await $.get(url)
        } catch (e) {
            result = null
        }
    }
    const badge = result.testResult ? '<span class="badge bg-success">synchronizace funkční</span>' : '<span class="badge bg-danger">nelze synchronizovat</span>'
    element.html(badge)
}

$(() => {
    $(".sync-test-status").each(function () {
        const url = $(this).attr("data-test-url")
        testSync(url, $(this))
    })
})
